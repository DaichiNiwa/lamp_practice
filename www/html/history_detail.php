<?php
require_once '../conf/const.php';
require_once MODEL_PATH . 'functions.php';
require_once MODEL_PATH . 'user.php';
require_once MODEL_PATH . 'item.php';
require_once MODEL_PATH . 'cart.php';
require_once MODEL_PATH . 'history.php';
require_once MODEL_PATH . 'history_detail.php';

session_start();

if(is_logined() === false){
  redirect_to(LOGIN_URL);
}

$history_id = get_get('history_id');
// 正規表現で、数字でない場合と0の場合は履歴一覧へリダイレクト
if(is_positive_integer($history_id) === false ||
  $history_id == 0){
  redirect_to(HISTORY_URL);
}

$db = get_db_connect();
$user = get_login_user($db);

$history = get_history($db, $history_id);
$history_details = get_history_details($db, $history_id);

// 購入したユーザーではなく、管理者でもない場合リダイレクト
if($user['user_id'] !== $history['user_id'] &&
  !is_admin($user)){
  redirect_to(HISTORY_URL);
}

include_once '../view/history_detail_view.php';