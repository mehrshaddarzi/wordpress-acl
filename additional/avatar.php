<?php

namespace WordPress_ACL;

class Avatar
{
    public static $acf_avatar_field_name = 'wp_user_avatar';
    public static $avatar_image_size = 'thumbnail';

    /**
     * Avatar constructor.
     *
     * @Rquired:
     * 1) ACF Pro
     */
    public function __construct()
    {
        // Create ACF Field
        add_action('acf/init', array($this, 'my_acf_add_local_field_groups'));

        // Disable Gravatar in WordPress
        add_filter("option_show_avatars", function () {
            return 0;
        }, 999);

        // Get avatar Function filter
        // @see https://developer.wordpress.org/reference/functions/get_avatar_data/
        add_filter('pre_get_avatar_data', array($this, 'filter_get_avatar_data'), 10, 2);
    }

    /**
     * Create ACF Field
     *
     * @Hook
     */
    public function my_acf_add_local_field_groups()
    {
        if (function_exists('acf_add_local_field_group')):

            acf_add_local_field_group(array(
                'key' => 'group_5e9d94182e428',
                'title' => 'آواتار',
                'fields' => array(
                    array(
                        'key' => 'field_5e9d944d796fc',
                        'label' => 'آواتار',
                        'name' => self::$acf_avatar_field_name,
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'id',
                        'preview_size' => 'thumbnail',
                        'library' => 'all',
                        'min_width' => '',
                        'min_height' => '',
                        'min_size' => '',
                        'max_width' => '',
                        'max_height' => '',
                        'max_size' => '',
                        'mime_types' => '',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'user_form',
                            'operator' => '==',
                            'value' => 'all',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));

        endif;
    }

    /**
     * GeT Default Avatar
     *
     * @return string
     */
    public static function get_default_avatar()
    {
        return apply_filters('wp_get_default_user_avatar_src', rtrim(\WP_MVC::$plugin_url, "/") . '/asset/img/avatar/default_avatar.jpg');
    }

    /**
     * Filter Get User Avatar
     *
     * @Hook
     * @param $args
     * @param $id_or_email
     * @return mixed
     */
    public static function filter_get_avatar_data($args, $id_or_email)
    {
        $user_id = $id_or_email;
        if (is_email($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            if (!$user) {
                $user_id = 0;
            } else {
                $user_id = $user->ID;
            }
        }
        $user_avatar = get_user_meta($user_id, self::$acf_avatar_field_name, true);
        if (empty($user_avatar)) {
            $args['url'] = self::get_default_avatar();
        } else {
            if (file_exists(get_attached_file($user_avatar))) {
                $avatar = wp_get_attachment_image_src($user_avatar, self::$avatar_image_size);
                $args['url'] = $avatar[0];
            } else {
                $args['url'] = self::get_default_avatar();
            }
        }

        return $args;
    }

    /**
     * GET User Avatar By ID
     *
     * @param $user_id
     * @return mixed|string
     */
    public static function get($user_id = false)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        return get_avatar_url($user_id);
    }

    /**
     * change User Avatar By $_FILE
     *
     * @param $file
     * @param bool $user_id
     * @return array
     */
    public static function change_avatar($file, $user_id = false)
    {
        // Get User ID
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (isset($_FILES[$file]) && !empty($_FILES[$file]["name"])) {

            // Upload Image
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attachment_id = media_handle_upload($file, 0);
            if (is_wp_error($attachment_id)) {
                return array('status' => false, 'message' => 'error in upload file');
            }

            // Remove Before User Avatar if Exist
            $_before_attachment = get_user_meta($user_id, self::$acf_avatar_field_name, true);
            if (!empty($_before_attachment)) {
                wp_delete_attachment($_before_attachment, true);
            }

            // Update User Meta
            update_user_meta($user_id, self::$acf_avatar_field_name, $attachment_id);

            // return
            return array('status' => true, 'avatar' => get_avatar_url($user_id), 'attachment_id' => $attachment_id);
        }

        return array('status' => false, 'message' => 'file not found');
    }
}

new Avatar;