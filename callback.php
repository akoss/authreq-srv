<?php 

$data = json_decode(file_get_contents('php://input'), true);

$nonce = base64_decode($data["bencodedOriginalMessage"]);
$signature = $data["signature"];
$pem = $data["publickey"];


$say = openssl_verify($nonce, hex2bin($signature), $pem, "sha256");
shell_exec("say " . $say);

$status_header = 'HTTP/1.1 ' . "200";
header($status_header);
header('Content-type: ' . "application/json");
echo json_encode(array("success" => ($say == 1)));

?>