-- 图书管理系统升级SQL：新增读者账号、借阅申请、续借申请、公告表

-- 读者账号表（用于读者端登录，与readers表关联）
CREATE TABLE IF NOT EXISTS `reader_accounts` (
  `id`         int unsigned NOT NULL AUTO_INCREMENT,
  `reader_id`  int unsigned NOT NULL DEFAULT 0 COMMENT '关联readers.id',
  `username`   varchar(50)  NOT NULL DEFAULT '' COMMENT '登录账号（默认借书证号）',
  `password`   varchar(64)  NOT NULL DEFAULT '' COMMENT 'MD5密码',
  `last_login` datetime     DEFAULT NULL,
  `status`     tinyint      NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `created_at` datetime     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_reader_id` (`reader_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 借阅申请表（读者在线申请，管理员审核）
CREATE TABLE IF NOT EXISTS `borrow_apply` (
  `id`          int unsigned NOT NULL AUTO_INCREMENT,
  `reader_id`   int unsigned NOT NULL DEFAULT 0,
  `book_id`     int unsigned NOT NULL DEFAULT 0,
  `apply_date`  date         NOT NULL,
  `want_days`   int          NOT NULL DEFAULT 14 COMMENT '希望借阅天数',
  `status`      tinyint      NOT NULL DEFAULT 0 COMMENT '0待审核 1已批准 2已拒绝',
  `admin_id`    int          DEFAULT NULL COMMENT '审核管理员',
  `admin_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '审核备注',
  `created_at`  datetime     DEFAULT NULL,
  `updated_at`  datetime     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reader_id` (`reader_id`),
  KEY `idx_book_id` (`book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 续借申请表（读者申请续借，管理员审核）
CREATE TABLE IF NOT EXISTS `renew_apply` (
  `id`          int unsigned NOT NULL AUTO_INCREMENT,
  `borrow_id`   int unsigned NOT NULL DEFAULT 0 COMMENT '关联borrow.id',
  `reader_id`   int unsigned NOT NULL DEFAULT 0,
  `renew_days`  int          NOT NULL DEFAULT 14 COMMENT '申请续借天数',
  `status`      tinyint      NOT NULL DEFAULT 0 COMMENT '0待审核 1已批准 2已拒绝',
  `admin_id`    int          DEFAULT NULL,
  `admin_remark` varchar(255) NOT NULL DEFAULT '',
  `created_at`  datetime     DEFAULT NULL,
  `updated_at`  datetime     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_borrow_id` (`borrow_id`),
  KEY `idx_reader_id` (`reader_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 公告表（管理员发布，读者端展示）
CREATE TABLE IF NOT EXISTS `notices` (
  `id`         int unsigned NOT NULL AUTO_INCREMENT,
  `title`      varchar(200) NOT NULL DEFAULT '',
  `content`    text         NOT NULL,
  `admin_id`   int          NOT NULL DEFAULT 0,
  `status`     tinyint      NOT NULL DEFAULT 1 COMMENT '1发布 0草稿',
  `created_at` datetime     DEFAULT NULL,
  `updated_at` datetime     DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入示例公告
INSERT IGNORE INTO `notices` (`id`, `title`, `content`, `admin_id`, `status`, `created_at`, `updated_at`) VALUES
(1, '图书馆开放时间调整通知', '各位读者，本学期图书馆开放时间调整为：周一至周五 8:00-22:00，周六周日 9:00-20:00，请合理安排借阅时间。', 1, 1, NOW(), NOW()),
(2, '新书上架公告', '本周新增图书50余册，涵盖计算机科学、经济管理、文学艺术等多个类别，欢迎广大读者前来借阅。', 1, 1, NOW(), NOW()),
(3, '借阅规则提醒', '请各位读者注意：每人最多可借阅5本图书，借阅期限为14天，如需续借请提前3天在系统内申请。超期未还将影响借阅资格。', 1, 1, NOW(), NOW());

-- 为已有读者创建默认账号（账号=借书证号，密码=123456的MD5）
INSERT IGNORE INTO `reader_accounts` (`reader_id`, `username`, `password`, `status`, `created_at`)
SELECT id, card_no, MD5('123456'), 1, NOW() FROM `readers`;
