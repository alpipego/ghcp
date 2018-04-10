<?php
/**
 * Plugin Name: Github Markdown to Custom Post
 * Plugin URI: https://github.com/alpipego/ghcp
 * Description: Create and update custom posts in WordPress from GitHub markdown files
 * Author: Alexander Goller
 * Author URI: https://alexandergoller.com
 * Text Domain: ghcp
 * Domain Path: /languages
 * Version: 0.1.0
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Requires PHP: 7.1
 * Requires at least: 4.5
 * Tested up to: 4.9
 *
 * @package Alpipego\GhCp
 * @wordpress-plugin
 *
 */

add_action('rest_api_init', function () {
    (new \Alpipego\GhCp\RestRoute(new \Alpipego\GhCp\PayloadParser()))->register();
});

add_action('init', function () {
    $postType = new \Alpipego\GhCp\PostType('ghcp', 'Github Markdown Doc', 'Github Markdown Docs');
    $postType
        ->rewrite([
            'slug'       => (string)apply_filters('ghcp/rewrite/slug', 'ghcp'),
            'with_front' => (bool)apply_filters('ghcp/rewrite/with_front', true),
            'feeds'      => (bool)apply_filters('ghcp/rewrite/slug', true),
            'pages'      => (string)apply_filters('ghcp/rewrite/slug', true),
        ])
        ->show_in_rest((bool)apply_filters('ghcp/show_in_rest', false))
        ->publicly_queryable(true);

    $postType = apply_filters('ghcp/post_type_object', $postType);

    $postType->create();
});

if ((bool)apply_filters('ghcp/code_highlighting', true)) {
    add_action('wp_enqueue_scripts', function () {
        wp_enqueue_style(
            'pygments-default',
            plugin_dir_url(__FILE__) . 'assets/github-light' . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min') . '.css');
    });
}

register_activation_hook(__FILE__, function () {
    add_action('init', 'flush_rewrite_rules', 100);
});
register_deactivation_hook(__FILE__, function () {
    add_action('shutdown', function () {
        unregister_post_type('ghcp');
        flush_rewrite_rules();
    });
});
