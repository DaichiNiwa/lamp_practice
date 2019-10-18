<?php
require_once '../conf/const.php';
require_once MODEL_PATH . 'functions.php';
require_once MODEL_PATH . 'user.php';
require_once MODEL_PATH . 'item.php';
require_once MODEL_PATH . 'cart.php';

session_start();

if(is_logined() === false){
  redirect_to(LOGIN_URL);
}

$history_id = get_get('history_id');
// リダイレクト、正規表現で数字か確認

$db = get_db_connect();
$user = get_login_user($db);

// 正しいユーザーか✓管理者か
$history_details = get_history_details($db, $history_id);
$total_price = sum_purchased_carts($history_details);
include_once '../view/history_detail_view.php';