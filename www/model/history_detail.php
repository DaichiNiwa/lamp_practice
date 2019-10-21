<?php
function get_history($db, $history_id, $user_id){
	$sql = "
	SELECT
		histories.history_id,
		histories.user_id,
		SUM(amount * purchased_price) as sum,
		histories.created
	FROM
		histories
	JOIN
		purchased_carts
	ON
		histories.history_id = purchased_carts.history_id
	WHERE
    histories.history_id = :history_id
  ";

  $params = array(
    ':history_id' => $history_id,
  );

  if($user_id !== ADMIN_USER_ID){
    $sql .= "
    AND
      histories.user_id = :user_id
    ";
    $params[':user_id'] = $user_id;
  }
  
  $sql .= "
    GROUP BY
      histories.history_id
	";

	return fetch_query($db, $sql, $params);
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