<?php
namespace CF7DBTOOL;
/**
 * Config class
 */
class Config{
	/**
	 * wpdb instance
	 * @var string
	 */
	public $wpdb;
	/**
	 * form table in db
	 * @var string
	 */
	public $formTable;
	/**
	 * form entries table in db
	 * @var string
	 */
	public $entriesTable;
	/**
	 * method __construct()
	 */
	public function __construct()
	{
		global $wpdb;
		// set wpdb instance
		$this->wpdb = $wpdb;
		// set form db table name
		$this->formTable = $this->wpdb->prefix . 'cf7_dbt_forms';
		//set entries db table name
		$this->entriesTable = $this->wpdb->prefix . 'cf7_dbt_entries';
	}
}