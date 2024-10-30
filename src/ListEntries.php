<?php
namespace CF7DBTOOL;
if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class ListEntries extends \WP_List_Table
{
	/**
	 * table name for get data
	 * @var string
	 */
	private $config;

	/**
	 * form id to retrieve form details
	 * @var string
	 */
	private $formId;

	/**
	 * available form fields
	 * @var array
	 */
	private $formFields;

	private $form;

	/**
	 * method __construct()
	 */
	public function __construct($config,$form)
	{
		parent::__construct(array(
			'ajax' => false
		));
		$this->form = $form;
		$this->config = $config;
		$this->formId = $form->form_id;
		$this->formFields = unserialize($form->fields);
	}

	/**
	 * prepare items for table
	 * @return void
	 */

	public function prepare_items()
	{
		// get ordering clause from query string
		$orderby = isset($_GET['orderby']) ? trim(sanitize_sql_orderby($_GET['orderby'])) : 'time';
		$order = isset($_GET['order']) ? trim(sanitize_sql_orderby($_GET['order'])) : 'desc';
		// get search term from query string
		$searchTerm = isset($_POST['s']) ? trim(sanitize_text_field($_POST['s'])) : '';
		// set data per page
		$per_page = 20;
		// get current page
		$current_page = $this->get_pagenum();
		// set pagination arguments for table
		if (empty($searchTerm)) {
			$total_items = $this->config->wpdb->get_var("SELECT COUNT(*) FROM ".$this->config->entriesTable." WHERE form_id = $this->formId");
			$this->set_pagination_args(array(
				'total_items' => $total_items,
				'per_page' => $per_page
			));
		}
		// set column header
		$this->_column_headers = array($this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns());
		// process bulk action
		$this->process_bulk_action();
		// get data for table
		$this->items = $this->_get_data($orderby, $order, $searchTerm, $current_page, $per_page);
	}

// get form list from database
    public function get_prepare_forms_list()
    {
        global $wpdb;
        $result = $wpdb->get_results("SELECT * FROM " .$wpdb->prefix. "cf7_dbt_forms");
        return $result;
    }

// get mail list from database
    public function get_prepare_mail_items()
    {
        global $wpdb;
        $result = $wpdb->get_results("SELECT fields FROM " .$wpdb->prefix. "cf7_dbt_entries");
        return $result;
    }

    // get mail list from specific form
    public function get_prepare_mail_items_from_target_form()
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT fields FROM " .$wpdb->prefix. "cf7_dbt_entries where form_id=6"  );
        return $results;
    }



	/**
	 * set columns for datatable
	 * @return array
	 */
	public function get_columns()
	{
		$columns['cb'] = '<input type="checkbox" />';
		$columns['id'] = 'ID';
		foreach ($this->formFields as $field) {
			$columns[$field] = ucfirst($field);
		}
		$columns['status'] = __('Status', 'cf7-db-tool');
		$columns['time'] = __('Submit time', 'cf7-db-tool');
		return $columns;
	}

	/**
	 * set default column values
	 * @param array
	 * @param string
	 * @return string
	 */
	public function column_default($item, $column_name)
	{
		return !empty($item[$column_name]) ? '<a href="admin.php?page=cf7_dbt&entry_id=' . $item['id'] . '">' . $item[$column_name] . '</a>' : __('No data', 'cf7-db-tool');
	}

	/**
	 * set hidden columns
	 * @return array
	 */
	public function get_hidden_columns()
	{
		if (count($this->formFields) > 5) {
			$hidden = array_slice($this->formFields, 5);
			array_push($hidden, 'id');
		} else {
			$hidden = array('id');
		}
		return $hidden;

	}

	/**
	 * set sortable columns
	 * @return array
	 */
	public function get_sortable_columns()
	{
		return array(
			'status' => array('status', false),
			'time' => array('time', false)
		);
	}

	/**
	 * set bulk actions
	 * @return array
	 */
	public function get_bulk_actions()
	{
		return array(
			'export' => __('Export to CSV', 'cf7-db-tool'),
			'delete' => __('Delete', 'cf7-db-tool')
		);
	}

	/**
	 * set checkbox in columns for bulk actions
	 * @return array
	 */
	public function column_cb($item)
	{
		return sprintf('<input type="checkbox" name="cf7-dbt-bulk-entries[]" value="%s"/>', $item['id']);
	}

	/**
	 * process bulk actions
	 * @return bool
	 */
	public function process_bulk_action()
	{
		$action = $this->current_action();
		$bulkEntries = isset($_POST['cf7-dbt-bulk-entries']) ? $_POST['cf7-dbt-bulk-entries'] : '';
		if (!empty($bulkEntries)) {
			$entryIds = '';
			foreach ($bulkEntries as $entry) {
				$entryIds .= $entry . ',';
			}
			$entryIds = rtrim($entryIds, ',');
			if ($action == 'delete') {
				// perform delete action
				$deleted = $this->config->wpdb->query("DELETE FROM ".$this->config->entriesTable." WHERE id IN (" . $entryIds . ")");
				$this->_updateTotalEntry();
				// on success deletion redirect
				if (!empty($deleted)) {
					add_action('wp_loaded', [$this,'_safeRedirect']);
				}
			}
		}
		return true;
	}
	/**
	 * update total entries
	 * @return void
	 */
	private function _updateTotalEntry()
	{
		$totalEntries = $this->config->wpdb->get_var("SELECT COUNT(*) FROM ".$this->config->entriesTable." WHERE form_id = $this->formId");
		$this->config->wpdb->update(
			$this->config->formTable,
			array(
				'entries' => $totalEntries
			),
			array('form_id' => $this->formId)
		);
	}
	/**
	 * get data for table
	 * @return array
	 */
	private function _get_data($orderby = 'time', $order = 'dasc', $searchTerm = '', $current_page = 1, $per_page = 15)
	{
		$data = array();
		// set limit and offset for pagination
		if ($current_page == 1) {
			$limit = 'LIMIT ' . $per_page;
		} else {
			$limit = 'LIMIT ' . $per_page . ' OFFSET ' . ($current_page - 1) * $per_page;
		}
		if (!empty($searchTerm)) {
			// query to get entries matching search term
			$entries = $this->config->wpdb->get_results(
				"SELECT * FROM ".$this->config->entriesTable." WHERE form_id = $this->formId AND fields LIKE '%" . $searchTerm . "%' ORDER BY $orderby $order"
			);
			// set pagination args
			$this->set_pagination_args(array(
				'total_items' => count($entries),
				'per_page' => count($entries)
			));
		} else {
			// query to get entries
			$entries = $this->config->wpdb->get_results(
				"SELECT * FROM ".$this->config->entriesTable." WHERE form_id = $this->formId ORDER BY $orderby $order $limit"
			);
		}
		// format data for table
		foreach ($entries as $entry) {
			$fields['id'] = $entry->id;
			if ($entry->status == 'failed') {
				$fields['status'] = '<span class="cf7-dbt-failed">' . __('Mail send failed', 'cf7-db-tool') . '</span>';
			} else {
				$fields['status'] = '<span class="cf7-dbt-success">' . __('Mail send success', 'cf7-db-tool') . '</span>';
			}
			$fields['time'] = human_time_diff(strtotime($entry->time), current_time('timestamp')) . ' ' . __('ago');
			$data[] = array_merge($fields, unserialize($entry->fields));
		}
		return $data;
	}
	/**
	 * safe redirect for avoid warning
	 * @return void
	 */
	private function _safeRedirect()
	{
		$redirectUrl = 'admin.php?page=cf7_dbt&form_id=' . $this->formId;
		wp_redirect($redirectUrl);
	}
}