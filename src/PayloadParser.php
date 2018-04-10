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
    private $repo;

    public function setBody(string $body) : int
    {
        try {
            $payload    = json_decode($body);
            $this->repo = $payload->repository->full_name;
            $this->connect();
            // TODO validate previous state before / after
            $commit = \Requests::get($this->gh->buildUrl(self::COMMIT, $this->repo, $payload->after), $this->headers);
            if ((int)$commit->status_code !== 200) {
                return 0;
            }

            $body = json_decode($commit->body);

            foreach ($body->files as $file) {
                $rendered = \Requests::post($this->gh->getUrl() . '/markdown', $this->headers, json_encode([
                    'text'    => \Requests::get($file->raw_url)->body,
                    'mode'    => 'gfm',
                    'context' => $this->repo,
                ]));

                if ($rendered->status_code !== 200) {
                    continue;
                }

                $name = preg_replace('/\.md$/', '', $file->filename);
                $post = get_page_by_path($name, OBJECT, 'ghcp');

                $postarr = [];
                $meta    = [];
                if (is_a($post, 'WP_Post')) {
                    $postarr = (array)$post;
                    $meta    = get_post_meta($post->ID);
                }

                $title = null;
                $content = preg_replace_callback('/<h1>([^<]+)<\/h1>/', function($matches) use (&$title) {
                    $title = $matches[1];

                    return '';
                }, $rendered->body,1);

                $postarr['post_name']         = $postarr['post_name'] ?? $name;
                $postarr['post_title']        = $title ?? $name;
                $postarr['post_content']      = $content;
                $postarr['post_type']         = 'ghcp';
                $postarr['post_status']       = 'publish';
                $postarr['post_modified_gmt'] = $postarr['post_modified_gmt'] ?? strtotime($body->commit->committer->date);

                $meta['authors'][$body->author->login] = [
                    'login'  => $body->author->login,
                    'name'   => $body->commit->author->name,
                    'avatar' => $body->author->avatar_url,
                ];
                $meta['current_commit']                = $body->sha;
                $postarr['meta_input']                 = $meta;

                return wp_insert_post($postarr);
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
