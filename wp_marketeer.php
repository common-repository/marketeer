<?php

/*
   Plugin Name: Marketeer WordPress Plugin
   Plugin URI: https://wordpress.org/plugins/marketeer/
   Description: Marketeer WordPress plugin verify API key and paste code before body. You need an account at Marketeer.co
   Version: 1.1
   Author: Marketeerlab
   Author URI: http://www.marketeer.co
   License: 
   */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'MARKETEER_VERSION', '1.1' );
define( 'MARKETEER__MINIMUM_WP_VERSION', '1.1' );
define( 'MARKETEER__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MARKETEER_DELETE_LIMIT', 100000 );

register_activation_hook( __FILE__, array( 'Marketeer', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Marketeer', 'plugin_edactivation' ) );

require_once (MARKETEER__PLUGIN_DIR.'includes/marketeer_core.php');