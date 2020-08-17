<?php

namespace WordPress_ACL;

class Email
{

    public static $acf_setting_page_slug = 'wp-theme-settings';

    public function __construct()
    {
        // Create Tab Email in Setting ACF
        add_action('acf/init', array(__CLASS__, 'acf_add_local_field_groups'));
    }

    public static function acf_add_local_field_groups()
    {
        if (function_exists('acf_add_local_field_group')):
            acf_add_local_field_group(array(
                'key' => 'group_wp_email_setting',
                'title' => __('Email Settings', 'wordpress-acl'),
                'fields' => apply_filters('wordpress_acl_email_acf_settings', array(
                        array(
                            'key' => 'field_active_email_acl',
                            'label' => __('Active email system', 'wordpress-acl'),
                            'name' => 'email-active',
                            'type' => 'select',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'hide_admin' => 0,
                            'choices' => array(
                                1 => __('no', 'wordpress-acl'),
                                2 => __('yes', 'wordpress-acl'),
                            ),
                            'default_value' => 2,
                            'allow_null' => 0,
                            'multiple' => 0,
                            'ui' => 1,
                            'ajax' => 0,
                            'return_format' => 'value',
                            'placeholder' => '',
                        ))
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => self::$acf_setting_page_slug,
                        ),
                    ),
                ),
            ));
        endif;
    }

    // Load Email Template From plugin to this plugin
    // Add Method for send sms from utility wp-mvc
    // Add Method for convert message shortcode from wp-sms-dev

}

new Email;