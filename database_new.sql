/* ACCOUNTS AND RELATED */
DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts`
(
  `account_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,

  `login`         VARCHAR(16)  NOT NULL,
  `email`         VARCHAR(30)  NOT NULL,

  `password_hash` CHAR(31)     NOT NULL,
  `password_salt` CHAR(22)     NOT NULL,

  `name`          VARCHAR(25)  NOT NULL,
  `surname`       VARCHAR(30)  NOT NULL,

  `birth_date`    DATE         NOT NULL,


  PRIMARY KEY (`account_id`),

  UNIQUE (`login`),
  UNIQUE (`email`),
  UNIQUE (`password_hash`, `password_salt`)
)
  ENGINE = INNODB;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions`
(
  `session_id`    INT  UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id`    INT  UNSIGNED NOT NULL,

  `session_key`   CHAR(31)      NOT NULL,

  `last_activity` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  `browser_ip`    CHAR(15)      NOT NULL,
  `browser_id`    CHAR(32)      NOT NULL,


  PRIMARY KEY (`session_id`),

  FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  UNIQUE (`account_id`),
  UNIQUE (`session_key`)
)
  ENGINE = INNODB;


/* LECTOR AND RELATED */
DROP TABLE IF EXISTS `languages`;
CREATE TABLE `languages`
(
  `language_id` INT    UNSIGNED NOT NULL AUTO_INCREMENT,

  `name`        VARCHAR(20)     NOT NULL,
  `level`       CHAR(2)         NOT NULL,


  PRIMARY KEY (`language_id`),

  UNIQUE (`name`, `level`)
)
  ENGINE = INNODB;

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers`
(
  `teacher_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id` INT UNSIGNED NOT NULL,


  PRIMARY KEY (`teacher_id`),

  FOREIGN KEY (`account_id`) REFERENCES accounts (`account_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  UNIQUE (`account_id`)
)
  ENGINE = INNODB;


/* COURSE AND RELATED */
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses`
(
  `course_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,

  `teacher_id`  INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,


  PRIMARY KEY (`course_id`),

  FOREIGN KEY (`teacher_id`) REFERENCES teachers (`teacher_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  FOREIGN KEY (`language_id`) REFERENCES languages (`language_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  UNIQUE (`teacher_id`, `language_id`)
)
  ENGINE = INNODB;

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms`
(
  `room_id`  INT   UNSIGNED     NOT NULL AUTO_INCREMENT,

  `number`   VARCHAR(5)         NOT NULL,
  `capacity` SMALLINT  UNSIGNED NOT NULL,


  PRIMARY KEY (`room_id`),

  UNIQUE (`number`)
)
  ENGINE = INNODB;


DROP TABLE IF EXISTS `students`;
CREATE TABLE `students`
(
  `student_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id` INT UNSIGNED NOT NULL,


  PRIMARY KEY (`student_id`),

  FOREIGN KEY (`account_id`) REFERENCES accounts (`account_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  UNIQUE (`account_id`)
)
  ENGINE = INNODB;

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups`
(
  `group_id`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` INT UNSIGNED NOT NULL,


  PRIMARY KEY (`group_id`),

  FOREIGN KEY (`course_id`) REFERENCES courses (`course_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  UNIQUE (`course_id`)
)
  ENGINE = INNODB;

DROP TABLE IF EXISTS `group_assignments`;
CREATE TABLE `group_assignments`
(
  `group_id`   INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,


  FOREIGN KEY (`group_id`) REFERENCES groups (`group_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  FOREIGN KEY (`student_id`) REFERENCES students (`student_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  UNIQUE (`group_id`, `student_id`)
)
  ENGINE = INNODB;

DROP TABLE IF EXISTS `group_meetings`;
CREATE TABLE `group_meetings`
(
  `group_id` INT UNSIGNED                                                     NOT NULL,
  `room_id`  INT UNSIGNED                                                     NOT NULL,

  `weekday`  ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', '') NOT NULL,
  `time`     TIME(5)                                                          NOT NULL,
  `duration` SMALLINT UNSIGNED                                                NOT NULL,


  FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  UNIQUE (`room_id`, `weekday`, `time`)
)