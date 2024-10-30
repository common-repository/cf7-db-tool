<?php

namespace CF7DBTOOL;
class Form extends Plugin
{
	/**
	 * method construct
	 */
	public function __construct()
	{
		parent::__construct();
		// save forms that created before plugin install
		$this->saveExistingForms();
		// save new created forms
		add_action('wpcf7_after_save', [$this, 'saveForm'], 10, 1);

	}

	/**
	 * save existing forms in database
	 * @return void
	 */
	public function saveExistingForms()
	{
		// get forms
		$args = array(
			'post_type' => 'wpcf7_contact_form',
			'order' => 'ASC',
			'posts_per_page' => -1,
		);

		$the_query = new \WP_Query($args);
		$totalForms = $this->wpdb->get_var("SELECT COUNT(*) FROM $this->formTable");
		// check if already in db
		if ((int)$totalForms == (int)$the_query->post_count) {
			return;
		}
		foreach ($the_query->posts as $form) {
			$existingForm = $this->_getForm($form->ID);
			if (empty($existingForm)) {
				$fields = $this->_getFields($form->post_content);
				// insert form
				$formData = array(
					'time' => $form->post_date,
					'form_id' => $form->ID,
					'title' => $form->post_title,
					'fields' => serialize($fields)

				);
				$this->wpdb->insert($this->formTable, $formData);
			}

		}
		wp_reset_postdata();
	}

	/**
	 * save form in db
	 * @param object
	 * @return void
	 */
	public function saveForm($instance)
	{
		$existingForm = $this->_getForm($instance->id);
		if (!empty($existingForm)) {
			if (unserialize($existingForm->fields) == $this->_getFields($instance->form)) {
				return;
			} else {
				$this->wpdb->update(
					$this->formTable,
					array(
						'fields' => serialize($this->_getFields($instance->form))
					),
					array('id' => $existingForm->id)
				);
			}
			return;
		}
		$formData = array(
			'time' => current_time('mysql'),
			'form_id' => $instance->id,
			'title' => $instance->title,
			'fields' => serialize($this->_getFields($instance->form))

		);
		$this->wpdb->insert($this->formTable, $formData);
	}

	/**
	 * generate form fields
	 * @param string
	 * @return array
	 */
	private function _getFields($fieldsString)
	{
		$fieldsString = substr($fieldsString, 0, strpos($fieldsString, 'submit') - 2);
		preg_match_all('/\[(.+?)\]/', $fieldsString, $parsed_fields);
		$fields = array();
		if (!empty($parsed_fields[1])) {
			foreach ($parsed_fields[1] as $field) {
				if (strpos($field, 'submit') !== false) {
					continue;
				} else {
					$fields[] = explode(' ', $field)[1];
				}
			}
		}
		return $fields;
	}

	/**
	 * get a form
	 * @param string
	 * @return object
	 */
	private function _getForm($formId)
	{
		return $this->wpdb->get_row(
			"SELECT * FROM $this->formTable WHERE form_id = $formId"
		);
	}
}