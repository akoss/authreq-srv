<?php 
class SignatureRequest { 
	public $nonce;
	public $push_title;
	public $push_subtitle;
	public $push_category;
	public $push_text;
	public $short_title;
	public $message_id;
	public $reply_status;
	public $response_url;
	public $timestamp;
	public $reply_timestamp;
	private $expiry;

	private $srv_signature;
	public $expiry_in_seconds;

	function getPrivateKey() {
		return openssl_pkey_get_private('file://../srv-keys/mykey.pem');
	}

	function getPublicKey() {
		return openssl_pkey_get_public('file://../srv-keys/mykey.pub');
	}

	function getBencode() {
		return BEncode::encode([
			'body'=>$this->push_text,
			'title'=>$this->push_title,
			'subtitle'=>$this->push_subtitle,
			'category'=>$this->push_category,
			'response_url'=>$this->response_url,
			'message_id'=>$this->message_id,
			'short_title'=>$this->short_title,
			'nonce'=>$this->nonce,
			'expiry'=>$this->expiry,
		]);
	}

	function getHash() {
		$bencoded = $this->getBencode();
		return base64_encode(hash("sha256", $bencoded, true));
	}

	function getSignature() {
		$data = $this->getBencode();

		$private_key = $this->getPrivateKey();
		$public_key  = $this->getPublicKey();

		$binary_signature = "";

		openssl_sign($data, $binary_signature, $private_key, 'sha256WithRSAEncryption');

		$ok = openssl_verify($data, $binary_signature, $public_key, 'sha256');

		if ($ok != 1) {
		    return null;
		}

		$ok = openssl_verify('tampered'.$data, $binary_signature, $public_key, 'sha256');

		if ($ok != 0) {
		    return null;
		}

		return base64_encode($binary_signature);
	}

	function getBencodeForSignature() {

	}

	function setupWith($service_provider_name, $message_id, $response_url, $long_description, $short_description, $nonce = null, $expiry = null){
		$this->push_text = $long_description; 
		$this->push_subtitle = $service_provider_name;
		$this->expiry_in_seconds = $expiry; 
		$this->short_title = $short_description;
		$this->message_id = $message_id; 
		$this->nonce = $nonce; 
		$this->response_url = $response_url; 
		$this->expiry = time() + $this->expiry_in_seconds;
		$this->push_category = 'challengecategory';

		$this->push_title = json_decode('"\uD83D\uDD35"') . " " . 'New Signature Request';

		$this->srv_signature = $this->getSignature(); 
	}

	function sendPush($token) {
		$push = new ApnsPHP_Push(
			ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
			'both.pem'
		);
		$push->setLogger(new ApnsPHP_Log_Silent());
		$push->setRootCertificationAuthority('entrust_root_certification_authority.pem');
		$push->connect();
		$message = new ApnsPHP_Message($token); // X
		//$message = new ApnsPHP_Message('fc27299d7a17ef04da8fb4448bca82f4ca620ea0134537c3cdce0f017c6e5a05'); // 5s

		$message->setBadge(1);

		$message->setText($this->push_text);
		$message->setTitle($this->push_title);
		$message->setSubtitle($this->push_subtitle);
		$message->setCategory($this->push_category);
		$message->setContentAvailable(true);
		$message->setSound();
		//throw new Exception($this->srv_signature);
		$message->setCustomProperty('additional_data', array('bencode' => $this->getBencode(), 'expiry' => $this->expiry, 'short_title' => $this->short_title, 'signature' => $this->srv_signature, 'message_id' => $this->message_id, 'nonce' => $this->nonce, 'response_url'=> $this->response_url));

		$message->setExpiry($this->expiry_in_seconds);
		$push->add($message);
		$push->send();
		$push->disconnect();

		$aErrorQueue = $push->getErrors();
		if (!empty($aErrorQueue)) {
			var_dump($aErrorQueue);
		}
	}
}
?>