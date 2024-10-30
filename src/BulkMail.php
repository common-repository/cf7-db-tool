<?php
/**
 *
 *Bulk email admin page template
 */
namespace CF7DBTOOL;

class BulkMail extends  ListEntries{

	public function __construct(){

		$this->cf7DBBulkMail();
		add_action('wp_ajax_nopriv_bulkMailAjaxDataAction', array( $this, 'bulkMailAjaxDataAction' ));
		add_action('wp_ajax_bulkMailAjaxDataAction', array( $this, 'bulkMailAjaxDataAction' ));

	}

	/**
	 *
	 */
	public function cf7DBBulkMail() {
		// get site info to construct 'FROM' for email
		$from_name = wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );
		$from_email = get_bloginfo('admin_email');

		// initialize
		$send_mail_message = false;

		//wp_nonce_field( 'dbtool_send_email', 'dbtool-form-nonce' );

		if ( !empty( $_POST ) && check_admin_referer( 'dbtool_send_email', 'dbtool-form-nonce' ) ) {


			// handle attachment
			$attachment_path = '';
			if ( $_FILES ) {
				if ( !function_exists( 'wp_handle_upload' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}
				$uploaded_file = $_FILES['attachment'];
				$upload_overrides = array( 'test_form' => false );
				$attachment = wp_handle_upload( $uploaded_file, $upload_overrides );
				if ( $attachment && !isset( $attachment['error'] ) ) {
					// file was successfully uploaded
					$attachment_path = $attachment['file'];
				} else {
					// echo $attachment['error'];
				}
			}


			// get the posted form values
			$dbtool_recipient_emails = isset( $_POST['dbtool_recipient_emails'] ) ? trim($_POST['dbtool_recipient_emails']) : ''; // Get email list
			$dbtool_subject = isset( $_POST['dbtool_subject'] ) ? stripslashes(trim($_POST['dbtool_subject'])) : '';
			$dbtool_body = isset( $_POST['dbtool_body'] ) ? stripslashes(nl2br($_POST['dbtool_body']))  : '';
			//$dbtool_group_email = isset( $_POST['dbtool_group_email'] ) ? trim($_POST['dbtool_group_email']) : 'no';
			$recipients = explode( ',',$dbtool_recipient_emails ); // Email list to array

            //var_dump($dbtool_recipient_emails);
            //var_dump($recipients);




			// initialize some vars
			$errors = array();
			$valid_email = true;

			// simple form validation
			if ( empty( $dbtool_recipient_emails ) ) {
				$errors[] = __( "Please enter an email recipient in the To: field.", 'dbtool' );
			} else {
				// Loop through each email and validate it
				foreach( $recipients as $recipient ) {
					if ( !filter_var( trim($recipient), FILTER_VALIDATE_EMAIL ) ) {
						$valid_email = false;
						break;
					}
				}
				// create appropriate error msg
				if ( !$valid_email ) {
					$errors[] = _n( "The To: email address appears to be invalid.", "One of the To: email addresses appears to be invalid.", count($recipients), 'cf7-db-tool' );
				}
			}
			if ( empty($dbtool_subject) ) $errors[] = __( "Please enter a Subject.", 'cf7-db-tool' );
			if ( empty($dbtool_body) ) $errors[] = __( "Please enter a Message.", 'cf7-db-tool' );

			// send the email if no errors were found
			if ( empty($errors) ) {
				$headers[] = "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";
				$headers[] = 'From: ' . $from_name . ' <' . $from_email . ">\r\n";
				$attachments = $attachment_path;


				$active_mailer = get_option('active_mailer');
				$mailProvider = get_option( 'select_mailer' );

				if($active_mailer ){

					if('Mailgun'===$mailProvider){
						require_once 'connected_mail/mailgun.php';

							foreach( $recipients as $recipient ) {
								$results =  sendmailbymailgun($recipient,'',$from_name,$from_email,$dbtool_subject,$dbtool_body, $attachments);

								$result = json_decode($results['body'],true);

								if(in_array('id',array_keys($result))){
									$send_mail_message .= '<div class="updated">' . __( 'Your email has been successfully sent to ', 'cf7-db-tool' ) . esc_html($recipient) . '!</div>';
								} else{
									$send_mail_message .= '<div class="error">' . __( 'There was an error sending the email to ', 'cf7-db-tool' ) . esc_html($recipient) . '</div>';
								}
							}

                    }
					elseif('SendGrid'===$mailProvider){
						require_once 'connected_mail/sendgrid.php';

						foreach( $recipients as $recipient ) {

							$results =  sendmailBySendGrid($recipient,'',$from_name,$from_email,$dbtool_subject,$dbtool_body, $attachments);

							$result = json_decode($results['body'], true);

							if(in_array('errors',array_keys(json_decode($results, true)))){
								$send_mail_message = '<div class="error">' . __(  'Please check your credentials', 'cf7-db-tool' ) . '</div>';
							}
                            elseif('success'==$result['message']){
	                            $send_mail_message .= '<div class="updated">' . __( 'Your email has been successfully sent to ', 'cf7-db-tool' ) . esc_html($recipient) . '!</div>';
							}
						}

                    }
				}

				else{
						foreach( $recipients as $recipient ) {
							if ( wp_mail( $recipient, $dbtool_subject, $dbtool_body, $headers, $attachments ) ) {
								$send_mail_message .= '<div class="updated">' . __( 'Your email has been successfully sent to ', 'cf7-db-tool' ) . esc_html($recipient) . '!</div>';
							} else {
								$send_mail_message .= '<div class="error">' . __( 'There was an error sending the email to ', 'cf7-db-tool' ) . esc_html($recipient) . '</div>';
							}
						}
				}
				// delete the uploaded file (attachment) from the server
				if ( $attachment_path ) {
					unlink($attachment_path);
				}
			}
		}
		?>


        <div class="wrap">

            <div class="cf7-dbt-container bulkmail">

                <div class="cf7-dbt-content">
                    <div class="wrap" id="cf7-db-bulkmail-wrapper">
                        <h2><?php _e( 'Send Email From Admin', 'cf7-db-tool' ); ?></h2>
						<?php

						if ( !empty($errors) ) {
							echo '<div class="error"><ul>';
							foreach ($errors as $error) {
								echo "<li>$error</li>";
							}
							echo "</ul></div>\n";
						}
						if ( $send_mail_message ) {
							echo $send_mail_message;
						}
						?>
                        <div>
                            <div id="post-body" class="metabox-holder columns-2">
                                <div id="post-body-content">
                                    <form method="POST" id="cf7-db-bulkmail" enctype="multipart/form-data">
										<?php wp_nonce_field( 'dbtool_send_email', 'dbtool-form-nonce' ); ?>
                                        <table cellpadding="0" border="0" class="form-table">
                                            <tr>
                                                <th scope=”row”>From:</th>
                                                <td><input type="text"  value="<?php echo "$from_name &lt;$from_email&gt;"; ?>" required>
                                                    <div class="note"><?php _e( 'These can be changed in Settings->General.', 'cf7-db-tool' ); ?></div>
                                                    <div class="note"><?php _e( 'Setting for another Mailer?  CF7 DB Tool->Mail settings.', 'cf7-db-tool' ); ?></div>

                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope=”row”><label for="dbtool-recipient-emails">To:</label></th>

                                                <td>
                                                    <input type="email" multiple id="dbtool-recipient-emails" name="dbtool_recipient_emails" value="<?php echo esc_attr($this->cf7PluginInstector($dbtool_recipient_emails) ); ?>" required>
                                                    <div class="note">
														<?php _e( 'To send to multiple recipients, enter each email address separated by a comma or choose from the user list below.', 'cf7-db-tool' ); ?>
                                                    </div>
                                                    <div class="select_user_wrapper">
                                                        <select  multiple="multiple" id="dbtool-user-list" name="dbtool-user-list[]">
															<?php


															$allMails = $this->get_prepare_mail_items();
															$usersEmail=[];

															foreach ($allMails as $mail){
																$fields = unserialize($mail->fields);

																if(!in_array($fields["your-email"],$usersEmail)){
																	array_push($usersEmail,$fields["your-email"]);
																}

															}

															foreach ( $usersEmail as $email ) {

																echo '<option value="' . esc_html( $email ) . '">' . esc_html( $email ) . '</option>';
															};
															?>
                                                        </select>
                                                        <div class="select-all">
                                                            <input  type="button" id="select_all" name="select_all" value="Select All user">
                                                        </div>


                                                        <div class="select-session">
                                                            <select   id="select-session_list">
                                                                <option value="">-- <?php _e( 'Select Form / CSV', 'cf7-db-tool' ); ?> --</option>
                                                                <optgroup label="Select CF7 Form">

                                                                </optgroup>
																<?php

																$allForms = $this->get_prepare_forms_list();
																foreach ($allForms as $form){
																	echo ' <option value="'.$form->form_id.'">'.$form->title.'</option>';
																}
																?>

                                                                <optgroup label="Upload By CSV">
                                                                    <option value="csv"> <?php _e( 'Select CSV', 'cf7-db-tool' ); ?> </option>
                                                                </optgroup>

                                                            </select>

                                                            <input type="file" name="csv" class="uploadByCsv" id="uploadCSV">


                                                        </div>

                                                        <div class="reset-entery-data">
                                                            <button type="reset" id="ResetData">Reset Data</button>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
<!--                                            <tr>-->
<!--                                                <th scope=”row”></th>-->
<!--                                                <td>-->
<!--                                                    <div class="dbtool-radio-wrap">-->
<!--                                                        <input type="radio" class="radio" name="dbtool_group_email" value="no" id="no"--><?php //if ( isset($dbtool_group_email) && $dbtool_group_email === 'no' ) echo ' checked'; ?><!-- required>-->
<!--                                                        <label for="no">--><?php //_e( 'Send each recipient an individual email', 'dbtool-toolkit' ); ?><!--</label>-->
<!--                                                    </div>-->
<!--                                                    <div class="dbtool-radio-wrap">-->
<!--                                                        <input type="radio" class="radio" name="dbtool_group_email" value="yes" id="yes"--><?php //if ( isset($dbtool_group_email) && $dbtool_group_email === 'yes' ) echo ' checked'; ?><!-- required>-->
<!--                                                        <label for="yes">--><?php //_e( 'Send a group email to all recipients', 'dbtool-toolkit' ); ?><!--</label>-->
<!--                                                    </div>-->
<!--                                                </td>-->
<!--                                            </tr>-->
                                            <tr>
                                                <th scope=”row”><label for="dbtool-subject">Subject:</label></th>
                                                <td><input type="text" id="dbtool-subject" name="dbtool_subject" value="<?php echo esc_attr(  $this->cf7PluginInstector($dbtool_subject) );?>" required></td>
                                            </tr>
                                            <tr>
                                                <th scope=”row”><label for="dbtool_body">Message:</label></th>
                                                <td align="left">
													<?php
													$settings = array( "editor_height" => "200" );
													wp_editor( $this->cf7PluginInstector($dbtool_body), "dbtool_body", $settings );
													?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope=”row”><label for="attachment">Attachment:</label></th>
                                                <td><input type="file" id="attachment" name="attachment"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" align="right">
                                                    <input  type="submit" value="<?php _e( 'Send Email', 'cf7-db-tool' ); ?>" name="submit" class="button button-primary">
                                                </td>
                                            </tr>
                                        </table>
                                    </form>
                                    <div id="dvCSV">

                                    </div>
                                </div>

                                <div class="clear"></div>
                            </div>
                        </div>
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
	}

	/**
	 * Helper function for form values
	 *
	 * @since 0.9
	 *
	 * @param string $var Var name to test isset
	 *
	 * @return string $var value if isset or ''
	 */
	public function cf7PluginInstector(&$var) {
		return isset($var) ? $var : '';
	}


}
