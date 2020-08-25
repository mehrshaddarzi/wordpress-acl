<?php

namespace WordPress_ACL;

class Mobile_Login
{
    public static $mobile_user_meta = 'billing_phone';

    public function __construct()
    {

        // Disable Email Require For Register in WordPress
        add_action('user_profile_update_errors', array($this, 'disable_email'));

        // Allow + Character For Username as Mobile
        add_filter('sanitize_user', array($this, 'sanitize_user'), 10, 3);

        // User Validation in Insert Or Update Request
        add_filter('user_profile_update_errors', array($this, 'user_validation'), 50, 3);

        // Hide field From Admin Wordpress
        add_action("admin_head", array($this, 'admin_head'), 20);
        add_action('user_new_form', array($this, 'fb_add_custom_user_profile_fields'), 10);

        // Register User
        add_action('user_register', array($this, 'register'), 10, 1);

        // Login With billing_phone as Login
        add_filter('authenticate', array($this, 'authenticate'), 11, 3);
        add_filter('wordpress_acl_user_login_name', function () {
            return __('mobile', 'wordpress-acl');
        });
        add_filter('wordpress_acl_user_search_value', function () {
            return __('mobile', 'wordpress-acl');
        });
        add_filter('wordpress_acl_credentials_error_login', function () {
            return __('Invalid mobile or incorrect password.', 'wordpress-acl');
        });
        add_filter('wordpress_acl_search_user_error', function () {
            return __('No user was found with this mobile number.', 'wordpress-acl');
        });
        add_filter('wordpress_acl_exists_username_error', function () {
            return __('This mobile is already registered.', 'wordpress-acl');
        });
        add_action('wordpress_acl_search_user_success', array($this, 'forget_password_send_code'), 10, 2);
        add_action('wordpress_acl_search_user_action', array($this, 'register_send_code'), 10, 3);
        add_action('wordpress_acl_user_register_custom_fields', array($this, 'check_register_code'));

        // Disable Email Setting ACF system in WordPress ACL
        remove_action('acf/init', array('WordPress_ACL\Email', 'acf_add_local_field_groups'));

        // Add Setting SMS text in ACF
        add_filter('wp_sms_dev_acf_settings', array($this, 'sms_setting_acf_field'));
    }

    /**
     * Send SMS in Reset Password
     *
     * @Hook
     * @param $user
     * @param $user_id
     */
    public function forget_password_send_code($user, $user_id)
    {
        if (isset($_REQUEST['do_action']) and $_REQUEST['do_action'] == "forget_password") {

            // Get User Mobile
            $user_mobile = Helper::get_user_phone_number($user_id);

            // Generate New Code
            $code = self::generate_new_otp_code('mobile', $user_mobile, $user_id);

            // Send SMS
            $get_sms_text = \WP_SMS_Helper::get_text_message(array(
                'option_name' => 'sms-reset-password',
                'params' => array_merge(self::generate_default_sms_data($user_id), array('[password]' => $code['code']))
            ));
            \WP_SMS_Helper::send_sms($user_mobile, $get_sms_text);
        }
    }

    /**
     * Send SMS Code in Register
     *
     * @Hook
     * @param $user_by
     * @param $user_value
     * @param $user
     */
    public function register_send_code($user_by, $user_value, $user)
    {
        // Check Only For Mobile
        if ($user_by != 'billing_phone') {
            return;
        }

        // User ID
        $user_id = 0;
        if ($user != false) {
            $user_id = $user->ID;
        }

        if (isset($_REQUEST['do_action']) and $_REQUEST['do_action'] == "register_code") {

            // Get User Mobile
            $user_mobile = $user_value;

            // Check Validation Mobile Number
            $mobile_check = Persian_ACL::validate_mobile($user_mobile);
            if ($mobile_check['success'] === false) {
                wp_send_json_error(array(
                    'message' => $mobile_check['text']
                ), 400);
            }

            // Check User Before Exist
            if (username_exists($user_mobile) != false) {
                wp_send_json_error(array(
                    'code' => 'already_register_login',
                    'message' => apply_filters('wordpress_acl_exists_username_error', __('This username is already registered.', 'wordpress-acl'))
                ), 400);
            }

            // Generate New Code
            $code = self::generate_new_otp_code('mobile', $user_mobile, $user_id);

            // Send SMS
            $get_sms_text = \WP_SMS_Helper::get_text_message(array(
                'option_name' => 'sms-new-user-register-code',
                'params' => array('[code]' => $code['code'])
            ));
            \WP_SMS_Helper::send_sms($user_mobile, $get_sms_text);

            // Result
            wp_send_json_success(array(
                'message' => __('The registration authentication code was sent to your mobile number.', 'wordpress-acl')
            ), 200);
        }
    }

