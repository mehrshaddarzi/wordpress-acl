<?php

namespace WordPress_ACL;

class Role
{

    public function __construct()
    {
        // Role
        add_action('init', array($this, 'role'));

        // Remove WooCommerce Default Role From Edit and insert User
        // https://codex.wordpress.org/Plugin_API/Filter_Reference/editable_roles
        add_filter('editable_roles', array($this, 'edit_select_roles'));
    }

    /**
     * Config Role
     *
     * @see https://wordpress.org/support/article/roles-and-capabilities/
     * @see https://developer.wordpress.org/reference/functions/remove_role/
     */
    public function role()
    {
        global $wp_roles;

        // Check Has Role global
        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        //Show list all roles
        // echo '<pre>';
        // print_r(array_keys($wp_roles->roles));
        // exit;

        // Remove Role in WordPress
        // $arr = array('shop_manager', 'customer');
        // foreach($arr as $r) {
        //     remove_role($r);
        // }

        //You can list all currently available roles like this...
        //$roles = $wp_roles->get_names();
        // We Don't Use remove_role because it removed completely From DB
        unset($wp_roles->roles['editor']);
        unset($wp_roles->roles['contributor']);

        // Change name of Role
        //$wp_roles->roles['author']['name'] = 'کاربر';
        //$wp_roles->role_names['author']    = 'کاربر';

        // Create New role
        //		if ( ! isset( $wp_roles->roles['agent'] ) ) {
        //			$author = $wp_roles->get_role( 'author' );
        //			$wp_roles->add_role( 'agent', 'پرسش گر', $author->capabilities );
        //		}
    }

    public function edit_select_roles($all_roles)
    {
        global $pagenow;

        if ($pagenow == "user-new.php") {
            //unset($all_roles['shop_manager']);
            //unset($all_roles['customer']);
        }

        return $all_roles;
    }

}

new Role;