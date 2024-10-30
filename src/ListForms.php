<?php
namespace CF7DBTOOL;
if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class ListForms extends \WP_List_Table
{

	/**
	 * hold config values
	 * @var object
	 */
	private $config;

	/**
	 * method __construct()
	 */
	public function __construct($config)
	{
		parent::__construct();
		$this->config = $config;
	}

	/**
	 * prepare items for table
	 */

	public function prepare_items()
	{
		// get ordering clause from query string
		$orderby = isset($_GET['orderby']) ? trim(sanitize_sql_orderby($_GET['orderby'])) : 'time';
		$order = isset($_GET['order']) ? trim(sanitize_sql_orderby($_GET['order'])) : 'desc';
		// set column headers
		$this->_column_headers = array($this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns());
		// get data for table
		$this->items = $this->_get_data($orderby, $order);
	}

	/**
	 * set columns for datatable
	 * @return array
	 */
	public function get_columns()
	{
		return array(
			'id' => 'ID',
			'title' => 'Title',
			'entries' => 'Entries',
			'time' => 'Created'
		);
	}

	/**
	 * set default column values
	 * @param array
	 * @param string
	 * @return mixed
	 */
	public function column_default($item, $column_name)
	{
		switch ($column_name) {
			case 'id':
			case 'title':
			case 'entries':
			case 'time':
				return $item[$column_name];
			default:
				return 'No values';
		}
	}

	/**
	 * set hidden columns
	 * @return array
	 */
	public function get_hidden_columns()
	{
		return array('id');
	}

	/**
	 * set sortable columns
	 * @return array
	 */
	public function get_sortable_columns()
	{
		return array(
			'title' => array('title', false),
			'entries' => array('entries', false),
			'time' => array('time', false)
		);
	}

	/**
	 * get data for table
	 * @return array
	 */
	private function _get_data($orderby = 'time', $order = 'desc')
	{
		$data = array();
		// get forms
		$forms = $this->config->wpdb->get_results(
			"SELECT * FROM ".$this->config->formTable." ORDER BY $orderby $order"
		);
		// format data for table
		foreach ($forms as $form) {
			$data[] = array(
				'id' => $form->form_id,
				'title' => '<a class="row-title" href="admin.php?page=cf7_dbt&form_id=' . $form->form_id . '">' . $form->title . '</a>',
				'entries' => '<a class="row-title" href="admin.php?page=cf7_dbt&form_id=' . $form->form_id . '">' . $form->entries . '</a>',
				'time' => '<a class="row-title" href="admin.php?page=cf7_dbt&form_id=' . $form->form_id . '">' . human_time_diff(strtotime($form->time), current_time('timestamp')) . ' ' . __('ago') . '</a>'
			);
		}
		return $data;
	}
}