<?php
/* Template Name: Special Layout*/

get_header();

if (have_posts()) :
    while (have_posts()) : the_post(); ?>

<article class="post page">
    <h2><?php the_title(); ?></h2>

    <div class="info-box">
        <h4>Disclaimer</h4>
        <p>sTamquam propriae vim et. Duo ad clita tamquam, no accusam expetenda interesset pro, tota nostrud usu te. 
            Audiam lucilius id duo, ei stet duis voluptua quo, duo et omnis quidam vituperatoribus. 
            Ei scripta efficiantur interpretaris has, delicata scripserit nam ne. Dico quas moderatius vel ne. Ea regione molestie vel.</p>
    </div>
    <?php the_content();?>
</article>

    <?php endwhile;

    else :
        echo '<p>No Content Found</p>';

    endif;

    get_footer();
?>
