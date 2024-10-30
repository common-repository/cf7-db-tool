<?php
namespace CF7DBTOOL;
class FormEntries
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
		add_action('wpcf7_mail_failed', [$this, 'saveFailedValues'], 10, 1);
		add_action('wpcf7_mail_sent', [$this, 'saveSentValues'], 10, 1);
		add_action( 'wp_ajax_cf7_dbt_reply', [$this, 'handleReply']);
		if(isset($_POST)){
			if(isset($_POST['action']) && $_POST['action'] == 'export'){
				$this->_handleCsvDownload();
			}
		}
	}
	/**
	 * save values after mail sent
	 * @param object
	 * @return void
	 */
	public function saveSentValues($contact_form)
	{
		$formId = (int)$contact_form->id();
		$values = $this->_getValues($formId);
		if ($values) {
			$this->config->wpdb->insert($this->config->entriesTable, array(
				'time' => current_time('mysql'),
				'form_id' => $formId,
				'fields' => serialize($values),
				'status' => 'sent',
			));
			if ($this->config->wpdb->insert_id) {
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
			$this->config->wpdb->insert($this->config->entriesTable, array(
				'time' => current_time('mysql'),
				'form_id' => $formId,
				'fields' => serialize($values),
				'status' => 'failed',
			));
			if ($this->config->wpdb->insert_id) {
				$this->_updateTotalEntry($formId);
			}
		}
	}

	/**
	 * list entries
	 * @param int
	 * @uses ListEntries
	 * @return void
	 */
	public function allEntries($formId)
	{
		$form = $this->_getRowFromDb($this->config->formTable,'form_id',$formId);
		$listEntries = new ListEntries($this->config,$form);
		ob_start();
		?>
		<div class="wrap cft-dbt-wrapper">
			<h2> <?php echo __('CF7 DB Tool - Entries in ','cf7-db-tool').'"'.$form->title.'"'; ?></h2>
			<?php $listEntries->prepare_items(); ?>
			<div class="cf7-dbt-search <?php echo isset($_POST['s']) ? 'cf7-dbt-search-active' : ''; ?>">
				<?php echo isset($_POST['s']) ? '<h4 style="margin: 5px 0">'.__("Search result for:",'cf7-db-tool'). trim(sanitize_text_field($_POST['s'])) .'</h4>' : ''; ?>
				<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=cf7_dbt&form_id=' . $formId; ?>">
					<?php $listEntries->search_box('Search Entry', 'cf7-dbt-entry'); ?>
				</form>
				<?php if (isset($_POST['s'])): ?>
					<a href="<?php echo $_SERVER['PHP_SELF'] . '?page=cf7_dbt&form_id=' . $formId ?>"
					   class="button cf7-dbt-search-reset"> <?php _e('Reset', 'cf7-db-tool') ?> </a>
				<?php endif; ?>
			</div>
			<form method="post" id="cf7-dbt-entries-form" action="<?php echo $_SERVER['PHP_SELF'] . '?page=cf7_dbt&form_id=' . $formId; ?>">
				<?php $listEntries->display(); ?>
			</form>

		</div>
		<?php
		ob_get_flush();
	}
	/**
	 * entry details view
	 * @param int
	 * @return void
	 */

	public function renderDetails($entryId)
	{
		$hasEmailField = false;
		$userEmail = '';
		$entryDetails = $this->_getRowFromDb($this->config->entriesTable,'id',$entryId);
		$form = $this->_getRowFromDb($this->config->formTable,'form_id',$entryDetails->form_id);
		ob_start();
		?>
		<div class="wrap">
			<div class="cf7-dbt-container">
				<h2><?php _e('CF7 DB Tool - Entry details ', 'cf7-db-tool'); ?></h2>
				<div class="cf7-dbt-content">
					<div class="cf7-dbt-entry-details">
						<div class="cf7-dbt-entry-header">
							<h3 style="margin: 0"><?php echo __('Form Submitted in: ', 'cf7-db-tool') . $form->title; ?></h3>
							<a href="admin.php?page=cf7_dbt&form_id=<?php echo $entryDetails->form_id ?>" class="button button-primary"><?php _e('Back','cf7-db-tool');?></a>
						</div>
						<table style="margin: 10px 0">
							<?php foreach (unserialize($entryDetails->fields) as $key => $value): ?>
								<?php
									if($this->_checkForEmail($value)){
										$hasEmailField = true;
										$userEmail = $value;
									}
								?>
								<tr>
									<td style="padding: 5px 20px 3px 0"><strong><?php echo ucfirst($key); ?>:</strong></td>
									<td><?php echo $value; ?></td>
								</tr>
							<?php endforeach; ?>
							<tr>
								<td style="padding: 5px 20px 3px 0"><strong><?php _e('Status:', 'cf7-db-tool') ?></strong></td>
								<td>
							<span class="text-danger">
								<?php _e('Mail '.ucfirst($entryDetails->status), 'cf7-db-tool') ?>
							</span>
								</td>
							</tr>
						</table>
						<?php
							if($hasEmailField)
								$this->_renderReplyForm($entryId,$userEmail);
						?>
					</div>
				</div>
				<?php
				// Inlcue rating sidebar
				$sidebar_template = plugin_dir_path(__FILE__) . 'sidebar.php';

				if(file_exists($sidebar_template)){
					include_once $sidebar_template;
				}
				?>
			</div>
		</div>
		<?php
		ob_get_flush();
	}
	/**
	 * ajax callback for send email
	 * @return void
	 */
	public function handleReply()
	{
		$mailArgs = array();
		if(!check_ajax_referer( 'cf7-dbt-reply-nonce', 'security', false)){
			echo 'Nonce not varified';
			wp_die();
		}else{
			if(isset($_POST['formData']) && !empty($_POST['formData'])){
				foreach ( $_POST['formData'] as $data){
					if($data['name'] == 'reply-email'){
						if(!empty($data['value']) && $this->_checkForEmail($data['value'])){
							$mailArgs['replyEmail'] = $data['value'];
						}else{
							echo json_encode(['hasError'=>true,'errorType'=>'emptyEmail','message'=>'Email field is empty']);
							wp_die();
						}
					}
					if($data['name'] == 'reply-subject'){
						if(!empty($data['value'])){
							$mailArgs['subject'] = $data['value'];
						}else{
							$mailArgs['subject'] = 'Reply from '.get_bloginfo('name');
						}
					}
					if($data['name'] == 'reply-msg'){
						if(!empty($data['value'])){
							$mailArgs['message'] = nl2br(stripslashes($data['value']));
						}else{
							echo json_encode(['hasError'=>true,'errorType'=>'emptyMsg','message'=>'Please enter your message']);
							wp_die();
						}
					}

				}
			}
			if(!empty($mailArgs)){
				$mailObject = new Mail($mailArgs);
				$mailSent = $mailObject->sendMail();
				if($mailSent){
					echo json_encode(['mailSent'=>true]);
				}else{
					echo json_encode(['hasError'=>true,'mailSent'=>false]);
				}
				wp_die();
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
		$form = $this->_getRowFromDb($this->config->formTable,'form_id',$formId);
		$formValues = array();
		if ($submission) {
			$posted_data = $submission->get_posted_data();
			foreach (unserialize($form->fields) as $key => $value) {
				if (array_key_exists($value, $posted_data)) {
					$formValues[$value] = stripslashes($posted_data[$value]);
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
		$totalEntries = $this->config->wpdb->get_var("SELECT COUNT(*) FROM ".$this->config->entriesTable." WHERE form_id = $formId");
		$this->config->wpdb->update(
			$this->config->formTable,
			array(
				'entries' => $totalEntries
			),
			array('form_id' => $formId)
		);
	}
	/**
	 * match if there is email field
	 * @param string
	 * @return bool
	 */
	private function _checkForEmail($email){
		$regexp = '/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/';
		if(preg_match($regexp,$email)){
			return true;
		}
		return false;
	}
	/**
	 * render reply form with reply button
	 * @param string
	 * @return void
	 */
	private function _renderReplyForm($entryId,$replyEmail)
	{
		?>
		<a href="#" class="button button-primary cf7-dbt-show-reply-form"><?php _e('Reply by email','cf7-db-tool');?></a>
		<div class="cf7-dbt-reply-form-container">
			<div class="cf7-dbt-reply-status">
				<p class="success"><?php _e('Email sent successfully!','cf7-db-tool');?></p>
				<p class="failed"><?php _e('Email sent failed! Please try again.','cf7-db-tool');?></p>
			</div>
			<form action="<?php echo $_SERVER['PHP_SELF']."?page=cf7_dbt&entry_id=".$entryId;?>" id="cf7-dbt-reply-form">
				<input type="hidden" name="reply-email" value="<?php echo $replyEmail;?>">
				<div class="cf7-dbt-form-field">
					<label for="reply-subject">Subject</label>
					<input type="text" name="reply-subject" value="" placeholder='Default value: "Reply from <?php bloginfo('name');?>"'>
				</div>
				<div class="cf7-dbt-form-field cf7-dbt-msg-field">
					<label for="reply-msg">Your message</label>
					<textarea name="reply-msg" id="" cols="30" rows="10" placeholder="Enter your message, you can use line breaks"></textarea>
					<p class="cf7-dbt-form-error"></p>
				</div>
				<div class="cf7-dbt-form-footer">
					<input type="submit" value="Send reply">
					<input type="button" value="Cancel" class="cf7-dbt-cancel-reply">
				</div>
			</form>
			<div class="cf7-dbt-loader">
				<div class="cf7-dbt-loader-inner">
					<span class="cf7-dbt-loading-icon"></span>
					<p class="cf7-dbt-loading-text"><?php _e('Sending your email','cf7-db-tool') ?></p>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * handle csv download
	 * @return void
	 */
	private function _handleCsvDownload()
	{
		if(isset($_POST['cf7-dbt-bulk-entries']) && is_array($_POST['cf7-dbt-bulk-entries'])){
			$entryIds = $_POST['cf7-dbt-bulk-entries'];
		}else{
			$entryIds = '-1';
		}
		new CsvExport(array(
			'config'=>$this->config,
			'formId'=>$_GET['form_id'],
			'entryIds'=>$entryIds
		));
	}
	/**
	 * get single row from database
	 * @param string
	 * @param string
	 * @param string
	 * @return array
	 */
	private function _getRowFromDb($table,$whereClause,$value)
	{
		return $this->config->wpdb->get_row(
			"SELECT * FROM $table WHERE $whereClause = $value"
		);
	}
}