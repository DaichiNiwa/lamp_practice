<?php
require_once '../conf/const.php';
require_once '../model/functions.php';
require_once '../model/user.php';
require_once '../model/item.php';

session_start();

if(is_logined() === false){
  redirect_to(LOGIN_URL);
}

$token = get_csrf_token();

$db = get_db_connect();
$user = get_login_user($db);

// 総商品数を取得
$all_items_amount = get_all_items_amount($db);
// 総ページ数を算出
$total_pages_number = ceil($all_items_amount / DISPLAY_ITEMS_NUMBER);

// ゲットで現在のページ番号を取得。ないときはトップページとして１
if (isset($_GET['current_page'])){
  $current_page = (int)get_get('current_page');
} else{
  $current_page = 1;
}

// 商品一覧の一番最初の商品の番号を算出
$list_start_number = DISPLAY_ITEMS_NUMBER * ($current_page - 1);
// 表示する商品を８つ取得
$items = get_open_items($db, $list_start_number);

// 「xx件中 xx - xx件の商品」の表示のためのテキスト
$items_count_text = 
  $all_items_amount 
  . '件中 ' 
  . ($list_start_number + 1) 
  . '-' 
  . ($list_start_number + 8) 
  . '件の商品'
;

include_once '../view/index_view.php';