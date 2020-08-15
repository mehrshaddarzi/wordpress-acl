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

        // Login With billing_phone as Login
        add_filter('authenticate', array($this, 'authenticate'), 11, 3);

        // User Validation in Insert Or Update Request
        add_filter('user_profile_update_errors', array($this, 'user_validation'), 50, 3);

        // Hide field From Admin Wordpress
        add_action("admin_head", array($this, 'admin_head'), 20);
        add_action('user_new_form', array($this, 'fb_add_custom_user_profile_fields'), 10);

        // Register User
        add_action('user_register', array($this, 'register'), 10, 1);
    }

    public function register($user_id)
    {
        // Set Billing Phone
        $user = get_userdata($user_id);
        update_user_meta($user_id, self::$mobile_user_meta, $user->user_login);
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
            return null;
        }
    }

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
            /* tr.pw-weak {
                display: none !important;
            } */

            .wrap {
                display: none;
            }

            /*tr.user-pass1-wrap {
                display: none;
            }*/
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