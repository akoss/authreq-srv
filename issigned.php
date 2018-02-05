<?php 

include('SignatureRequest.php');
include('Signature.php');
include('bencode.php');
include('ApnsPHP/Autoload.php');

$data = json_decode(file_get_contents('php://input'), true);

$message_id = $_GET["message_id"];
if(empty($message_id)) {
	die("No message ID found");
}

$db = new Db('localhost','root','almakorte','authreq-srv');
$answer = DatabaseSignatureRequest::isSigned($db, $message_id);

header('Content-type: ' . "application/json");
$status_header = 'HTTP/1.1 ' . "200";
header($status_header);
echo json_encode(array("signed" => $answer));

?>
