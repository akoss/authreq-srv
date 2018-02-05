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

	public $srv_signature;
	public $expiry_in_seconds;

    function __construct() {
    }

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
			'expiry'=>$this->timestamp + $this->expiry_in_seconds,
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

	public function setupWith($service_provider_name, $message_id = null, $response_url, $long_description, $short_description, $nonce = null, $expiry_in_seconds = 300){

		$this->push_text = $long_description; 
		$this->push_subtitle = $service_provider_name;
		$this->expiry_in_seconds = $expiry_in_seconds; 
		$this->short_title = $short_description;
		$this->message_id = $message_id; 

		if(empty($nonce)) {
			$this->nonce = bin2hex(openssl_random_pseudo_bytes(32));
		} else {
			$this->nonce = $nonce; 
		}
		
		$this->response_url = $response_url; 
		$this->timestamp = time();
		$this->push_category = 'challengecategory';

		$this->push_title = json_decode('"\uD83D\uDD35"') . " " . 'New Signature Request';

		$this->srv_signature = $this->getSignature(); 
	}

	function sendPush($token, $pem, $rootca) {
		$push = new ApnsPHP_Push(
			ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
			$pem
		);
		$push->setLogger(new ApnsPHP_Log_Silent());
		$push->setRootCertificationAuthority($rootca);
		$push->connect();
		$message = new ApnsPHP_Message($token);

		$message->setBadge(1);

		$message->setText($this->push_text);
		$message->setTitle($this->push_title);
		$message->setSubtitle($this->push_subtitle);
		$message->setCategory($this->push_category);
		$message->setContentAvailable(true);
		$message->setSound();

		$additional_data = array('bencode' => $this->getBencode(), 'expiry' => $this->timestamp + $this->expiry_in_seconds, 'short_title' => $this->short_title, 'signature' => $this->srv_signature, 'message_id' => $this->message_id, 'nonce' => $this->nonce, 'response_url'=> $this->response_url);

		$message->setCustomProperty('additional_data', $additional_data);

		$message->setExpiry($this->expiry_in_seconds);
		$push->add($message);
		$push->send();
		$push->disconnect();

		$aErrorQueue = $push->getErrors();
		if (!empty($aErrorQueue)) {
			// var_dump($aErrorQueue);
			return false;
		}
		return true;
	}
}

class DatabaseSignatureRequest extends SignatureRequest {

	private $db; 
	public $saved = false;
	public $device_id;

    function __construct($db) {
        parent::__construct();
        $this->db = $db; 
    }

	public function save() {
		$nonce = $this->db->quote($this->nonce);
		$push_title = $this->db->quote($this->push_title);
		$push_subtitle = $this->db->quote($this->push_subtitle);
		$push_category = $this->db->quote($this->push_category);
		$push_text = $this->db->quote($this->push_text);
		$short_title = $this->db->quote($this->short_title);
		$message_id = $this->db->quote($this->message_id);
		$response_url = $this->db->quote($this->response_url);
		$timestamp = $this->db->quote($this->timestamp);
		$device_id = $this->db->quote($this->device_id);

		$expiry_in_seconds = $this->db->quote($this->expiry_in_seconds);

		$query = "INSERT INTO `signaturerequest` (`nonce`,`push_title`,`push_subtitle`,`push_category`,`push_text`,`short_title`,`message_id`,`response_url`,`timestamp`,`expiry_in_seconds`, `device_id`) VALUES (" . $nonce . "," . $push_title . "," . $push_subtitle . "," . $push_category . "," . $push_text . "," . $short_title . "," . $message_id . "," . $response_url . "," . $timestamp . "," . $expiry_in_seconds . "," . $device_id . ")";

		$result = $this->db->query($query) == 1;

		if($result) {
			$this->message_id = $this->db->insert_id();
		}

		return $result;
	}

	public static function loadFromDatabase($db, $message_id) {
		$msgid = $db->quote($message_id);
		$records = $db->select("SELECT * FROM `signaturerequest` WHERE message_id = " . $msgid . ";");

		if(count($records) == 1) {
			$record = $records[0];
			$self = new DatabaseSignatureRequest($db);
			$self->nonce = $record['nonce'];
			$self->push_title = $record['push_title'];
			$self->push_subtitle = $record['push_subtitle'];
			$self->push_category = $record['push_category'];
			$self->push_text = $record['push_text'];
			$self->short_title = $record['short_title'];
			$self->message_id = intval($record['message_id']);
			$self->response_url = $record['response_url'];
			$self->timestamp = $record['timestamp'];
			$self->expiry_in_seconds = $record['expiry_in_seconds'];
			$self->device_id = $record['device_id'];

		} else {
			die("Not found");
			return null;
		}

		return $self;
	}

	public function setupWith($service_provider_name, $message_id, $response_url, $long_description, $short_description, $nonce, $expiry_in_seconds, $device_id){
		parent::setupWith($service_provider_name, $message_id, $response_url, $long_description, $short_description, $nonce, $expiry_in_seconds);

		$this->device_id = $device_id; 

		$this->saved = ($this->save() == 1);
		$this->srv_signature = $this->getSignature();
	}

	public static function isSigned($db, $message_id) {
		$msgid = $db->quote($message_id);
		$query = "SELECT * FROM `signature` WHERE message_id = " . $msgid . " AND success = 1;";
		$records = $db->select($query);

		return (count($records) >= 1);
	}

	public function sendPush($pem, $rootca) {
		$records = $this->db->select("SELECT * FROM `device` WHERE device_id = " . $this->device_id . ";");
		if(count($records) == 1) {
			$record = $records[0];
			$token = $record['token'];
		} else {
			return false;
		}

		return parent::sendPush($token, $pem, $rootca);
	}
}

?>