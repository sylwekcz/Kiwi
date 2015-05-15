DROP TABLE `card`;
DROP TABLE `accounts`;
DROP TABLE `sessions`;


CREATE TABLE `cards`
(
  `card_id`      INT         NOT NULL AUTO_INCREMENT,

  `first_name`   VARCHAR(25) NOT NULL,
  `middle_name`  VARCHAR(25) NOT NULL,
  `surname`      VARCHAR(25) NOT NULL,
  `birth_date`   DATETIME    NOT NULL,

  `phone_number` VARCHAR(12) NOT NULL,

  `address`      VARCHAR(10) NOT NULL,
  `city`         VARCHAR(25) NOT NULL,
  `postal_code`  VARCHAR(5)  NOT NULL,
  `street`       VARCHAR(25) NOT NULL,


  PRIMARY KEY (`card_id`),
  UNIQUE (`first_name`, `middle_name`, `surname`, `birth_date`),
  UNIQUE (`phone_number`)
);

CREATE TABLE `accounts`
(
  `account_id`    INT         NOT NULL AUTO_INCREMENT,

  `login`         VARCHAR(16) NOT NULL,
  `email`         VARCHAR(30) NOT NULL,

  `password_hash` VARCHAR(31) NOT NULL,
  `password_salt` VARCHAR(22) NOT NULL,

  `card_id`       INT         NOT NULL,


  PRIMARY KEY (`account_id`),
  FOREIGN KEY (`card_id`) REFERENCES `cards` (`card_id`),

  UNIQUE (`login`),
  UNIQUE (`email`),
  UNIQUE (`password_hash`, `password_salt`),
  UNIQUE (`card_id`)
)
  ENGINE = INNODB;

CREATE TABLE `sessions`
(
  `session_id`    INT         NOT NULL AUTO_INCREMENT,
  `account_id`    INT         NOT NULL,

  `session_key`   VARCHAR(32) NOT NULL,

  `last_activity` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,

  `browser_ip`    VARCHAR(11) NOT NULL,
  `browser_agent` VARCHAR(64) NOT NULL,


  PRIMARY KEY (`session_id`),
  FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`),

  UNIQUE (`session_key`)
)
  ENGINE = INNODB;