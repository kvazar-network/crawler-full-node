<?php

class Keva {

  public $username;
  public $password;

  public $host;
  public $port;
  public $url;

  public $proto = 'http';
  public $CACertificate = null;

  public $status;
  public $error;
  public $rawResponse;
  public $response;

  private $id = 0;

  public function setSSL($certificate = null) {
    $this->proto         = 'https';
    $this->CACertificate = $certificate;
  }

  public function __call($method, $params) {

    $this->status       = null;
    $this->error        = null;
    $this->rawResponse  = null;
    $this->response     = null;

    $params = array_values($params);

    $this->id++;

    $request = json_encode(array(
      'method' => $method,
      'params' => $params,
      'id'     => $this->id
    ));

    $curl    = curl_init("{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
    $options = array(
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        CURLOPT_USERPWD        => $this->username . ':' . $this->password,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_HTTPHEADER     => array('Content-type: text/plain'),
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $request
    );

    if (ini_get('open_basedir')) {
      unset($options[CURLOPT_FOLLOWLOCATION]);
    }

    if ($this->proto == 'https') {
      if (!empty($this->CACertificate)) {
        $options[CURLOPT_CAINFO] = $this->CACertificate;
        $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
      } else {
        $options[CURLOPT_SSL_VERIFYPEER] = false;
      }
    }

    curl_setopt_array($curl, $options);

    $this->rawResponse = curl_exec($curl);
    $this->response     = json_decode($this->rawResponse, true);

    $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    $curl_error = curl_error($curl);

    curl_close($curl);

    if (!empty($curl_error)) {
        $this->error = $curl_error;
    }

    if ($this->response['error']) {
        $this->error = $this->response['error']['message'];
    } elseif ($this->status != 200) {

      switch ($this->status) {
        case 400:
          $this->error = 'HTTP_BAD_REQUEST';
          break;
        case 401:
          $this->error = 'HTTP_UNAUTHORIZED';
          break;
        case 403:
          $this->error = 'HTTP_FORBIDDEN';
          break;
        case 404:
          $this->error = 'HTTP_NOT_FOUND';
          break;
      }
    }

    if ($this->error) {
      return false;
    }

    return $this->response['result'];
  }
}
