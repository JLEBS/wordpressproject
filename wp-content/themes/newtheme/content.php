<article class="post <?php if( has_post_thumbnail() ) { ?>has-thumbnail <?php } ?>">


    
    <div class="post-thumbnail">
        <a href="<?php the_permalink();?>"><?php the_post_thumbnail('banner-image');?></a>
    </div>
   

    <h2><a href="<?php the_permalink();?>"><?php the_title(); ?></a></h2>

    <p class="post-info"><?php the_time('F j, Y g:i a'); ?> | by <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>"><?php the_author();?></a> | Posted in

    <?php

        $categories = get_the_category();
        $seperator = ", ";
        $output = '';

        if($categories)
        {
            foreach ($categories as $category){

                $output .= '<a href="'. get_category_link($category->term_id).'">' . $category->cat_name . '</a>' . $seperator;
            }

            echo trim($output, $seperator);
        }

    ?>
    </p>

    <?php 

    if(is_search() OR is_archive())
    {

        if (is_singular())
        {
            the_content();
        } 
        else 
        {
            echo get_the_excerpt(); ?>
            <a href="<?php the_permalink(); ?>">Read More&raquo;</a>
            <?php 
       }
    }


    else
    {

        if (is_singular())
        {
            the_content();
        } 
        else 
        {
            echo get_the_excerpt(); ?>
            <a href="<?php the_permalink(); ?>">Read More&raquo;</a>

    <?php 
        }

    }
    ?>

</article>