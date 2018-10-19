<?php get_header();

$events = get_posts(
    ['post_type' => 'event']
);
  // var_dump($events);

   foreach($events as $event){

        echo $event->event;
       // var_dump($event);
   }

if (have_posts()) :
    while (have_posts()) : the_post(); 

    get_template_part('content', get_post_format());
   
    endwhile;

    else :
        echo '<p>No Content Found</p>';

    endif;

    get_footer();
?>