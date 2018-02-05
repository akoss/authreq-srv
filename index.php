<?php

include('SignatureRequest.php');
include('Signature.php');
include('Db.php');
include('bencode.php');
include('ApnsPHP/Autoload.php');

date_default_timezone_set('Europe/Budapest');

$db = new Db('localhost','root','almakorte','authreq-srv');
$signatureRequest = new DatabaseSignatureRequest($db);

$signatureRequest->setupWith(
	$service_provider_name = 'Nationwide', 
	$message_id = null,
	$response_url = 'http://192.168.100.139:8080/authreq-srv/callback.php', 
	$long_description = 'Sending £47.00 to Mr L Balog from your FlexDirect debit account on 13rd Oct, 16:07', 
	$short_description = '£47.00 to Mr L Balog', 
	$nonce = null, 
	$expiry_in_seconds = 5000, 
	$device_id = 2
);

if(!$signatureRequest->saved) {
	die("not saved");
}

// $signatureRequest->sendPush($token = '5b54f9bd4215c3ba405a200d7e4353946f60e7878db7334b4cdc94bf53dbce52');
$signatureRequest->sendPush('/Users/harfox/uni/l5project/authreq-site/vendors/authreq-sdk/both.pem', '/Users/harfox/uni/l5project/authreq-site/vendors/authreq-sdk/entrust_root_certification_authority.pem');

echo "<h1>Fin</h1>";