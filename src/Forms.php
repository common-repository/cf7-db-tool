<?php
namespace CF7DBTOOL;
class Forms
{
	/**
	 * hold config values
	 * @var object
	 */
	private $config;
	/**
	 * method construct
	 */
	public function __construct($config)
	{
		$this->config = $config;
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
		$totalForms = $this->config->wpdb->get_var("SELECT COUNT(*) FROM ".$this->config->formTable);
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
				$this->config->wpdb->insert($this->config->formTable, $formData);
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
				$this->config->wpdb->update(
					$this->config->formTable,
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
		$this->config->wpdb->insert($this->config->formTable, $formData);
	}

	/**
	 * list forms with WP_List_Table
	 * @return void
	 */
	public function allForms()
	{
		$listForms = new ListForms($this->config);
		$listForms->prepare_items();
		?>
			<div class="wrap">
				<div class="cf7-dbt-container">
					<h2>CF7 DB Tools - Contact Forms</h2>
					<div class="cf7-dbt-content">
						<?php $listForms->display();?>
					</div>
                    <?php
                    // Include Rating us sidebar
                    $sidebar_template = plugin_dir_path(__FILE__) . 'sidebar.php';

                    if(file_exists($sidebar_template)){
	                    include_once $sidebar_template;
                    }

                    ?>

				</div>
			</div>
		<?php
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
		return $this->config->wpdb->get_row(
			"SELECT * FROM ".$this->config->formTable." WHERE form_id = $formId"
		);
	}
}