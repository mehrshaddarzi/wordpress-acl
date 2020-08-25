jQuery(document).ready(function ($) {

    var wordpress_acl_otp_methods = {
        countdown: function () {
            var seconds = 100;

            function tick() {
                var counter = document.getElementById("countdown");
                seconds--;
                counter.innerHTML = "0:" + (seconds < 10 ? "0" : "") + String(seconds);
                if (seconds > 0) {
                    setTimeout(tick, 1000);
                } else {
                    $("a[data-function=send_new_code]").show();
                    $("#countdown").hide();
                }
            }

            tick();
        }
    };

    // Push To global Rewrite API Js
    if (typeof window.rewrite_api_method !== 'undefined') {
        $.extend(window.rewrite_api_method, wordpress_acl_otp_methods);
    }
});