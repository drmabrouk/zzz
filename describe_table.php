<?php require_once('wp-load.php'); global $wpdb; $res = $wpdb->get_results('DESCRIBE ' . $wpdb->prefix . 'sm_messages'); print_r($res);