    /**
     * Check User Register Code
     *
     * @Hook
     * @param $user_login
     */
    public function check_register_code($user_login)
    {
        // Wrong Token Message
        $wrong_token = array(
            'message' => __('Your authentication code is incorrect', 'wordpress-acl')
        );

        // Only Check Register Code
        if (isset($_REQUEST['do_action']) and $_REQUEST['do_action'] == "check_register_code") {

            // Get User Mobile
            $user_mobile = $user_login;
            $code = sanitize_text_field($_REQUEST['code']);

            // Check User Code
            $code_query = self::check_user_opt_code($code, 'mobile', $user_mobile);
            if ($code_query != false and self::check_expire_time_user_otp($code_query) === true) {
                // True
                wp_send_json_success(array(
                    'code' => $code
                ), 200);
            } else {
                // False
                wp_send_json_error($wrong_token, 400);
            }
        }

        //  Check User code in Register
        if (!isset($_REQUEST['code']) || (isset($_REQUEST['code']) and empty($_REQUEST['code']))) {
            wp_send_json_error($wrong_token, 400);
        }
        $code_query = self::check_user_opt_code($_REQUEST['code'], 'mobile', $user_login);
        if ($code_query === false) {
            wp_send_json_error($wrong_token, 400);
        }
        if (self::check_expire_time_user_otp($code_query) === false) {
            wp_send_json_error(array(
                'message' => __('Your authentication code has expired', 'wordpress-acl')
            ), 400);
        }

        // Check Persian first_name and last_name
        if(empty($_REQUEST['first_name'])) {
            wp_send_json_error(array(
                'message' => 'لطفا نام خود را وارد نمایید'
            ), 400);
        }
        if(empty($_REQUEST['last_name'])) {
            wp_send_json_error(array(
                'message' => 'لطفا نام خانوادگی خود را وارد نمایید'
            ), 400);
        }
        if (Persian_ACL::check_persian_input($_REQUEST['first_name']) ===false) {
            wp_send_json_error(array(
                'message' => __('لطفا نام خود را به فارسی وارد کنید', 'wordpress-acl')
            ), 400);
        }
        if (Persian_ACL::check_persian_input($_REQUEST['last_name']) ===false) {
            wp_send_json_error(array(
                'message' => 'لطفا نام خانوادگی خود را به فارسی تایپ کنید'
            ), 400);
        }
    }

