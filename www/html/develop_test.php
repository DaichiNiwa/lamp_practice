<?php
function h($word){
    return htmlspecialchars($word, ENT_QUOTES, 'UTF-8');
  }

$tanaka = "<h1>hello</h1>";
    echo $tanaka;

$yamada = "<h1>happy</h1>";

echo h($yamada);
