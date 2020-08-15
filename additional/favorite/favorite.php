<?php

namespace WordPress_ACL;

class Favorite_Post
{
    public static $favorite_post_list_user_meta = 'favorite_post';

    public static function user_meta_favorite_name()
    {
        return apply_filters('wordpress_acl_user_meta_favorite_name', self::$favorite_post_list_user_meta);
    }

    public static function add($user_id, $post_id, $category = '')
    {
        // Get Current Favorite List
        $current_favorite_list = self::get($user_id);
        if (empty($current_favorite_list)) {
            $current_favorite_list = self::generate_default_favorite_list();
        }

        // Convert to int
        $post_id = (int)$post_id;

        // Get Post Type
        if (empty($category)) {
            $category = get_post_type($post_id);
        }

        // Add to List
        if (!in_array($post_id, $current_favorite_list[$category])) {
            $current_favorite_list[$category][] = $post_id;
        }

        // Save Again
        update_user_meta($user_id, self::user_meta_favorite_name(), $current_favorite_list);
    }

    public static function get($user_id, $count = false, $category = '')
    {
        $favorite_list = get_user_meta($user_id, self::user_meta_favorite_name(), true);
        if (empty($favorite_list) || !is_array($favorite_list)) {
            if ($count) {
                return 0;
            } else {
                return array();
            }
        }

        // Check Custom Category
        if (!empty($category)) {
            if ($count === true) {
                if (isset($favorite_list[$category])) {
                    return count($favorite_list[$category]);
                } else {
                    return 0;
                }
            } else {
                if (isset($favorite_list[$category])) {
                    return $favorite_list[$category];
                } else {
                    return array();
                }
            }
        }

        // Get Count All Post
        if ($count) {
            $sum = 0;
            foreach ($favorite_list as $category => $list) {
                $sum = $sum + count($list);
            }

            return $sum;
        }

        return $favorite_list;
    }

    public static function remove($user_id, $post_id)
    {
        // Get Current Favorite List
        $current_favorite_list = self::get($user_id);
        foreach ($current_favorite_list as $category => $list) {
            // Remove From List By Value
            if (($key = array_search((int)$post_id, $list)) !== false) {
                unset($current_favorite_list[$category][$key]);
            }
        }

        // Save Again
        update_user_meta($user_id, self::user_meta_favorite_name(), $current_favorite_list);
    }

    public static function get_post_types($return = 'key')
    {
        $args = array(
            'public' => true,
        );
        $post_types = get_post_types($args, 'objects');
        if ($return == 'key') {
            return array_keys($post_types);
        }

        return $post_types;
    }

    public static function get_default_category_list()
    {
        return apply_filters('wordpress_acl_user_favorite_post_category', self::get_post_types());
    }

    public static function generate_default_favorite_list()
    {
        $array = self::get_default_category_list();
        $list = array();
        foreach ($array as $category) {
            $list[$category] = array();
        }

        return $list;
    }
}

new Favorite_Post;