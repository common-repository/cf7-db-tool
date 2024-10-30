<?php
namespace CF7DBTOOL;
class Mail{
	/**
	 * email address to sent
	 * @var string
	 */
	public $email;
	/**
	 * email subject
	 * @var string
	 */
	public $subject;
	/**
	 * email message
	 * @var string
	 */
	public $message;
	/**
	 * method __construct();
	 */
	public function __construct($args)
	{
		$this->email = $args['replyEmail'];
		$this->subject = $args['subject'];
		$this->message = $args['message'];
	}
	/**
	 * send email
	 * @uses wp_mail()
	 * @return bool
	 */
	public function sendMail()
	{
		$headers = array();
		add_filter( 'wp_mail_content_type', function( $content_type ) {return 'text/html';});
		$headers[] = 'From: '.get_bloginfo('name').'<'.get_option('admin_email').'>'."\r\n";
		$mailSent = wp_mail( $this->email,$this->subject,$this->message, $headers);
		remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
		return $mailSent;
	}
}