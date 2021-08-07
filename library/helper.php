<?php

function decodeString($string) {

  if (is_numeric($string) && $string < 0xFFFFFFFF) {
    return mb_chr($string, 'ASCII');
  } else {
    return hex2bin($string);
  }
}

function filterString($string) {

  return strip_tags(html_entity_decode($string, ENT_QUOTES, 'UTF-8'));
}
