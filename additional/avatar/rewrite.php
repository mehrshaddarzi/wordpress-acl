<?php

namespace WordPress_Rewrite_API_Request;

class avatar
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, '_register_js_script'), 8);

        add_filter('rewrite_api_request_localize', function ($localize) {
            $localize['avatar'] = self::_localize_avatar();
            return $localize;
        }, 9);
    }

    public function _register_js_script()
    {
        wp_enqueue_script('wordpress-acl-avatar', \WordPress_ACL::$plugin_url . '/additional/avatar/script.js', array('jquery', 'wp-rewrite-api'), \WordPress_ACL::$plugin_version, true);
    }

    public static function _get_max_image_avatar_size()
    {
        return apply_filters('wordpress_acl_max_avatar_image_size', 5);
    }

    public static function _localize_avatar()
    {
        return array(
            'mb' => (int) self::_get_max_image_avatar_size(),
            'size' => sprintf(
                __('File size must be excately %s MB.', 'wordpress-acl'),
                self::_get_max_image_avatar_size()
            ),
            'ext' => __('Please select png or jpg file', 'wordpress-acl')
        );
    }

    /**
     * Change User Avatar
     *
     * @return void
     */
    public function change()
    {
        // Check User Login
        WordPress_Rewrite_API_Request::auth_error();

        // Check Require Params
        if (!isset($_FILES['profile_image'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Get localize
        $localize = self::_localize_avatar();

        // Check Empty
        if (empty($_FILES['profile_image']["name"])) {
            wp_send_json_error(array(
                'error' => 'select_avatar_image',
                'message' => __('Please select an image', 'wordpress-acl')
            ), 400);
        }

        // Check File Extension
        $allowed = array('png', 'jpg', 'jpeg');
        $ext = pathinfo($_FILES['profile_image']["name"], PATHINFO_EXTENSION);
        if (!in_array($ext, $allowed)) {
            wp_send_json_error(array(
                'error' => 'avatar_image_ext',
                'message' => $localize['ext']
            ), 400);
        }

        // Check File Size
        $avatar_image_size = self::_get_max_image_avatar_size();
        if ($_FILES['profile_image']['size'] > ($avatar_image_size * 1024 * 1024)) {
            wp_send_json_error(array(
                'error' => 'avatar_image_size',
                'message' => $localize['size']
            ), 400);
        }

        // Change Avatar
        $avatar = \WordPress_ACL\Avatar::change_avatar('profile_image');

        // Check Error
        if ($avatar['status'] === false) {
            wp_send_json_error(array(
                'message' => $avatar['message']
            ), 400);
        }

        // success
        wp_send_json_success(array(
            'avatar' => $avatar['avatar'],
            'attachment_id' => $avatar['attachment_id']
        ), 200);
    }
}

new avatar;
