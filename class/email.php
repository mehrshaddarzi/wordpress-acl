<?php

namespace WordPress_ACL;

use WordPress_Rewrite_API_Request\user;

class Email
{
    public static $acf_setting_page_slug = 'wp-theme-settings';

    public function __construct()
    {
        // Create Tab Email in Setting ACF
        add_action('acf/init', array($this, 'acf_add_local_field_groups'));

        // Register User
        add_action('user_register', array($this, 'register'), 10, 1);

        // Send forget Password Email
        add_action('wordpress_acl_search_user_success', array($this, 'forget_password_send_email'), 10, 2);
    }

    /**
     * Send SMS in Reset Password
     *
     * @Hook
     * @param $user
     * @param $user_id
     */
    public function forget_password_send_email($user, $user_id)
    {
        if (isset($_REQUEST['do_action']) and $_REQUEST['do_action'] == "forget_password") {

            // Get Email
            $user = get_userdata($user_id);
            $email = $user->user_email;
            if (empty($email)) {
                return;
            }

            // Send Email
            $password = strtolower( wp_generate_password( 10, false ) );
            update_user_meta($user_id, user::$recovery_pass_meta, $password);
            //wp_set_password($password, $user_id); // Dont change Password For Hack
            $get_mail_body = self::get_mail_body(array(
                'option_name' => 'wp_acl_email_forget_body',
                'params' => array_merge(self::get_default_params($user_id), array('[password]' => $password))
            ));
            $get_mail_subject = self::get_mail_subject(array(
                'option_name' => 'wp_acl_email_forget_subject',
                'params' => array_merge(self::get_default_params($user_id), array('[password]' => $password))
            ));
            wp_send_mail($email, $get_mail_subject, $get_mail_body);
        }
    }

    public function register($user_id)
    {
        // Get Email
        $user = get_userdata($user_id);
        $email = $user->user_email;
        if (empty($email)) {
            return;
        }

        // Send Email
        if (isset($_REQUEST['user_pass'])) {
            $password = sanitize_text_field($_REQUEST['user_pass']);
        } else {
            $password = wp_generate_password( 8, false );
            wp_set_password($password, $user_id);
        }
        $get_mail_body = self::get_mail_body(array(
            'option_name' => 'wp_acl_email_register_body',
            'params' => array_merge(self::get_default_params($user_id), array('[password]' => $password))
        ));
        $get_mail_subject = self::get_mail_subject(array(
            'option_name' => 'wp_acl_email_register_subject',
            'params' => array_merge(self::get_default_params($user_id), array('[password]' => $password))
        ));
        wp_send_mail($email, $get_mail_subject, $get_mail_body);
    }

