<?php
/*
   Plugin Name: WPJM Analytics
   Version: 1.0.0
   Author: Gabriel Maldonado
   Author URI: https://tilcode.blog
   Description: Track, report, and export Job Listings and Applications data for WP Job Manager.
   Text Domain: wpjm-analytics
   License: GPLv3
*/

/**
 * Prevent direct access data leaks
 **/
if (! defined( 'ABSPATH' )) {
  exit;
}

// Enqueue plugin scripts
function gma_wpjm_analytics_admin_scripts(){
  wp_enqueue_script( 'wpjm-analytics-scripts', plugins_url( 'wpjm-analytics-scripts.js', __FILE__ ), [], time(), true );
  wp_register_style( 'wpjm-analytics-css', plugins_url( 'wpjm-analytics-admin-style.css', __FILE__ ), false, '1.0.0' );
  wp_enqueue_style( 'wpjm-analytics-css' );
}
add_action( 'admin_enqueue_scripts', 'gma_wpjm_analytics_admin_scripts' );


/**
* WP Job Manager class check
*/
if ( ! class_exists( 'WP_Job_Manager' )) {
  return;
}

/**
* WP Job Manager Applications extension class check
*/
global $flag_wpjm_applications_enabled;
if ( ! class_exists( 'WP_Job_Manager_Applications' )) {
  $flag_wpjm_applications_enabled = false;
} else {
    $flag_wpjm_applications_enabled = true;
}

/**
* Adds Job Listings > Analytics submenu
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
add_action('admin_menu',  'gma_wpjm_register_custom_menu_page'  ); // Hooks into WPJM admin menus

/**
* Job Listings > Analytics . Admin panel where all analytics data is displayed
*/
function gma_wpjm_analytics_admin_page(  ){

    global $flag_wpjm_applications_enabled;

    echo '<h1 class="wp-heading-inline">WP Job Manager Analytics</h1>';

    // Get Job Listings
    $args = array(
    'post_type'=> 'job_listing',
    ); 

    $the_query = new WP_Query( $args );
    $titles = get_posts($args);
    $total_jobs = count($titles);

    // Outputs upper data section
    gma_wpjm_analytics_admin_page_general_data_section( $total_jobs );

    // Outputs middle data section
    ?>
      <table class='wp-list-table widefat fixed'>
        <tr>
          <th>Job ID</th>
          <th>Job Title</th>
          <th>Total Visits</th>

          <?php 
            if ($flag_wpjm_applications_enabled){
              echo "<th>Total Applications</th>";
              echo "<th>Applications/Visit (%)</th>";
            } else {
              echo "<th>Total Applications";
              echo "<br>";
              echo "<span class='foo'>This feature needs " . '<a href="https://wpjobmanager.com/add-ons/applications/">WP Job Manager Applications</a></span>';
              echo "</th>";
              echo "<th>Applications/Visit (%)";
              echo "<br>";
              echo "<span class='foo'>This feature needs " . '<a href="https://wpjobmanager.com/add-ons/applications/">WP Job Manager Applications</a></span>';
              echo "</th>";
            }
          ?>

        </tr>
        
        <?php

        // Fills the data section with job and application data
        foreach ($titles as $title) {
      
          $id = $title->ID;
          $total_visits = count(get_post_meta($id,'_view_counter'));

          if (function_exists('get_job_application_count')) {
            $job_application_count_per_job = get_job_application_count( $id );
          }

          echo '<tr>';
          echo "<td>" . $id . "</td>";
          echo "<td>" . $title->post_title . "</td>";
          echo "<td>" . $total_visits . "</td>";

          if ($flag_wpjm_applications_enabled) {

            echo "<td>" . $job_application_count_per_job . "</td>"; // Total Applications
            echo "<td>" . gma_wpjm_get_application_per_visit_percentage( $job_application_count_per_job , $total_visits ) . "</td>"; // Applications/Visit (%)
          
          } else {

            echo "<td>" . "-" . "</td>"; // Total Applications
            echo "<td>" . "-" . "</td>"; // Applications/Visit (%)
          
          }
          echo '</tr>';
        }

        wp_reset_query();
        
        ?>
      </table>

      <!-- Export Data Button -->
        <div>
        <form method="post" id="download_form" action="">
          <input type="submit" name="download_csv" class="button-primary" id="button-export-data"> Export data into CSV</input>
        </form>
        </div>

    <?php

    echo '</div>';

}

/**
* Job Listings > Analytics . Data upper section
*/
function gma_wpjm_analytics_admin_page_general_data_section( $total_jobs ){

  echo '<table class="wp-list-table widefat fixed">';
  echo '<tr>';
  echo '<th>Total Jobs Posted: ' . $total_jobs . '</th>';
  echo '<th>Total Positions Filled: '  . gma_wpjm_analytics_admin_page_get_total_filled_positions() . '</th>'; // 
  echo '<tr>
        </table>
        <br>';

}

/**
* Exports data to CSV
*/
function gma_wpjm_export_analytics_data_to_csv(){
  
  global $flag_wpjm_applications_enabled;

  $filename = 'wpjm-analytics-export-' . time() . '.csv';

  // Initial array declaration:
  $data = array(
        array( 'item' => 'Job ID', 'Job Title', 'Total Visits', 'Total Applications', 'Applications/Visit(%)' )
  );
  
  // Get Job Listings
  $args = array(
    'post_type'=> 'job_listing',
  ); 

  $the_query = new WP_Query( $args );
  $titles = get_posts($args);

  // Fill the array with Job Listing data, this will go into the final CSV      
  foreach ($titles as $title) {
      
    $id = $title->ID;
    $total_visits = count(get_post_meta($id,'_view_counter'));
          
    $item_1 = $id;
    $item_2 = $title->post_title;
    $item_3 = $total_visits;
    if ($flag_wpjm_applications_enabled) {
      $job_application_count_per_job = get_job_application_count( $id );
      $item_4 = $job_application_count_per_job;
      $item_5 = gma_wpjm_get_application_per_visit_percentage($job_application_count_per_job , $total_visits);
    } else {
      $item_4 = '-';
      $item_5 = '-';
    }

    array_push($data, array( 'item' => $item_1, $item_2, $item_3, $item_4, $item_5 ) );
  }

  wp_reset_query();

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
* Tracks total number of visits per job application
*/  
function gma_wpjm_visit_job_page_counter(){

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
add_action('job_application_start', 'gma_wpjm_visit_job_page_counter');

/**
* Returns % applications / job visit for each job
*/  
function gma_wpjm_get_application_per_visit_percentage( $job_application_count_per_job = 0, $total_visits = 0){

  $percentage;

  if ($total_visits == 0 || $job_application_count_per_job == 0) {
    $percentage = '0%';
  } else {
    $percentage = round($job_application_count_per_job / ($total_visits / 100),2) . '%';
  }
  return $percentage;

}
add_action("admin_init", "gma_wpjm_get_application_per_visit_percentage");

/**
* Returns the number of global filled positions
*/ 
function gma_wpjm_analytics_admin_page_get_total_filled_positions(){

  // Get Job Listings that have been filled only
  $args = array(
    'post_type'=> 'job_listing',
      'meta_query' => array(
        array(
          'key' => '_filled',
          'value' => 1,
        )
      ),
  ); 

  $the_query = new WP_Query( $args );
  $titles = get_posts($args);

  return count($titles);
}
add_action("admin_init", "gma_wpjm_analytics_admin_page_get_total_filled_positions");