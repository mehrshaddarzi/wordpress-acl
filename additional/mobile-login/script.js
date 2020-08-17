jQuery(document).ready(function ($) {

    var wordpress_acl_otp_methods = {

    };

    // Push To global Rewrite API Js
    if (typeof window.rewrite_api_method !== 'undefined') {
        $.extend(window.rewrite_api_method, wordpress_acl_otp_methods);
    }
});