    public static function acf_add_local_field_groups()
    {

        if (function_exists('acf_add_local_field_group')):

            // Common Tag
            $use_tg = __('Use tags', 'wordpress-acl') . ':<br>';
            $first_name_tg = __('First name', 'wordpress-acl') . ': [first-name]<br>';
            $last_name_tg = __('Last name', 'wordpress-acl') . ': [last-name]<br>';
            $user_id_tg = __('User id', 'wordpress-acl') . ': [id]<br>';
            $user_login_tg = __('UserLogin', 'wordpress-acl') . ': [user-login]<br>';
            $user_email_tg = __('Email', 'wordpress-acl') . ': [user-email]<br>';
            $user_mobile_tg = __('Mobile', 'wordpress-acl') . ': [mobile]<br>';
            $user_password_tg = __('Password', 'wordpress-acl') . ': [password]<br>';
            $site_name_tg = __('Site Name', 'wordpress-acl') . ': [site-name]<br>';
            $site_url = __('Site Url', 'wordpress-acl') . ': [site-url]<br>';
            $default_instructions_content = $use_tg . $first_name_tg . $last_name_tg . $user_id_tg . $user_login_tg . $user_email_tg . $user_mobile_tg . $site_name_tg . $site_url;
            $default_instructions_subject = $use_tg . $site_name_tg;
            $compact = compact('use_tg', 'first_name_tg', 'last_name_tg', 'user_id_tg', 'user_login_tg', 'user_email_tg', 'user_mobile_tg', 'user_password_tg', 'site_name_tg', 'site_url');

            // Register Email Body
            $email_register_body = __('Hi', 'wordpress-acl') . ', [first-name] [last-name]';
            $email_register_body .= '<br />';
            $email_register_body .= __('Your registration is complete. Your user information:', 'wordpress-acl');
            $email_register_body .= '<br />';
            $email_register_body .= __('Email', 'wordpress-acl') . ': [user-email]';
            $email_register_body .= '<br />';
            $email_register_body .= __('Password', 'wordpress-acl') . ': [password]';

            // Forget Password Email
            $email_forget_body = __('Hi', 'wordpress-acl') . ', [first-name] [last-name]';
            $email_forget_body .= '<br />';
            $email_forget_body .= __('Your new password on the website is:', 'wordpress-acl');
            $email_forget_body .= '[password]';
            $email_forget_body .= '<br /><br />';
            $email_forget_body .= __('Ignore this email if it was not sent to your request.', 'wordpress-acl');

            // List Fields
            acf_add_local_field_group(array(
                'key' => 'group_email_settings',
                'title' => __('Email Settings', 'wordpress-acl'),
                'fields' => apply_filters('wordpress_acl_email_acf_fields', array(
                    array(
                        'key' => 'field_5f54d4318ba21',
                        'label' => __('Active Email', 'wordpress-acl'),
                        'name' => 'active_email',
                        'type' => 'true_false',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'message' => '',
                        'default_value' => 1,
                        'ui' => 1,
                        'ui_on_text' => '',
                        'ui_off_text' => '',
                    ),
                    array(
                        'key' => 'wp_acl_email_basic_tab',
                        'label' => __('Basic', 'wordpress-acl'),
                        'name' => '',
                        'type' => 'tab',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_5f54d4318ba21',
                                    'operator' => '==',
                                    'value' => '1',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'placement' => 'top',
                        'endpoint' => 0,
                    ),
                    array(
                        'key' => 'wp_acl_email_register_subject',
                        'label' => __('Register Email Subject', 'wordpress-acl'),
                        'name' => 'email_register_subject',
                        'type' => 'text',
                        'instructions' => $default_instructions_subject,
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '[site-name] | ' . __('Register', 'wordpress-acl'),
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'wp_acl_email_register_body',
                        'label' => __('Register Email Body', 'wordpress-acl'),
                        'name' => 'email_register_body',
                        'type' => 'wysiwyg',
                        'instructions' => $default_instructions_content . $user_password_tg,
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => $email_register_body,
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'delay' => 0,
                    ),
                    array(
                        'key' => 'wp_acl_email_forget_subject',
                        'label' => __('Forget Password Email Subject', 'wordpress-acl'),
                        'name' => 'email_forget_subject',
                        'type' => 'text',
                        'instructions' => $default_instructions_subject,
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '[site-name] | ' . __('Recovery Password', 'wordpress-acl'),
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'wp_acl_email_forget_body',
                        'label' => __('forget Password Email Body', 'wordpress-acl'),
                        'name' => 'email_forget_body',
                        'type' => 'wysiwyg',
                        'instructions' => $default_instructions_content . $user_password_tg,
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => $email_forget_body,
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'delay' => 0,
                    ),
                ), $compact, $default_instructions_content, $default_instructions_subject),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'wp-theme-settings',
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

    public static function is_active()
    {
        $active = get_field('active_email', 'option');
        if ($active === true) {
            return true;
        }

        return false;
    }

    public static function get_mail_body($arg = array())
    {
        $defaults = array(
            'option_name' => '',
            'params' => array()
        );
        $args = wp_parse_args($arg, $defaults);

        // Get Option Text
        $option_name = apply_filters('wordpress_acl_option_mail_body', $args['option_name']);
        $message = get_field($option_name, 'option');
        if (empty($message)) {
            return false;
        }

        // Setup custom Arg
        if (!empty($args['params'])) {
            $message = str_ireplace(array_keys($args['params']), array_values($args['params']), $message);
        }

        return $message;
    }

    public static function get_mail_subject($arg = array())
    {
        $defaults = array(
            'option_name' => '',
            'params' => array()
        );
        $args = wp_parse_args($arg, $defaults);

        // Get Option Text
        $option_name = apply_filters('wordpress_acl_option_mail_subject', $args['option_name']);
        $subject = get_field($option_name, 'option');
        if (empty($subject)) {
            return false;
        }

        // Setup custom Arg
        if (!empty($args['params'])) {
            $subject = str_ireplace(array_keys($args['params']), array_values($args['params']), $subject);
        }

        return $subject;
    }

    public static function get_default_params($user_id = '', $additional = array())
    {
        $user = get_user_by('id', $user_id);
        $array = array(
            '[first-name]' => $user->first_name,
            '[last-name]' => $user->last_name,
            '[id]' => $user_id,
            '[ID]' => $user_id,
            '[user-login]' => $user->user_login,
            '[user-email]' => $user->user_email,
            '[mobile]' => Helper::get_user_phone_number($user_id),
            '[site-name]' => get_option('blogname'),
            '[site-url]' => get_option('siteurl'),
        );

        return apply_filters('wordpress_acl_default_email_data', $array);
    }
}

new Email;