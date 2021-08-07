<?php

require_once('config.php');
require_once('library/mysql.php');
require_once('library/keva.php');
require_once('library/hash.php');
require_once('library/base58.php');
require_once('library/base58check.php');
require_once('library/crypto.php');

$db   = new MySQL();
$node = new Keva();

$node->host     = KEVA_HOST;
$node->username = KEVA_USERNAME;
$node->password = KEVA_PASSWORD;
$node->port     = KEVA_PORT;

$blockLast  = $db->getLastBlock();
$blockTotal = $node->getblockcount();

$response = [];

if ($blockTotal > $blockLast) {

  for ($blockCurrent = $blockLast; $blockCurrent <= $blockLast + STEP_BLOCK_LIMIT; $blockCurrent++) {

    if ($blockHash = $node->getblockhash($blockCurrent)) {

      $blockData = $node->getblock($blockHash);

      if (!$blockId = $db->getBlock($blockCurrent)) {
           $blockId = $db->addBlock($blockCurrent);
      }

      foreach ($blockData['tx'] as $transaction) {

        $transactionRaw = $node->getrawtransaction($transaction, 1);

        foreach($transactionRaw['vout'] as $vout) {

          $asmArray = explode(' ', $vout['scriptPubKey']['asm']);

          if($asmArray[0] == 'OP_KEVA_NAMESPACE' || $asmArray[0] == 'OP_KEVA_PUT') { // OP_KEVA_DELETE

            $hash      = Base58Check::encode($asmArray[1], false , 0 , false);
            $nameSpace = $node->keva_get($hash, '_KEVA_NS_');

            $nameSpaceValue = strip_tags(html_entity_decode($nameSpace['value'], ENT_QUOTES, 'UTF-8'));

            if ((empty(KEVA_NS) || (!empty(KEVA_NS) && KEVA_NS == $nameSpaceValue))) {

               if (!$nameSpaceId = $db->getNameSpace($hash)) {
                    $nameSpaceId = $db->addNameSpace($hash, $nameSpaceValue);
               }

               if (!$db->getData($blockId, $nameSpaceId)) {
                    $db->addData($blockId,
                                 $nameSpaceId,
                                 $transactionRaw['time'],
                                 $transactionRaw['size'],
                                 $transactionRaw['txid'],
                                 strip_tags(html_entity_decode(@hex2bin($asmArray[2]), ENT_QUOTES, 'UTF-8')),
                                 strip_tags(html_entity_decode(@hex2bin($asmArray[3]), ENT_QUOTES, 'UTF-8')));
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
                   'title'     => $nameSpaceValue,
                   'key'       => strip_tags(html_entity_decode(@hex2bin($asmArray[2]), ENT_QUOTES, 'UTF-8')),
                   'vale'      => strip_tags(html_entity_decode(@hex2bin($asmArray[3]), ENT_QUOTES, 'UTF-8'))
                 ];
               }
            }
          }
        }
      }

    } else {

      // @TODO block not found
    }
  }
}

// Debug
if (CRAWLER_DEBUG) {
  print_r($response);
}
