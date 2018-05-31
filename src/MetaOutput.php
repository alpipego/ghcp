<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 31.05.18
 * Time: 14:19
 */
declare(strict_types = 1);

namespace Alpipego\GhCp;

class MetaOutput
{
    private $meta = [
        'title',
        'description',
        'robots',
    ];
    private $keys;

    public function __construct(array $metaInventory)
    {
        $this->meta = (array)apply_filters('ghcp/meta/names', $this->meta);
        $this->keys = $metaInventory;
    }

    public function output()
    {
        foreach ($this->meta as $metaName) {
            if (($key = array_search($metaName, array_column($this->keys, 'key'))) === false) {
                continue;
            }

            $metaMeta    = $this->keys[$key];
            $metaContent = get_post_meta(get_the_ID(), (! empty($metaMeta['meta_key']) ? $metaMeta['meta_key'] : $metaMeta['key']), true);

            printf('<meta name="%s" content="%s">', $metaName, $metaContent);
        }
    }

    public function filterTitle()
    {
        if (($titleKey = array_search('title', $this->meta)) !== false) {
            $this->outputTitle();
            unset($this->meta[$titleKey]);
        }
    }

    private function outputTitle()
    {
        if (($key = array_search('title', array_column($this->keys, 'key'))) === false) {
            return;
        }
        $before   = apply_filters('ghcp/meta/title_before', '');
        $after    = apply_filters('ghcp/meta/title_after', get_bloginfo('name'));
        $metaMeta = $this->keys[$key];
        $title    = get_post_meta(get_the_ID(), (! empty($metaMeta['meta_key']) ? $metaMeta['meta_key'] : $metaMeta['key']), true);
        $action   = current_theme_supports('title_tag') ? 'document_title_parts' : 'wp_title_parts';

        add_filter($action, function () use ($before, $after, $title) {
            return array_filter([
                $before,
                $title,
                $after,
            ]);
        });
    }
}
