<?php 

$nonce = "70696e6166c3bc6c36"; 
$signature = "304402204821b7530417f2c9342e50d98730cae08dfd9304c3789b9af30faad9dae9b5a002202f2542516108a7b16c71eab4c9de43fe97963cdbf741452e2d58ab17b48c8b1e"; 

$pem = "-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEQywSi38cwFxvo8xSa1g2H0dqKXHr
HnVAwfKPo1xcsVF+E4R5rEN7yhtIyj5veIOtOXxiKq5eE0NMBwgVounyvg==
-----END PUBLIC KEY-----";

echo openssl_verify(hex2bin($nonce), hex2bin($signature), $pem, "sha256");

?>