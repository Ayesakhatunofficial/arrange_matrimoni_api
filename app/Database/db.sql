CREATE TABLE `net_dashboard_banners` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `banner` VARCHAR(255) NULL DEFAULT NULL,
    `is_active` TINYINT NOT NULL DEFAULT '1',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME on update CURRENT_TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

CREATE TABLE `net_contact_us` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NULL DEFAULT NULL,
    `mobile` VARCHAR(10) NULL DEFAULT NULL,
    `email` VARCHAR(255) NULL DEFAULT NULL,
    `message` TEXT NULL DEFAULT NULL,
    `created_by` VARCHAR(100) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME on update CURRENT_TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

CREATE TABLE `net_dashboard_video` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `video_url` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME on update CURRENT_TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

CREATE TABLE `net_probability_percentage` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pv_from` VARCHAR(10) NULL DEFAULT NULL,
    `pv_to` VARCHAR(10) NULL DEFAULT NULL,
    `percentage` VARCHAR(5) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

ALTER TABLE
    `net_shortlist` DROP `like_id`;

ALTER TABLE
    `net_shortlist`
ADD
    `like_id` INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
ADD
    PRIMARY KEY (`like_id`);

UPDATE
    net_profile
SET
    religion = NULL
WHERE
    religion = '';

UPDATE
    net_profile
SET
    caste = NULL
WHERE
    caste = '';

ALTER TABLE
    `net_profile` CHANGE `religion` `religion` INT(255) NULL DEFAULT NULL;

ALTER TABLE
    `net_profile`
ADD
    INDEX `religion_index_net_profile` (`religion`);

ALTER TABLE
    `net_profile` CHANGE `caste` `caste` INT(255) NULL DEFAULT NULL;

ALTER TABLE
    `net_profile`
ADD
    INDEX `caste_index_net_profile` (`caste`);

ALTER TABLE
    `net_profile`
ADD
    INDEX `servicetype_index_net_profile` (`servicetype`);

ALTER TABLE
    `net_profile`
ADD
    INDEX `gender_index_net_profile` (`gender`);

ALTER TABLE
    `net_shortlist`
ADD
    INDEX `sender_customer_id_index_net_shortlist` (`sender_customer_id`);

ALTER TABLE
    `net_shortlist`
ADD
    INDEX `receiver_customer_id_index_net_shortlist` (`receiver_customer_id`);

ALTER TABLE
    `net_shortlist` CHANGE `sender_customer_id` `sender_customer_id` VARCHAR(500) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    CHANGE `receiver_customer_id` `receiver_customer_id` VARCHAR(500) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE
    `net_like` CHANGE `sender_customer_id` `sender_customer_id` VARCHAR(500) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    CHANGE `receiver_customer_id` `receiver_customer_id` VARCHAR(500) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE
    `net_like`
ADD
    INDEX `sender_customer_id_index_net_like` (`sender_customer_id`);

ALTER TABLE
    `net_like`
ADD
    INDEX `receiver_customer_id_index_net_like` (`receiver_customer_id`);

ALTER TABLE
    `net_like` DROP `like_id`;

ALTER TABLE
    `net_like`
ADD
    `like_id` INT NOT NULL AUTO_INCREMENT FIRST,
ADD
    PRIMARY KEY (`like_id`);

ALTER TABLE
    `both_like_tag_generate_tbl` DROP `id`;

ALTER TABLE
    `both_like_tag_generate_tbl`
ADD
    `id` INT NOT NULL AUTO_INCREMENT FIRST,
ADD
    PRIMARY KEY (`id`);

-- 31-07-2024 --
CREATE TABLE `net_payment_orders`(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `marchant_id` VARCHAR(100) NOT NULL,
    `mobile_number` VARCHAR(10) NULL DEFAULT NULL,
    `transaction_id` VARCHAR(200) NOT NULL,
    `amount` DECIMAL(10, 2) NULL DEFAULT NULL,
    `status` VARCHAR(100) NULL DEFAULT NULL,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `webhook_response` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP NULL DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY(`id`),
    UNIQUE `unq_net_payment_orders_transaction_id`(`transaction_id`)
) ENGINE = InnoDB;

ALTER TABLE
    `net_payment_orders` CHANGE `marchant_id` `merchant_id` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE
    `net_payment_orders`
ADD
    `plan_id` INT NULL DEFAULT NULL
AFTER
    `amount`,
ADD
    `plan_name` VARCHAR(255) NULL DEFAULT NULL
AFTER
    `plan_id`,
ADD
    `plan_amount` DECIMAL(10, 2) NULL DEFAULT NULL
AFTER
    `plan_name`;

ALTER TABLE
    `net_profile`
ADD
    `plan_id` INT NULL DEFAULT NULL
AFTER
    `amount`;

-- 06-08-2024
ALTER TABLE
    `net_plan`
ADD
    `validity` VARCHAR(50) NULL DEFAULT NULL
AFTER
    `amount`,
ADD
    `profile` VARCHAR(150) NULL DEFAULT NULL
AFTER
    `validity`,
ADD
    `app_access` VARCHAR(200) NULL DEFAULT NULL
AFTER
    `profile`;

UPDATE
    `net_plan`
SET
    `plan_name` = 'FIRST MARRIAGE',
    `amount` = '500',
    `validity` = 'Lifetime',
    `profile` = 'Arrange Matrimony Website Ads',
    `app_access` = 'N/A'
WHERE
    `net_plan`.`plan_id` = 1;

UPDATE
    `net_plan`
SET
    `plan_name` = 'FIRST MARRIAGE',
    `amount` = '1500',
    `validity` = 'Lifetime',
    `profile` = 'Arrange Matrimony Website Ads ',
    `app_access` = 'Full'
WHERE
    `net_plan`.`plan_id` = 2;

UPDATE
    `net_plan`
SET
    `plan_name` = 'FIRST MARRIAGE',
    `amount` = '5000',
    `validity` = 'Lifetime',
    `profile` = 'Arrange Matrimony Website Ads ',
    `app_access` = 'Full + Executive Support '
WHERE
    `net_plan`.`plan_id` = 3;

UPDATE
    `net_plan`
SET
    `plan_name` = 'SECOND MARRIAGE',
    `amount` = '2000',
    `validity` = 'Lifetime',
    `profile` = 'Arrange Matrimony Website Ads ',
    `app_access` = 'Full '
WHERE
    `net_plan`.`plan_id` = 4;

INSERT INTO
    `net_plan` (
        `plan_id`,
        `plan_name`,
        `amount`,
        `validity`,
        `profile`,
        `app_access`
    )
VALUES
    (
        NULL,
        'SECOND MARRIAGE',
        '5000',
        'Lifetime',
        'Arrange Matrimony Website Ads ',
        'Full + Executive Support'
    );