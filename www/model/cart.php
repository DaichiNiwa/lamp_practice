<?php 
require_once 'functions.php';
require_once 'db.php';

function get_user_carts($db, $user_id){
  $sql = "
    SELECT
      items.item_id,
      items.name,
      items.price,
      items.stock,
      items.status,
      items.image,
      carts.cart_id,
      carts.user_id,
      carts.amount
    FROM
      carts
    JOIN
      items
    ON
      carts.item_id = items.item_id
    WHERE
      carts.user_id = :user_id
  ";

  $params = array(
    ':user_id' => $user_id
  );

  return fetch_all_query($db, $sql, $params);
}

function get_user_cart($db, $user_id, $item_id){
  $sql = "
    SELECT
      items.item_id,
      items.name,
      items.price,
      items.stock,
      items.status,
      items.image,
      carts.cart_id,
      carts.user_id,
      carts.amount
    FROM
      carts
    JOIN
      items
    ON
      carts.item_id = items.item_id
    WHERE
      carts.user_id = :user_id
    AND
      items.item_id = :item_id
  ";

  $params = array(
    ':user_id' => $user_id,
    ':item_id' => $item_id
  );

  return fetch_query($db, $sql, $params);
}

function add_cart($db, $item_id, $user_id) {
  $cart = get_user_cart($db, $item_id, $user_id);
  if($cart === false){
    return insert_cart($db, $user_id, $item_id);
  }
  return update_cart_amount($db, $cart['cart_id'], $cart['amount'] + 1);
}

function insert_cart($db, $item_id, $user_id, $amount = 1){
  $sql = "
    INSERT INTO
      carts(
        item_id,
        user_id,
        amount
      )
    VALUES(:item_id, :user_id, :amount)
  ";

  $params = array(
    ':item_id' => $item_id,
    ':user_id' => $user_id,
    ':amount' => $amount
  );

  return execute_query($db, $sql, $params);
}

function update_cart_amount($db, $cart_id, $amount){
  $sql = "
    UPDATE
      carts
    SET
      amount = :amount
    WHERE
      cart_id = :cart_id
    LIMIT 1
  ";
  
  $params = array(
    ':amount' => $amount,
    ':cart_id' => $cart_id
  );

  return execute_query($db, $sql, $params);
}

function delete_cart($db, $cart_id){
  $sql = "
    DELETE FROM
      carts
    WHERE
      cart_id = :cart_id
    LIMIT 1
  ";

  $params = array(
    ':cart_id' => $cart_id
  );

  return execute_query($db, $sql, $params);
}

function purchase_carts($db, $carts){
  if(validate_cart_purchase($carts) === false){
    return false;
  }

  $db->beginTransaction();
  try{
    //購入履歴テーブルへのデータ保存
    if(insert_histories($db, $carts[0]['user_id']) === false){
      set_error('購入に失敗しました。');
    }

    // 上の購入履歴のhistory_idを取得
    $history_id = $db->lastInsertId();

    foreach($carts as $cart){
      // 購入詳細テーブルへのデータ保存と
      // 在庫数を減らす
      if(insert_purchased_carts($db, $history_id, $cart) === false ||
        update_item_stock(
          $db, 
          $cart['item_id'], 
          $cart['stock'] - $cart['amount']
        ) === false){
        set_error($cart['name'] . 'の購入に失敗しました。');
      }
    }
    
    // カート内の商品をすべて削除
    if(delete_user_carts($db, $carts[0]['user_id']) === false){
      set_error('購入に失敗しました。');
    }

    if(has_error() === true){
      return false;
    }
    $db->commit();
    return true;
  }catch(PDOException $e){
    $db->rollback();
    return false;
  }
}

//購入履歴テーブルへのデータ保存
function insert_histories($db, $user_id){
  $sql = "
    INSERT INTO
      histories(
        user_id
      )
    VALUES(:user_id)
  ";

  $params = array(':user_id' => $user_id);
  
  return execute_query($db, $sql, $params);
}

// 購入詳細テーブルへのデータ保存
function insert_purchased_carts($db, $history_id, $cart){
  $sql = "
    INSERT INTO
      purchased_carts(
        history_id,
        item_id,
        amount,
        purchased_price
      )
    VALUES(
      :history_id, 
      :item_id, 
      :amount,
      :purchased_price
    )
  ";

  $params = array(
    ':history_id' => $history_id,
    ':item_id' => $cart['item_id'],
    ':amount' => $cart['amount'],
    ':purchased_price' => $cart['price']
  );

  return execute_query($db, $sql, $params);
}

function delete_user_carts($db, $user_id){
  $sql = "
    DELETE FROM
      carts
    WHERE
      user_id = :user_id
  ";

  $params = array(
    ':user_id' => $user_id
  );
  
  execute_query($db, $sql, $params);
}


function sum_carts($carts){
  $total_price = 0;
  foreach($carts as $cart){
    $total_price += $cart['price'] * $cart['amount'];
  }
  return $total_price;
}

function validate_cart_purchase($carts){
  if(count($carts) === 0){
    set_error('カートに商品が入っていません。');
    return false;
  }
  foreach($carts as $cart){
    if(is_open($cart) === false){
      set_error($cart['name'] . 'は現在購入できません。');
    }
    if($cart['stock'] - $cart['amount'] < 0){
      set_error($cart['name'] . 'は在庫が足りません。購入可能数:' . $cart['stock']);
    }
  }
  if(has_error() === true){
    return false;
  }
  return true;
}

