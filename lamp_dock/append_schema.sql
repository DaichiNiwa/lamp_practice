CREATE TABLE histories ( 
    `history_id` INT(11) NOT NULL AUTO_INCREMENT , 
    `item_id` INT(11) NOT NULL , 
    `amount` INT(11) NOT NULL , 
    `price` INT(11) NOT NULL ,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , 
    PRIMARY KEY (`history_id`));