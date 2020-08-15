jQuery(document).ready(function ($) {

    var favorite_post_methods = {
        add_favorite_post: function ($tag = false, $post_id = 0, $category = '') {
            // Sanitize Params
            if ($tag !== false) {
                $post_id = $tag.attr('data-post-id');
                if ($tag.attr('data-category')) {
                    $category = $tag.attr('data-category');
                }
            }
            window.rewrite_api_method.request('favorite_post/add', 'GET', {
                'post_id': $post_id,
                'category': $category
            });
        },
        remove_favorite_post: function ($tag = false, $post_id = 0) {
            // Sanitize Params
            if ($tag !== false) {
                $post_id = $tag.attr('data-post-id');
            }
            window.rewrite_api_method.request('favorite_post/remove', 'GET', {
                'post_id': $post_id
            });
        }
    };

    // Push To global Rewrite API Js
    if (typeof window.rewrite_api_method !== 'undefined') {
        $.extend(window.rewrite_api_method, favorite_post_methods);
    }
});