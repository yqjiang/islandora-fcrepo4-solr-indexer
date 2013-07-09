<?php

/**
 * Class to listen for the JMS messages and filter them
 * based on the rules defined in config.xml
 * 
 * @author Richard Wincewicz
 */
$connect = new Connect();
$connect->listen();
unset($connect);

class Connect {

  function __construct() {
    include_once 'message.php';
    //include_once 'fedoraConnection.php';
    include_once 'connect.php';
    //include_once 'Derivatives.php';
    include_once 'Logging.php';


    // Load config file
    $config_file = file_get_contents('config.xml');
    $this->config_xml = new SimpleXMLElement($config_file);

    // Logging settings
    $log_file = $this->config_xml->log->file;

    $this->log = new Logging();
    $this->log->lfile($log_file);
    $prot = empty($this->config_xml->fedora->protocol) ? 'http' : $this->config_xml->fedora->protocol;
    
    // Set up stomp settings
    $stomp_url = 'tcp://' . $this->config_xml->stomp->host . ':' . $this->config_xml->stomp->port;
    $channel = $this->config_xml->stomp->channel;


    // Make a connection
    try {
      $this->con = new Stomp($stomp_url);
    } catch (StompException $e) {
      file_put_contents('php://stderr', "Could not open a connection to $stomp_url - " . $e->getMessage());
      throw($e);
    }
    $this->con->sync = TRUE;
    $this->con->setReadTimeout(1);

    // Subscribe to the queue
    try {
      $this->con->subscribe((string) $channel[0], array('activemq.prefetchSize' => 1));
    } catch (Exception $e) {
      file_put_contents('php://stderr', "Could not subscribe to the channel $channel - " . $e->getMessage());
      throw($e);
    }
  }

  function listen() {
    // Receive a message from the queue
    $returnResult = TRUE; //we will acknowledge message by default
    if ($this->msg = $this->con->readFrame()) {
      // do what you want with the message
      if ($this->msg != NULL) {
        $message = new Message($this->msg->body);
        $pid = $this->msg->headers['pid'];
        $modMethod = $this->msg->headers['methodName'];
        $this->log->lwrite("Method: " . $modMethod, 'MODIFY_OBJECT', $pid);
        $this->handleMessage($pid, $modMethod);
      }
    }
    // Close log file
    $this->log->lclose();
  }

  function handleMessage($pid, $method) {
    
  }

}

?>
