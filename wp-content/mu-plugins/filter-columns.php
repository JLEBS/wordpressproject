<?php

add_action( 'restrict_manage_posts', 'wpse45436_admin_posts_filter_restrict_manage_posts' );
/**
 * First create the dropdown
 * make sure to change POST_TYPE to the name of your custom post type
 * 
 * @author Ohad Raz
 * 
 * @return void
 */
function wpse45436_admin_posts_filter_restrict_manage_posts(){
    $type = 'post';

    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }

    //only add filter to post type you want
    if ($type !== 'session') {
        return;
    }

    $events = get_posts([
        'post_type' => 'event'
    ]);

   // var_dump($events);

    $values = [];

    foreach ($events as $event) {
        $values[$event->ID] = $event->post_title;
    }

    ?>

    <select name="event_id">
    <option value="">All Events</option>
    <?php
        $current_v = isset($_GET['event_id'])? $_GET['event_id'] : '';
        foreach ($values as $value => $label) {
            printf(
                    '<option value="%s"%s>%s</option>',
                    $value,
                    $value == $current_v? ' selected="selected"':'',
                    $label
                );
            }
    ?>
    </select>
    <?php

}

add_filter( 'parse_query', 'wpse45436_posts_filter' );

/**
 * if submitted filter by post meta
 * 
 * make sure to change META_KEY to the actual meta key
 * and POST_TYPE to the name of your custom post type
 * @author Ohad Raz
 * @param  (wp_query object) $query
 * 
 * @return Void
 */

function wpse45436_posts_filter( $query ){    
    global $pagenow;
    $type = 'post';
    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }

    if (!$query->is_main_query())
    {
        return;
    }

    //only add filter to post type you want
    if ($type !== 'session') {
        return;
    }

    if (is_admin() && $pagenow=='edit.php' && isset($_GET['event_id']) && $_GET['event_id'] != '') {
        $query->query_vars['meta_key'] = 'sessionEvent';
        $query->query_vars['meta_value'] = $_GET['event_id'];
    }
}

?>