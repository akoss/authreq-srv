<?php

date_default_timezone_set('Europe/Budapest');

require_once 'ApnsPHP/Autoload.php';

$push = new ApnsPHP_Push(
	ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
	'both.pem'
);
$push->setLogger(new ApnsPHP_Log_Silent());
$push->setRootCertificationAuthority('entrust_root_certification_authority.pem');
$push->connect();
$message = new ApnsPHP_Message('5b54f9bd4215c3ba405a200d7e4353946f60e7878db7334b4cdc94bf53dbce52'); // X
//$message = new ApnsPHP_Message('fc27299d7a17ef04da8fb4448bca82f4ca620ea0134537c3cdce0f017c6e5a05'); // 5s

//$message->setBadge(1);

$message->setText('Sending £46.00 to Mr L Balog from your FlexDirect debit account on 13rd Oct, 16:07');
$message->setTitle(json_decode('"\uD83D\uDD35"') . " New Signature Request");
$message->setSubtitle('Nationwide');
$message->setCategory('challengecategory');
$message->setContentAvailable(true);
$message->setSound();
$message->setCustomProperty('additional_data', array('expiry' => time() + 15, 'short_title' => '£45.00 to Mr ' . time(), 'signature' => 'to_be_filled', 'message_id' => 2341234, 'nonce' => 'pinafül7', 'response_url'=> 'http://192.168.100.139:8080/authreq-srv/callback.php'));
$message->setExpiry(60);
$push->add($message);
$push->send();
$push->disconnect();

$aErrorQueue = $push->getErrors();
if (!empty($aErrorQueue)) {
	var_dump($aErrorQueue);
}

echo "<h1>Fin</h1>";