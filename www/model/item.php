<?php
require_once 'functions.php';
require_once 'db.php';

// DB利用

function get_item($db, $item_id){
  $sql = "
    SELECT
      item_id, 
      name,
      stock,
      price,
      image,
      status
    FROM
      items
    WHERE
      item_id = :item_id
  ";

  $params = array(
    ':item_id' => $item_id
  );

  return fetch_query($db, $sql, $params);
}

function get_items($db, $is_open = false, $list_start_number = 0){
  $sql = '
    SELECT
      item_id, 
      name,
      stock,
      price,
      image,
      status
    FROM
      items
  ';

  // $is_openがfalseのときは管理画面での全商品表示、
  // trueのときは商品一覧画面で８つずつ表示するのを想定
  if($is_open === true){
    $sql .= '
      WHERE status = 1
      LIMIT :list_start_number,
    ' . DISPLAY_ITEMS_NUMBER;
    $params = array(
      ':list_start_number' => $list_start_number
    );
  }
  return fetch_all_query($db, $sql, $params);
}

function get_all_items($db){
  return get_items($db);
}

function get_open_items($db, $current_page){
  $list_start_number = DISPLAY_ITEMS_NUMBER * ($current_page - 1);
  return get_items($db, true, $list_start_number);
}

function get_all_items_amount($db){
  $sql = '
    SELECT
      COUNT(item_id) as count
    FROM
      items
    WHERE status = 1
  ';
  $all_items_amount = fetch_query($db, $sql);
  return $all_items_amount['count'];
}

function regist_item($db, $name, $price, $stock, $status, $image){
  $filename = get_upload_filename($image);
  if(validate_item($name, $price, $stock, $filename, $status) === false){
    return false;
  }
  return regist_item_transaction($db, $name, $price, $stock, $status, $image, $filename);
}

function regist_item_transaction($db, $name, $price, $stock, $status, $image, $filename){
  $db->beginTransaction();
  if(insert_item($db, $name, $price, $stock, $filename, $status) 
    && save_image($image, $filename)){
    $db->commit();
    return true;
  }
  $db->rollback();
  return false;
  
}

function insert_item($db, $name, $price, $stock, $filename, $status){
  $status_value = PERMITTED_ITEM_STATUSES[$status];
  $sql = "
    INSERT INTO
      items(
        name,
        price,
        stock,
        image,
        status
      )
    VALUES(:name, :price, :stock, :filename, :status_value);
  ";

  $params = array(
    ':name' => $name,
    ':price' => $price,
    ':stock' => $stock,
    ':filename' => $filename,
    ':status_value' => $status_value,
  );

  return execute_query($db, $sql, $params);
}

function update_item_status($db, $item_id, $status){
  $sql = "
    UPDATE
      items
    SET
      status = :status
    WHERE
      item_id = :item_id
    LIMIT 1
  ";

  $params = array(
    ':status' => $status,
    ':item_id' => $item_id
  );

  return execute_query($db, $sql, $params);
}

function update_item_stock($db, $item_id, $stock){
  $sql = "
    UPDATE
      items
    SET
      stock = :stock
    WHERE
      item_id = :item_id
    LIMIT 1
  ";
  
  $params = array(
    ':stock' => $stock,
    ':item_id' => $item_id
  );

  return execute_query($db, $sql, $params);
}

function destroy_item($db, $item_id){
  $item = get_item($db, $item_id);
  if($item === false){
    return false;
  }
  $db->beginTransaction();
  if(delete_item($db, $item['item_id'])
    && delete_image($item['image'])){
    $db->commit();
    return true;
  }
  $db->rollback();
  return false;
}

function delete_item($db, $item_id){
  $sql = "
    DELETE FROM
      items
    WHERE
      item_id = :item_id
    LIMIT 1
  ";

  $params = array(
    ':item_id' => $item_id
  );
  
  return execute_query($db, $sql, $params);
}


// 非DB

function is_open($item){
  return $item['status'] === 1;
}

function validate_item($name, $price, $stock, $filename, $status){
  $is_valid_item_name = is_valid_item_name($name);
  $is_valid_item_price = is_valid_item_price($price);
  $is_valid_item_stock = is_valid_item_stock($stock);
  $is_valid_item_filename = is_valid_item_filename($filename);
  $is_valid_item_status = is_valid_item_status($status);

  return $is_valid_item_name
    && $is_valid_item_price
    && $is_valid_item_stock
    && $is_valid_item_filename
    && $is_valid_item_status;
}

function is_valid_item_name($name){
  $is_valid = true;
  if(is_valid_length($name, ITEM_NAME_LENGTH_MIN, ITEM_NAME_LENGTH_MAX) === false){
    set_error('商品名は'. ITEM_NAME_LENGTH_MIN . '文字以上、' . ITEM_NAME_LENGTH_MAX . '文字以内にしてください。');
    $is_valid = false;
  }
  return $is_valid;
}

function is_valid_item_price($price){
  $is_valid = true;
  if(is_positive_integer($price) === false){
    set_error('価格は0以上の整数で入力してください。');
    $is_valid = false;
  }
  return $is_valid;
}

function is_valid_item_stock($stock){
  $is_valid = true;
  if(is_positive_integer($stock) === false){
    set_error('在庫数は0以上の整数で入力してください。');
    $is_valid = false;
  }
  return $is_valid;
}

function is_valid_item_filename($filename){
  $is_valid = true;
  if($filename === ''){
    $is_valid = false;
  }
  return $is_valid;
}

function is_valid_item_status($status){
  $is_valid = true;
  if(isset(PERMITTED_ITEM_STATUSES[$status]) === false){
    $is_valid = false;
  }
  return $is_valid;
}

// 「xx件中 xx - xx件の商品」の表示のためのテキストを生成
function make_items_count_text($all_items_amount, $current_page){
  $list_start_number = DISPLAY_ITEMS_NUMBER * ($current_page - 1) + 1;
  $list_end_number = $current_page * DISPLAY_ITEMS_NUMBER;
  // 商品が1つもない場合
  if($all_items_amount === 0){
    return '商品がありません';
  }
  // 最終ページで商品が１つしかない場合
  if($all_items_amount === $list_start_number){
    return "{$all_items_amount}件中 {$all_items_amount}件目の商品";
  }
  // 最終ページの場合
  if($all_items_amount < $list_end_number){
    return "{$all_items_amount}件中 {$list_start_number} - {$all_items_amount}件目の商品";
  }
  // 通常のページ  
  return "{$all_items_amount}件中 {$list_start_number} - {$list_end_number}件目の商品";
}

function calculate_total_pages_number($all_items_amount){
  return ceil($all_items_amount / DISPLAY_ITEMS_NUMBER);
}