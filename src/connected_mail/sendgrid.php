<?php

/**
 * Get SendGrid access from admin section
 */

$sendGridUser     = get_option( 'sendgrid-user' );
$sendGridPassword = get_option( 'sendgrid-password' );

define( 'sendGridUser', $sendGridUser );
define( 'sendGridPassword', $sendGridPassword );


function sendmailBySendGrid( $to, $toname, $mailfromname, $mailfrom, $subject, $html, $attachments ) {

	if($attachments){
		$file_name = basename($attachments);
		$finfo = finfo_open(FILEINFO_MIME);
		$mimeType = finfo_file($finfo, $attachments);
		finfo_close($finfo);
		if ($mimeType) {
			$tempArr = explode(";", $mimeType);
			$mimeType = $tempArr[0];
		}

		$params = array(
			'api_user'  => sendGridUser,
			'api_key'   => sendGridPassword,
			'to'        => $to,
			'subject'   => $subject,
			'html'      => $html,
			'text'      => $html,
			'from'      => $mailfrom,
			'files['.$file_name.']' => curl_file_create($attachments, $mimeType),
		);
	}else{
		$params = array(
			'api_user'  => sendGridUser,
			'api_key'   => sendGridPassword,
			'to'        => $to,
			'subject'   => $subject,
			'html'      => $html,
			'text'      => $html,
			'from'      => $mailfrom,
		);
	}


	$request = 'https://api.sendgrid.com/api/mail.send.json';


	$response = wp_remote_post( $request, array(
			'method'      => 'POST',
			'httpversion' => '1.0',
			'blocking'    => true,
			'body'        => $params,
		)
	);

	return $response;
}


?>