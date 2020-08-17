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
            ["user_login", "user_password"].forEach(function (item, index) {
                let input = $('#' + item);
                if (input.length && input.val().length > 0) {
                    arg[item] = input.val();
                }
            });

            // prepare remember
            if ($('input#remember').length && $("input#remember").prop('checked') === true) {
                arg['remember'] = 'yes';
            }

            // Send Data
            window.rewrite_api_method.request('user/login', 'GET', arg);
        },
        logout_user: function ($tag = false) {
            window.rewrite_api_method.request('user/logout', 'GET');
        },
        search_user: function ($tag = false, $user_by = '', $user_value = '') {
            // Prepare user_by
            if ($('#user_by').length) {
                $user_by = $('#user_by').val();
            }

            // prepare user_value
            if ($('#user_value').length) {
                $user_value = $('#user_value').val();
            }

            // Check Action
            let arg = {
                'user_by': $user_by,
                'user_value': $user_value
            };
            if ($tag.attr('data-do-action')) {
                arg['do_action'] = $tag.attr('data-action');
            }

            // Send Request
            window.rewrite_api_method.request('user/search', 'GET', arg);
        },
        register_user: function ($tag = false, $user_login = '') {

            // arg
            let arg = {
                'user_login': $user_login
            };

            // extra parameters
            ["user_login", "user_email", "user_pass", "first_name", "last_name", "display_name"].forEach(function (item, index) {
                let input = $('#' + item);
                if (input.length && input.val().length > 0) {
                    arg[item] = input.val();
                }
            });

            // Do Action
            if ($tag.attr('data-do-action')) {
                arg['do_action'] = $tag.attr('data-action');
            }

            // Send Data
            window.rewrite_api_method.request('user/register', 'POST', arg);
        },
        change_password_user: function ($tag = false, $now_pass = '', $new_pass = '', $new_pass_2 = '') {

            // argument
            let arg = {
                'now_pass': $now_pass,
                'new_pass': $new_pass,
                'new_pass_2': $new_pass_2,
            };
            ["now_pass", "new_pass", "new_pass_2"].forEach(function (item, index) {
                let input = $('#' + item);
                if (input.length && input.val().length > 0) {
                    arg[item] = input.val();
                }
            });

            // Send Data
            window.rewrite_api_method.request('user/change_password', 'POST', arg);
        },
        edit_user: function ($tag = false, $first_name = '', $last_name = '') {

            // arg
            let arg = {
                'first_name': $first_name,
                'last_name': $last_name
            };

            // extra parameters
            ["first_name", "last_name"].forEach(function (item, index) {
                let input = $('#' + item);
                if (input.length && input.val().length > 0) {
                    arg[item] = input.val();
                }
            });

            // Send Data
            window.rewrite_api_method.request('user/edit', 'POST', arg);
        }
    };

    // Push To global Rewrite API Js
    if (typeof window.rewrite_api_method !== 'undefined') {
        $.extend(window.rewrite_api_method, wordpress_acl_methods);
    }
});