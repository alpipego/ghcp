<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 09.04.18
 * Time: 13:42
 */
declare(strict_types = 1);

namespace Alpipego\GhCp;

class PayloadParser
{
    const REPOSITORIES = '/installation/repositories';
    const COMMIT = '/repos/%s/commits/%s';
    const ACCEPT_HEADER = 'application/vnd.github.v3+json';
    /**
     * @var GitHub
     */
    private $gh;
    private $headers;
    private $metadata;

    public function __construct(MetaDataParser $metadata)
    {
        $this->metadata = $metadata;
    }

    public function setBody(string $body) : int
    {
        try {
            $payload = json_decode($body);
            $repo    = $payload->repository->full_name;
            $this->connect();
            // TODO validate previous state before / after
            $renderedFiles = [];
            foreach ($payload->commits as $commit) {
                if (count($payload->commits) > 1 && $commit->id === $payload->head_commit->id) {
                    continue;
                }

                $singleCommit = \Requests::get($this->gh->buildUrl(self::COMMIT, $repo, $commit->id), $this->headers);
                if ((int)$singleCommit->status_code !== 200) {
                    // TODO handle error
                    return 0;
                }

                $body = json_decode($singleCommit->body);

                foreach ($body->files as $file) {
                    $name    = preg_replace('/\.md$/', '', $file->filename);
                    $post    = get_page_by_path($name, OBJECT, 'ghcp');
                    $postarr = [];
                    $meta    = [];
                    $title   = null;
                    if (is_a($post, 'WP_Post')) {
                        $postarr = (array)$post;
                        $meta    = get_post_meta($post->ID);
                        $meta    = array_map(function ($value) {
                            return maybe_unserialize($value[0]);
                        }, $meta);
                    }

                    $rawBody = \Requests::get($file->raw_url)->body;
                    $meta = array_merge($meta, $this->metadata->parse($rawBody));

                    if ( ! array_key_exists($file->sha, $renderedFiles)) {
                        $rendered = \Requests::post($this->gh->getUrl() . '/markdown', $this->headers, json_encode([
                            'text'    => $rawBody,
                            'mode'    => 'gfm',
                            'context' => $repo,
                        ]));

                        if ($rendered->status_code !== 200) {
                            continue;
                        }

                        $content = preg_replace_callback('/<h1>([^<]+)<\/h1>/', function ($matches) use (&$title) {
                            $title = $matches[1];

                            return '';
                        }, $rendered->body, 1);

                        $renderedFiles[$file->sha] = [
                            'title'   => $title,
                            'content' => $content,
                        ];
                    } else {
                        $title   = $renderedFiles[$file->sha]['title'];
                        $content = $renderedFiles[$file->sha]['content'];
                    }

                    $postarr['post_name']                       = $postarr['post_name'] ?? $name;
                    $postarr['post_title']                      = $title ?? $name;
                    $postarr['post_content']                    = $content;
                    $postarr['post_type']                       = 'ghcp';
                    $postarr['post_status']                     = 'publish';
                    $postarr['post_modified_gmt']               = strtotime($payload->head_commit->timestamp);
                    $meta['ghcp_current_commit']                = $body->sha;
                    $meta['ghcp_authors'][$body->author->login] = [
                        'username' => $body->author->login,
                        'name'     => $body->commit->author->name,
                        'avatar'   => $body->author->avatar_url,
                    ];
                    $postarr['meta_input']                      = $meta;

                    return wp_insert_post($postarr);
                }
            }
        } catch (\Exception $e) {
        }

        return 0;
    }

    private function connect()
    {
        $this->gh      = (new GitHub())->connect();
        $this->headers = [
            'Authorization' => 'token ' . $this->gh->getToken(),
            'User-Agent'    => $this->gh->getUserAgent(),
            'Accept'        => self::ACCEPT_HEADER,
            'Time-Zone'     => 'Zulu',
        ];
    }
}
