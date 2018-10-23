<?php

get_header();

$events = get_posts(
    ['post_type' => 'event']
 );

$speakers = get_posts(
    ['post_type' => 'speaker']
);

$sessions = get_posts(
    ['post_type' => 'session']
);
  
?>
	
	<article class="post page">
        <table>
            <caption class="tname">Events Table</caption>
            <tr>
                <th>Event</th>
                <!--<td>Name</td>-->
                <th>Topic</th>
                <th>Description</th>
                <th>Starts</th>
                <th>Ends</th>
            </tr>
            <?php
                foreach($events as $event)
                {
                    echo '<tr>';
                    //echo '<td>' . $event->post_title . '</td>';
                    echo '<td>' . get_field('eventName', $event->ID) . '</td>';
                    echo '<td>' . get_field('eventTopic', $event->ID) . '</td>';
                    echo '<td>' . get_field('eventDescription', $event->ID) . '</td>';
                    echo '<td>' . get_field('eventStartTime', $event->ID) . '</td>';
                    echo '<td>' . get_field('eventEndTime', $event->ID) . '</td>';
                    echo '</tr>';
            
                }
            ?>
        </table>

         <table>
            <caption class="tname">Speaker Table</caption>
            <tr>
                <th>Title</th>
                <th>Firstname</th>
                <th>Surname</th>
                <th>Country/Region</th>
                <th>Bio</th>
                <th>Company</th>
                <th>Job Title</th>
            </tr>
            <?php
                foreach($speakers as $speaker)
                {
                    //echo "<pre>";
                    //var_dump($speakers);
                    //echo "</pre>";
                    
                    echo '<tr>';
                    echo '<td>' . get_field('speakerTitle', $speaker->ID) . '</td>';
                    echo '<td>' . get_field('speakerFirstname', $speaker->ID) . '</td>';
                    echo '<td>' . get_field('speakerSurname', $speaker->ID) . '</td>';
                    echo '<td>' . get_field('speakerResidence', $speaker->ID) . '</td>';
                    echo '<td>' . get_field('speakerBio', $speaker->ID) . '</td>';
                    echo '<td>' . get_field('speakerCompany', $speaker->ID) . '</td>';
                    echo '<td>' . get_field('speakerJobtitle', $speaker->ID) . '</td>';
                    echo '</tr>';
            
                }
            ?>
        </table>

         <table>
            <caption class="tname">Session Table</caption>
            <tr>
                <th>Name</th>
                <th>Topic</th>
                <th>Description</th>
                <th>Starts</th>
                <th>Ends</th>
                <th>Speaker(s)</th>
                <th>Breakout Session?</th>
            </tr>
            <?php
                foreach($sessions as $session)
                {

                    echo '<tr>';
                    echo '<td>' . get_field('sessionName', $session->ID) . '</td>';
                    echo '<td>' . get_field('sessionTopic', $session->ID) . '</td>';
                    echo '<td>' . get_field('sessionDescription', $session->ID) . '</td>';
                    echo '<td>' . get_field('sessionStartTime', $session->ID) . '</td>';
                    echo '<td>' . get_field('sessionEndTime', $session->ID) . '</td>';
                    echo "<td>";

                    $speakers = get_field('sessionSpeaker', $session->ID);

                    echo "Number of Speakers: " . count($speakers) . "</br></br>";

                    foreach($speakers as $speaker)
                    {   
                        echo $speaker->speakerFirstname . " " . $speaker->speakerSurname;
                        echo "<ul><li>". "Firstname: " . $speaker->speakerFirstname . "</li>";
                        echo "<li>". "Surname: " . $speaker->speakerSurname . "</li>";
                        echo "<li>". "Title: " . $speaker->speakerTitle . "</li>";
                        echo "<li>". "Bio: " . $speaker->speakerBio . "</li>";
                        echo "<li>". "Company: " . $speaker->speakerCompany . "</li>";
                        echo "<li>". "Job Title: " . $speaker->speakerJobtitle . "</li></ul> ";
                    }
                    echo '</td>';

                    $istrue = get_field('isBreakoutSession', $session->ID);
                        
                    if ($istrue == 1)
                        {
                            echo "<td>Yes</td>";
                        }
                        else{
                            echo "<td>No</td>";
                        } 
                 
                    echo '</tr>';
            
                }
            ?>
        </table>


	</article>
	<?php
	
get_footer();

?>