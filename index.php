<?php
/**
 * @package GuardianKey
 * @version 1.0
 */
/*
Plugin Name: GuardianKey
Plugin URI: http://wordpress.org/plugins/guardiankey/
Description: Tests
Author: Gesiel Bernardes and Paulo Angelo
Version: 1.0
Author URI: http://guardiankey.com/
*/


defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

add_action( 'admin_init', 'register_mysettings' );
add_action('admin_menu', 'guardiankey_options_page');
add_action('admin_post_submit-ck', 'create_key');
add_action('init', 'guardiankey_checkUser');

register_activation_hook(__FILE__, 'createJob');


include( plugin_dir_path( __FILE__ ).'functions.php');



?>
