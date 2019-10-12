<?php
function h($word){
    return htmlspecialchars($word, ENT_QUOTES, 'UTF-8');
  }

$tanaka = 132;
    

$yamada = 132;

function a($a, $b){
    return $a === $b;
}

var_dump(substr(base_convert(hash('sha256', uniqid()), 16, 36), 0, 48));
var_dump(sha1(uniqid(mt_rand(), true)));