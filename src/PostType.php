<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 09.04.18
 * Time: 12:18
 */
declare(strict_types = 1);

namespace Alpipego\GhCp;

/**
 * Class PostType
 * @package Alpipego\GhCp
 *
 * @method label(string $label)
 * @method labels(array $labels)
 * @method description(string $description)
 * @method public (bool $public)
 * @method exclude_from_search(bool $exclude_from_search)
 * @method publicly_queryable(bool $publicly_queryable)
 * @method show_ui(bool $show_ui)
 * @method show_in_nav_menus(bool $show_in_nav_menus)
 * @method show_in_menu(bool $show_in_menu)
 * @method show_in_admin_bar(bool $show_in_admin_bar)
 * @method menu_position(int $public)
 * @method menu_icon(string $menu_icon)
 * @method capability_type(string | array $capability_type)
 * @method capabilities(array $capabilities)
 * @method map_meta_cap(bool $map_meta_cap)
 * @method hierarchical(bool $hierarchical)
 * @method supports(bool | array $supports)
 * @method register_meta_box_cb(callable $register_meta_box_cb)
 * @method taxonomies(array $taxonomies)
 * @method has_archive(bool $has_archive)
 * @method rewrite(bool | array $rewrite)
 * @method permalink_epmask(bool $permalink_epmask)
 * @method query_var(bool | string $query_var)
 * @method can_export(bool $can_export)
 * @method delete_with_user(bool $delete_with_user)
 * @method show_in_rest(bool $show_in_rest)
 * @method rest_base(string $rest_base)
 * @method rest_controller_class(\WP_REST_Controller $rest_controller_class)
 */
class PostType
{
    private $posttype;
    private $singular;
    private $plural;
    private $labels = [];
    private $args = ['public' => true];
    private $capability_type = 'post';
    private $capabilities = [];

    public function __construct($posttype, $singular, $plural)
    {
        $this->posttype = $posttype;
        $this->singular = $singular;
        $this->plural   = $plural;

        $this->labels       = $this->defaultLabels();
        $this->capabilities = $this->defaultCaps();
    }

    private function defaultLabels() : array
    {
        return [
            'name'               => sprintf(_x('%s', 'General CPT Name', 'ghcp'), $this->plural),
            'singular_name'      => sprintf(_x('%s', 'Singular CPT Name', 'ghcp'), $this->singular),
            'add_new'            => __('Add New', 'ghcp'),
            'add_new_item'       => sprintf(__('Add new %s', 'ghcp'), $this->singular),
            'edit_item'          => sprintf(__('Edit %s', 'ghcp'), $this->singular),
            'new_item'           => sprintf(__('New %s', 'ghcp'), $this->singular),
            'view_item'          => sprintf(__('View %s', 'ghcp'), $this->singular),
            'search_items'       => sprintf(__('Search %s', 'ghcp'), $this->plural),
            'not_found'          => sprintf(__('No %s found', 'ghcp'), $this->plural),
            'not_found_in_trash' => sprintf(__('No %s found in Trash', 'ghcp'), $this->plural),
            'parent_item_colon'  => sprintf(__('Parent: %s', 'ghcp'), $this->singular),
            'all_items'          => sprintf(__('All %s', 'ghcp'), $this->plural),
            'archives'           => sprintf(__('%s Archive', 'ghcp'), $this->singular),
            'menu_name'          => sprintf(_x('%s', 'Menu Name', 'ghcp'), $this->plural),
            'name_admin_bar'     => sprintf(_x('%s', 'Admin Bar Name', 'ghcp'), $this->singular),
        ];
    }

    private function defaultCaps() : array
    {
        return [
            // meta caps
            'edit_post'              => 'edit_' . $this->posttype,
            'read_post'              => 'read_' . $this->posttype,
            'delete_post'            => 'delete_' . $this->posttype,
            // primitive
            'edit_posts'             => 'edit_' . $this->posttype . 's',
            'edit_others_posts'      => 'edit_others_' . $this->posttype . 's',
            'read_private_posts'     => 'read_private_' . $this->posttype,
            'publish_posts'          => 'publish_' . $this->posttype . 's',
            // additional primitive
            'read'                   => 'read',
            'delete_posts'           => 'delete_' . $this->posttype . 's',
            'delete_private_posts'   => 'delete_private_' . $this->posttype . 's',
            'delete_published_posts' => 'delete_published_' . $this->posttype . 's',
            'delete_others_posts'    => 'delete_others_' . $this->posttype . 's',
            'edit_private_posts'     => 'edit_private_' . $this->posttype . 's',
            'edit_published_posts'   => 'edit_published_' . $this->posttype . 's',
            'create_posts'           => 'create_' . $this->posttype . 's',
        ];
    }

    public function __call($name, $arguments) : self
    {
        if (!isset($arguments[0])) {
            throw new \InvalidArgumentException(sprintf('You must pass a value to %s', $name));
        }

        return $this->set($name, $arguments[0]);
    }

    private function set($name, $value) : self
    {
        if (property_exists($this, $name)) {
            if (is_array($this->$name)) {
                if ( ! is_array($value)) {
                    $this->$name[] = $value;
                } else {
                    $this->$name = array_merge($this->$name, $value);
                }
            } else {
                $this->$name = $value;
            }
        } else {
            $this->args[$name] = $value;
        }

        return $this;
    }

    /**
     * @return \WP_Error|\WP_Post_Type
     */
    public function create()
    {
        $args = $this->mapArgs();

        return register_post_type($this->posttype, $args);
    }

    private function mapArgs() : array
    {
        $args       = [];
        $unfiltered = array_merge($this->args, $this->labels, $this->capabilities, [$this->capability_type]);
        foreach ($unfiltered as $key => $arg) {
            if (is_array($arg)) {
                $arg = array_unique($arg);
            }
            $args[$key] = $arg;
        }

        return $args;
    }
}
