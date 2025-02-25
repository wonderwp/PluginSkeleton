<?php
add_filter('wwp.fe_controller.renderPage', 'wwp_register_render_block_theme_post_content');
function wwp_register_render_block_theme_post_content($post)
{
    // Modify the post-content via its prerender hook
    add_filter('pre_render_block', function ($pre_render, $parsed_block) use ($post) {
        return wwp_prerender_block_theme_post_content($pre_render, $parsed_block, $post);
    }, 10, 2);

    //Modify the post-title via its prerender hook
    add_filter('pre_render_block', function ($pre_render, $parsed_block) use ($post) {
        return wwp_prerender_block_theme_post_title($pre_render, $parsed_block, $post);
    }, 10, 2);

    //Modify the post-excerpt via its prerender hook
    add_filter('pre_render_block', function ($pre_render, $parsed_block) use ($post) {
        return wwp_prerender_block_theme_post_excerpt($pre_render, $parsed_block, $post);
    }, 10, 2);

    //Modify the post-featured-image via its prerender hook
    add_filter('pre_render_block', function ($pre_render, $parsed_block) use ($post) {
        return wwp_prerender_block_theme_post_featured_image($pre_render, $parsed_block, $post);
    }, 10, 2);

    //Modify the page html title
    add_filter('pre_get_document_title', function ($title) use ($post) {
        return $post->post_title . ' - ' . get_bloginfo('name');
    }, 10, 1);
}

if (!function_exists('wwp_prerender_block_theme_post_content')) {
function wwp_prerender_block_theme_post_content($pre_render, $parsed_block, $post)
{
    if ($parsed_block['blockName'] === 'core/post-content') {
        $pre_render = $post->post_content;
    }

        return $pre_render;
    }
}

if (!function_exists('wwp_prerender_block_theme_post_title')) {
    function wwp_prerender_block_theme_post_title($pre_render, $parsed_block, $post)
    {
        if ($parsed_block['blockName'] === 'core/post-title') {
            $pre_render = $post->post_title;
        }

        return $pre_render;
    }
}

if (!function_exists('wwp_prerender_block_theme_post_excerpt')) {
    function wwp_prerender_block_theme_post_excerpt($pre_render, $parsed_block, $post)
    {
        if ($parsed_block['blockName'] === 'core/post-excerpt') {
            $pre_render = $post->post_excerpt;
        }

        return $pre_render;
    }
}

if (!function_exists('wwp_prerender_block_theme_post_featured_image')) {
    function wwp_prerender_block_theme_post_featured_image($pre_render, $parsed_block, $post)
    {
        if ($parsed_block['blockName'] === 'core/post-featured-image') {
            $pre_render = get_the_post_thumbnail($post->ID, 'full');
        }

        return $pre_render;
    }
}
