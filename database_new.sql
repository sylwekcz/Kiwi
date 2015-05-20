DROP TABLE `cards`;
DROP TABLE `accounts`;
DROP TABLE `sessions`;


/* ACCOUNTS AND RELATED */
CREATE TABLE `cards`
(
  `card_id`      INT         NOT NULL AUTO_INCREMENT,

  `first_name`   VARCHAR(25) NOT NULL,
  `middle_name` VARCHAR(25) DEFAULT NULL,
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
)
  ENGINE = INNODB;

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

  `session_key` VARCHAR(31) NOT NULL,

  `last_activity` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,

  `browser_ip`  VARCHAR(15) NOT NULL,
  `browser_id`  VARCHAR(32) NOT NULL,


  PRIMARY KEY (`session_id`),
  FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`),

  UNIQUE (`account_id`),
  UNIQUE (`session_key`)
)
  ENGINE = INNODB;


/* LECTOR AND RELATED */
CREATE TABLE `languages`
(
  `language_id` INT         NOT NULL AUTO_INCREMENT,

  `name`        VARCHAR(20) NOT NULL,
  `level`       CHAR(2)     NOT NULL,


  PRIMARY KEY (`language_id`),

  UNIQUE (`name`, `level`)
)
  ENGINE = INNODB;

CREATE TABLE `teachers` (
  `teacher_id` INT NOT NULL AUTO_INCREMENT,
  `account_id` INT NOT NULL,


  PRIMARY KEY (`teacher_id`),
  FOREIGN KEY (`account_id`) REFERENCES accounts (`account_id`),

  UNIQUE (`account_id`)
)
  ENGINE = INNODB;


/* COURSE AND RELATED */
CREATE TABLE `courses` (
  `course_id`   INT NOT NULL AUTO_INCREMENT,

  `teacher_id`  INT NOT NULL,
  `language_id` INT NOT NULL,


  PRIMARY KEY (`course_id`),
  FOREIGN KEY (`teacher_id`) REFERENCES teachers (`teacher_id`),
  FOREIGN KEY (`language_id`) REFERENCES languages (`language_id`),

  UNIQUE (`teacher_id`, `language_id`)
)
  ENGINE = INNODB;

CREATE TABLE `rooms` (
  `room_id`  INT        NOT NULL AUTO_INCREMENT,

  `number`   VARCHAR(5) NOT NULL,
  `capacity` INT        NOT NULL,


  PRIMARY KEY (`room_id`),

  UNIQUE (`number`)
)
  ENGINE = INNODB;