    /**
     * SMS Setting
     *
     * @Hook
     * @param $fields
     * @return mixed
     */
    public function sms_setting_acf_field($fields)
    {
        // Common Tag
        $use_tg = __('Use tags', 'wordpress-acl') . ':<br>';
        $first_name_tg = __('First name', 'wordpress-acl') . ': [first-name]<br>';
        $last_name_tg = __('Last name', 'wordpress-acl') . ': [last-name]<br>';
        $user_id_tg = __('User id', 'wordpress-acl') . ': [id]<br>';
        $user_password_tg = __('Password', 'wordpress-acl') . ': [password]<br>';
        $user_login_tg = __('UserLogin', 'wordpress-acl') . ': [user-login]<br>';
        $user_email_tg = __('Email', 'wordpress-acl') . ': [user-email]<br>';
        $user_code_tg = __('Code', 'wordpress-acl') . ': [code]<br>';
        $default_instructions = $use_tg . $first_name_tg . $last_name_tg . $user_id_tg . $user_password_tg . $user_login_tg;

        // Add To forget Password
        $fields[] = array(
            'key' => 'field_sllnvr5ds4',
            'label' => __('Forget Password', 'wordpress-acl'),
            'name' => 'sms-reset-password',
            'type' => 'textarea',
            'instructions' => $default_instructions,
            'required' => 0,
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_5f158bc2bf5a5',
                        'operator' => '==',
                        'value' => '2',
                    ),
                ),
            ),
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'hide_admin' => 0,
            'default_value' => __('Hi [first-name] [last-name], Your New Password is [password]', 'wordpress-acl'),
            'placeholder' => '',
            'maxlength' => '',
            'rows' => '',
            'new_lines' => '',
        );

        // Add To New User
        $fields[] = array(
            'key' => 'field_df9vwagvyu',
            'label' => __('New user register', 'wordpress-acl'),
            'name' => 'sms-new-user-register',
            'type' => 'textarea',
            'instructions' => $default_instructions,
            'required' => 0,
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_5f158bc2bf5a5',
                        'operator' => '==',
                        'value' => '2',
                    ),
                ),
            ),
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'hide_admin' => 0,
            'default_value' => __('Hi [first-name] [last-name], Your login profile is, username: [user-login] and password: [password]', 'wordpress-acl'),
            'placeholder' => '',
            'maxlength' => '',
            'rows' => '',
            'new_lines' => '',
        );

        // Add Register Code
        $fields[] = array(
            'key' => 'field_7bgjx1hrrm',
            'label' => __('New user register Code', 'wordpress-acl'),
            'name' => 'sms-new-user-register-code',
            'type' => 'textarea',
            'instructions' => $use_tg . $user_code_tg,
            'required' => 0,
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_5f158bc2bf5a5',
                        'operator' => '==',
                        'value' => '2',
                    ),
                ),
            ),
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
            'hide_admin' => 0,
            'default_value' => __('Your register code is: [code]', 'wordpress-acl'),
            'placeholder' => '',
            'maxlength' => '',
            'rows' => '',
            'new_lines' => '',
        );

        return $fields;
    }

    /**
     * Generate Default SMS Data
     *
     * @Helper
     * @param $user_id
     * @return mixed|void
     */
    public static function generate_default_sms_data($user_id)
    {
        $user = get_user_by('id', $user_id);
        $array = array(
            '[first-name]' => $user->first_name,
            '[last-name]' => $user->last_name,
            '[id]' => $user_id,
            '[ID]' => $user_id,
            '[user-login]' => $user->user_login,
            '[user-email]' => $user->user_email
        );

        return apply_filters('wordpress_acl_default_sms_data', $array);
    }

    public function register($user_id)
    {
        // Set Billing Phone
        $user = get_userdata($user_id);
        update_user_meta($user_id, self::$mobile_user_meta, $user->user_login);

        // Send SMS
        $code = rand(10000, 99999);
        wp_set_password($code, $user_id);
        $get_sms_text = \WP_SMS_Helper::get_text_message(array(
            'option_name' => 'sms-new-user-register',
            'params' => array_merge(self::generate_default_sms_data($user_id), array('[password]' => $code))
        ));
        \WP_SMS_Helper::send_sms($user->user_login, $get_sms_text);

        // Remove All Code
        self::remove_all_otp_code('mobile', $user->user_login);

        // Auto Login
        if (!is_admin() and !is_user_logged_in()) {
            Helper::set_current_user($user_id, true);
        }
    }

    public function disable_email($arg)
    {
        if (!empty($arg->errors['empty_email'])) {
            unset($arg->errors['empty_email']);
        }
    }

    /**
     * Sanitize Username Filter
     * @see https://developer.wordpress.org/reference/functions/sanitize_user/
     *
     * @param $username
     * @param $raw_username
     * @param $strict
     * @return void
     */
    public function sanitize_user($username, $raw_username, $strict)
    {
        $first_character = substr($raw_username, 0, 1);
        if ($first_character == "+") {
            return "+" . trim($username);
        }

        return $username;
    }

    /**
     * Add billing_phone as Username in Login
     *
     * @Hook
     * @param $user
     * @param $username
     * @param $password
     * @return mixed
     */
    public function authenticate($user, $username, $password)
    {
        if (!is_null($user)) {
            return $user;
        }

        // Check billing_phone User
        $user_ids = Helper::get_users(array(
            'meta_query' => array(
                array(
                    'key' => self::$mobile_user_meta,
                    'value' => $username,
                    'compare' => '='
                )
            )
        ));
        if (count($user_ids) < 1) {
            return null;
        }

        // Check Password
        $user = get_user_by('id', $user_ids[0]);
        if ($user && wp_check_password($password, $user->data->user_pass, $user->ID)) {
            return $user;
        } else {

            // Check OTP Password
            $user_query = self::check_user_opt_code_by_id($password, $user_ids[0]);
            if ($user_query != false) {

                // Check Expire Time OTP
                if (!self::check_expire_time_user_otp($user_query)) {
                    return null;
                }

                // Set Password
                wp_set_password($password, $user_ids[0]);

                // Remove All User OTP
                self::remove_all_users_otp_code($user_ids[0]);

                // get User
                return $user;
            }

            return null;
        }
    }

    /**
     * Remove All User ID OTP Code
     *
     * @Helper
     * @param $User_ID
     */
    public static function remove_all_users_otp_code($User_ID)
    {
        global $wpdb;
        $wpdb->query("DELETE FROM `{$wpdb->prefix}users_code` WHERE `user_id` = {$User_ID}");
    }

    /**
     * Remove OTP Code
     *
     * @Helper
     * @param $login_by
     * @param string $value
     */
    public static function remove_all_otp_code($login_by, $value = '')
    {
        global $wpdb;
        $wpdb->query("DELETE FROM `{$wpdb->prefix}users_code` WHERE `{$login_by}` = '{$value}'");
    }

    /**
     * Check User otp Code by ID
     *
     * @Helper
     * @param $code
     * @param $user_id
     * @return string|null
     */
    public static function check_user_opt_code_by_id($code, $user_id)
    {
        global $wpdb;
        $query = $wpdb->get_row("SELECT `created_at`, `ID` FROM `{$wpdb->prefix}users_code` WHERE `code` = '{$code}' AND `user_id` = {$user_id}", ARRAY_A);
        if (is_null($query)) {
            return false;
        }

        return $query;
    }

    /**
     * Check User otp Code by Data
     *
     * @Helper
     * @param $code
     * @param string $by
     * @param $value
     * @return string|null
     */
    public static function check_user_opt_code($code, $by = 'mobile', $value)
    {
        global $wpdb;
        $query = $wpdb->get_row("SELECT `created_at`, `ID` FROM `{$wpdb->prefix}users_code` WHERE `code` = '{$code}' AND `$by` = '{$value}'", ARRAY_A);
        if (is_null($query)) {
            return false;
        }

        return $query;
    }

    /**
     * Get Expire Time OTP
     *
     * @Helper
     * @return mixed|void
     */
    public static function get_expire_time_otp()
    {
        return apply_filters('wordpress_acl_otp_time_expire', (MINUTE_IN_SECONDS * 5));
    }

    /**
     * Check Expire Code
     *
     * @Helper
     * @param $query
     * @return bool
     */
    public static function check_expire_time_user_otp($query)
    {
        $created_at = strtotime($query['created_at']);
        $current = current_time('timestamp');
        if (($created_at + self::get_expire_time_otp()) < $current) {
            return false;
        }
        return true;
    }

    /**
     * Generate OTP Code
     *
     * @Helper
     * @param string $login_by
     * @param string $value
     * @param int $user_id
     * @return array
     */
    public static function generate_new_otp_code($login_by = 'email', $value = '', $user_id = 0)
    {
        global $wpdb;

        // Remove All OTP Code
        self::remove_all_otp_code($login_by, $value);

        // Check Before User ID
        if ($user_id == 0) {
            if ($login_by == 'email') {
                $user = get_user_by('email', $value);
                if ($user != false) {
                    $user_id = $user->ID;
                }
            }
            if ($login_by == 'mobile') {
                $user_ids = Helper::get_users(array(
                    'meta_query' => array(
                        array(
                            'key' => self::$mobile_user_meta,
                            'value' => $value,
                            'compare' => '='
                        )
                    )
                ));
                if (count($user_ids) > 0) {
                    $user = get_user_by('id', $user_ids[0]);
                    $user_id = $user->ID;
                }
            }
        }

        // Create Code
        $code = rand(10000, 99999);
        $array = array(
            'code' => $code,
            'created_at' => current_time('mysql'),
            'user_id' => $user_id
        );
        $array[$login_by] = $value;
        $wpdb->insert(
            $wpdb->prefix . 'users_code',
            $array
        );

        return array('code' => $code, 'db_id' => $wpdb->insert_id);
    }

    /**
     * @Hook
     */
    public function admin_head()
    {
        global $pagenow;
        if ($pagenow == "users.php") {
            ?>
            <style>
                .row-actions span.view {
                    display: none;
                }

                td.column-username,
                td.column-phone {
                    direction: ltr;
                    text-align: right;
                }

                td.column-username strong {
                    font-weight: normal;
                }
            </style>
            <?php
        }

        if ($pagenow == "user-edit.php" || $pagenow == "profile.php") {
            // @see https://wordpress.stackexchange.com/questions/69461/how-to-obtain-the-user-id-of-the-current-profile-being-edited-in-wp-admin
            global $user_id;
            ?>
            <style>
                li#wp-admin-bar-view {
                    display: none;
                }

                h2 {
                    display: none;
                }

                .wrap {
                    display: none;
                }

                tr.user-user-login-wrap td span {
                    display: none;
                }
            </style>
            <script>
                jQuery(document).ready(function ($) {
                    $("label[for=user_login]").html("موبایل");
                    $("input#rich_editing").parent().parent().parent().parent().hide();
                    $("textarea#description").parent().parent().parent().hide();
                    $("input#url").parent().parent().hide();
                    // $("input#email").parent().parent().parent().hide();
                    $("label[for=email]").html("پست الکترونیک");
                    $("#email-description").hide();
                    $("input#nickname").parent().parent().hide();
                    $("select#display_name").parent().parent().hide();
                    $("#fieldset-shipping").hide();

                    // Show Form at last
                    $(".wrap").fadeIn('normal');
                });
            </script>
            <?php
        }
    }

    public function fb_add_custom_user_profile_fields()
    {
        ?>
        <style>
            tr.pw-weak {
                display: none !important;
            }

            .wrap {
                display: none;
            }

            tr.user-pass1-wrap {
                display: none;
            }
        </style>
        <script>
            jQuery(document).ready(function ($) {

                // change Page title
                $("h1").html("افزودن آیتم");

                // Remove Wrap P Tags First
                $("div.wrap p:first").remove();

                // Hide Email
                //$("input#email").parent().parent().hide();
                $('#email').closest('tr').removeClass('form-required').find('.description').remove();
                $("label[for=email]").html("پست الکترونیک");
                $("#email-description").hide();

                // Hide Url
                $("input#url").parent().parent().hide();

                // Hide Send Notification
                $("input#send_user_notification").parent().parent().hide();
                $("input[name=send_user_notification]").prop('checked', false);

                // Change User Login to Mobile
                jQuery.fn.ForceNumericOnly =
                    function () {
                        return this.each(function () {
                            $(this).keydown(function (e) {
                                var key = e.charCode || e.keyCode || 0;
                                return (
                                    key == 8 ||
                                    key == 9 ||
                                    key == 13 ||
                                    key == 46 ||
                                    key == 110 ||
                                    key == 190 ||
                                    (key >= 35 && key <= 40) ||
                                    (key >= 48 && key <= 57) ||
                                    (key >= 96 && key <= 105));
                            });
                        });
                    };
                $("label[for=user_login]").html("موبایل");
                $("input[name=user_login]").addClass("ltr");
                $("input[name=user_login]").val("09");
                $("input[name=user_login]").ForceNumericOnly();

                // Disable Auto complete
                $("form[name=createuser]").attr("autocomplete", "off");

                // Hide User Password and Set Automatic
                //$("input[name=pw_weak]").attr("checked", "checked").parent().parent().hide();
                //$("tr[class=pw-weak]").attr("style", "display:none;");
                //$("tr[class=pw-weak]").attr("style", "display:none;");
                //$("div[id=pass-strength-result]").hide();
                //jQuery(document).on('keyup', 'input[name=pass1-text]', function(e) {
                //    e.preventDefault();
                //    $("tr[class=pw-weak]").attr("style", "display:none;");
                //});
                //let math_num = Math.floor((Math.random() * 99999) + 10000);
                //$("input[name=pass1]").val(math_num).attr("data-pw", math_num).attr("readonly", "readonly");

                // Move Role To The first Item
                let role_group = $("select[name=role]").parent().parent();
                let role_clone = role_group.clone();
                role_group.remove();
                $(role_clone).prependTo("table.form-table:first tbody");

                // Set First to author in role select tbox
                $("select[name=role]").val('author');
                $(document).on("change", "select[name=role]", function (e) {
                    e.preventDefault();
                    let val = $(this).val();

                    // First Default
                    //jQuery("label[for^='pass1']").parent().parent().hide();

                    // Hide For Brand
                    if (val === "administrator") {
                        // jQuery("label[for^='pass1']").parent().parent().show();
                    }
                });

                // Show Form at last
                $(".wrap").fadeIn('normal');
            });
        </script>
        <?php
    }

    public function user_validation($errors, $update, $user)
    {
        // For insert New User
        if ($update === false) {
            // Space in user login
            if (preg_match('/\s/', $_POST['user_login'])) {
                $errors->add('space_in_user_login_required', __('<p><strong>خطا</strong>: لطفا از خط تیره به جای قاصله در نام کاربری استفاده کنید</p>'));
            }

            // Only English character
            //			if ( preg_match( '/[^A-Za-z0-9]+/', $_POST['user_login'] ) ) {
            //				$errors->add( 'english_in_user_login_required', __( '<p><strong>خطا</strong>: نام کاربری می بایست با حروف انگلیسی یا اعداد نوشته شود</p>' ) );
            //			}

            // Check Mobile number
            $mobile_check = Persian_ACL::validate_mobile($_POST['user_login']);
            if ($mobile_check['success'] === false) {
                $errors->add('mobile_number_error', __('<p><strong>خطا</strong>: ' . $mobile_check['text'] . '</p>'));
            }
        }

        // Together insert and update
        // Required Name
        if (empty($_POST['first_name'])) {
            $errors->add('first_name_required', __('<p><strong>خطا</strong>: لطفا نام را وارد نمایید</p>'));
        } else {
            if (Persian_ACL::check_persian_input($_POST['first_name']) === false) {
                $errors->add('first_name_persian', __('<p><strong>خطا</strong>: نام میبایست فارسی باشد</p>'));
            }
        }

        // Check Last name
        if (empty($_POST['last_name'])) {
            $errors->add('last_name_required', __('<p><strong>خطا</strong>: لطفا نام خانوادگی را وارد نمایید</p>'));
        } else {
            if (Persian_ACL::check_persian_input($_POST['last_name']) === false) {
                $errors->add('last_name_persian', __('<p><strong>خطا</strong>: نام خانوادگی میبایست فارسی باشد</p>'));
            }
        }

        return $errors;
    }
}

new Mobile_Login;