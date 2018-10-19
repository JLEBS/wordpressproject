<?php

//require_once('wp-content/themes/newtheme/update-columns.php');

//require_once $_SERVER['DOCUMENT_ROOT'] . 'wp-content/themes/newtheme/update-columns.php';

function learningWordPress(){
    wp_enqueue_style('style', get_stylesheet_uri(), [], time());

}

add_action('wp_enqueue_scripts', 'learningWordPress');

//Navigation Menus

register_nav_menus(array(
    'primary' => __( 'Primary Menu'),
    'footer' => __( 'Footer Menu'),
));

//Get top ancestor
function get_top_ancestor_id(){

    global $post;

    if ($post->post_parent) {
        $ancestors = array_reverse(get_post_ancestors($post->ID));
            return $ancestors[0];


    }

    return $post->ID;
} 

//does page have child
function has_children(){

    global $post;

    $pages = get_pages('child_of=' . $post->ID);
    return count($pages);
}

//Customize excerpt word count length
function custom_excerpt_length(){

    return 50;
}

add_filter('excerpt_length', 'custom_excerpt_length');

//Feeatured image support
function image_thumbnail(){
    add_theme_support('post-thumbnails');
    add_image_size('small-thumbnail', 920, 210, false);
    add_image_size('banner-image', 920, 210, false);
}
add_action('after_setup_theme', 'image_thumbnail');
