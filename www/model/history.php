<?php
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
  ";

  // 管理者以外のユーザーは自分の購入した商品のみ閲覧できる。
  if($user_id !== ADMIN_USER_ID){
    $sql .= "
    WHERE
      histories.user_id = :user_id
    ";
    $params[':user_id'] = $user_id;
  }

  $sql .= "
  GROUP BY
    histories.history_id
  ORDER BY
    created
  DESC
	";

  return fetch_all_query($db, $sql, $params);
}
