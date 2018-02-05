<?php 

include('SignatureRequest.php');
include('Signature.php');
include('bencode.php');
include('ApnsPHP/Autoload.php');

$data = json_decode(file_get_contents('php://input'), true);

$message_id = $data["message_id"];
$signature = $data["signature"];

$db = new Db('localhost','root','almakorte','authreq-srv');
$sig = new DatabaseSignature($db);
$sig->setupWith(
	$message_id = $message_id,
	$signature = $signature
);

if(!$sig->saved) {
	die("not saved");
}

$status_header = 'HTTP/1.1 ' . "200";
header($status_header);
header('Content-type: ' . "application/json");
echo json_encode(array("success" => ($sig->success == 1)));

?>
