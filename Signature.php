<?php 
class Signature { 

	public $message_id;
	public $signature;
	public $pem;
	public $device_id;
	public $timestamp;
	public $success;

    function __construct() {
    }

	public function setupWith($message_id, $signature, $pem, $device_id){
		$this->message_id = $message_id; 
		$this->signature = $signature;
		$this->pem = $pem; 
		$this->device_id = $device_id;
		$this->timestamp = time();

		$this->success = false; 
	}

	public function validate($bencodedOriginalMessage) {
		$result = openssl_verify($bencodedOriginalMessage, hex2bin($this->signature), $this->pem, "sha256");
		return ($result == 1);
	}
}

class DatabaseSignature extends Signature {

	private $db; 
	public $saved = false;

	public $signature_id;

    function __construct($db) {
        parent::__construct();
        $this->db = $db; 
    }

	public function save() {
		$message_id = $this->db->quote($this->message_id);
		$signature = $this->db->quote($this->signature);
		$pem = $this->db->quote($this->pem);
		$device_id = $this->db->quote($this->device_id);
		$timestamp = $this->db->quote($this->timestamp);
		$success = $this->db->quote($this->success);

		$query = "INSERT INTO `signature` (`message_id`,`signature`,`pem`,`device_id`,`timestamp`,`success`) VALUES (" . $message_id . "," . $signature . "," . $pem . "," . $device_id . "," . $timestamp . "," . $success . ")";

		$result = $this->db->query($query) == 1;

		if($result) {
			$this->signature_id = $this->db->insert_id();
		}

		return $result;
	}

	public static function loadFromDatabase($db, $signature_id) {
		$sigid = $db->quote($signature_id);
		$records = $db->select("SELECT * FROM `signature` WHERE signature_id = " . $sigid . ";");

		if(count($records) == 1) {
			$record = $records[0];
			$self = new DatabaseSignature($db);
			$self->message_id = $record['message_id'];
			$self->signature = $record['signature'];
			$self->pem = $record['pem'];
			$self->device_id = $record['device_id'];
			$self->timestamp = $record['timestamp'];
			$self->success = $record['success'];
		} else {
			die("Not found");
			return null;
		}
		return $self;
	}

	public function setupWith($message_id, $signature, $pem, $device_id){
		parent::setupWith($message_id, $signature, $pem, $device_id);
		$this->success = $this->validate();
		$this->saved = ($this->save() == 1);
	}

	public function validate() {
		$record = DatabaseSignatureRequest::loadFromDatabase($this->db, $this->message_id);
		$bencodedOriginalMessage = $record->getBencode();

		return parent::validate($bencodedOriginalMessage);
	}
}

?>