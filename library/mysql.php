<?php

class MySQL {

  public function __construct() {

    try {

      $this->_db = new PDO('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8', DB_USERNAME, DB_PASSWORD, [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
      $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      $this->_db->setAttribute(PDO::ATTR_TIMEOUT, 600);

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
    }
  }

  public function getLastBlock() {

    try {

      $query = $this->_db->query('SELECT MAX(`blockId`) AS `lastBlock` FROM `block`')->fetch();

      return (int) $query['lastBlock'] ? $query['lastBlock'] : 1;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function getBlock($blockId) {

    try {

      $query = $this->_db->prepare('SELECT `blockId` FROM `block` WHERE `blockId` = ? LIMIT 1');

      $query->execute([$blockId]);

      return $query->rowCount() ? $query->fetch()['blockId'] : false;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function getNameSpace($hash) {

    try {

      $query = $this->_db->prepare('SELECT `nameSpaceId` FROM `namespace` WHERE `hash` = ? LIMIT 1');

      $query->execute([$hash]);

      return $query->rowCount() ? $query->fetch()['nameSpaceId'] : false;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function getData($blockId, $nameSpaceId) {

    try {

      $query = $this->_db->prepare('SELECT `dataId` FROM `data` WHERE `blockId` = ? AND `nameSpaceId` = ? LIMIT 1');

      $query->execute([$blockId, $nameSpaceId]);

      return $query->rowCount() ? $query->fetch()['dataId'] : false;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function addBlock($blockId) {

    try {

      $query = $this->_db->prepare('INSERT INTO `block` SET `blockId` = ?,
                                                            `timeIndexed` = UNIX_TIMESTAMP()');

      $query->execute([$blockId]);

      return $blockId;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function addNameSpace($hash, $value) {

    try {

      $query = $this->_db->prepare('INSERT INTO `namespace` SET `hash`  = ?,
                                                                `value` = ?,
                                                                `timeIndexed` = UNIX_TIMESTAMP()');

      $query->execute([$hash, $value]);

      return $this->_db->lastInsertId();

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function addData($blockId, $nameSpaceId, $time, $size, $txid, $key, $value) {

    try {

      $query = $this->_db->prepare('INSERT INTO `data` SET `blockId`     = ?,
                                                           `nameSpaceId` = ?,
                                                           `time`        = ?,
                                                           `size`        = ?,
                                                           `txid`        = ?,
                                                           `key`         = ?,
                                                           `value`       = ?,
                                                           `timeIndexed` = UNIX_TIMESTAMP()');

      $query->execute([$blockId, $nameSpaceId, $time, $size, $txid, $key, $value]);

      return $this->_db->lastInsertId();

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }
}
