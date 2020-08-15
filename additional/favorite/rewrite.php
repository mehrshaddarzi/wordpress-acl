<?php

namespace WordPress_Rewrite_API_Request;

class favorite_post
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, '_register_js_script'), 9);
    }

    public function _register_js_script()
    {
        wp_enqueue_script('wordpress-acl-favorite-post', \WordPress_ACL::$plugin_url . '/additional/favorite/script.js', array('jquery', 'wp-rewrite-api'), \WordPress_ACL::$plugin_version, true);
    }

    public static function _validation_post_id($post_id)
    {
        if (!is_numeric($post_id) || get_post_type($post_id) === false) {
            wp_send_json_error(array(
                'code' => 'post_not_exist',
                'message' => __('Post not exist.', 'wordpress-acl')
            ), 400);
        }
    }

    public static function add()
    {
        // Check Auth
        WordPress_Rewrite_API_Request::auth_error();

        // Check Params
        if (!isset($_REQUEST['post_id'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Sanitize Data
        $user_id = get_current_user_id();
        $post_id = sanitize_text_field($_REQUEST['post_id']);
        $category = '';
        if (isset($_REQUEST['category'])) {
            $category = sanitize_text_field($_REQUEST['category']);
        }

        // Valid Post_id
        self::_validation_post_id($post_id);

        // Add
        \WordPress_ACL\Favorite_Post::add($user_id, $post_id, $category);

        // Return Success
        wp_send_json_success(array(
            'list' => \WordPress_ACL\Favorite_Post::get(get_current_user_id()),
            'count' => \WordPress_ACL\Favorite_Post::get(get_current_user_id(), true)
        ), 200);
    }

    public static function remove()
    {
        // Check Auth
        WordPress_Rewrite_API_Request::auth_error();

        // Check Params
        if (!isset($_REQUEST['post_id'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Sanitize Data
        $user_id = get_current_user_id();
        $post_id = sanitize_text_field($_REQUEST['post_id']);

        // Valid Post_id
        self::_validation_post_id($post_id);

        // Remove
        \WordPress_ACL\Favorite_Post::remove($user_id, $post_id);

        // Return Success
        wp_send_json_success(array(
            'list' => \WordPress_ACL\Favorite_Post::get(get_current_user_id()),
            'count' => \WordPress_ACL\Favorite_Post::get(get_current_user_id(), true)
        ), 200);
    }

    public static function get()
    {
        // Check Auth
        WordPress_Rewrite_API_Request::auth_error();

        // Sanitize Data
        $user_id = get_current_user_id();
        $category = '';
        if (isset($_REQUEST['category'])) {
            $category = sanitize_text_field($_REQUEST['category']);
        }
        $count = false;
        if (isset($_REQUEST['count']) and $_REQUEST['count'] == 'true') {
            $count = true;
        }

        // Return Success
        wp_send_json_success(array(
            'list' => \WordPress_ACL\Favorite_Post::get($user_id, $count, $category),
            'count' => \WordPress_ACL\Favorite_Post::get(get_current_user_id(), true)
        ), 200);
    }
}

new favorite_post();