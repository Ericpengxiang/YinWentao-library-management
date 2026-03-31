-- 智慧图书管理系统 - 数据库初始化脚本
-- 编码：utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `borrow`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `readers`;
DROP TABLE IF EXISTS `category`;
DROP TABLE IF EXISTS `admin`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE DATABASE IF NOT EXISTS `library` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `library`;

-- ① 管理员表
CREATE TABLE `admin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `real_name` varchar(50) NOT NULL DEFAULT '',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ② 分类表
CREATE TABLE `category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ③ 图书表
CREATE TABLE `books` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `isbn` varchar(20) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `author` varchar(100) NOT NULL DEFAULT '',
  `publisher` varchar(100) NOT NULL DEFAULT '',
  `category_id` int unsigned NOT NULL DEFAULT '0',
  `cover` varchar(255) NOT NULL DEFAULT '',
  `total` int unsigned NOT NULL DEFAULT '0',
  `available` int unsigned NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `publish_date` date DEFAULT NULL,
  `description` text,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1上架 0下架',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ④ 读者表
CREATE TABLE `readers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `card_no` varchar(20) NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL DEFAULT '',
  `gender` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1男 2女',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `class_name` varchar(100) NOT NULL DEFAULT '',
  `max_borrow` int unsigned NOT NULL DEFAULT '5',
  `borrow_count` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1启用 0禁用',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_no` (`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ⑤ 借阅表
CREATE TABLE `borrow` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `book_id` int unsigned NOT NULL DEFAULT '0',
  `reader_id` int unsigned NOT NULL DEFAULT '0',
  `admin_id` int unsigned NOT NULL DEFAULT '0',
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0借阅中 1已归还',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `reader_id` (`reader_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 初始数据：管理员 admin / 123456 -> MD5
INSERT INTO `admin` (`id`, `username`, `password`, `real_name`, `last_login`, `created_at`) VALUES
(1, 'admin', 'e10adc3949ba59abbe56e057f20f883e', '系统管理员', NULL, NOW());

-- 分类
INSERT INTO `category` (`id`, `name`) VALUES
(1, '计算机'),
(2, '文学'),
(3, '历史'),
(4, '经济'),
(5, '科学'),
(6, '艺术'),
(7, '教育'),
(8, '其他');

-- 示例图书 5 条
INSERT INTO `books` (`id`, `isbn`, `title`, `author`, `publisher`, `category_id`, `cover`, `total`, `available`, `price`, `publish_date`, `description`, `status`, `created_at`) VALUES
(1, '9787111544937', '深入理解计算机系统', 'Randal E. Bryant', '机械工业出版社', 1, '', 10, 8, 139.00, '2016-11-01', '计算机系统经典教材，涵盖程序结构、链接、虚拟内存等。', 1, NOW()),
(2, '9787020008735', '红楼梦', '曹雪芹', '人民文学出版社', 2, '', 6, 6, 59.70, '1996-12-01', '中国古典四大名著之一。', 1, NOW()),
(3, '9787101003048', '史记', '司马迁', '中华书局', 3, '', 5, 5, 198.00, '2013-08-01', '纪传体史书典范。', 1, NOW()),
(4, '9787300236849', '经济学原理', '曼昆', '北京大学出版社', 4, '', 8, 7, 88.00, '2015-05-01', '经济学入门经典。', 1, NOW()),
(5, '9787030129226', '时间简史', '史蒂芬·霍金', '科学出版社', 5, '', 4, 3, 45.00, '2012-01-01', '宇宙与时空科普名著。', 1, NOW());

-- 示例读者 3 条
INSERT INTO `readers` (`id`, `card_no`, `name`, `gender`, `phone`, `email`, `class_name`, `max_borrow`, `borrow_count`, `status`, `created_at`) VALUES
(1, 'R0000001', '张三', 1, '13800138001', 'zhangsan@school.edu', '计算机应用2101', 5, 2, 1, NOW()),
(2, 'R0000002', '李四', 2, '13800138002', 'lisi@school.edu', '软件技术2102', 5, 0, 1, NOW()),
(3, 'R0000003', '王五', 1, '13800138003', 'wangwu@school.edu', '网络技术2103', 5, 0, 1, NOW());

-- 借阅示例：2 条借阅中（用于首页与列表演示）
UPDATE `readers` SET `borrow_count` = 2 WHERE `id` = 1;

INSERT INTO `borrow` (`id`, `book_id`, `reader_id`, `admin_id`, `borrow_date`, `due_date`, `return_date`, `status`, `remark`, `created_at`) VALUES
(1, 1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 0, '', NOW()),
(2, 4, 1, 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL, 0, '超期示例', NOW());

UPDATE `books` SET `available` = `available` - 1 WHERE `id` IN (1, 4);
