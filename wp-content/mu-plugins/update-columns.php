<?php

    add_filter( 'manage_session_posts_columns', 'set_custom_updates_columns' );

    function getColumnNames () {
        return [
            'sessionEvent' => 'Event', 
            'sessionStartTime' => 'Start Time',
            'sessionEndTime' => 'End Time',
            'isBreakoutSession' => 'Breakout Session' 
        ];
    }

    function set_custom_updates_columns($columns) {
        return array_merge($columns, getColumnNames());
    }

    // Add the data to the custom columns for the updates post type:
    add_action( 'manage_session_posts_custom_column' , 'custom_updates_column', 10, 2 );

    function custom_updates_column( $columnName, $post_id ) {
        global $wpdb;

        $value = get_field($columnName, $post_id);
        
        switch ($columnName) {
            case 'sessionEvent':
                $event = get_field('sessionEvent', $post_id);
                echo get_the_title($event);
                return;
            case 'isBreakoutSession':
                echo $value
                    ? 'Yes'
                    : 'No';
                return;
        }

        echo $value;
    }

?>