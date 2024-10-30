<?php

namespace CF7DBTOOL;
class FormEntry extends Plugin
{
	/**
	 * method construct
	 */
	public function __construct()
	{
		parent::__construct();
		add_action('wpcf7_mail_failed', [$this, 'saveFailedValues'], 10, 1);
		add_action('wpcf7_mail_sent', [$this, 'saveSentValues'], 10, 1);
	}

	/**
	 * save values after mail sent
	 * @param object
	 * @return void
	 */
	public function saveSentValues($contact_form)
	{
		$formId = $contact_form->id();
		$values = $this->_getValues($formId);
		if ($values) {
			$this->wpdb->insert($this->entriesTable, array(
				'time' => current_time('mysql'),
				'form_id' => $formId,
				'fields' => serialize($values),
				'status' => 'sent',
			));
			if ($this->wpdb->insert_id) {
				$this->_updateTotalEntry($formId);
			}
		}
	}

	/**
	 * save values after mail failed
	 * @param object
	 * @return void
	 */
	public function saveFailedValues($contact_form)
	{
		$formId = (int)$contact_form->id();
		$values = $this->_getValues($formId);
		if ($values) {
			$this->wpdb->insert($this->entriesTable, array(
				'time' => current_time('mysql'),
				'form_id' => $formId,
				'fields' => serialize($values),
				'status' => 'failed',
			));
			if ($this->wpdb->insert_id) {
				$this->_updateTotalEntry($formId);
			}
		}
	}

	/**
	 * get submitted values
	 * @param int
	 * @return array | bool
	 */
	private function _getValues($formId)
	{
		$submission = \WPCF7_Submission::get_instance();
		$form = $this->wpdb->get_row(
			"SELECT * FROM $this->formTable WHERE form_id = $formId"
		);
		$formValues = array();
		if ($submission) {
			$posted_data = $submission->get_posted_data();
			foreach (unserialize($form->fields) as $key => $value) {
				if (array_key_exists($value, $posted_data)) {
					$formValues[$value] = $posted_data[$value];
				}
			}
			return $formValues;
		}
		return false;
	}

	/**
	 * update total entries
	 * @param int
	 * @return void
	 */
	private function _updateTotalEntry($formId)
	{
		$totalEntries = $this->wpdb->get_var("SELECT COUNT(*) FROM $this->entriesTable WHERE form_id = $formId");
		$this->wpdb->update(
			$this->formTable,
			array(
				'entries' => $totalEntries
			),
			array('form_id' => $formId)
		);
	}
}