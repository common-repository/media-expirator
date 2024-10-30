<?php
/*
Plugin Name: Media Expirator
Plugin URI: http://www.peakweb.it/
Description: Add Expiration field to media library items. On Expire images will be deleted.
version: 0.2
Author: Marco - Peakweb
Author URI: http://www.peakweb.it/
Copyright: Marco Alluvion
*/

/* Start Functions */


// Load plugin textdomain

add_action( 'plugins_loaded', 'mediaexpirator_load_textdomain' );

function mediaexpirator_load_textdomain() {
  load_plugin_textdomain( 'peakweb-media-expirator', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' ); 
}

// Add Expiration field to media library items:

function attachment_expiration_field($form_fields, $post) {
    // input type = checkbox
    $values = get_post_meta($post->ID, 'expiry_check', true);
    $values = $values[0];
    if ($values == "1") {
        $values_val = "checked";
    } else {
        $values_val = "";
    }
    $form_fields['expiry_check'] = array(
        'label' => __('Enable Expiration', 'peakweb-media-expirator'),
        'input' => 'html',
        'html'  => '<input type="checkbox" value="1" '.$values_val.' name="attachments['.$post->ID.'][expiry_check]" id="attachments-'.$post->ID.'-expiry_check" />',
        'value' => get_post_meta($post->ID, 'expiry_check', true),
        'helps' => __('Set a date on which the image will be automatically deleted', 'peakweb-media-expirator'), );
    // input type = text
    $form_fields['expiry_date'] = array(
        'label' => __('Expiration Date', 'peakweb-media-expirator'),
        'input' => 'text',
        'value' => get_post_meta($post->ID, 'expiry_date', true),
        'helps' => __('Date format: YYYY-MM-DD', 'peakweb-media-expirator'), );

    return $form_fields;
}

add_filter('attachment_fields_to_edit', 'attachment_expiration_field', 10, 2);

// Save values Expiration field to media library item:

function attachment_expiration_field_save($post, $attachment) {
    if (isset($attachment['expiry_check'])) {
        update_post_meta($post['ID'], 'expiry_check', $attachment['expiry_check']);
    } else {
        update_post_meta($post['ID'], 'expiry_check', '0');
    }
    if (isset($attachment['expiry_date'])) update_post_meta($post['ID'], 'expiry_date', $attachment['expiry_date']);

    return $post;
}

add_filter('attachment_fields_to_save', 'attachment_expiration_field_save', 10, 2);


// Function expired_post_delete hook fires when the Cron is executed

add_action('expired_post_delete', 'delete_expired_posts');

// This function will run once the 'expired_post_delete' is called

function delete_expired_posts() {

    date_default_timezone_set('Europe/Rome');
    $todays_date = date("Y-m-d");

    $args = array(
        'post_status'    => 'any',
        'post_type'      => 'attachment',
        // Enable to set media types to delete - default: All
        // 'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'meta_query'     => array(
    array(
        'key'     => 'expiry_date',
        'value'   => $todays_date,
        'type'    => 'DATE',
        'compare' => '<'),
    array(
        'key' => 'expiry_check',
        'value' => 1)));
    $posts = new WP_Query($args);

    // The Loop
    if ($posts->have_posts()) {

        while ($posts->have_posts()) {
            $posts->the_post();
            // For test purpose - Disable to prevent the deletion of images
            wp_delete_post(get_the_ID());
            // For test purpose - Enable to print if the are images expired
            // echo 'find imange';
        }

    } else {
        // no posts found
    }
    // Restore original Post Data
    wp_reset_postdata();
}

// Add function to register event to WordPress init
add_action('init', 'register_daily_post_delete_event');

// Function which will register the event
function register_daily_post_delete_event() {
    // Make sure this event hasn't been scheduled
    if (!wp_next_scheduled('expired_post_delete')) {
        // Schedule the event
        wp_schedule_event(time(), 'daily', 'expired_post_delete');
    }
}

// For test purpose - Enable to run
//add_action('get_footer', 'delete_expired_posts');

/* Stop Functions */
?>