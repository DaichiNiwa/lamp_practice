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
  //購入履歴テーブルへのデータ保存
  if(insert_history($db, $carts[0]['user_id']) === false){
    set_error('購入に失敗しました。');
    $db->rollback();
    return false;
  }

  // 上の購入履歴のhistory_idを取得
  $history_id = $db->lastInsertId();
  foreach($carts as $cart){
    // 購入詳細テーブルへのデータ保存
    if(insert_purchased_cart(
        $db,
        $history_id, 
        $cart['item_id'], 
        $cart['amount'], 
        $cart['price']
      ) === false){
      set_error($cart['name'] . 'の購入に失敗しました。');
      $db->rollback();
      return false;
    }
    // 在庫数を減らす
    if(update_item_stock(
        $db, 
        $cart['item_id'], 
        $cart['stock'] - $cart['amount']
      ) === false){
      set_error($cart['name'] . 'の購入に失敗しました。');
      $db->rollback();
      return false;
    }
  }
  
  // カート内の商品をすべて削除
  if(delete_user_carts($db, $carts[0]['user_id']) === false){
    set_error('購入に失敗しました。');
    $db->rollback();
    return false;
  }

  $db->commit();
  return true;
}

//購入履歴テーブルへのデータ保存
function insert_history($db, $user_id){
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
function insert_purchased_cart($db, $history_id, $item_id, $amount, $purchased_price){
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
    ':item_id' => $item_id,
    ':amount' => $amount,
    ':purchased_price' => $purchased_price
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

function sum_purchased_carts($carts){
  $total_price = 0;
  foreach($carts as $cart){
    $total_price += $cart['purchased_price'] * $cart['amount'];
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

// 新しいファイル2つ作ってそちらに分離
function get_histories($db, $user_id){
  $sql = "
  SELECT
	  histories.history_id,
    SUM(amount * purchased_price) as sum,
    histories.created
  FROM
    histories
  JOIN
    purchased_carts
  ON
    histories.history_id = purchased_carts.history_id
  WHERE
    histories.user_id = :user_id
  GROUP BY
    histories.history_id
  ORDER BY
    created
  DESC
  ";

  $params = array(
    ':user_id' => $user_id
  );

  return fetch_all_query($db, $sql, $params);
}

function get_history_details($db, $history_id){
  $sql = "
    SELECT
      purchased_carts.purchased_id,
      purchased_carts.history_id,
      purchased_carts.amount,
      purchased_carts.purchased_price,
      purchased_carts.created,
      items.name
    FROM
      purchased_carts
    JOIN 
      items 
    ON 
      purchased_carts.item_id = items.item_id
    WHERE
      purchased_carts.history_id = :history_id
  ";

  $params = array(
    ':history_id' => $history_id
  );

  return fetch_all_query($db, $sql, $params);
}

// get_historyでひとつ取得する