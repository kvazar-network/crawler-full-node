<?php

class SQLite {

  private $_db;

  public function __construct($database, $username, $password) {

    try {

      $this->_db = new PDO('sqlite:' . $database, $username, $password);
      $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      $this->_db->setAttribute(PDO::ATTR_TIMEOUT, 600);

      $this->_db->query('
        CREATE TABLE IF NOT EXISTS "namespace"(
          "nameSpaceId" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL CHECK("nameSpaceId">=0),
          "timeIndexed" INTEGER NOT NULL CHECK("timeIndexed">=0),
          "hash" CHAR(34) NOT NULL,
          CONSTRAINT "hash_UNIQUE"
            UNIQUE("hash")
        )
      ');

      $this->_db->query('
        CREATE TABLE IF NOT EXISTS "block"(
          "blockId" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL CHECK("blockId">=0),
          "timeIndexed" INTEGER NOT NULL CHECK("timeIndexed">=0),
          "lostTransactions" INTEGER NOT NULL CHECK("lostTransactions">=0)
        )
      ');

      $this->_db->query('
        CREATE TABLE IF NOT EXISTS "data"(
          "dataId" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL CHECK("dataId">=0),
          "nameSpaceId" INTEGER NOT NULL CHECK("nameSpaceId">=0),
          "blockId" INTEGER NOT NULL CHECK("blockId">=0),
          "time" INTEGER NOT NULL CHECK("time">=0),
          "timeIndexed" INTEGER NOT NULL CHECK("timeIndexed">=0),
          "size" INTEGER NOT NULL,
          "ns" TEXT NOT NULL CHECK("ns" IN(\'0\', \'1\')),
          "deleted" TEXT NOT NULL CHECK("deleted" IN(\'0\', \'1\')),
          "txid" CHAR(64) NOT NULL,
          "key" TEXT NOT NULL,
          "value" TEXT NOT NULL,
          CONSTRAINT "txid_UNIQUE"
            UNIQUE("txid"),
          CONSTRAINT "fk_data_namespace"
            FOREIGN KEY("nameSpaceId")
            REFERENCES "namespace"("nameSpaceId"),
          CONSTRAINT "fk_data_block"
            FOREIGN KEY("blockId")
            REFERENCES "block"("blockId")
        )

      ');

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

      $result = $query->fetch();

      return $result ? $result['blockId'] : false;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function getNameSpace($hash) {

    try {

      $query = $this->_db->prepare('SELECT `nameSpaceId` FROM `namespace` WHERE `hash` = ? LIMIT 1');

      $query->execute([$hash]);

      $result = $query->fetch();

      return $result ? $result['nameSpaceId'] : false;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function getData($txId) {

    try {

      $query = $this->_db->prepare('SELECT `dataId` FROM `data` WHERE `txId` = ? LIMIT 1');

      $query->execute([$txId]);

      $result = $query->fetch();

      return $result ? $result['dataId'] : false;

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function addBlock($blockId) {

    try {

      $query = $this->_db->prepare('INSERT INTO `block` (`blockId`, `lostTransactions`, `timeIndexed`)
                                           VALUES (?, 0, ?)');

      $query->execute([$blockId, time()]);

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

      $query = $this->_db->prepare('INSERT INTO `namespace` (`hash`, `timeIndexed`)
                                           VALUES (?, ?)');

      $query->execute([$hash, time()]);

      return $this->_db->lastInsertId();

    } catch(PDOException $e) {
      trigger_error($e->getMessage());
      return false;
    }
  }

  public function addData($blockId, $nameSpaceId, $time, $size, $txid, $key, $value, $ns, $deleted = false) {

    try {

      $query = $this->_db->prepare('INSERT INTO `data` (

                                                        `blockId`,
                                                        `nameSpaceId`,
                                                        `time`,
                                                        `size`,
                                                        `txid`,
                                                        `key`,
                                                        `value`,
                                                        `deleted`,
                                                        `ns`,
                                                        `timeIndexed`

                                                       )

                                           VALUES (

                                                   :blockId,
                                                   :nameSpaceId,
                                                   :time,
                                                   :size,
                                                   :txid,
                                                   :key,
                                                   :value,
                                                   :deleted,
                                                   :ns,
                                                   :timeIndexed

                                                  )');

      $query->bindValue(':blockId', $blockId, PDO::PARAM_INT);
      $query->bindValue(':nameSpaceId', $nameSpaceId, PDO::PARAM_INT);
      $query->bindValue(':time', $time, PDO::PARAM_INT);
      $query->bindValue(':size', $size, PDO::PARAM_INT);
      $query->bindValue(':txid', $txid, PDO::PARAM_STR);
      $query->bindValue(':key', $key, PDO::PARAM_STR);
      $query->bindValue(':value', $value, PDO::PARAM_STR);
      $query->bindValue(':deleted', (int) $deleted, PDO::PARAM_STR);
      $query->bindValue(':ns', (int) $ns, PDO::PARAM_STR);
      $query->bindValue(':timeIndexed', time(), PDO::PARAM_INT);

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
