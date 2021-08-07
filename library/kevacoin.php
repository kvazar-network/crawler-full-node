<?php

class KevaCoin {

  private $_id = 0;

  private $_curl;
  private $_protocol;
  private $_host;
  private $_port;

  public function __construct($protocol, $host, $port, $username, $password) {

    $this->_protocol  = $protocol;
    $this->_host      = $host;
    $this->_port      = $port;

    $this->_curl      = curl_init();

    curl_setopt_array($this->_curl, [CURLOPT_RETURNTRANSFER => true,
                                     CURLOPT_FOLLOWLOCATION => true,
                                     CURLOPT_FRESH_CONNECT  => true,
                                     CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
                                     CURLOPT_USERPWD        => $username . ':' . $password,
                                     CURLOPT_RETURNTRANSFER => true,
                                     CURLOPT_FOLLOWLOCATION => true,
                                     //CURLOPT_VERBOSE        => true,
                                     CURLOPT_HTTPHEADER     => [
                                       'Content-Type: application/plain',
                                     ],
                                    ]);
  }

  public function __destruct() {
    curl_close($this->_curl);
  }

  protected function prepare($url, $method, array $postfields = []) {

    curl_setopt($this->_curl, CURLOPT_URL, $this->_protocol . '://' . $this->_host . ':' . $this->_port . $url);
    curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, $method);

    if ($method == 'POST' && $postfields) {
      curl_setopt($this->_curl, CURLOPT_POSTFIELDS, json_encode($postfields));
    }
  }

  protected function execute($json = true) {

    $response    = curl_exec($this->_curl);
    $errorNumber = curl_errno($this->_curl);
    $errorText   = curl_error($this->_curl);

    if ($errorNumber > 0) {
      //return false;
    }

    if ($response) {
      if ($json) {
        return json_decode($response, true);
      } else {
        return $response;
      }
    }

    return false;
  }

  public function getblockcount() {

    $this->_id++;

    $this->prepare('', 'POST', [
      'method' => 'getblockcount',
      'params' => [],
      'id'     => $this->_id
    ]);

    $response = $this->execute();

    if (isset($response['result']) && is_int($response['result'])) {

      return $response['result'];

    } else {

      return false;
    }
  }

  public function getblockhash($block) {

    $this->_id++;

    $this->prepare('', 'POST', [
      'method' => 'getblockhash',
      'params' => [$block],
      'id'     => $this->_id
    ]);

    $response = $this->execute();

    if (isset($response['result']) && 64 == strlen($response['result'])) {

      return $response['result'];

    } else {

      return false;
    }
  }

  public function getblock($hash) {

    $this->_id++;

    $this->prepare('', 'POST', [
      'method' => 'getblock',
      'params' => [$hash],
      'id'     => $this->_id
    ]);

    $response = $this->execute();

    if (isset($response['result']) && is_array($response['result'])) {

      return $response['result'];

    } else {

      return false;
    }
  }

  public function getrawtransaction($txid, $decode = true) {

    $this->_id++;

    $this->prepare('', 'POST', [
      'method' => 'getrawtransaction',
      'params' => [$txid, $decode],
      'id'     => $this->_id
    ]);

    $response = $this->execute();

    if (isset($response['result']) && is_array($response['result'])) {

      return $response['result'];

    } else {

      return false;
    }
  }
}
