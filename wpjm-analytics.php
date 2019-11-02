<?php
/*
   Plugin Name: WPJM Analytics
   Version: 0.0.1
   Author: Gabriel Maldonado
   Author URI: https://tilcode.blog
   Description: WPJM Analytics
   Text Domain: wpjm-analytics
   License: GPLv3
*/

// TODO: add gma_ to methods before submitting to REPO

// // Jobs
// part-time vs full-time
// Job types vs total
// WIP: applications
// add analytics tab to admin
// views vs applications


/**
 * Prevent direct access data leaks
 **/
if (! defined( 'ABSPATH' )) {
  exit;
}

// Enqueue plugin scripts
function gma_wpjm_analytics_admin_scripts(){
  wp_enqueue_script( 'wpjm-analytics-scripts', plugins_url( 'wpjm-analytics-scripts.js', __FILE__ ), [], time(), true );
}
add_action( 'admin_enqueue_scripts', 'gma_wpjm_analytics_admin_scripts' );

/**
* TODO: WP Job Manager CLASS CHECK
*/

/**
* ADDS SUBMENU OPTION
*/
function gma_wpjm_register_custom_menu_page(  ) {
    
    add_submenu_page( 
      'edit.php?post_type=job_listing', 
      __( 'Analytics', 'wpjm-analytics' ), 
      __( 'Analytics', 'wpjm-analytics' ), 
      'manage_options', 
      'wpjm-analytics',
      'gma_wpjm_analytics_admin_page'
    );

}
add_action('admin_menu',  'gma_wpjm_register_custom_menu_page'  ); // Hook into WPJM admin menus

/**
* TODO: BACKEND WITH SIMPLE HTML LIST: NAME OF JOB + TOTAL VISITS
*/
function gma_wpjm_analytics_admin_page(  ){

    // echo 'HELLO,THIS IS SECTION 1: JOB ANALYTICS';
    // echo '<div class="wrap">';
    // echo '<p>Oh hei! Welcome to WPJM Analytics!</p>';
    echo '<h1 class="wp-heading-inline">WP Job Manager Analytics</h1>';
    // Get Job Listings
    $args = array(
    'post_type'=> 'job_listing',
    ); 

    $the_query = new WP_Query( $args );
    $titles = get_posts($args);

    ?>
      <table class='wp-list-table widefat fixed'>
        <tr>
          <th>Job ID</th>
          <th>Job Title</th>
          <th>Total Applications</th>
        </tr>
        

        <?php

        foreach ($titles as $title) {
      
          $id = $title->ID;
          $total_applications = count(get_post_meta($id,'_view_counter'));

          echo '<tr>';
          echo "<td>" . $id . "</td>";
          echo "<td>" . $title->post_title . "</td>";
          echo "<td>" . $total_applications . "</td>";
          echo '</tr>';
        }
        
        ?>
      </table>

      <!-- Export Data Button -->
      <!--<p class="submit">-->
        <form method="post" id="download_form" action="">
          <input type="submit" name="download_csv" class="button-primary" id="button-export-data"> Export data into CSV</input>
        </form>
      <!--</p>-->

    <?php

    echo '</div>';

    //echo '<div class="wrap">';
    //echo '<p>Status</p>';
    //gma_wpjm_analytics_admin_page_status_section();
    //echo '</div>';

}

function gma_wpjm_analytics_admin_page_status_section(){

  echo 'HELLO,THIS IS SECTION 2: STATUS' . '<br>';
  echo 'WP Job Manager version: <span>&#9989;</span>' . '<br>';
  echo 'recaptcha: Active <span>&#9989;</span>' . '<br>';

}

/**
* TODO: ADD TOTAL CLICK PER APPLICATION PER JOB (AJAX ON application_button button)
*/

/**
* TODO: BACKEND WITH SIMPLE HTML LIST:  APPLICATION_CLICKS / TOTAL VISITS %
*/

/**
* TODO: ADD LINK TO FORUMS FOR IDEAS SO FOLKS CAN CONTRIBUTE TO NEXT ENHANCEMENTS
*/

/**
* TODO: ADD % OF JOB TYPES / TOTAL
*/

// TODO: Delete Data On Uninstall

// TODO: JOB SUBMISSIONS VS JOB APPLICATIONS DATA

// TODO: % FILLED POSITIONS

// TODO: % EXPIRED LISTINGS / TOTAL

// TODO: EXPORT DATA TO .CSV

function gma_wpjm_export_analytics_data_to_csv(){
  
  $filename = 'wpjm-analytics-export-' . time() . '.csv';

  // Initial array declaration:
  $data = array(
        array( 'item' => 'Job ID', 'Job Title', 'Total Applications' )
  );
  
  // Get Job Listings
  $args = array(
    'post_type'=> 'job_listing',
  ); 

  $the_query = new WP_Query( $args );
  $titles = get_posts($args);

  foreach ($titles as $title) {
      
    $id = $title->ID;
    $total_applications = count(get_post_meta($id,'_view_counter'));
          
    $item_1 = $id;
    $item_2 = $title->post_title;
    $item_3 = $total_applications;
    // Fill the array with Job Listing data, this will go into the final CSV      
    array_push($data, array( 'item' => $item_1, $item_2, $item_3 ) );
  }

  // POST request when downloading the CSV
  if (isset($_POST['download_csv'])) {
    
    ob_clean(); // Clean (erase) the output buffer
    
    header( 'Pragma: public' );
    header( 'Expires: 0' );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Cache-Control: private', false );
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment;filename=' . $filename );
    
    $fp = fopen( 'php://output', 'w' );
    
    foreach ($data as $values) {
      fputcsv( $fp, $values );  
    }
    fclose($fp);
    
    ob_flush(); // Flush (send) the output buffer
    
    exit;
    
  }

}
add_action("admin_init", "gma_wpjm_export_analytics_data_to_csv");


/**
* TRACKS TOTAL VISITS PER JOB APPLICATION
*/  
function gma_wpjm_apply_for_job_counter(){

  $id = get_the_ID();
  

  if (!get_post_meta('_view_counter')) {
    add_post_meta( $id, '_view_counter', 0);
  } else {
    $var = get_post_meta($id, '_view_counter', true) + 1;
    update_post_meta($id, '_view_counter', $var);
  }
  
  echo 'the POST ID is ' . $id . '<br>';
  $total_views = get_post_meta($id,'_view_counter');

  echo 'total views = ';
  var_dump(count($total_views));

}
add_action('job_application_start', 'gma_wpjm_apply_for_job_counter');