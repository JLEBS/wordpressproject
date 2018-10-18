<?php
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