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
    while (have_posts()) : the_post(); ?>

    <article class="post">
        <h2><a href="<?php the_permalink();?>"><?php the_title(); ?></a></h2>
        
        <p class="post-info"><?php the_time('F j, Y g:i a'); ?> | by <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>"><?php the_author();?></a> | Posted in
        
        
        <?php
        
        $categories = get_the_category();
        $seperator = ", ";
        $output = '';

        if($categories){
            foreach ($categories as $category){

                $output .= '<a href="'. get_category_link($category->term_id).'">' . $category->cat_name . '</a>' . $seperator;

            }

            echo trim($output, $seperator);
        }
        ?>
    
    </p>

        <?php 
        the_post_thumbnail('banner-image');
      
        the_content();
    

        ?>
    </article>

    <?php endwhile;

    else :
        echo '<p>No Content Found</p>';

    endif;

    get_footer();
?>