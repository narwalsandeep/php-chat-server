<?php

namespace Chat\Controller;

use Ratchet\Server\IoServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Zend\Mvc\Controller\AbstractActionController;

/**
 *
 * @author narwalsandeep at gee mail dot com
 *        
 */
class ServerController extends AbstractActionController implements MessageComponentInterface {
	/**
	 *
	 * @var unknown
	 */
	private static $_prompt = ": ";
	
	/**
	 *
	 * @var unknown
	 */
	private static $_help_message = "";
	
	/**
	 *
	 * @var unknown
	 */
	private static $_unknown_command = "Unknown Command";
	
	/**
	 *
	 * @var unknown
	 */
	protected $clients;
	
	/**
	 */
	public function __construct() {
		$this->activeClients = array ();
		$this->activeClientsNameMap = array ();
		$this->queuedClients = array ();
		$this->requestPending = array ();
		$this->requestApproved = array ();
	}
	
	/**
	 */
	public function startAction() {
		$server = IoServer::factory ( new ServerController (), 9999 );
		$server->run ();
		// die ( "server started ..." );
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see \Ratchet\ComponentInterface::onOpen()
	 */
	public function onOpen(ConnectionInterface $conn) {
		$this->queuedClients [] = $conn;
		$this->filterRequest ( $conn, "?" );
		$this->processMsgQueue ();
	}
	
	/**
	 */
	public function runHelpCommand() {
		$this->msgToSource = array (
			"success" => "true" 
		);
	}
	
	/**
	 *
	 * @param unknown $msg        	
	 */
	public function filterRequest($conn, $msg) {
		$this->sourceConn = $conn;
		$this->destinationConn = "";
		$this->msgToSource = "";
		$this->msgToDestination = "";
		$this->msgToBroadcast = "";
		
		$this->payload = str_replace ( "\r\n", '', $msg );
		$explode = explode ( ":", $this->payload );
		
		if (array_key_exists ( trim ( $explode [0] ), $this->activeClients ) && trim ( $explode [1] ) != "") {
			if (trim ( $explode [0] ) != $this->getConnName ( $this->sourceConn )) {
				$this->cmd = "msg";
			}
		} else {
			$check = $this->payload [0];
			switch ($check) {
				case '=' :
					$this->cmd = "login";
					break;
				case '#' :
					$this->cmd = "request";
					break;
				case '?' :
					$this->cmd = "help";
					break;
				case '+' :
					$this->cmd = "approve";
					break;
				case '*' :
					$this->cmd = "list";
					break;
				case '~' :
					$this->cmd = "status";
					break;
				case '<' :
					$this->cmd = "typing";
					break;
				case '^' :
					$this->cmd = "upload";
					break;
				case 'x' :
					$this->cmd = "logout";
					break;
			}
		}
		
		$this->cmd = "run" . ucwords ( $this->cmd ) . "Command";
		if (method_exists ( $this, $this->cmd )) {
			$this->{$this->cmd} ();
		} else {
			if ($this->payload != "") {
				$this->msgToSource = array (
					"success" => false,
					"msg" => self::$_unknown_command 
				);
			}
		}
	}
	
	/**
	 *
	 * @param number $id        	
	 * @return unknown
	 */
	public function getUser($id = 0) {
		$config = new \Zend\Config\Config ( include DOC_ROOT . '/config/autoload/local.php' );
		$username = $config->get ( "db" )->get ( "username" );
		$password = $config->get ( "db" )->get ( "password" );
		$dsn = explode ( ";", explode ( ":", $config->get ( "db" )->get ( "dsn" ) )[1] );
		$dbname = explode ( "=", $dsn [0] )[1];
		$host = explode ( "=", $dsn [1] )[1];
		$adapter = new \Zend\Db\Adapter\Adapter ( array (
			'driver' => 'mysqli',
			'host' => ($host == "") ? "localhost" : $host,
			'database' => $dbname,
			'username' => $username,
			'password' => $password 
		) );
		$statement = $adapter->createStatement ( "select * from user where id ='" . $id . "'" );
		$result = $statement->execute ();
		$data = $result->current ();
		echo "\nselect * from user where id ='" . $id . "'\n";
		echo json_encode ( $data );
		echo "\n";
		return $data;
	}
	
	/**
	 */
	public function runLoginCommand() {
		echo json_encode ( $this->activeClients );
		echo json_encode ( $this->activeClientsNameMap );
		
		$param = trim ( substr ( $this->payload, 1 ) );
		$UserData = $this->getUser ( $param );
		
		if ($UserData ['id'] > 1) {
			if (! $this->login ( $UserData )) {
				$this->msgToSource = array (
					"cmd" => "=",
					"debug" => json_encode ( $UserData ),
					"success" => false 
				);
			} else {
				$this->msgToBroadcast = array (
					"cmd" => "=",
					"msg" => "$param" 
				);
				$this->msgToSource = array (
					"cmd" => "=",
					"success" => true 
				);
			}
		} else {
			$this->msgToSource = array (
				"success" => false,
				"debug" => json_encode ( $UserData ),
				"msg" => self::$_unknown_command 
			);
		}
	}
	
	/**
	 */
	public function runStatusCommand() {
		$param = trim ( substr ( $this->payload, 1 ) );
		
		if ($param != "") {
			if (! $this->isReceiverLoggedin ( $param )) {
				$this->msgToSource = array (
					"success" => true,
					"cmd" => "~" 
				);
			} else {
				$this->msgToSource = array (
					"success" => false,
					"debug" => $param,
					"cmd" => "~" 
				);
			}
		} else {
			$this->msgToSource = array (
				"success" => false,
				"debug" => $param,
				"msg" => self::$_unknown_command 
			);
		}
	}
	
	/**
	 */
	public function runUploadCommand() {
		// TODO : upload a file
	}
	
	/**
	 */
	public function runTypingCommand() {
		// TODO : return typing statuss
	}
	
	/**
	 */
	public function runRequestCommand() {
		$param = trim ( substr ( $this->payload, 1 ) );
		
		if ($param != "") {
			if (! $this->isReceiverLoggedin ( $param )) {
				$this->msgToSource = array (
					"cmd" => "@",
					"debug" => $param,
					"success" => false 
				);
			} else {
				$this->requestPending [$this->getConnId ( $this->sourceConn )] [$param] = true;
				$this->msgToSource = array (
					"cmd" => "@",
					"success" => true 
				);
				$this->msgToDestination = array (
					"cmd" => "@",
					"id" => $this->getConnId ( $this->sourceConn ),
					"name" => $this->getConnName ( $this->sourceConn ) 
				);
				$this->destinationConn = $this->activeClients [$param];
			}
		} else {
			$this->msgToSource = array (
				"success" => false,
				"debug" => $param,
				"msg" => self::$_unknown_command 
			);
		}
	}
	
	/**
	 */
	public function runApproveCommand() {
		$param = trim ( substr ( $this->payload, 1 ) );
		
		if ($param != "") {
			if (! $this->isReceiverLoggedin ( $param )) {
				$this->msgToSource = array (
					"cmd" => "+",
					"success" => false 
				);
			} else {
				$this->requestApproved [$param] [$this->getConnId ( $this->sourceConn )] = true;
				$this->msgToDestination = array (
					"cmd" => "+",
					"success" => true,
					"id" => $this->getConnId ( $this->sourceConn ),
					"name" => $this->getConnName ( $this->sourceConn ) 
				);
				$this->destinationConn = $this->activeClients [$param];
				$this->msgToSource = array (
					"cmd" => "+",
					"success" => true,
					"id" => $param,
					"name" => $this->getConnName ( $this->destinationConn ) 
				);
				unset ( $this->requestPending [$param] [$this->getConnId ( $this->sourceConn )] );
			}
		} else {
			$this->msgToSource = array (
				"success" => false,
				"msg" => self::$_unknown_command 
			);
		}
	}
	
	/**
	 */
	public function runListCommand() {
		foreach ( $this->activeClients as $key => $value ) {
			$this->msgToSource .= "$key,";
		}
	}
	
	/**
	 */
	public function runMsgCommand() {
		$explode = explode ( ":", $this->payload );
		$to = trim ( $explode [0] );
		$msg = substr ( $this->payload, strlen ( $to ) + 1 );
		
		if ($this->isReceiverLoggedin ( $to )) {
			if ($this->isMessageValid ( $msg )) {
				$this->destinationConn = $this->activeClients [$to];
				if ($this->isChatApproved ( $to )) {
					$this->msgToDestination = array (
						"cmd" => "$",
						"id" => $this->getConnId ( $this->sourceConn ),
						"name" => $this->getConnName ( $this->sourceConn ),
						"msg" => $this->prepareMsg ( $msg ) 
					);
				}
			}
		} else {
			$this->msgToSource = array (
				"success" => false,
				"msg" => self::$_unknown_command 
			);
		}
	}
	
	/**
	 */
	public function runLogoutCommand() {
		$this->sourceConn->close ();
	}
	
	/**
	 *
	 * @param unknown $msg        	
	 */
	public function prepareMsg($msg) {
		return trim ( $msg );
	}
	
	/**
	 *
	 * @param unknown $name        	
	 */
	public function login($UserData) {
		$id = $UserData ['id'];
		$name = $UserData ['first_name'] . " " . $UserData ['last_name'];
		if (trim ( $name ) == "") {
			$name = $UserData ['username'];
		}
		if ($id > 1) {
			if (! array_key_exists ( $id, $this->activeClients )) {
				foreach ( $this->queuedClients as $key => $value ) {
					if ($value->resourceId == $this->sourceConn->resourceId) {
						$this->activeClients [$id] = $this->sourceConn;
						$this->activeClientsNameMap [$id] = $name;
						unset ( $this->queuedClients [$key] );
						break;
					}
				}
			}
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 *
	 * @param unknown $name        	
	 * @return boolean
	 */
	public function isReceiverLoggedin($id) {
		if (array_key_exists ( trim ( $id ), $this->activeClients )) {
			return true;
		}
		return false;
	}
	
	/**
	 *
	 * @param unknown $name        	
	 */
	public function isChatApproved($to) {
		if ($this->requestApproved [$to] [$this->getConnId ( $this->sourceConn )]) {
			return true;
		}
		if ($this->requestApproved [$this->getConnId ( $this->sourceConn )] [$to]) {
			return true;
		}
		
		return false;
	}
	
	/**
	 *
	 * @param unknown $msg        	
	 * @return boolean
	 */
	public function isMessageValid($msg) {
		if (strlen ( $msg ) > 0 && strlen ( $msg ) < 150) {
			return true;
		}
		return false;
	}
	
	/**
	 *
	 * @param unknown $conn        	
	 * @return boolean
	 */
	public function broadcast() {
		if ($this->msgToBroadcast) {
			foreach ( $this->activeClients as $key => $value ) {
				if ($this->sourceConn->resourceId != $value->resourceId) {
					$value->send ( json_encode ( $this->msgToBroadcast ) );
					$value->send ( "\n" . self::$_prompt );
				} else {
					$value->send ( json_encode ( $this->msgToBroadcast ) );
				}
			}
		}
	}
	
	/**
	 */
	public function processMsgQueue() {
		if ($this->msgToSource) {
			$this->sourceConn->send ( json_encode ( $this->msgToSource ) . "\n" );
		}
		if ($this->msgToDestination) {
			$this->destinationConn->send ( json_encode ( $this->msgToDestination ) . "\n" );
			// $this->destinationConn->send ( "\n" . self::$_prompt );
		}
		// $this->sourceConn->send ( "\n" . self::$_prompt );
	}
	
	/**
	 *
	 * @param unknown $conn        	
	 * @return boolean
	 */
	public function getConnName($conn) {
		foreach ( $this->activeClients as $key => $value ) {
			if ($value->resourceId == $conn->resourceId) {
				return $this->activeClientsNameMap [$key];
			}
		}
		
		return false;
	}
	
	/**
	 *
	 * @param unknown $conn        	
	 * @return boolean
	 */
	public function getConnId($conn) {
		foreach ( $this->activeClients as $key => $value ) {
			if ($value->resourceId == $conn->resourceId) {
				return $key;
			}
		}
		
		return false;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see \Ratchet\MessageInterface::onMessage()
	 */
	public function onMessage(ConnectionInterface $conn, $msg) {
		$this->filterRequest ( $conn, $msg );
		// $this->broadcast ();
		$this->processMsgQueue ();
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see \Ratchet\ComponentInterface::onClose()
	 */
	public function onClose(ConnectionInterface $conn) {
		// The connection is closed, remove it, as we can no longer send it messages
		foreach ( $this->activeClients as $key => $value ) {
			if ($value->resourceId == $conn->resourceId) {
				$id = $key;
				break;
			}
		}
		unset ( $this->activeClients [$id] );
		unset ( $this->activeClientsNameMap [$id] );
		$this->msgToSource = array (
			"cmd" => "x",
			"success" => true 
		);
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see \Ratchet\ComponentInterface::onError()
	 */
	public function onError(ConnectionInterface $conn, \Exception $e) {
		echo "[CODE EXCEPTION] {$e->getMessage()}";
		// $conn->close ();
	}
}
