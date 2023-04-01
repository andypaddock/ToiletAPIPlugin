<?php

/**
 * Toilet API pull and push into ACF fileds
 *
 * @package     Toilet
 * @author      Andy Paddock
 * @copyright   
 * @license     
 *
 * @wordpress-plugin
 * Plugin Name: Norfolk Toilets
 * Plugin URI:  
 * Description: 
 * Version:     1.0.0
 * Author:      
 * Author URI:  
 * Text Domain: 
 * License:     
 * License URI: 
 */




// Schedule the event on plugin activation
register_activation_hook(__FILE__, 'toiletmap_schedule_update');
function toiletmap_schedule_update()
{
    wp_schedule_event(time(), 'weekly', 'toiletmap_update_event');
}

// Hook the code to the scheduled event
add_action('toiletmap_update_event', 'toiletmap_update_posts');
function toiletmap_update_posts()
{
    $url = "https://www.toiletmap.org.uk/api";
    $query = <<<QUERY
query loosInNorfolk {
  loosByProximity(from: {lat: 52.630886, lng: 1.297355, maxDistance: 50000}) {
    name
    location {
      lat
      lng
    }
    accessible
    noPayment
    updatedAt
  }
}
QUERY;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('query' => $query)));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $toilets = $data['data']['loosByProximity'];

    foreach ($toilets as $toilet) {
        $post_title = $toilet['name'];
        // $post_content = "Accessible: " . ($toilet['accessible'] ? 'Yes' : 'No') . "<br>";
        // $post_content .= "No Payment: " . ($toilet['noPayment'] ? 'Yes' : 'No') . "<br>";
        // $post_content .= "Latitude: " . $toilet['location']['lat'] . "<br>";
        // $post_content .= "Longitude: " . $toilet['location']['lng'] . "<br>";
        $field_values = array(
            'field_6425adf022d3b' => $toilet['name'],
            'field_6425adfd22d3c' => $toilet['location']['lng'],
            'field_6425ae0722d3d' => $toilet['location']['lat'],
            'field_6425ae0f22d3e' => $toilet['accessible']? 'Yes' : 'No',
            'field_6425ae1722d3f' => $toilet['noPayment'] ? 'Yes' : 'No',
            'field_6425ae1f22d40' => $toilet['updatedAt'],
        );

        $post_id = wp_insert_post(array(
            'post_title' => $post_title,
            'post_content' => $post_content,
            'post_status' => 'publish',
            'post_type' => 'toilet',
        ));
        foreach ($field_values as $key => $value) {
            update_field($key, $value, $post_id);
        }

        if (is_wp_error($post_id)) {
            echo "Error inserting post: " . $post_id->get_error_message() . "<br>";
        } else {
            echo "Inserted post with ID: " . $post_id . "<br>";
        }
    }



}
// Unschedule the event on plugin deactivation
register_deactivation_hook(__FILE__, 'toiletmap_unschedule_update');
function toiletmap_unschedule_update()
{
    wp_clear_scheduled_hook('toiletmap_update_event');
}