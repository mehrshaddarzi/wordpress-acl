<?php

namespace WordPress_ACL;

class Helper
{
    /**
     * Get User Data
     *
     * @param bool $user_id
     * @return array
     */
    public static function get($user_id = false)
    {
        //@TODO Use wp_set_cache system similar woocommerce product git
        // Get User ID
        $user_id = $user_id ? $user_id : get_current_user_id();

        // Get User Data
        $user_data = get_userdata($user_id);
        $user_info = get_object_vars($user_data->data);
        unset($user_info['user_pass']);

        // Get User roles
        $user_info['role'] = $user_data->roles;
        //if ( in_array( 'author', (array) $user->roles ) ) {}

        // Get User Caps
        $user_info['cap'] = $user_data->caps;

        // Get User Meta
        $user_info['meta'] = array_map(function ($a) {
            return $a[0];
        }, get_user_meta($user_id));

        // Remove Unused data
        if (!is_admin()) {
            unset($user_info['meta']['session_tokens']);
            foreach ($user_info['meta'] as $meta_key => $meta_value) {
                if (substr($meta_key, 0, 1) == "_" || substr($meta_key, 0, 8) == "meta-box" || substr($meta_key, 0, 13) == "metaboxhidden") {
                    unset($user_info['meta'][$meta_key]);
                }
            }
        }

        // Return Data
        $user_info = apply_filters('wordpress_acl_get_user_data', $user_info, $user_id);
        return $user_info;
    }

    /**
     * Get User Phone number
     * We use `billing_phone` user meta from WooCommerce
     *
     * @param bool $user_id
     * @return string
     */
    public static function get_user_phone_number($user_id = false)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $phone = get_user_meta($user_id, 'billing_phone', true);
        if (empty($phone)) {
            return false;
        }

        return $phone;
    }

    /**
     * Get User email
     *
     * @param bool $user_id
     * @return string
     */
    public static function get_user_email($user_id = false)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        //setup user data
        $user_info = get_userdata($user_id);
        return $user_info->user_email;
    }

    /**
     * Get User Name
     *
     * @param bool $user_id
     * @return string
     */
    public static function get_user_full_name($user_id = false)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Get User Data
        $user_info = get_userdata($user_id);

        //check display name
        if ($user_info->display_name != "") {
            return $user_info->display_name;
        }

        //Check First and Last name
        if ($user_info->first_name != "") {
            return $user_info->first_name . " " . $user_info->last_name;
        }

        //return Username
        return $user_info->user_login;
    }

    /**
     * Check User Exist By id
     *
     * @param $user
     * @return bool
     * We Don`t Use get_userdata or get_user_by function, because We need only count nor UserData object.
     */
    public static function user_id_exists($user)
    {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $user));
        if ($count == 1) {
            return true;
        }

        return false;
    }

    /**
     * Set Role For User ID
     *
     * @param $user_id
     * @param $new_role
     * @return void
     * @see https://usersinsights.com/wordpress-custom-role/
     */
    public static function set_role($user_id, $new_role)
    {

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = new \WP_User($user_id);
        $user->set_role($new_role);
    }

    /**
     * Add Role For User ID
     *
     * @param [type] $user_id
     * @return void
     * @see https://usersinsights.com/wordpress-custom-role/
     */
    public static function add_role($user_id, $additional_role)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = new \WP_User($user_id);
        $user->add_role($additional_role);
    }


    /**
     * Get Users
     * @see https://developer.wordpress.org/reference/classes/wp_user_query/
     * @see https://generatewp.com/wp_user_query/
     *
     * @param array $arg
     * @return mixed
     */
    public static function get_users($arg = array())
    {

        $list = array();
        $default = array(
            'fields' => array('id'),
            'orderby' => 'id',
            'order' => 'ASC',
            'count_total' => false
        );
        $args = wp_parse_args($arg, $default);

        $user_query = new \WP_User_Query($args);
        //[Get Request SQL]
        //echo $user_query->request; 
        foreach ($user_query->get_results() as $user) {
            $list[] = $user->id;
        }

        return $list;
    }

    public static function get_only_count_user($user_query)
    {
        global $wpdb;
        $user_query->query_fields = 'COUNT(*) as count';
        return $user_query;
    }

    /**
     * Count Users
     * @see https://developer.wordpress.org/reference/classes/wp_user_query/
     * @see https://generatewp.com/wp_user_query/
     *
     * @param array $arg
     * @return mixed
     */
    public static function countUsers($arg = array())
    {

        // Add Action Count
        add_action('pre_user_query', array(__CLASS__, 'get_only_count_user'));

        // Get List
        $default = array(
            'fields' => array('id'),
            'orderby' => 'id',
            'order' => 'ASC',
            'count_total' => false
        );
        $args = wp_parse_args($arg, $default);
        $user_query = new \WP_User_Query($args);

        // Remove Action Count
        remove_action('pre_user_query', array(__CLASS__, 'get_only_count_user'));

        // Get Request
        //echo $user_query->request;
        //exit;

        //Return
        $data = $user_query->get_results();
        if (isset($data['0']->count) and $data['0']->count > 0) {
            return (int)$data['0']->count;
        }
        return 0;
    }

    /**
     * Search By NiceName
     *
     * @param $search
     * @return array
     */
    public static function SearchByNiceName($search)
    {
        $search_string = trim($search);
        $user_query = new \WP_User_Query(array(
            'search' => "*{$search_string}*",
            'search_columns' => array('display_name'),
        ));
        if (empty($user_query->results)) {
            return array();
        } else {
            $list = array();
            foreach ($user_query->results as $user) {
                $list[] = $user->ID;
            }
            return $list;
        }
    }

    /**
     * Set Current User
     *
     * @param $user_id
     * @param bool $remember
     */
    public static function set_current_user($user_id, $remember = true)
    {
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, $remember);
    }

}
