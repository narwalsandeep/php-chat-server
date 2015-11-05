<?php

namespace Chat\Controller;

use Ratchet\Server\IoServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Core\System\Base;
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
	CONST CMD_LENGTH = 3;
	
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
		$server = IoServer::factory ( new ServerController (), 2852 );
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
		$this->filterRequest ( $conn, "HLP" );
		$this->processMsgQueue ();
	}
	
	/**
	 */
	public function runHelpCommand() {
		$this->msgToSource = array (
			"success" => "true",
			"msg" => $this->sourceConn->remoteAddress 
		);
	}
	
	/**
	 *
	 * @return unknown
	 */
	public function getCmd() {
		for($i = 0; $i < self::CMD_LENGTH; $i ++) {
			$cmd .= $this->payload [$i];
		}
		return $cmd;
	}
	
	/**
	 *
	 * @param unknown $msg        	
	 */
	public function filterRequest($conn, $msg) {
		echo "$conn->resourceId from $conn->remoteAddress called $msg\n";
		$this->sourceConn = $conn;
		$this->destinationConn = "";
		$this->msgToSource = "";
		$this->msgToDestination = "";
		$this->msgToBroadcast = "";
		$this->cmd = "";
		$this->payload = str_replace ( "\r\n", '', $msg );
		$explode = explode ( ":", $this->payload );
		
		if (array_key_exists ( trim ( $explode [0] ), $this->activeClients ) && trim ( $explode [1] ) != "") {
			if (trim ( $explode [0] ) != $this->getConnName ( $this->sourceConn )) {
				$this->method = "msg";
				$this->cmd = "MGR";
			}
		} else {
			$this->cmd = $this->getCmd ();
			switch ($this->cmd) {
				case 'LGN' :
					$this->method = "login";
					break;
				case 'RST' :
					$this->method = "request";
					break;
				case 'HLP' :
					$this->method = "help";
					break;
				case 'APR' :
					$this->method = "approve";
					break;
				case 'LST' :
					$this->method = "list";
					break;
				case 'STS' :
					$this->method = "status";
					break;
				case 'TYP' :
					$this->method = "typing";
					break;
				case 'UPL' :
					$this->method = "upload";
					break;
				case 'LGT' :
					$this->method = "logout";
					break;
			}
		}
		
		$this->cmdMethod = "run" . ucwords ( $this->method ) . "Command";
		if (method_exists ( $this, $this->cmdMethod )) {
			$this->{$this->cmdMethod} ();
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
		/*
		 * UNCOMMENT AND WORK ON BELOW CODES TO ENABLE THE DATABASE ENABLED CHAT
		 * 
		 * $config = new \Zend\Config\Config ( include DOC_ROOT . '/config/autoload/local.php' );
		 * $username = $config->get ( "db" )->get ( "username" );
		 * $password = $config->get ( "db" )->get ( "password" );
		 * $dsn = explode ( ";", explode ( ":", $config->get ( "db" )->get ( "dsn" ) )[1] );
		 * $dbname = explode ( "=", $dsn [0] )[1];
		 * $host = explode ( "=", $dsn [1] )[1];
		 * $adapter = new \Zend\Db\Adapter\Adapter ( array (
		 * 'driver' => 'mysqli',
		 * 'host' => ($host == "") ? "localhost" : $host,
		 * 'database' => $dbname,
		 * 'username' => $username,
		 * 'password' => $password
		 * ) );
		 * $statement = $adapter->createStatement ( "select * from user where id ='" . $id . "'" );
		 * $result = $statement->execute ();
		 * $data = $result->current ();
		 * $adapter->getDriver ()->getConnection ()->disconnect ();
		 * return $data;
		 */
		$data ['id'] = time ();
		$data ['first_name'] = time ();
		return $data;
	}
	
	/**
	 */
	public function runLoginCommand() {
		$param = trim ( substr ( $this->payload, self::CMD_LENGTH ) );
		$UserData = $this->getUser ( $param );
		
		if ($UserData ['id'] > 1) {
			if (! $this->login ( $UserData )) {
				$this->msgToSource = array (
					"cmd" => $this->cmd,
					"debug" => json_encode ( $UserData ),
					"success" => false 
				);
			} else {
				$this->msgToBroadcast = array (
					"cmd" => $this->cmd,
					"msg" => "$param" 
				);
				$this->msgToSource = array (
					"cmd" => $this->cmd,
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
		$param = trim ( substr ( $this->payload, self::CMD_LENGTH ) );
		
		if ($param != "") {
			if (! $this->isReceiverLoggedin ( $param )) {
				$this->msgToSource = array (
					"success" => true,
					"cmd" => $this->cmd 
				);
			} else {
				$this->msgToSource = array (
					"success" => false,
					"debug" => $param,
					"cmd" => $this->cmd 
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
		$param = trim ( substr ( $this->payload, self::CMD_LENGTH ) );
		
		if ($param != "") {
			if (! $this->isReceiverLoggedin ( $param )) {
				$this->msgToSource = array (
					"cmd" => $this->cmd,
					"debug" => $param,
					"success" => false 
				);
			} else {
				$this->requestPending [$this->getConnId ( $this->sourceConn )] [$param] = true;
				$this->msgToSource = array (
					"cmd" => $this->cmd,
					"success" => true 
				);
				$this->msgToDestination = array (
					"cmd" => $this->cmd,
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
		$param = trim ( substr ( $this->payload, self::CMD_LENGTH ) );
		
		if ($param != "") {
			if (! $this->isReceiverLoggedin ( $param )) {
				$this->msgToSource = array (
					"cmd" => $this->cmd,
					"success" => false 
				);
			} else {
				$this->requestApproved [$param] [$this->getConnId ( $this->sourceConn )] = true;
				$this->msgToDestination = array (
					"cmd" => $this->cmd,
					"success" => true,
					"id" => $this->getConnId ( $this->sourceConn ),
					"name" => $this->getConnName ( $this->sourceConn ) 
				);
				$this->destinationConn = $this->activeClients [$param];
				$this->msgToSource = array (
					"cmd" => $this->cmd,
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
						"cmd" => $this->cmd,
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
			"cmd" => $this->cmd,
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
