CREATE TABLE histories ( 
    `history_id` INT(11) NOT NULL AUTO_INCREMENT , 
    `user_id` INT(11) NOT NULL , 
    `item_id` INT(11) NOT NULL , 
    `amount` INT(11) NOT NULL , 
    `purchased_price` INT(11) NOT NULL ,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , 
    PRIMARY KEY (`history_id`));