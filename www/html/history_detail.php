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

$db = get_db_connect();
$history_id = get_get('history_id');

$history_details = get_history_detail($db, $history_id);
$total_price = sum_purchased_carts($history_details);
include_once '../view/history_detail_view.php';