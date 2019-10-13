-- 購入履歴のテーブル
CREATE TABLE histories ( 
    `history_id` INT(11) NOT NULL AUTO_INCREMENT , 
    `user_id` INT(11) NOT NULL , 
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , 
    PRIMARY KEY (`history_id`));

-- 購入された商品の詳細のテーブル
CREATE TABLE purchased_carts ( 
    `purchased_id` INT(11) NOT NULL AUTO_INCREMENT ,
    `history_id` INT(11) NOT NULL,
    `item_id` INT(11) NOT NULL , 
    `amount` INT(11) NOT NULL , 
    `purchased_price` INT(11) NOT NULL ,
    PRIMARY KEY (`purchased_id`));