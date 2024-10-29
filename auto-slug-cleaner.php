<?php
/*
Plugin Name: Auto Slug Cleaner
Plugin URI: https://wordpress.org/plugins/auto-slug-cleaner
Description: Automatic clean post slug with define your words
Author: Mostafa Soufi
Version: 1.0
Author URI: http://mostafa-soufi.ir
Text Domain: auto-slug-cleaner
*/

define('WP_AUTO_SLUG_CLEANER_DIR', plugin_dir_url(__FILE__));

load_plugin_textdomain('auto-slug-cleaner', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

class Auto_Slug_Cleaner{	
	/**
	 * Wordpress Database
	 *
	 * @var string
	 */
	protected $db;
	
	/**
	 * Wordpress Table prefix
	 *
	 * @var string
	 */
	protected $tb_prefix;
	
	/**
	 * Constructors plugin
	 *
	 * @param Not param
	 */
	public function __construct() {
		global $wpdb, $table_prefix;
		
		$this->db = $wpdb;
		$this->tb_prefix = $table_prefix;
		
		__('Auto Slug Cleaner', 'auto-slug-cleaner');
		__('Automatic clean post slug with define your words', 'auto-slug-cleaner');
		
		$this->setting();

		add_filter( 'wp_unique_post_slug', array($this, 'modify_slug'), 10, 4 );
	}

	/**
	 * Setting plugin
	 *
	 * @param  Not param
	 */
	public function setting() {
		require_once( 'includes/settings.php' );
		global $asc_settings;
		$asc_settings = asc_get_settings();
	}

	/**
	 * Modify post slug
	 *
	 * @param  $slug (string) Post slug
	 * @param  $post_ID (integer) Post ID
	 * @param  $post_status (string) Post status
	 * @param  $post_type (string) Post type
	 */
	public function modify_slug( $slug, $post_ID, $post_status, $post_type ) {
		global $asc_settings;

		// Check if words have been value
		if(empty($asc_settings['words']))
			return $slug;

		// Skip cleaner if slug entred with manually
		if( isset($_REQUEST['new_slug']) )
			return sanitize_title($_REQUEST['new_slug']);

		// Split string to array with line
		$words_array = preg_split("/\\r\\n|\\r|\\n/", $asc_settings['words']);

		// Decode slug
		$slug = urldecode($slug);

		foreach ($words_array as $word) {

			if( isset($final_slug) )
				$slug = $final_slug;

			// Remove word in first content
			$final_slug = preg_replace("/(^".$word."-)/", "", $slug);

			// Remove word in content
			$final_slug = preg_replace("/(-".$word."-)/", "-", $final_slug);
		}
		
		return sanitize_title($final_slug);
	}

}

// Create object of plugin
$Auto_Slug_Cleaner = new Auto_Slug_Cleaner;