<?php

include('SignatureRequest.php');
require 'bencode.php';
require_once 'ApnsPHP/Autoload.php';

date_default_timezone_set('Europe/Budapest');

$signatureRequest = new SignatureRequest;

$signatureRequest->setupWith(
	$service_provider_name = 'Nationwide', 
	$message_id = 2341234, 
	$response_url = 'http://192.168.1.128:8080/authreq-srv/callback.php', 
	$long_description = 'Sending £46.00 to Mr L Balog from your FlexDirect debit account on 13rd Oct, 16:07', 
	$short_description = '£46.00 to Mr L Balog', 
	$nonce = 'pinafül7', 
	$expiry = 5000
);

//echo $signatureRequest->getBencode();

echo $signatureRequest->getSignature();

// $signatureRequest->sendPush($token = '5b54f9bd4215c3ba405a200d7e4353946f60e7878db7334b4cdc94bf53dbce52');
$signatureRequest->sendPush($token = '53202ca1d0276be7084b7e4dc760bba70500a4ec5937149cee9a1fa889f5cec0');

echo "<h1>Fin</h1>";