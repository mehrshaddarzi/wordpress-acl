<?php

namespace WordPress_Rewrite_API_Request;

class otp
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, '_register_js_script'), 8);
    }

    public function _register_js_script()
    {
        wp_enqueue_script('wordpress-acl-otp', \WordPress_ACL::$plugin_url . '/additional/mobile-login/script.js', array('jquery', 'wp-rewrite-api'), \WordPress_ACL::$plugin_version, true);
    }

}

new otp;
