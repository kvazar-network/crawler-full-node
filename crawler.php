<?php

$semaphore = sem_get(crc32('kvazar-network.crawler_full_node'), 1);

if (false === sem_acquire($semaphore, true)) {

  echo "database locked by the another process..." . PHP_EOL;
  exit;
}

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/library/sqlite.php');
require_once(__DIR__ . '/library/kevacoin.php');
require_once(__DIR__ . '/library/hash.php');
require_once(__DIR__ . '/library/base58.php');
require_once(__DIR__ . '/library/base58check.php');
require_once(__DIR__ . '/library/crypto.php');
require_once(__DIR__ . '/library/helper.php');

$db       = new SQLite(DB_NAME, DB_USERNAME, DB_PASSWORD);
$kevaCoin = new KevaCoin(KEVA_PROTOCOL, KEVA_HOST, KEVA_PORT, KEVA_USERNAME, KEVA_PASSWORD);

$blockLast  = $db->getLastBlock();
$blockTotal = $kevaCoin->getblockcount();

if (false === $blockTotal) {
  echo "API connection error." . PHP_EOL;
  exit;
}

$response = [];

if (CRAWLER_DEBUG) {
  echo "scanning blockhain..." . PHP_EOL;
}

for ($blockCurrent = ($blockLast + 1); $blockCurrent <= $blockLast + STEP_BLOCK_LIMIT; $blockCurrent++) {

  if ($blockCurrent > $blockTotal) {

    if (CRAWLER_DEBUG) {
      echo "database is up to date." . PHP_EOL;
    }

    break;
  }

  if (CRAWLER_DEBUG) {
    echo sprintf("reading block %s", $blockCurrent) . PHP_EOL;
  }

  if (!$blockHash = $kevaCoin->getblockhash($blockCurrent)) {

    if (CRAWLER_DEBUG) {
      echo "could not read the block hash. waiting for reconnect." . PHP_EOL;
    }

    break;
  }

  if (!$blockData = $kevaCoin->getblock($blockHash)) {

    if (CRAWLER_DEBUG) {
      echo "could not read the block data. waiting for reconnect." . PHP_EOL;
    }

    break;
  }

  if (!$blockId = $db->getBlock($blockCurrent)) {
        $blockId = $db->addBlock($blockCurrent);

        if (CRAWLER_DEBUG) {
          echo sprintf("add block %s", $blockCurrent) . PHP_EOL;
        }
  }

  $lostTransactions = 0;

  foreach ($blockData['tx'] as $transaction) {

    if (!$transactionRaw = $kevaCoin->getrawtransaction($transaction)) {

      $lostTransactions++;

      $db->setLostTransactions($blockId, $lostTransactions);

      if (CRAWLER_DEBUG) {
        echo sprintf("could not read the transaction %s. skipped.", $transaction) . PHP_EOL;
      }

      break;
    }

    foreach($transactionRaw['vout'] as $vout) {

      $asmArray = explode(' ', $vout['scriptPubKey']['asm']);

      if (in_array($asmArray[0], ['OP_KEVA_NAMESPACE', 'OP_KEVA_PUT', 'OP_KEVA_DELETE'])) {

        $hash = Base58Check::encode($asmArray[1], false , 0 , false);

        switch ($asmArray[0]) {

          case 'OP_KEVA_DELETE':

            $key   = filterString(decodeString($asmArray[2]));
            $value = '';

          break;

          case 'OP_KEVA_NAMESPACE':

            $key   = '_KEVA_NS_';
            $value = filterString(decodeString($asmArray[2]));

          break;

          default:

            $key   = filterString(decodeString($asmArray[2]));
            $value = filterString(decodeString($asmArray[3]));
        }

        if (!$nameSpaceId = $db->getNameSpace($hash)) {
              $nameSpaceId = $db->addNameSpace($hash);

              if (CRAWLER_DEBUG) {
                echo sprintf("add namespace %s", $hash) . PHP_EOL;
              }
        }

        if (!$dataId = $db->getData($transactionRaw['txid'])) {
              $dataId = $db->addData($blockId,
                                    $nameSpaceId,
                                    $transactionRaw['time'],
                                    $transactionRaw['size'],
                                    $transactionRaw['txid'],
                                    $key,
                                    $value,
                                    ($key == '_KEVA_NS_'),
                                    empty($value));

          if ($value) {

            $db->setDataKeyDeleted($nameSpaceId, $key, false);

            if (CRAWLER_DEBUG) {
              echo sprintf("add new key/value %s", $transactionRaw['txid']) . PHP_EOL;
            }

          } else {

            $db->setDataKeyDeleted($nameSpaceId, $key, true);

            if (CRAWLER_DEBUG) {
              echo sprintf("delete key %s from namespace %s", $key, $hash) . PHP_EOL;
            }
          }
        }

        if (CRAWLER_DEBUG) {
          $response[] = [
            'blocktotal'=> $blockTotal,
            'block'     => $blockCurrent,
            'blockhash' => $transactionRaw['blockhash'],
            'txid'      => $transactionRaw['txid'],
            'version'   => $transactionRaw['version'],
            'size'      => $transactionRaw['size'],
            'time'      => $transactionRaw['time'],
            'blocktime' => $transactionRaw['blocktime'],
            'namehash'  => $hash,
            'key'       => $key,
            'value'     => $value
          ];
        }
      }
    }
  }
}


// Debug
if (CRAWLER_DEBUG) {
  echo "scanning completed." . PHP_EOL;
  # print_r($response);
}
