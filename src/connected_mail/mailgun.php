<?php

$mailgunUrl = get_option('mailgun-url');
$api_key = get_option('mailgun-key');
define('MAILGUN_URL', $mailgunUrl);
define('MAILGUN_KEY', $api_key );

function sendmailbymailgun($to,$toname,$mailfromname,$mailfrom,$subject,$html, $attachments){


	if($attachments){
		// Get File info
		$finfo = finfo_open(FILEINFO_MIME);
		$mimeType = finfo_file($finfo, $attachments);
		finfo_close($finfo);
		if ($mimeType) {
			$tempArr = explode(";", $mimeType);
			$mimeType = $tempArr[0];
		}
		$array_data = array(
			'from'=> $mailfrom,
			'to'=>$to,
			'subject'=>$subject,
			'html'=>$html,
			'text'=>$html,
			'attachment[0]' => curl_file_create($attachments, $mimeType),
			'o:tracking'=>'yes',
			'o:tracking-clicks'=>'yes',
			'o:tracking-opens'=>'yes',
//		'o:tag'=>$tag,
//		'h:Reply-To'=>$replyto
		);
	}else{
		$array_data = array(
			'from'=> $mailfrom,
			'to'=>$to,
			'subject'=>$subject,
			'html'=>$html,
			'text'=>$html,
			'o:tracking'=>'yes',
			'o:tracking-clicks'=>'yes',
			'o:tracking-opens'=>'yes',
		);

	}

	$apikey = MAILGUN_KEY;
	$url = MAILGUN_URL .'/messages';

	$headers = array(
		'Authorization' => 'Basic ' . base64_encode("api:{$apikey}"),
	);

	$results = wp_remote_post($url, array(
			'method' => 'POST',
			'headers' => $headers,
			'httpversion' => '1.0',
			'sslverify' => false,
			'body' => $array_data
		)
	);




	return $results;
}


?>