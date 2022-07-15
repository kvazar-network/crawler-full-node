<?php

class MySQL {

  private $_db;

  public function __construct($host, $port, $database, $username, $password) {

    try {

      $this->_db = new PDO('mysql:dbname=' . $database . ';host=' . $host . ';port=' . $port . ';charset=utf8', $username, $password, [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
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

      return (int) $query['lastBlock'];

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

  public function getData($txId) {

    try {

      $query = $this->_db->prepare('SELECT `dataId` FROM `data` WHERE `txId` = ? LIMIT 1');

      $query->execute([$txId]);

      return $query->rowCount() ? $query->fetch()['dataId'] : false;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function addBlock($blockId) {

    try {

      $query = $this->_db->prepare('INSERT INTO `block` SET `blockId`          = ?,
                                                            `lostTransactions` = 0,
                                                            `timeIndexed`      = UNIX_TIMESTAMP()');

      $query->execute([$blockId]);

      return $blockId;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function setLostTransactions($blockId, $lostTransactions) {

    try {

      $query = $this->_db->prepare('UPDATE `block` SET `lostTransactions` = ? WHERE `blockId` = ? LIMIT 1');

      $query->execute([$lostTransactions, $blockId]);

      return $blockId;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function addNameSpace($hash) {

    try {

      $query = $this->_db->prepare('INSERT INTO `namespace` SET `hash`  = ?,
                                                                `timeIndexed` = UNIX_TIMESTAMP()');

      $query->execute([$hash]);

      return $this->_db->lastInsertId();

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function addData($blockId, $nameSpaceId, $time, $size, $txid, $key, $value, $ns, $deleted = false) {

    try {

      $query = $this->_db->prepare('INSERT INTO `data` SET `blockId`     = :blockId,
                                                           `nameSpaceId` = :nameSpaceId,
                                                           `time`        = :time,
                                                           `size`        = :size,
                                                           `txid`        = :txid,
                                                           `key`         = :key,
                                                           `value`       = :value,
                                                           `deleted`     = :deleted,
                                                           `ns`          = :ns,
                                                           `timeIndexed` = UNIX_TIMESTAMP()');

      $query->bindValue(':blockId', $blockId, PDO::PARAM_INT);
      $query->bindValue(':nameSpaceId', $nameSpaceId, PDO::PARAM_INT);
      $query->bindValue(':time', $time, PDO::PARAM_INT);
      $query->bindValue(':size', $size, PDO::PARAM_INT);
      $query->bindValue(':txid', $txid, PDO::PARAM_STR);
      $query->bindValue(':key', $key, PDO::PARAM_STR);
      $query->bindValue(':value', $value, PDO::PARAM_STR);
      $query->bindValue(':deleted', (int) $deleted, PDO::PARAM_STR);
      $query->bindValue(':ns', (int) $ns, PDO::PARAM_STR);

      $query->execute();

      return $this->_db->lastInsertId();

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function setDataKeyDeleted($nameSpaceId, $key, $deleted) {

    try {

      $query = $this->_db->prepare('UPDATE `data` SET   `deleted`     = :deleted
                                                  WHERE `nameSpaceId` = :nameSpaceId AND `key` LIKE :key');

      $query->bindValue(':nameSpaceId', $nameSpaceId, PDO::PARAM_INT);
      $query->bindValue(':key', $key, PDO::PARAM_STR);
      $query->bindValue(':deleted', (int) $deleted, PDO::PARAM_STR);

      $query->execute();

      return $query->rowCount();

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }
}
