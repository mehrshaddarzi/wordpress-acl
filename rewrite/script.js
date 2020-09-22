jQuery(document).ready(function ($) {

    var wordpress_acl_methods = {
        is_email: function (email) {
            let regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        },
        login_user: function ($tag = false, $user_login = '', $user_password = '', $remember = 'no') {

            // arg
            let arg = {
                'user_login': $user_login,
                'user_password': $user_password,
                'remember': $remember
            };

            // extra parameters
            if ($tag !== false) {
                arg = $.extend(arg, window.rewrite_api_method.get_form_inputs($tag));

                // prepare remember
                let form_name = $tag.attr('data-form');
                if ($('input#remember' + '[data-form=' + form_name + ']').length && $("input#remember").prop('checked') === true) {
                    arg['remember'] = 'yes';
                }
            }

            // Send Data
            window.rewrite_api_method.request('user/login', 'GET', arg, $tag);
        },
        logout_user: function ($tag = false) {
            window.rewrite_api_method.request('user/logout', 'GET', {}, $tag);
        },
        search_user: function ($tag = false, $user_by = '', $user_value = '') {
            // Check Action
            let arg = {
                'user_by': $user_by,
                'user_value': $user_value
            };

            // Prepare tags
            if ($tag !== false) {
                arg = $.extend(arg, window.rewrite_api_method.get_form_inputs($tag));
            }

            // Send Request
            window.rewrite_api_method.request('user/search', 'GET', arg, $tag);
        },
        register_user: function ($tag = false, $user_login = '') {

            // arg
            let arg = {
                /*'user_login': $user_login*/
            };

            // Extra parameters
            if ($tag !== false) {
                arg = $.extend(arg, window.rewrite_api_method.get_form_inputs($tag));
            }

            // Send Data
            window.rewrite_api_method.request('user/register', 'GET', arg, $tag);
        },
        change_password_user: function ($tag = false, $now_pass = '', $new_pass = '', $new_pass_2 = '') {

            // argument
            let arg = {
                'now_pass': $now_pass,
                'new_pass': $new_pass,
                'new_pass_2': $new_pass_2,
            };

            // Prepare tags
            if ($tag !== false) {
                arg = $.extend(arg, window.rewrite_api_method.get_form_inputs($tag));
            }

            // Send Data
            window.rewrite_api_method.request('user/change_password', 'GET', arg, $tag);
        },
        edit_user: function ($tag = false, $first_name = '', $last_name = '') {

            // arg
            let arg = {
                'first_name': $first_name,
                'last_name': $last_name
            };

            // extra parameters
            if ($tag !== false) {
                arg = $.extend(arg, window.rewrite_api_method.get_form_inputs($tag));
            }

            // Send Data
            window.rewrite_api_method.request('user/edit', 'POST', arg, $tag);
        }
    };

    // Push To global Rewrite API Js
    if (typeof window.rewrite_api_method !== 'undefined') {
        $.extend(window.rewrite_api_method, wordpress_acl_methods);
    }
});