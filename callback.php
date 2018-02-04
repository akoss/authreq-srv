<?php 

include('SignatureRequest.php');
include('bencode.php');
include('ApnsPHP/Autoload.php');

$data = json_decode(file_get_contents('php://input'), true);

$message_id = $data["message_id"];

$signature = $data["signature"];
$pem = $data["publickey"];

$db = new Db();
$record = DatabaseSignatureRequest::loadFromDatabase($db, $message_id);

$bencodedOriginalMessage = $record->getBencode();

$say = openssl_verify($bencodedOriginalMessage, hex2bin($signature), $pem, "sha256");

$status_header = 'HTTP/1.1 ' . "200";
header($status_header);
header('Content-type: ' . "application/json");
echo json_encode(array("success" => ($say == 1)));

?>