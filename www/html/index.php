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
$total_pages_number = calculate_total_pages_number($all_items_amount);

// ゲットで現在のページ番号を取得
$current_page = (int)get_get('current_page', 1);

// 表示する商品を８つ取得
$items = get_open_items($db, $current_page);

// 「xx件中 xx - xx件の商品」の表示のためのテキストを生成
$items_count_text = make_items_count_text($all_items_amount, $current_page);

include_once '../view/index_view.php';