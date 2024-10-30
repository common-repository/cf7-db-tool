<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Plugin Name: CF7 DB Tool & Bulk Email
 * Plugin URI: https://orangetoolz.com/
 * Description: Save all contact form 7 submitted data to the database, View, Export, See status and butlkmail with maingun & sendgrid.
 * Author: OrangeToolz
 * Text Domain: cf7-db-tool
 * Tags: contact, cf7, integration, bulk mail, contact form 7, db, export, save, wpcf7, contact form 7 db,  contact form 7 database, contact form 7 data export, contact form 7 database addon
 * Version: 4.3.0
 */

define('CF7_DBT_VERSION', '4.3.0');
define('CF7_DBT_DB_VERSION', '4.3.0');
define('CF7_DBT_PATH', __DIR__);
define('CF7_DBT_URL', plugins_url(basename(CF7_DBT_PATH)));
/**
 * init plugin
 */
function cf7_dbt_init()
{
	require_once CF7_DBT_PATH . '/src/Plugin.php';
	$plugin = new CF7DBTOOL\Plugin();
}

add_action('plugins_loaded', 'cf7_dbt_init');

/**
 * perform actions on activation
 */

function cf7_dbt_activation()
{
	if ( ! class_exists('WPCF7_ContactForm') ) {
		set_transient('cf7-dbt-warning',true, 5);
	}
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "cf7_dbt_forms (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			form_id varchar(16) NOT NULL,
			title text NOT NULL,
			entries int(11) NOT NULL,
			fields text NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
	$sql2 = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "cf7_dbt_entries (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			form_id mediumint(9) NOT NULL,
			status varchar(24) NOT NULL,
			fields text NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	dbDelta($sql2);
	// save options in db
	update_option('cf7-dbt-version', CF7_DBT_VERSION);
	update_option('cf7-dbt-db-version', CF7_DBT_DB_VERSION);
}

register_activation_hook(__FILE__, 'cf7_dbt_activation');

/**
 * perform actions on delete
 */
function cf7_dbt_uninstaller()
{
	// delete db tables
	global $wpdb;
	$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "cf7_dbt_forms;");
	$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "cf7_dbt_entries;");
	// delete options
	delete_option('cf7-dbt-version');
	delete_option('cf7-dbt-db-version');
}

register_uninstall_hook(__FILE__, 'cf7_dbt_uninstaller');