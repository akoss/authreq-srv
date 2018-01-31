<?php 

$nonce = "736466617364666c616b6a73686466"; 
$signature = "304502210089107dc72c85d2469e57f8379a51ad6ec194275038cda9f81c9f21925ed085730220519b2d53246bff10c64a93efbf96d9ffaa89f8ef040adb19e3e3d7f72b88bf31"; 

$pem = "-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEeXGgRqcDZBCXQ96LWVVq2cxg0+Hx
P8NxGIllbp24Txxa62b/dXBc++iI3JWQOZbUa2lxyxHTwcxvjzRm4eblcQ==
-----END PUBLIC KEY-----";

$say = openssl_verify(hex2bin($nonce), hex2bin($signature), $pem, "sha256");
shell_exec("say " . $say);

$status_header = 'HTTP/1.1 ' . "200";
header($status_header);
header('Content-type: ' . "application/json");
echo json_encode(array("success" => true));

?>