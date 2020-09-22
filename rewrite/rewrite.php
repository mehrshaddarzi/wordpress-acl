<?php

namespace WordPress_Rewrite_API_Request;

use WordPress_ACL\Helper;

class user
{
    public static $recovery_pass_meta = 'recovery_password';

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, '_register_js_script'), 7);

        add_action('wp', array($this, '_user_logout'));
        add_filter('rewrite_api_request_overhang_alert', function ($array) {
            $array['acl_logout'] = __('You have successfully logged out', 'wordpress-acl');
            return $array;
        });

        add_filter('authenticate', array($this, '_recovery_password_login'), 11, 3);
    }

    public function _register_js_script()
    {
        wp_enqueue_script('wordpress-acl', \WordPress_ACL::$plugin_url . '/rewrite/script.js', array('jquery', 'wp-rewrite-api'), \WordPress_ACL::$plugin_version, true);
    }

    public function _user_logout()
    {
        if (isset($_GET['user_logout']) and $_GET['user_logout'] == "true") {

            // Logout From System
            wp_logout();

            // Url
            $url = \WordPress_Rewrite_API_Request_Ui_Component::generate_overhang_link(
                remove_query_arg('user_logout'),
                'success',
                'acl_logout'
            );
            wp_redirect(apply_filters('wordpress_acl_url_after_logout', $url));
            exit;
        }
    }

    /**
     * Login User
     *
     * @return void
     */
    public static function login()
    {
        // Check Require Params
        if (!isset($_REQUEST['user_login']) || !isset($_REQUEST['user_password'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Prepare Data
        $user_login = sanitize_text_field($_REQUEST['user_login']);
        $user_password = sanitize_text_field($_REQUEST['user_password']);

        // Check Empty User Login
        if (empty($user_login)) {
            WordPress_Rewrite_API_Request::empty_param(apply_filters('wordpress_acl_user_login_name', __('username', 'wordpress-acl')));
        }

        // Check Empty Password
        if (empty($user_password)) {
            WordPress_Rewrite_API_Request::empty_param(apply_filters('wordpress_acl_user_password_name', __('password', 'wordpress-acl')));
        }

        // Check $remember
        $remember = false;
        if (isset($_REQUEST['remember']) and $_REQUEST['remember'] == "yes") {
            $remember = true;
        }

        // Prepare Data
        $data = apply_filters('wordpress_acl_credentials_data', array(
            'user_login' => $user_login,
            'user_password' => $user_password,
            'remember' => $remember
        ));

        // Use add_filter('authenticate', filter for custom login process
        // @see https://developer.wordpress.org/reference/hooks/authenticate/
        $user = wp_signon($data, false);
        if (is_wp_error($user) || is_null($user)) {
            wp_send_json_error(array(
                'code' => 'invalid_login',
                'message' => apply_filters('wordpress_acl_credentials_error_login', __('Invalid username, email address or incorrect password.', 'wordpress-acl'))
            ), 400);
        }

        // Return Data
        wp_send_json_success(apply_filters('wordpress_acl_login_return_data', Helper::get($user->ID)), 200);
    }

    /**
     * Add Recovery Password in Login User
     *
     * @Hook
     * @param $user
     * @param $username
     * @param $password
     * @return mixed
     */
    public function _recovery_password_login($user, $username, $password)
    {
        if (!is_null($user)) {
            return $user;
        }

        // Check Recover Password User
        $user_ids = Helper::get_users(array(
            'meta_query' => array(
                array(
                    'key' => self::$recovery_pass_meta,
                    'value' => $password,
                    'compare' => '='
                )
            )
        ));
        if (count($user_ids) < 1) {
            return null;
        }

        // Check Password
        $user = get_user_by('id', $user_ids[0]);
        if ($user && ($user->user_email == $username || $user->user_login == $username)) {
            update_user_meta($user->ID, self::$recovery_pass_meta, '');
            wp_set_password($password, $user->ID);
            return $user;
        }

        return null;
    }

    /**
     * Logout User
     *
     * @return void
     */
    public static function logout()
    {
        // Check User Logged IN
        WordPress_Rewrite_API_Request::auth_error();

        // Logout
        wp_logout();

        // Return
        wp_send_json_success(array(
            'message' => __('You have been successfully logged out', 'wordpress-acl')
        ), 200);
    }

    /**
     * Search User
     */
    public static function search()
    {
        // Check Require Params
        if (!isset($_REQUEST['user_by']) || !isset($_REQUEST['user_value'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Prepare Data
        $user_by = sanitize_text_field($_REQUEST['user_by']);
        $user_value = sanitize_text_field($_REQUEST['user_value']);

        // Check Empty
        if (empty($user_by)) {
            WordPress_Rewrite_API_Request::empty_param('user_by');
        }
        if (empty($user_value)) {
            WordPress_Rewrite_API_Request::empty_param(apply_filters('wordpress_acl_user_search_value', __('user_value', 'wordpress-acl')));
        }

        // Search User
        $user = false;
        if (in_array($user_by, array('id', 'email', 'login', 'slug'))) {
            $user = get_user_by($user_by, $user_value);
        } else {
            $user_ids = Helper::get_users(array(
                'meta_query' => array(
                    array(
                        'key' => $user_by,
                        'value' => $user_value,
                        'compare' => '='
                    )
                )
            ));
            if (count($user_ids) > 0) {
                $user = get_user_by('id', $user_ids[0]);
            }
        }

        // Get User Data
        $user = apply_filters('wordpress_acl_search_user', $user);

        // Action After Search User
        do_action('wordpress_acl_search_user_action', $user_by, $user_value, $user); //$user === false if not Found

        // Check Has User
        if (!$user) {

            // action user not found
            do_action('wordpress_acl_search_user_fail', $user_by, $user_value);

            // show error
            wp_send_json_error(array(
                'code' => 'invalid_login',
                'message' => apply_filters('wordpress_acl_search_user_error', __('No username or email found.', 'wordpress-acl'))
            ), 400);
        }

        // action after search User
        do_action('wordpress_acl_search_user_success', $user, $user->ID);

        // Return
        wp_send_json_success(array(
            'user_id' => $user->ID
        ), 200);
    }

    /**
     * Register in Site System
     *
     * @Rewrite_Ajax
     * @return void
     */
    public static function register()
    {
        // If User Logged Not Allow Register
        if (is_user_logged_in()) {
            WordPress_Rewrite_API_Request::not_permission();
        }

        // Check Require Params
        if (!isset($_REQUEST['user_login']) and !isset($_REQUEST['user_email'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Prepare Params
        $user_login = '';
        if(isset($_REQUEST['user_login'])) {
            $user_login = sanitize_text_field($_REQUEST['user_login']);

            // Check empty user_login
            if (empty($user_login)) {
                WordPress_Rewrite_API_Request::empty_param(apply_filters('wordpress_acl_user_login_name', __('username', 'wordpress-acl')));
            }

            // Check Before Get UserLogin
            if (username_exists($user_login) != false) {
                wp_send_json_error(array(
                    'code' => 'already_register_login',
                    'message' => apply_filters('wordpress_acl_exists_username_error', __('This username is already registered.', 'wordpress-acl'))
                ), 400);
            }
        }

        // Generate UserData
        $user_data = array();
        $user_data['user_login'] = $user_login;

        // Email
        if (isset($_REQUEST['user_email']) and !empty($_REQUEST['user_email'])) {
            $user_email = sanitize_text_field($_REQUEST['user_email']);

            // Check Valid
            if (is_email($user_email) === false) {
                wp_send_json_error(array(
                    'code' => 'invalid_email',
                    'message' => __('Invalid email address.', 'wordpress-acl')
                ), 400);
            }

            // Check Exist Before
            if (email_exists($user_email) != false) {
                wp_send_json_error(array(
                    'code' => 'already_register__email',
                    'message' => __('This email is already registered.', 'wordpress-acl')
                ), 400);
            }

            // Push to Data
            $user_data['user_email'] = $user_email;

            // Set user_login same user_email
            if(empty($user_login)) {
                $user_data['user_login'] = $user_email;
            }
        }

        // Password
        $user_data['user_pass'] = wp_generate_password(8, false);
        if (isset($_REQUEST['user_pass']) and !empty($_REQUEST['user_pass'])) {
            $user_data['user_pass'] = sanitize_text_field($_REQUEST['user_pass']);
        }

        // Password Confirm
        if (isset($_REQUEST['user_pass_confirm']) and !empty($_REQUEST['user_pass_confirm'])) {
            if($_REQUEST['user_pass'] !=$_REQUEST['user_pass_confirm']) {
                wp_send_json_error(array(
                    'code' => 'match_password',
                    'message' => __('Passwords Don\'t Match', 'wordpress-acl')
                ), 400);
            }
        }

        // First Name and Last Name
        $user_data['first_name'] = '';
        if (isset($_REQUEST['first_name']) and !empty($_REQUEST['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($_REQUEST['first_name']);
        }
        $user_data['last_name'] = '';
        if (isset($_REQUEST['last_name']) and !empty($_REQUEST['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($_REQUEST['last_name']);
        }

        // Display Name
        $user_data['display_name'] = $user_data['first_name'] . " " . $user_data['last_name'];

        // User Role
        $user_data['role'] = get_option('default_role');

        // Custom Error Check
        do_action('wordpress_acl_user_register_custom_fields', $user_login);

        // Register in Site
        // Use add_action('user_register', For After action Registered
        $user_id = wp_insert_user($user_data);

        // After Register Success
        do_action('wordpress_acl_user_register_success', $user_id);

        // Return
        wp_send_json_success(Helper::get($user_id), 200);
    }

    /**
     * Change User Password
     */
    public static function change_password()
    {
        // Check User Login
        WordPress_Rewrite_API_Request::auth_error();

        // Check Require Params
        if (!isset($_REQUEST['now_pass']) || !isset($_REQUEST['new_pass']) || !isset($_REQUEST['new_pass_2'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Sanitize Field
        $now_pass = sanitize_text_field($_REQUEST['now_pass']);
        $new_pass = sanitize_text_field($_REQUEST['new_pass']);
        $new_pass_2 = sanitize_text_field($_REQUEST['new_pass_2']);

        // Check Eq New Pass
        if ($new_pass != $new_pass_2) {
            wp_send_json_error(array(
                'code' => 'match_password',
                'message' => __('Passwords Don\'t Match', 'wordpress-acl')
            ), 400);
        }

        // Check Min character new password
        $min_password_character = apply_filters('wordpress_acl_min_character_password', 4);
        if (mb_strlen($new_pass) < $min_password_character) {
            wp_send_json_error(array(
                'code' => 'min_character_password',
                'message' => sprintf(
                    __('Password must be at least %s characters long.', 'wordpress-acl'),
                    $min_password_character
                )
            ), 400);
        }

        // Check Before Password
        $user_data = get_userdata(get_current_user_id());
        if (!wp_check_password($now_pass, $user_data->user_pass)) {
            wp_send_json_error(array(
                'code' => 'wrong_current_password',
                'message' => __('Your current password is incorrect.', 'wordpress-acl')
            ), 400);
        }

        // Change Password
        $user_data = wp_update_user(array('ID' => get_current_user_id(), 'user_pass' => $new_pass));
        if (is_wp_error($user_data)) {
            WordPress_Rewrite_API_Request::not_success_action();
        }

        // Successful
        wp_send_json_success(array(
            'message' => __('Password changed successfully', 'wordpress-acl')
        ), 200);
    }

    /**
     * Edit User Info
     *
     * @return void
     */
    public static function edit()
    {
        // Check User Login
        WordPress_Rewrite_API_Request::auth_error();
        $user_id = (int)get_current_user_id();

        // Check Require Params
        if (!isset($_REQUEST['first_name']) || !isset($_REQUEST['last_name'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Sanitize
        $first_name = sanitize_text_field($_REQUEST['first_name']);
        $last_name = sanitize_text_field($_REQUEST['last_name']);

        // Check empty first_name
        if (empty($first_name)) {
            WordPress_Rewrite_API_Request::empty_param(__('First name', 'wordpress-acl'));
        }
        if (empty($last_name)) {
            WordPress_Rewrite_API_Request::empty_param(__('Last name', 'wordpress-acl'));
        }

        // Current Data
        $user_data = array(
            'ID' => get_current_user_id(),
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
        );

        // Check Email
        if (isset($_REQUEST['user_email']) and !empty($_REQUEST['user_email'])) {
            $user_email = sanitize_text_field($_REQUEST['user_email']);

            // Check Valid
            if (is_email($user_email) === false) {
                wp_send_json_error(array(
                    'code' => 'invalid_email',
                    'message' => __('Invalid email address.', 'wordpress-acl')
                ), 400);
            }

            // Check Exist Before
            if (email_exists($user_email) && (email_exists($user_email) != $user_id)) {
                wp_send_json_error(array(
                    'code' => 'already_register__email',
                    'message' => __('This email is already registered.', 'wordpress-acl')
                ), 400);
            }

            // Push
            $user_data['user_email'] = $user_email;
        }

        // Custom Error Check
        do_action('wordpress_acl_user_edit_custom_fields', $user_id);

        // Update User Data
        // Use do_action( 'profile_update',  action for update
        $update_user_data = wp_update_user($user_data);
        if (is_wp_error($update_user_data)) {
            WordPress_Rewrite_API_Request::not_success_action();
        }

        // After User Update
        do_action('wordpress_acl_user_edit_success', $user_id);

        // Response
        wp_send_json_success(array(
            'message' => __('User info edited successfully', 'wordpress-acl')
        ), 200);
    }
}

new user;
