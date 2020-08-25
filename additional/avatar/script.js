jQuery(document).ready(function ($) {

    var wordpress_acl_avatar_methods = {
        select_user_avatar: function ($tag = false) {
            $("input#profile_image_upload").click();
        },
        change_avatar_user: function ($tag = false) {

            // FormData
            let form_data = new FormData($tag[0]);

            // Check Image Size
            if (window.rewrite_api_method.check_file_size("profile_image_upload", rewrite_api.avatar.mb) === true) {
                if (window.rewrite_api_method.isset(window.rewrite_api_method, 'show_overhang')) {
                    window.rewrite_api_method.show_overhang('error', rewrite_api.avatar.size);
                } else {
                    alert(rewrite_api.avatar.size);
                }
            }

            // Check Image Type
            if (window.rewrite_api_method.check_file_ext("profile_image_upload") === false) {
                if (window.rewrite_api_method.isset(window.rewrite_api_method, 'show_overhang')) {
                    window.rewrite_api_method.show_overhang('error', rewrite_api.avatar.ext);
                } else {
                    alert(rewrite_api.avatar.ext);
                }
            }

            // Send Data
            window.rewrite_api_method.request('avatar/change', 'POST', form_data, $tag);
        }
    };

    // Push To global Rewrite API Js
    if (typeof window.rewrite_api_method !== 'undefined') {
        $.extend(window.rewrite_api_method, wordpress_acl_avatar_methods);
    }
});