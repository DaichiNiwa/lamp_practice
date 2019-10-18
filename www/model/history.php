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
