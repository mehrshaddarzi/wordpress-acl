<?php

namespace WordPress_ACL;

class Sign
{

    public function __construct()
    {

        // Fix Time in User Registered
        add_filter('wp_pre_insert_user_data', array($this, 'fix_user_registered'), 10, 3);

        // Remove Custom User Meta in Register
        add_filter('insert_user_meta', array($this, 'insert_user_meta'), 10, 3);

        // Register User
        add_action('user_register', array($this, 'register'), 10, 1);

        // Set Time Login for Remember
        add_filter('auth_cookie_expiration', array($this, 'auth_cookie_expiration_filter_5587'), 10, 3);

        // Action after Login User
        // @see https://developer.wordpress.org/reference/functions/wp_signon/
        add_action('wp_login', array($this, 'wp_login'), 10, 2);

        // Disable All Email
        add_filter('send_password_change_email', '__return_false');
        add_filter('send_email_change_email', '__return_false');
        add_filter('wp_new_user_notification_email', '__return_false');
        add_filter('wp_new_user_notification_email_admin', '__return_false');
    }

    /**
     * Login Process Action
     *
     * @param $user_login
     * @param $user
     * @return void
     */
    public function wp_login($user_login, $user)
    {
    }

    /**
     * Set Time Login Remember
     *
     * @param [type] $expiration
     * @param [type] $user_id
     * @param [type] $remember
     * @return void
     */
    function auth_cookie_expiration_filter_5587($expiration, $user_id, $remember)
    {
        if ($remember) {
            return MONTH_IN_SECONDS;
        }
        return $expiration;
    }

    public function register($user_id)
    {
        // Change Not Show Admin Bar
        $obj_user = new \WP_USER($user_id);
        if (in_array("subscriber", $obj_user->roles)) {
            update_user_meta($user_id, 'show_admin_bar_front', 'false');
        }
    }

    /**
     * Insert User Meta Filter
     *
     *
     * @param array $meta
     * @param $user
     * @param array $update
     * @return array
     */
    public function insert_user_meta($meta, $user, $update)
    {
        global $wpdb;

        // Disable Unused Meta
        if (in_array("subscriber", $user->roles)) {
            foreach (array('admin_color', 'comment_shortcuts', 'syntax_highlighting', 'show_admin_bar_front') as $m) {
                if (isset($meta[$m])) {
                    unset($meta[$m]);
                }
            }
        }

        // Set display_name from first_name and last_name
        if (isset($meta['first_name']) and isset($meta['last_name'])) {
            $wpdb->update(
                $wpdb->users,
                array(
                    'display_name' => trim($meta['first_name']) . ' ' . trim($meta['last_name'])
                ),
                array('ID' => $user->ID)
            );
        }

        return $meta;
    }


    public function fix_user_registered($data, $update, $id)
    {
        if ($update === false) {
            $data['user_registered'] = current_time('mysql');
        }
        return $data;
    }


}

new Sign;