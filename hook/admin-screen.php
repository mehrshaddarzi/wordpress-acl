<?php

namespace WordPress_ACL;

class Admin_Screen
{

    public function __construct()
    {
        // User row Action
        add_filter('user_row_actions', array($this, 'user_row_action'), 10, 2);

        // Add column For users.php
        add_filter('manage_users_columns', array($this, 'column'), 100);
        add_action('manage_users_custom_column', array($this, 'column_content'), 10, 3);
        add_filter('manage_users_sortable_columns', array($this, 'sortable_column'));
        add_action("pre_get_users", array($this, 'pre_user_query'), 10, 1);

        // Remove Help Tab
        add_action('admin_head', array($this, 'remove_help_tab'));

        // Remove User Contact [AIM , ..] in profile.php
        add_filter('user_contactmethods', array($this, 'update_contact_methods'));

        // Disable Admin Confirm Change Email
        remove_action('add_option_new_admin_email', 'update_option_new_admin_email');
        remove_action('update_option_new_admin_email', 'update_option_new_admin_email');
        add_action('add_option_new_admin_email', array($this, 'wpdocs_update_option_new_admin_email'), 10, 2);
        add_action('update_option_new_admin_email', array($this, 'wpdocs_update_option_new_admin_email'), 10, 2);
    }

    /**
     * Disable the confirmation notices when an administrator
     * changes their email address.
     *
     * @see http://codex.wordpress.com/Function_Reference/update_option_new_admin_email
     */
    function wpdocs_update_option_new_admin_email($old_value, $value)
    {
        update_option('admin_email', $value);
    }

    function user_row_action($actions, $user)
    {

        $actions['ID'] = $user->ID . '#';
        //unset($actions['delete']);

        return $actions;
    }

    public function sortable_column($columns)
    {
        //$columns['id'] = 'ID';
        $columns['date-reg'] = 'user_registered';
        $columns['id'] = 'ID';
        return $columns;
    }

    public function column($columns)
    {
        //$columns['id']           = 'شناسه سیستم';
        $columns['name-user'] = __('Full name', 'wordpress-acl');
        $columns['id'] = __('ID', 'wordpress-acl');
        $columns['phone'] = __('Mobile', 'wordpress-acl');
        $columns['date-reg'] = __('Register date', 'wordpress-acl');
        $columns['username'] = __('Username', 'wordpress-acl');

        unset($columns['posts']);
        unset($columns['name']);
        unset($columns['role']);
        //unset($columns['email']);
        return $columns;
    }

    public function column_content($value, $column_name, $user_id)
    {
        global $wpdb;

        $user = get_userdata($user_id);
        if ('id' == $column_name) {
            return $user_id;
        }
        if ('phone' == $column_name) {
            $phone = Helper::get_user_phone_number($user_id);
            if (empty($phone)) {
                return '-';
            } else {
                return $phone;
            }
        }
        if ('name-user' == $column_name) {
            return Helper::get_user_full_name($user_id);
        }
        if ('date-reg' == $column_name) {
            return date_i18n("Y-m-d H:i", $user->user_registered);
        }

        return $value;
    }

    public function pre_user_query($WP_User_Query)
    {
        if (!is_admin()) {
            return;
        }
        if (isset($WP_User_Query->query_vars["orderby"]) && ("order_num" === $WP_User_Query->query_vars["orderby"])) {
            $WP_User_Query->query_vars["meta_key"] = "_order_count";
            $WP_User_Query->query_vars["orderby"] = "meta_value";
        }
    }

    public function remove_help_tab()
    {
        global $current_screen;
        if ($current_screen->id == "users") {
            $current_screen->remove_help_tabs();
        }
    }

    public function update_contact_methods($contactmethods)
    {
        unset($contactmethods['aim']);
        unset($contactmethods['jabber']);
        unset($contactmethods['yim']);

        return $contactmethods;
    }

    public function user_validation($errors, $update, $user)
    {
        // For insert New User
        if (!$update) {
            // Space in user login
            if (preg_match('/\s/', $_POST['user_login'])) {
                $errors->add('space_in_user_login_required', __('<p><strong>خطا</strong>: لطفا از خط تیره به جای قاصله در نام کاربری استفاده کنید</p>'));
            }

            // Check Mobile number
            $mobile_check = Helper::mobile_check($_POST['user_login']);
            if ($mobile_check['success'] === false) {
                $errors->add('mobile_number_error', __('<p><strong>خطا</strong>: ' . $mobile_check['text'] . '</p>'));
            }
        }

        // Together insert and update
        // Required Name
        if (empty($_POST['first_name'])) {
            $errors->add('first_name_required', __('<p><strong>خطا</strong>: لطفا نام را وارد نمایید</p>'));
        } else {
            if (Helper::check_persian_input($_POST['first_name']) === false) {
                $errors->add('first_name_persian', __('<p><strong>خطا</strong>: نام میبایست فارسی باشد</p>'));
            }
        }

        // Check Last name
        if (empty($_POST['last_name'])) {
            $errors->add('last_name_required', __('<p><strong>خطا</strong>: لطفا نام خانوادگی را وارد نمایید</p>'));
        } else {
            if (Helper::check_persian_input($_POST['last_name']) === false) {
                $errors->add('last_name_persian', __('<p><strong>خطا</strong>: نام خانوادگی میبایست فارسی باشد</p>'));
            }
        }

        return $errors;
    }
}

new Admin_Screen;