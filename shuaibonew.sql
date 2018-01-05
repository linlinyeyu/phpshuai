/*
Navicat MySQL Data Transfer

Source Server         : 120.26.90.70
Source Server Version : 50542
Source Host           : 120.26.90.70:3306
Source Database       : shuaibonew

Target Server Type    : MYSQL
Target Server Version : 50542
File Encoding         : 65001

Date: 2018-01-05 14:53:26
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for vc_access
-- ----------------------------
DROP TABLE IF EXISTS `vc_access`;
CREATE TABLE `vc_access` (
  `role_id` smallint(6) unsigned NOT NULL,
  `node_id` smallint(6) unsigned NOT NULL,
  `pid` smallint(6) unsigned NOT NULL,
  `level` tinyint(1) NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  KEY `groupId` (`role_id`),
  KEY `nodeId` (`node_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_action
-- ----------------------------
DROP TABLE IF EXISTS `vc_action`;
CREATE TABLE `vc_action` (
  `action_id` smallint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '动作名称',
  `ios_param` varchar(100) DEFAULT NULL COMMENT 'ios对应的页面controller',
  `android_param` varchar(100) DEFAULT NULL COMMENT '安卓对应的包名',
  `wap_param` varchar(100) DEFAULT NULL,
  `web_param` varchar(100) DEFAULT NULL,
  `need_login` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0为不需要登录，1为需要登录',
  PRIMARY KEY (`action_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_activity
-- ----------------------------
DROP TABLE IF EXISTS `vc_activity`;
CREATE TABLE `vc_activity` (
  `id` smallint(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL COMMENT '标签名',
  `image` varchar(512) NOT NULL COMMENT '图标',
  `sort` smallint(4) NOT NULL COMMENT '排序',
  `date_add` int(10) unsigned NOT NULL,
  `status` smallint(4) NOT NULL DEFAULT '1' COMMENT '状态，1表示可用，0表示不可用',
  `action_id` smallint(4) NOT NULL DEFAULT '1' COMMENT '动作id',
  `params` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='主页的标签显示';

-- ----------------------------
-- Table structure for vc_ad
-- ----------------------------
DROP TABLE IF EXISTS `vc_ad`;
CREATE TABLE `vc_ad` (
  `ad_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `ad_name` varchar(60) NOT NULL DEFAULT '',
  `ad_link` varchar(255) NOT NULL DEFAULT '',
  `start_time` int(11) NOT NULL DEFAULT '0',
  `end_time` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `action_id` tinyint(3) DEFAULT NULL,
  `params` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ad_id`),
  KEY `enabled` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=226 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_address
-- ----------------------------
DROP TABLE IF EXISTS `vc_address`;
CREATE TABLE `vc_address` (
  `address_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `province_id` int(10) NOT NULL COMMENT '省',
  `city_id` int(10) NOT NULL COMMENT '市',
  `district_id` int(10) NOT NULL COMMENT '区',
  `name` varchar(64) NOT NULL COMMENT '姓名',
  `address` varchar(255) DEFAULT NULL,
  `province` varchar(32) NOT NULL,
  `city` varchar(128) NOT NULL,
  `district` varchar(128) NOT NULL,
  `postcode` varchar(12) DEFAULT NULL COMMENT '邮编',
  `phone` varchar(32) DEFAULT NULL COMMENT '手机号码',
  `tel` varchar(32) DEFAULT NULL COMMENT '电话',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `status` tinyint(1) DEFAULT '0' COMMENT '默认地址值为1',
  `date_add` int(10) unsigned NOT NULL,
  `date_upd` int(10) unsigned NOT NULL,
  PRIMARY KEY (`address_id`),
  KEY `customer_id` (`customer_id`),
  KEY `province_id` (`province_id`),
  KEY `city_id` (`city_id`),
  KEY `district_id` (`district_id`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_advertisement
-- ----------------------------
DROP TABLE IF EXISTS `vc_advertisement`;
CREATE TABLE `vc_advertisement` (
  `ad_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) DEFAULT NULL COMMENT '类型',
  `link` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(3) DEFAULT '1' COMMENT '是否可用',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `img` varchar(255) DEFAULT NULL COMMENT '首页活动图片',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  `action_id` int(10) DEFAULT NULL COMMENT '跳转id',
  `param` text COMMENT '跳转参数',
  `sort` smallint(4) NOT NULL DEFAULT '0' COMMENT '轮播图排序',
  PRIMARY KEY (`ad_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_advertisement_type
-- ----------------------------
DROP TABLE IF EXISTS `vc_advertisement_type`;
CREATE TABLE `vc_advertisement_type` (
  `type_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(16) DEFAULT NULL COMMENT '活动名称',
  `status` tinyint(3) DEFAULT '1' COMMENT '0-不可用，1-可用',
  `code` varchar(64) DEFAULT NULL COMMENT '方法',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加日期',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新日期',
  PRIMARY KEY (`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_app_home_activity
-- ----------------------------
DROP TABLE IF EXISTS `vc_app_home_activity`;
CREATE TABLE `vc_app_home_activity` (
  `home_activity_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `special_id` tinyint(3) DEFAULT NULL COMMENT '活动id',
  `status` tinyint(3) DEFAULT NULL COMMENT '是否可用',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `img` varchar(255) DEFAULT NULL COMMENT '首页活动图片',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  `action_id` int(10) DEFAULT NULL,
  `param` text,
  PRIMARY KEY (`home_activity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_app_home_activity_goods
-- ----------------------------
DROP TABLE IF EXISTS `vc_app_home_activity_goods`;
CREATE TABLE `vc_app_home_activity_goods` (
  `home_activity_goods_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) NOT NULL,
  `home_activity_id` int(10) NOT NULL,
  `image` text COMMENT '活动标题图',
  `sort` tinyint(3) DEFAULT NULL COMMENT '排序',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加日期',
  `status` tinyint(3) DEFAULT '1' COMMENT '是否上架',
  PRIMARY KEY (`home_activity_goods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_area
-- ----------------------------
DROP TABLE IF EXISTS `vc_area`;
CREATE TABLE `vc_area` (
  `area_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `area_name` varchar(8) DEFAULT NULL COMMENT '地区名称',
  `status` tinyint(3) DEFAULT '1' COMMENT '是否可用',
  `date_add` bigint(3) DEFAULT NULL COMMENT '添加日期',
  `date_upd` bigint(3) DEFAULT NULL COMMENT '更新日期',
  `image` varchar(255) DEFAULT NULL COMMENT '百城万店图片',
  PRIMARY KEY (`area_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_area_group
-- ----------------------------
DROP TABLE IF EXISTS `vc_area_group`;
CREATE TABLE `vc_area_group` (
  `area_group_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`area_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_auto_reply
-- ----------------------------
DROP TABLE IF EXISTS `vc_auto_reply`;
CREATE TABLE `vc_auto_reply` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '回复的组名,与vc_auto_reply_keyword中的group_id对应',
  `group_name` varchar(300) NOT NULL DEFAULT '',
  `reply_type` int(10) NOT NULL DEFAULT '1' COMMENT '回复类型(1为文字回复,2为图文回复)',
  `content` varchar(1000) DEFAULT NULL COMMENT '回复的主要内容(文字)',
  `params` text COMMENT '其他参数(用于除文字外的其他回复类型的参数保存,例如:title,content_url,redirect_url,desc等)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_auto_reply_keyword
-- ----------------------------
DROP TABLE IF EXISTS `vc_auto_reply_keyword`;
CREATE TABLE `vc_auto_reply_keyword` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) NOT NULL COMMENT '关键字所在分组 , 对应vc_auto_reply的id',
  `keyword` varchar(300) NOT NULL COMMENT '关键字名字',
  `match_rule` int(10) NOT NULL DEFAULT '3' COMMENT '匹配规则 : 1为完全匹配,2为正则匹配,3为模糊匹配(包含关键字即为匹配)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_banner
-- ----------------------------
DROP TABLE IF EXISTS `vc_banner`;
CREATE TABLE `vc_banner` (
  `banner_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL COMMENT '轮播图名字',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态，1为可用，0为不可用',
  `image` varchar(255) NOT NULL COMMENT '图片地址',
  `sort` smallint(4) NOT NULL DEFAULT '0' COMMENT '轮播图排序',
  `position` smallint(4) NOT NULL DEFAULT '0' COMMENT '轮播图位置，1为主页轮播',
  `date_add` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `params` varchar(200) DEFAULT NULL COMMENT '动作参数',
  `action_id` smallint(4) NOT NULL DEFAULT '1' COMMENT '动作id',
  PRIMARY KEY (`banner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_brand
-- ----------------------------
DROP TABLE IF EXISTS `vc_brand`;
CREATE TABLE `vc_brand` (
  `brand_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(60) NOT NULL DEFAULT '',
  `brand_logo` varchar(80) NOT NULL DEFAULT '',
  `brand_desc` text NOT NULL,
  `site_url` varchar(255) NOT NULL DEFAULT '',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '50',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `brand_banner` varchar(80) NOT NULL COMMENT '商品品牌banner',
  PRIMARY KEY (`brand_id`),
  KEY `is_show` (`is_show`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_calendar
-- ----------------------------
DROP TABLE IF EXISTS `vc_calendar`;
CREATE TABLE `vc_calendar` (
  `datelist` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_cart
-- ----------------------------
DROP TABLE IF EXISTS `vc_cart`;
CREATE TABLE `vc_cart` (
  `cart_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `goods_id` int(10) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `date_add` int(10) unsigned NOT NULL,
  `date_upd` int(10) unsigned NOT NULL,
  `option_id` int(10) NOT NULL DEFAULT '0',
  `option_name` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`cart_id`),
  KEY `customer_id` (`customer_id`),
  KEY `goods_id` (`goods_id`) USING BTREE,
  KEY `option_id` (`option_id`)
) ENGINE=InnoDB AUTO_INCREMENT=441 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_cart_goods
-- ----------------------------
DROP TABLE IF EXISTS `vc_cart_goods`;
CREATE TABLE `vc_cart_goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `data` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=482 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_category
-- ----------------------------
DROP TABLE IF EXISTS `vc_category`;
CREATE TABLE `vc_category` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `icon` varchar(200) DEFAULT NULL,
  `selected_icon` varchar(255) DEFAULT NULL COMMENT '选中图片',
  `level` tinyint(1) unsigned NOT NULL,
  `sort` int(10) unsigned NOT NULL,
  `active` tinyint(1) DEFAULT '1' COMMENT '是否可用，默认为可用',
  `date_add` int(10) unsigned NOT NULL,
  `summary` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  KEY `pid` (`pid`),
  KEY `ctp` (`category_id`,`pid`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1247 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_category_goods
-- ----------------------------
DROP TABLE IF EXISTS `vc_category_goods`;
CREATE TABLE `vc_category_goods` (
  `category_id` int(10) unsigned NOT NULL,
  `goods_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`category_id`,`goods_id`),
  KEY `goods_id` (`goods_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_cmb_protocol
-- ----------------------------
DROP TABLE IF EXISTS `vc_cmb_protocol`;
CREATE TABLE `vc_cmb_protocol` (
  `cmb_protocol` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) DEFAULT NULL COMMENT '用户id',
  `protocol_num` varchar(32) DEFAULT NULL COMMENT '协议号',
  `serial_num` varchar(64) DEFAULT NULL COMMENT '协议流水号',
  `status` tinyint(3) DEFAULT '0' COMMENT '0-未应答，1-成功，2失败',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`cmb_protocol`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='招行签约表';

-- ----------------------------
-- Table structure for vc_collection
-- ----------------------------
DROP TABLE IF EXISTS `vc_collection`;
CREATE TABLE `vc_collection` (
  `collection_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `goods_id` int(10) NOT NULL,
  `date_add` int(10) unsigned NOT NULL,
  `date_upd` int(10) unsigned NOT NULL,
  PRIMARY KEY (`collection_id`),
  KEY `customer_id` (`customer_id`),
  KEY `goods_id` (`goods_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_comment_reply
-- ----------------------------
DROP TABLE IF EXISTS `vc_comment_reply`;
CREATE TABLE `vc_comment_reply` (
  `comment_reply_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` int(10) DEFAULT NULL COMMENT '评论id',
  `content` text COMMENT '回复内容',
  `reply_date` bigint(10) DEFAULT NULL COMMENT '回复日期',
  `customer_id` int(10) DEFAULT NULL COMMENT '用户id',
  PRIMARY KEY (`comment_reply_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='评论回复表';

-- ----------------------------
-- Table structure for vc_comment_useful
-- ----------------------------
DROP TABLE IF EXISTS `vc_comment_useful`;
CREATE TABLE `vc_comment_useful` (
  `comment_useful_id` int(10) NOT NULL AUTO_INCREMENT,
  `comment_id` int(10) DEFAULT NULL COMMENT '评论id',
  `customer_id` int(10) DEFAULT NULL COMMENT '用户id',
  PRIMARY KEY (`comment_useful_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_complain
-- ----------------------------
DROP TABLE IF EXISTS `vc_complain`;
CREATE TABLE `vc_complain` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL,
  `title` varchar(128) DEFAULT NULL,
  `content` text,
  `reply` text,
  `date_add` int(10) NOT NULL,
  `date_reply` int(10) NOT NULL,
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_country
-- ----------------------------
DROP TABLE IF EXISTS `vc_country`;
CREATE TABLE `vc_country` (
  `country_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` int(10) unsigned NOT NULL,
  `id_currency` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL,
  `iso_code` varchar(3) NOT NULL,
  `call_prefix` int(10) NOT NULL DEFAULT '0',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `contains_states` tinyint(1) NOT NULL DEFAULT '0',
  `need_identification_number` tinyint(1) NOT NULL DEFAULT '0',
  `need_zip_code` tinyint(1) NOT NULL DEFAULT '1',
  `zip_code_format` varchar(12) NOT NULL DEFAULT '',
  `display_tax_label` tinyint(1) NOT NULL,
  PRIMARY KEY (`country_id`),
  KEY `country_iso_code` (`iso_code`),
  KEY `zone_id` (`zone_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_coupon
-- ----------------------------
DROP TABLE IF EXISTS `vc_coupon`;
CREATE TABLE `vc_coupon` (
  `coupon_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) NOT NULL,
  `limit` decimal(20,2) NOT NULL COMMENT '使用条件',
  `amount` decimal(20,2) NOT NULL COMMENT '红包价值',
  `take_condition` decimal(20,2) DEFAULT NULL COMMENT '领取条件',
  `use_total` int(8) DEFAULT '1' COMMENT '领取次数',
  `date_expire` int(10) DEFAULT NULL,
  `type` smallint(4) unsigned NOT NULL,
  `category_type` smallint(4) NOT NULL,
  `category_id` int(10) NOT NULL,
  `date_add` int(10) unsigned NOT NULL,
  `validate_type` tinyint(1) NOT NULL DEFAULT '0',
  `validate_time` int(10) unsigned DEFAULT NULL,
  `is_publish` tinyint(1) NOT NULL DEFAULT '0',
  `start_time` int(10) unsigned DEFAULT NULL,
  `start_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0代表获取当日起，1表示start_time的字段日起',
  `active` enum('1','0') NOT NULL DEFAULT '1' COMMENT '是否激活，1激活，0不激活，默认激活',
  PRIMARY KEY (`coupon_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_coupon_goods
-- ----------------------------
DROP TABLE IF EXISTS `vc_coupon_goods`;
CREATE TABLE `vc_coupon_goods` (
  `goods_id` int(10) NOT NULL,
  `coupon_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_coupon_type
-- ----------------------------
DROP TABLE IF EXISTS `vc_coupon_type`;
CREATE TABLE `vc_coupon_type` (
  `type_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `date_add` int(11) NOT NULL,
  PRIMARY KEY (`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_customer
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer`;
CREATE TABLE `vc_customer` (
  `customer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级',
  `group_id` smallint(4) NOT NULL DEFAULT '1' COMMENT '用户等级，默认为1级',
  `phone` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '电子邮箱',
  `nickname` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '昵称',
  `realname` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passwd` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weixin` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '账号余额',
  `total_account` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '账号共充值金额',
  `avater` varchar(511) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '头像',
  `sex` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '性别',
  `birthday` int(10) unsigned DEFAULT '0' COMMENT '生日',
  `province` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '省',
  `city` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '城市',
  `city_id` int(10) unsigned DEFAULT NULL COMMENT '城市对应的id',
  `qq_open_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wx_openid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wx_unionid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wx_gz_openid` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wx_web_openid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_token` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_add` int(11) NOT NULL,
  `date_upd` int(10) NOT NULL,
  `reg_ip` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '注册ip',
  `last_ip` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '上次登录ip',
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0为禁用，1为可用',
  `jpush_token` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '极光推送的token',
  `last_check_date` int(10) unsigned DEFAULT NULL COMMENT '最后检测日期',
  `commission` decimal(12,2) unsigned NOT NULL DEFAULT '0.00',
  `need_push` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否需要推送，1为需要，0为不需要，默认为1',
  `hx_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '环信id',
  `integration` decimal(12,2) DEFAULT '0.00' COMMENT '积分',
  `shopping_coin` decimal(11,2) DEFAULT '0.00' COMMENT '购物币',
  `last_area` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '上次登录地区',
  `is_subscribe` tinyint(3) DEFAULT '0',
  `is_recommend` tinyint(3) DEFAULT '0' COMMENT '是否有推荐关系 0-没有',
  `is_new` tinyint(3) NOT NULL DEFAULT '1' COMMENT '是否新用户 1：是 2：不是',
  `reward_amount` decimal(10,2) DEFAULT '0.00' COMMENT '奖励金',
  `hongfu` decimal(10,2) DEFAULT '0.00' COMMENT '鸿府积分',
  `transfer_amount` decimal(10,2) DEFAULT '0.00' COMMENT '转账金额',
  `is_frozen` tinyint(3) DEFAULT '0' COMMENT '是否冻结金额',
  `share_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '推荐码',
  `transfer_passwd` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '转账密码',
  `pay_passwd` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '支付密码',
  `protocol_num` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '招商协议号',
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `access_token` (`access_token`) USING BTREE,
  UNIQUE KEY `nickname` (`nickname`) USING BTREE,
  UNIQUE KEY `share_code` (`share_code`),
  KEY `phone` (`phone`) USING BTREE,
  KEY `agent_id` (`agent_id`),
  KEY `uuid` (`uuid`(191)) USING BTREE,
  KEY `group_id` (`group_id`),
  KEY `test` (`city`) USING BTREE,
  KEY `sex` (`sex`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11283 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for vc_customer_account
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer_account`;
CREATE TABLE `vc_customer_account` (
  `account_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `pay_account` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pay_account_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_add` int(11) NOT NULL,
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_customer_coupon
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer_coupon`;
CREATE TABLE `vc_customer_coupon` (
  `customer_coupon_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID 默认0代表系统自动发放',
  `date_add` int(10) NOT NULL,
  `state` enum('0','1','2') NOT NULL DEFAULT '0' COMMENT '优惠券是否使用，0代表未使用，1代表已使用，2代表已过期',
  `date_start` int(10) unsigned NOT NULL COMMENT '优惠券结束时间',
  `date_end` int(10) unsigned NOT NULL COMMENT '优惠券结束时间',
  PRIMARY KEY (`customer_coupon_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_customer_coupon_use
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer_coupon_use`;
CREATE TABLE `vc_customer_coupon_use` (
  `coupon_id` int(10) NOT NULL DEFAULT '0',
  `customer_id` int(10) NOT NULL DEFAULT '0',
  `seller_id` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_customer_extend
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer_extend`;
CREATE TABLE `vc_customer_extend` (
  `customer_extend_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL COMMENT '用户信息',
  `pid` int(10) unsigned NOT NULL COMMENT '对应的上级id',
  `level` smallint(4) NOT NULL DEFAULT '0' COMMENT '三级分销关联级别，1表示徒弟，2表示徒孙',
  `commission` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '总共获取的佣金',
  `date_add` int(10) unsigned NOT NULL COMMENT '添加日期',
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否可用',
  PRIMARY KEY (`customer_extend_id`),
  KEY `pid` (`pid`) USING BTREE,
  KEY `customer_id` (`customer_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户分销关系表';

-- ----------------------------
-- Table structure for vc_customer_extend_record
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer_extend_record`;
CREATE TABLE `vc_customer_extend_record` (
  `customer_extend_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `commission` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单次获得的佣金',
  `date_add` int(10) unsigned NOT NULL COMMENT '添加时间',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '对应的订单号',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '佣金状态，正常状态为1，如遇退款等状态为2，即不可提现',
  `state` smallint(4) NOT NULL DEFAULT '1' COMMENT '1为正常状态，2为不可提现，即遇退货等缘由',
  `withdraw_state` smallint(4) NOT NULL DEFAULT '0' COMMENT '提现状态',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `customer_extend_id` (`customer_extend_id`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE,
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='获取佣金记录表';

-- ----------------------------
-- Table structure for vc_customer_group
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer_group`;
CREATE TABLE `vc_customer_group` (
  `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `upgrade` int(10) unsigned NOT NULL DEFAULT '0',
  `privilege` varchar(128) DEFAULT NULL COMMENT '拥有特权',
  `requirement` varchar(128) DEFAULT NULL COMMENT '达成条件',
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_customer_log
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer_log`;
CREATE TABLE `vc_customer_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `ip` varchar(20) NOT NULL COMMENT 'ip地址',
  `terminal` varchar(20) NOT NULL COMMENT '终端',
  `date_add` int(10) unsigned NOT NULL COMMENT '添加日期',
  `type` smallint(4) NOT NULL COMMENT '类型',
  `comment` varchar(255) DEFAULT NULL COMMENT '概述',
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1054 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_customer_tag
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer_tag`;
CREATE TABLE `vc_customer_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  `date_subscribe` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_customer_withdraw
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer_withdraw`;
CREATE TABLE `vc_customer_withdraw` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) unsigned NOT NULL COMMENT '商品id',
  `order_sn` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '提现订单号',
  `customer_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `money` decimal(12,2) NOT NULL COMMENT '提现金额',
  `state` smallint(4) NOT NULL DEFAULT '1' COMMENT '申请状态，1为申请中，2为待结算，3为申请失败，4为已结算',
  `date_add` int(10) unsigned NOT NULL COMMENT '申请时间',
  `date_audit` int(10) unsigned DEFAULT NULL,
  `reply` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` smallint(4) NOT NULL DEFAULT '1' COMMENT '1为银行卡，2为支付宝',
  `account` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT '账号信息',
  `realname` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '真实姓名',
  `subbranch` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '开户支行',
  `out_trade_no` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '外部订单号',
  `user_id` int(10) unsigned NOT NULL COMMENT '审核人员',
  `orders` text COLLATE utf8mb4_unicode_ci COMMENT '对应订单号',
  `real_money` decimal(12,2) NOT NULL,
  `style` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1:余额提现,2:佣金提现,3：余额宝提现',
  `invoice` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '发票',
  PRIMARY KEY (`id`),
  KEY `goods_id` (`goods_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_sn` (`order_sn`),
  KEY `out_trade_no` (`out_trade_no`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for vc_customer_withdraw_order
-- ----------------------------
DROP TABLE IF EXISTS `vc_customer_withdraw_order`;
CREATE TABLE `vc_customer_withdraw_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL DEFAULT '0',
  `order_sn` varchar(32) DEFAULT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0：待结算 1：待提现  2:申请中  3：已提现',
  `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `commission_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '佣金',
  `date_add` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_essay
-- ----------------------------
DROP TABLE IF EXISTS `vc_essay`;
CREATE TABLE `vc_essay` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '封面地址',
  `content` text COLLATE utf8mb4_unicode_ci,
  `date_add` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL COMMENT '发布人员',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `sort` smallint(4) NOT NULL DEFAULT '0',
  `state` smallint(4) NOT NULL DEFAULT '1' COMMENT '1为正常，0为不显示',
  `click_count` int(10) NOT NULL DEFAULT '0' COMMENT '点击次数',
  `desc` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '咨询简介',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为热门咨询',
  `cid` int(11) DEFAULT NULL COMMENT '资讯所属分类ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for vc_essay_categorys
-- ----------------------------
DROP TABLE IF EXISTS `vc_essay_categorys`;
CREATE TABLE `vc_essay_categorys` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类id',
  `name` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '分类名称',
  `article_number` int(10) NOT NULL COMMENT '文章数目',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '修改时间',
  `parentid` int(10) DEFAULT NULL COMMENT '父级id',
  `level` int(255) DEFAULT NULL COMMENT '级别',
  `sort` smallint(11) DEFAULT NULL COMMENT '排序',
  `active` int(11) DEFAULT '1' COMMENT '默认为可用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_everyday_newgoods
-- ----------------------------
DROP TABLE IF EXISTS `vc_everyday_newgoods`;
CREATE TABLE `vc_everyday_newgoods` (
  `everyday_newgoods_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) NOT NULL COMMENT '商品id',
  `status` tinyint(3) DEFAULT NULL COMMENT '是否可用',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  `sort` smallint(6) NOT NULL DEFAULT '10' COMMENT '排序',
  PRIMARY KEY (`everyday_newgoods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='每日上新';

-- ----------------------------
-- Table structure for vc_express
-- ----------------------------
DROP TABLE IF EXISTS `vc_express`;
CREATE TABLE `vc_express` (
  `express_id` smallint(4) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) DEFAULT NULL,
  `express_name` varchar(255) NOT NULL COMMENT '快递公司名',
  `company_url` varchar(512) DEFAULT NULL COMMENT '公司网址',
  `date_add` int(10) unsigned DEFAULT NULL COMMENT '添加时间',
  `sort` int(5) NOT NULL DEFAULT '0' COMMENT '排序小到大升序',
  `add_order_show` int(1) NOT NULL DEFAULT '0' COMMENT '0为隐藏 1为显示',
  PRIMARY KEY (`express_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_express_template
-- ----------------------------
DROP TABLE IF EXISTS `vc_express_template`;
CREATE TABLE `vc_express_template` (
  `template_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(10) unsigned DEFAULT NULL COMMENT '店铺id',
  `name` varchar(50) DEFAULT NULL,
  `province_id` int(10) unsigned DEFAULT NULL,
  `first` decimal(8,2) DEFAULT NULL,
  `first_weight` decimal(8,3) DEFAULT NULL,
  `additional` decimal(8,2) DEFAULT NULL,
  `additional_weight` decimal(8,3) DEFAULT NULL,
  `date_add` int(10) unsigned DEFAULT NULL,
  `express` varchar(50) DEFAULT NULL COMMENT '快递编号',
  `items` text,
  PRIMARY KEY (`template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_favorite
-- ----------------------------
DROP TABLE IF EXISTS `vc_favorite`;
CREATE TABLE `vc_favorite` (
  `favorite_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) unsigned NOT NULL COMMENT '收藏产品ID',
  `customer_id` int(10) unsigned NOT NULL COMMENT '买家ID',
  `date_add` int(10) unsigned NOT NULL COMMENT '添加时间',
  `date_upd` int(10) unsigned NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`favorite_id`),
  KEY `customer_id` (`customer_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_feedback
-- ----------------------------
DROP TABLE IF EXISTS `vc_feedback`;
CREATE TABLE `vc_feedback` (
  `feedback_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `feedback_reason_id` int(11) NOT NULL,
  `content` text,
  `customer_id` int(11) NOT NULL,
  `date_add` int(11) unsigned DEFAULT NULL,
  `reply_content` varchar(500) DEFAULT NULL,
  `is_reply` tinyint(1) DEFAULT '0',
  `reply_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`feedback_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_feedback_reason
-- ----------------------------
DROP TABLE IF EXISTS `vc_feedback_reason`;
CREATE TABLE `vc_feedback_reason` (
  `feedback_reason_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `content` text,
  `status` int(10) DEFAULT '1' COMMENT '是否可用',
  `date_add` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`feedback_reason_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_finance
-- ----------------------------
DROP TABLE IF EXISTS `vc_finance`;
CREATE TABLE `vc_finance` (
  `record_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `finance_type_id` smallint(4) NOT NULL DEFAULT '0' COMMENT '类型,1-积分,2-购物币,3-资金,4-鸿府积分',
  `type` smallint(4) NOT NULL DEFAULT '1' COMMENT '类型',
  `amount` decimal(11,3) NOT NULL DEFAULT '0.000' COMMENT '金额',
  `order_sn` varchar(50) DEFAULT NULL COMMENT '对应订单号',
  `date_add` int(11) unsigned DEFAULT NULL COMMENT '添加日期',
  `extend_id` int(10) unsigned DEFAULT NULL,
  `comments` varchar(128) DEFAULT NULL COMMENT '详细记录',
  `title` varchar(64) DEFAULT NULL COMMENT '记录标题',
  `is_minus` tinyint(3) DEFAULT '1' COMMENT '1-正,0-负',
  PRIMARY KEY (`record_id`),
  KEY `date_add` (`date_add`)
) ENGINE=InnoDB AUTO_INCREMENT=972 DEFAULT CHARSET=utf8 COMMENT='用户钱包记录表';

-- ----------------------------
-- Table structure for vc_finance_op
-- ----------------------------
DROP TABLE IF EXISTS `vc_finance_op`;
CREATE TABLE `vc_finance_op` (
  `operation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `finance_type` smallint(4) NOT NULL DEFAULT '1' COMMENT '类型 1 支付 2 提现',
  `foregin_customer_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `foreign_id` int(10) unsigned DEFAULT NULL COMMENT '外键，根据type值对应不同的表，如充值表，订单表等',
  `amount` decimal(11,2) NOT NULL COMMENT '金额',
  `real_amount` decimal(11,2) NOT NULL COMMENT '真实金额',
  `date_add` int(10) unsigned NOT NULL COMMENT '对应用户id',
  `is_minus` tinyint(3) DEFAULT '1' COMMENT '是否是负 1表示是 0表示不是',
  `order_sn` varchar(50) DEFAULT NULL COMMENT '对应订单号',
  `comments` varchar(128) DEFAULT NULL COMMENT '详细记录',
  `title` varchar(64) DEFAULT NULL COMMENT '记录标题',
  PRIMARY KEY (`operation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=413 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_finance_type
-- ----------------------------
DROP TABLE IF EXISTS `vc_finance_type`;
CREATE TABLE `vc_finance_type` (
  `finance_type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `finance_type_name` varchar(32) NOT NULL COMMENT '记录类型',
  PRIMARY KEY (`finance_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COMMENT='记录类型表';

-- ----------------------------
-- Table structure for vc_fullcut
-- ----------------------------
DROP TABLE IF EXISTS `vc_fullcut`;
CREATE TABLE `vc_fullcut` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) NOT NULL,
  `limit` decimal(20,2) NOT NULL COMMENT '使用条件',
  `amount` decimal(20,2) NOT NULL COMMENT '红包价值',
  `take_condition` decimal(20,2) DEFAULT NULL COMMENT '领取条件',
  `use_total` int(8) DEFAULT '1' COMMENT '领取次数',
  `date_expire` int(10) DEFAULT NULL,
  `type` smallint(4) unsigned NOT NULL,
  `date_add` int(10) unsigned NOT NULL,
  `is_publish` tinyint(1) NOT NULL DEFAULT '0',
  `active` enum('1','0') NOT NULL DEFAULT '1' COMMENT '是否激活，1激活，0不激活，默认激活',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_goods
-- ----------------------------
DROP TABLE IF EXISTS `vc_goods`;
CREATE TABLE `vc_goods` (
  `goods_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_type` smallint(4) NOT NULL DEFAULT '1' COMMENT '商品类型',
  `is_alone_sale` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `on_sale` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否上架',
  `name` varchar(128) NOT NULL,
  `goods_name_style` varchar(60) NOT NULL DEFAULT '+',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `brand_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `cover` varchar(255) DEFAULT NULL COMMENT '商品图片封面',
  `sku` varchar(32) DEFAULT NULL,
  `quantity` int(10) NOT NULL DEFAULT '0' COMMENT '库存',
  `min_quantity` int(10) unsigned NOT NULL DEFAULT '1',
  `seller_id` int(11) NOT NULL,
  `promote_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '促销价',
  `shop_price` decimal(20,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `market_price` decimal(10,2) NOT NULL COMMENT '市场价',
  `weight` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `out_of_stock` int(10) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `date_add` int(10) unsigned NOT NULL,
  `date_upd` int(10) unsigned NOT NULL,
  `promote_date_end` int(10) unsigned DEFAULT NULL COMMENT '秒杀产品结束',
  `promote_date_start` int(10) DEFAULT NULL COMMENT '秒杀产品开始日期',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `is_display` tinyint(1) NOT NULL DEFAULT '0',
  `is_new` tinyint(1) NOT NULL DEFAULT '0',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0',
  `sale_count` int(10) NOT NULL DEFAULT '0' COMMENT '实际销售数量',
  `comments_count` int(10) DEFAULT '0' COMMENT '评价数量',
  `virtual_count` int(10) NOT NULL DEFAULT '0' COMMENT '虚拟销售数量',
  `stock_type` smallint(3) NOT NULL DEFAULT '0' COMMENT '0为购买后减库存，1为不减库存',
  `mini_name` varchar(50) NOT NULL COMMENT '简称',
  `description` text NOT NULL COMMENT '商品详情',
  `detail` varchar(500) NOT NULL COMMENT '商品简介',
  `is_virtual` tinyint(1) NOT NULL DEFAULT '0' COMMENT '虚拟商品',
  `dispatch_type` smallint(3) NOT NULL DEFAULT '0' COMMENT '运费模式，默认为0,0表示为包邮,1为固定运费，2为运费模板',
  `dispatch_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '固定运费',
  `dispatch_id` int(10) NOT NULL DEFAULT '0' COMMENT '如果选择为配送方式，此处为其id',
  `sort_order` int(10) NOT NULL DEFAULT '100' COMMENT '排序',
  `max_buy` int(10) NOT NULL DEFAULT '10' COMMENT '最大购买数量',
  `max_once_buy` int(10) NOT NULL DEFAULT '10' COMMENT '一次最大购买数量',
  `max_type` smallint(4) NOT NULL DEFAULT '0' COMMENT '最大购买数量限制，0为不限制，1为限制',
  `time_unit` smallint(3) NOT NULL DEFAULT '0' COMMENT '日期单位,1为天，2为小时',
  `time_number` int(10) NOT NULL DEFAULT '0' COMMENT '日期数值，根据单位可算出间隔时间',
  `banner` varchar(255) DEFAULT '' COMMENT 'banner图',
  `is_home` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为主页',
  `click_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击次数',
  `pv` decimal(10,3) DEFAULT '0.000' COMMENT 'pv比例',
  `max_integration` int(10) DEFAULT '0' COMMENT '最大购买积分',
  `max_shopping_coin` int(10) DEFAULT '0' COMMENT '最大购物币',
  `is_recommend` tinyint(3) DEFAULT '0' COMMENT '是否掌柜推荐',
  `seller_cat_id` smallint(5) DEFAULT NULL COMMENT '商品店铺分类',
  `date_on_sale` bigint(10) DEFAULT NULL COMMENT '上架时间',
  `sort` int(10) DEFAULT NULL COMMENT '排序',
  `apply_status` tinyint(3) DEFAULT '0' COMMENT '1-待审核,2-已通过,3-已拒绝',
  `apply_reply` text,
  `manage_fee` decimal(10,3) DEFAULT '0.000' COMMENT '审核回复',
  `is_verify` tinyint(3) DEFAULT '0',
  `reward_fee` decimal(4,2) DEFAULT NULL COMMENT '奖励比例',
  `purchase_fee` decimal(4,2) DEFAULT NULL COMMENT '购物积分比例',
  `integra_fee` decimal(4,2) DEFAULT NULL COMMENT '积分比例',
  `hongfu` decimal(10,2) DEFAULT '0.00' COMMENT '鸿府积分',
  `address` varchar(2500) DEFAULT NULL COMMENT '提货地址',
  `pv_self` decimal(4,2) DEFAULT '0.00' COMMENT 'pv自己费率',
  `pv_superior` decimal(4,2) DEFAULT '0.00' COMMENT 'pv上级费率',
  `need_protocol` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否需要协议',
  PRIMARY KEY (`goods_id`),
  UNIQUE KEY `name` (`name`),
  KEY `sku` (`sku`),
  KEY `date_add` (`date_add`)
) ENGINE=InnoDB AUTO_INCREMENT=7291 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_goods_option
-- ----------------------------
DROP TABLE IF EXISTS `vc_goods_option`;
CREATE TABLE `vc_goods_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) DEFAULT '0',
  `name` varchar(50) DEFAULT '',
  `sale_price` decimal(10,2) DEFAULT '0.00',
  `market_price` decimal(10,2) DEFAULT '0.00',
  `stock` int(11) DEFAULT '0',
  `weight` decimal(10,2) DEFAULT '0.00',
  `sort` int(11) DEFAULT '0',
  `specs` text,
  `goods_sn` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_goodsid` (`goods_id`),
  KEY `idx_displayorder` (`sort`)
) ENGINE=MyISAM AUTO_INCREMENT=51 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_goods_param
-- ----------------------------
DROP TABLE IF EXISTS `vc_goods_param`;
CREATE TABLE `vc_goods_param` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) DEFAULT '0' COMMENT '商品id',
  `name` varchar(50) DEFAULT '' COMMENT '参数名',
  `value` text COMMENT '参数值',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `idx_goodsid` (`goods_id`),
  KEY `idx_displayorder` (`sort`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_goods_spec
-- ----------------------------
DROP TABLE IF EXISTS `vc_goods_spec`;
CREATE TABLE `vc_goods_spec` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) DEFAULT '0',
  `name` varchar(50) DEFAULT '',
  `content` text,
  `sort` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_goodsid` (`goods_id`),
  KEY `idx_displayorder` (`sort`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_goods_spec_item
-- ----------------------------
DROP TABLE IF EXISTS `vc_goods_spec_item`;
CREATE TABLE `vc_goods_spec_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spec_id` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT '',
  `sort` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_specid` (`spec_id`),
  KEY `idx_displayorder` (`sort`)
) ENGINE=MyISAM AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_goods_type
-- ----------------------------
DROP TABLE IF EXISTS `vc_goods_type`;
CREATE TABLE `vc_goods_type` (
  `id` smallint(4) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品类型',
  `name` varchar(32) NOT NULL COMMENT '商品名',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_help
-- ----------------------------
DROP TABLE IF EXISTS `vc_help`;
CREATE TABLE `vc_help` (
  `help_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(2000) DEFAULT NULL COMMENT '问题标题',
  `content` text COMMENT '帮助内容',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加日期',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新日期',
  PRIMARY KEY (`help_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_home_activity
-- ----------------------------
DROP TABLE IF EXISTS `vc_home_activity`;
CREATE TABLE `vc_home_activity` (
  `home_activity_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `special_id` tinyint(3) DEFAULT NULL COMMENT '活动id',
  `status` tinyint(3) DEFAULT NULL COMMENT '是否可用',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `img` varchar(255) DEFAULT NULL COMMENT '首页活动图片',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  `action_id` int(10) DEFAULT NULL COMMENT '跳转id',
  `param` text COMMENT '跳转参数',
  PRIMARY KEY (`home_activity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_home_category
-- ----------------------------
DROP TABLE IF EXISTS `vc_home_category`;
CREATE TABLE `vc_home_category` (
  `home_category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) DEFAULT NULL COMMENT '分类id',
  `img` varchar(255) DEFAULT NULL COMMENT '首页分类图标',
  `status` tinyint(3) DEFAULT NULL COMMENT '是否可用',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `date_upd` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`home_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COMMENT='首页分类展示表';

-- ----------------------------
-- Table structure for vc_home_category_goods
-- ----------------------------
DROP TABLE IF EXISTS `vc_home_category_goods`;
CREATE TABLE `vc_home_category_goods` (
  `home_category_goods_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) NOT NULL,
  `home_category_id` int(10) NOT NULL,
  `image` text COMMENT '活动标题图',
  `sort` tinyint(3) DEFAULT NULL COMMENT '排序',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加日期',
  `status` tinyint(3) DEFAULT '1' COMMENT '是否上架',
  PRIMARY KEY (`home_category_goods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_home_tag
-- ----------------------------
DROP TABLE IF EXISTS `vc_home_tag`;
CREATE TABLE `vc_home_tag` (
  `id` smallint(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL COMMENT '标签名',
  `icon` varchar(512) NOT NULL COMMENT '图标',
  `sort` smallint(4) NOT NULL COMMENT '排序',
  `date_add` int(10) unsigned NOT NULL,
  `status` smallint(4) NOT NULL DEFAULT '1' COMMENT '状态，1表示可用，0表示不可用',
  `action_id` smallint(4) NOT NULL DEFAULT '1' COMMENT '动作id',
  `params` varchar(200) DEFAULT NULL,
  `is_app` tinyint(3) DEFAULT NULL COMMENT '1-app标签，0-网页标签',
  `position` tinyint(3) DEFAULT '0' COMMENT '1-banner下面4个标签，2-四个标签下三个大标签',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8 COMMENT='主页的标签显示';

-- ----------------------------
-- Table structure for vc_image
-- ----------------------------
DROP TABLE IF EXISTS `vc_image`;
CREATE TABLE `vc_image` (
  `image_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `sort` smallint(2) unsigned NOT NULL DEFAULT '0',
  `cover` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`image_id`),
  KEY `goods_id` (`goods_id`),
  KEY `goods_cover` (`goods_id`,`cover`)
) ENGINE=InnoDB AUTO_INCREMENT=317 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_information
-- ----------------------------
DROP TABLE IF EXISTS `vc_information`;
CREATE TABLE `vc_information` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `image` varchar(128) DEFAULT NULL,
  `mini_content` text,
  `content` text,
  `date_add` int(10) NOT NULL,
  `date_upd` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_information_image
-- ----------------------------
DROP TABLE IF EXISTS `vc_information_image`;
CREATE TABLE `vc_information_image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `image` varchar(128) DEFAULT NULL,
  `date_add` int(10) NOT NULL,
  `date_upd` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_intergral_shop
-- ----------------------------
DROP TABLE IF EXISTS `vc_intergral_shop`;
CREATE TABLE `vc_intergral_shop` (
  `intergral_shop_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `images` varchar(255) DEFAULT NULL COMMENT '图片',
  `seller_id` int(10) DEFAULT NULL COMMENT '积分店铺',
  `status` tinyint(3) DEFAULT NULL COMMENT '是否开启',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  `sort` smallint(10) DEFAULT NULL COMMENT '排序',
  PRIMARY KEY (`intergral_shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='积分店铺表';

-- ----------------------------
-- Table structure for vc_keyword
-- ----------------------------
DROP TABLE IF EXISTS `vc_keyword`;
CREATE TABLE `vc_keyword` (
  `keyword_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(32) DEFAULT NULL COMMENT '关键词',
  `status` tinyint(3) DEFAULT NULL COMMENT '是否可用',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  `sort` tinyint(3) DEFAULT '0',
  PRIMARY KEY (`keyword_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='关键词表';

-- ----------------------------
-- Table structure for vc_member_price
-- ----------------------------
DROP TABLE IF EXISTS `vc_member_price`;
CREATE TABLE `vc_member_price` (
  `price_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_rank` tinyint(3) NOT NULL DEFAULT '0',
  `user_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`price_id`),
  KEY `goods_id` (`goods_id`,`user_rank`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_message
-- ----------------------------
DROP TABLE IF EXISTS `vc_message`;
CREATE TABLE `vc_message` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '消息类型 1：系统消息 2：物流消息 3：订单消息',
  `title` varchar(63) NOT NULL,
  `content` varchar(127) DEFAULT NULL,
  `order_sn` varchar(64) NOT NULL COMMENT '订单号',
  `action_id` smallint(4) NOT NULL DEFAULT '1',
  `params` text COMMENT '外键',
  `date_add` int(10) unsigned NOT NULL,
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1',
  `sender_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '信息发送者(0表示系统消息)',
  `and_or_or` tinyint(1) unsigned NOT NULL COMMENT '标签全部满足或者只满足其中一个即可(0表示默认,满足其中一个即可)',
  `tags_count` int(8) unsigned NOT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=231 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_message_customer
-- ----------------------------
DROP TABLE IF EXISTS `vc_message_customer`;
CREATE TABLE `vc_message_customer` (
  `message_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL,
  `read_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_message_tag
-- ----------------------------
DROP TABLE IF EXISTS `vc_message_tag`;
CREATE TABLE `vc_message_tag` (
  `message_id` int(10) unsigned NOT NULL,
  `tag_id` varchar(16) NOT NULL COMMENT '标签id,当为0时表示发送给全体用户',
  KEY `INDEX_MESSAGE` (`message_id`),
  KEY `INDEX_TAGS` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_mobile_recharge
-- ----------------------------
DROP TABLE IF EXISTS `vc_mobile_recharge`;
CREATE TABLE `vc_mobile_recharge` (
  `recharge_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL,
  `mobile` varchar(16) DEFAULT NULL COMMENT '电话',
  `order_sn` varchar(64) DEFAULT NULL COMMENT '订单号',
  `out_order_no` varchar(64) DEFAULT NULL COMMENT '外部订单号',
  `amount` varchar(8) DEFAULT NULL COMMENT '面值',
  `total_fee` decimal(8,2) DEFAULT NULL COMMENT '实际金额',
  `type` tinyint(3) DEFAULT NULL COMMENT '1-话费充值，2-流量充值',
  `is_success` tinyint(3) DEFAULT '2' COMMENT '1-成功，2-失败',
  `date_add` bigint(10) DEFAULT '0' COMMENT '添加日期',
  PRIMARY KEY (`recharge_id`)
) ENGINE=InnoDB AUTO_INCREMENT=242 DEFAULT CHARSET=utf8 COMMENT='手机充值记录表';

-- ----------------------------
-- Table structure for vc_mobile_recharge_fee
-- ----------------------------
DROP TABLE IF EXISTS `vc_mobile_recharge_fee`;
CREATE TABLE `vc_mobile_recharge_fee` (
  `mobile_recharge_fee_id` smallint(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) DEFAULT NULL COMMENT '1-话费，2-流量',
  `amount` smallint(3) DEFAULT NULL COMMENT '面值',
  `actual_fee` decimal(8,2) DEFAULT NULL COMMENT '金额',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`mobile_recharge_fee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='手机充值设置金额表';

-- ----------------------------
-- Table structure for vc_node
-- ----------------------------
DROP TABLE IF EXISTS `vc_node`;
CREATE TABLE `vc_node` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL COMMENT '节点名称',
  `title` varchar(50) NOT NULL COMMENT '菜单名称',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否激活 1：是 2：否',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注说明',
  `pid` smallint(6) unsigned NOT NULL COMMENT '父ID',
  `level` tinyint(1) unsigned NOT NULL COMMENT '节点等级',
  `data` varchar(255) DEFAULT NULL COMMENT '附加参数',
  `sort` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '排序权重',
  `display` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '菜单显示类型 0:不显示 1:导航菜单 2:左侧菜单',
  `deep` smallint(4) NOT NULL DEFAULT '0' COMMENT '节点深度',
  PRIMARY KEY (`id`),
  KEY `level` (`level`),
  KEY `pid` (`pid`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=502 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for vc_order
-- ----------------------------
DROP TABLE IF EXISTS `vc_order`;
CREATE TABLE `vc_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(64) NOT NULL COMMENT '订单号',
  `out_order_sn` varchar(64) DEFAULT NULL COMMENT '支付订单号',
  `order_state` smallint(4) NOT NULL DEFAULT '1' COMMENT '订单状态',
  `address_id` int(10) unsigned NOT NULL COMMENT '地址id',
  `customer_id` int(10) unsigned NOT NULL COMMENT '用户信息',
  `seller_id` int(10) unsigned NOT NULL COMMENT '店铺信息',
  `coupon_id` int(10) unsigned NOT NULL COMMENT '优惠券id',
  `comment` varchar(255) DEFAULT '' COMMENT '备注',
  `order_amount` decimal(12,2) NOT NULL,
  `org_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '初始的订单价格，最初与order_amount保持一致',
  `goods_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `change_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '后台修改金额的价格',
  `express_amount` decimal(12,2) NOT NULL,
  `express_sn` varchar(64) DEFAULT NULL COMMENT '快递单号',
  `express_id` smallint(4) DEFAULT NULL COMMENT '快递公司中文',
  `express` varchar(32) DEFAULT NULL COMMENT '快递公司代号',
  `express_type` smallint(4) NOT NULL DEFAULT '0' COMMENT '收货类型 1：快递 2：自提 3：无需提货',
  `is_comment` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已评论，为未评论，1为已评论',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `date_add` int(10) unsigned DEFAULT NULL COMMENT '创建日期',
  `date_end` int(10) NOT NULL COMMENT '自动结束日期',
  `date_pay` int(10) unsigned DEFAULT NULL COMMENT '支付日期',
  `date_send` int(10) unsigned DEFAULT NULL COMMENT '发货日期',
  `date_received` int(10) unsigned DEFAULT NULL COMMENT '收货日期',
  `date_cancel` int(10) unsigned DEFAULT NULL COMMENT '取消日期',
  `date_refund` int(10) unsigned DEFAULT NULL,
  `date_finish` int(10) unsigned DEFAULT NULL,
  `date_finish_add` int(10) unsigned DEFAULT NULL COMMENT '追加评论时间',
  `pay_id` smallint(4) NOT NULL DEFAULT '0' COMMENT '付款方式',
  `is_delay` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否延长收货日期',
  `user_id` int(10) DEFAULT NULL COMMENT '发货操作员',
  `wx_goods_tag` varchar(255) NOT NULL COMMENT '微信支付商品标识',
  `max_integration` int(10) DEFAULT '0' COMMENT '最大购买积分',
  `max_shopping_coin` int(10) DEFAULT '0' COMMENT '最大购物币',
  `address` varchar(255) DEFAULT NULL COMMENT '自提地址',
  `max_hongfu` decimal(10,2) DEFAULT '0.00' COMMENT '鸿府积分',
  `is_reward` tinyint(3) DEFAULT '0' COMMENT '是否已计算奖励金',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_sn` (`order_sn`),
  KEY `out_order_sn` (`out_order_sn`),
  KEY `address_id` (`address_id`),
  KEY `express_id` (`express_id`),
  KEY `express_sn` (`express_sn`),
  KEY `customer_id` (`customer_id`),
  KEY `date_add` (`date_add`)
) ENGINE=InnoDB AUTO_INCREMENT=989 DEFAULT CHARSET=utf8mb4 COMMENT='订单信息，主要为订单状态等信息，此表为订单主表';

-- ----------------------------
-- Table structure for vc_order_address
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_address`;
CREATE TABLE `vc_order_address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `phone` varchar(64) NOT NULL COMMENT '手机号',
  `address` varchar(512) DEFAULT NULL COMMENT '详细地址',
  `province_id` int(10) unsigned DEFAULT NULL,
  `city_id` int(10) unsigned DEFAULT NULL,
  `district_id` int(10) unsigned DEFAULT NULL,
  `province` varchar(64) DEFAULT NULL,
  `city` varchar(64) DEFAULT NULL,
  `district` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`),
  KEY `province_id` (`province_id`),
  KEY `district_id` (`district_id`),
  KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=971 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_order_cancel
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_cancel`;
CREATE TABLE `vc_order_cancel` (
  `order_cancel_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_reason_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1-处理中，2-已受理，3-已拒绝',
  `other_reason` text NOT NULL,
  `date_add` int(10) unsigned NOT NULL,
  PRIMARY KEY (`order_cancel_id`),
  KEY `order_reason_id` (`order_reason_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_cancel_reason
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_cancel_reason`;
CREATE TABLE `vc_order_cancel_reason` (
  `order_reason_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`order_reason_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_comment
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_comment`;
CREATE TABLE `vc_order_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) DEFAULT '1' COMMENT '1-商品评价，2-店铺评价',
  `order_id` int(10) unsigned NOT NULL,
  `goods_id` int(10) unsigned NOT NULL,
  `option_id` int(10) unsigned NOT NULL,
  `seller_id` int(11) DEFAULT NULL COMMENT '商铺id',
  `customer_id` int(10) NOT NULL,
  `score` smallint(3) NOT NULL DEFAULT '2' COMMENT '1-一颗星，2-两颗星',
  `service_score` tinyint(3) DEFAULT NULL COMMENT '服务评价',
  `logistics_score` tinyint(3) DEFAULT NULL COMMENT '物流评价',
  `content` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images` text COLLATE utf8mb4_unicode_ci,
  `date_add` int(10) unsigned NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `reply_content` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reply_images` text COLLATE utf8mb4_unicode_ci,
  `date_reply` int(10) unsigned DEFAULT NULL,
  `is_anony` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0表示不匿名，1表示匿名',
  `user_id` int(10) NOT NULL COMMENT '操作的管理员id',
  `add_content` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '追评内容',
  `add_images` text COLLATE utf8mb4_unicode_ci COMMENT '追加评价图片',
  `add_date` int(10) unsigned DEFAULT NULL COMMENT '追评日期',
  `is_add` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否追加 0：否 1：是',
  `useful` int(10) DEFAULT '0' COMMENT '有用',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for vc_order_express_type
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_express_type`;
CREATE TABLE `vc_order_express_type` (
  `type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_goods
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_goods`;
CREATE TABLE `vc_order_goods` (
  `order_goods_id` int(10) NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `goods_id` int(10) NOT NULL COMMENT '商品信息',
  `price` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `quantity` int(10) NOT NULL DEFAULT '1' COMMENT '商品数量',
  `option_id` int(10) unsigned DEFAULT NULL COMMENT '样式id',
  `option_name` varchar(128) DEFAULT NULL,
  `full_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '满减金额',
  `new_user_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '新用户金额',
  `special_type` int(10) NOT NULL DEFAULT '0' COMMENT '活动类型',
  `max_integration` int(10) NOT NULL DEFAULT '0' COMMENT '最大购买积分',
  `max_shopping_coin` int(10) NOT NULL DEFAULT '0' COMMENT '最大购物币',
  `coupon_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `max_hongfu` decimal(10,2) DEFAULT NULL COMMENT '鸿府积分',
  `address` varchar(512) DEFAULT NULL COMMENT '购买时是否线下自提',
  PRIMARY KEY (`order_goods_id`),
  KEY `order_id` (`order_id`),
  KEY `goods_id` (`goods_id`),
  KEY `option_id` (`option_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1084 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_order_history
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_history`;
CREATE TABLE `vc_order_history` (
  `order_history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `order_state_id` int(10) unsigned NOT NULL,
  `date_add` int(10) unsigned NOT NULL,
  PRIMARY KEY (`order_history_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  KEY `order_state_id` (`order_state_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_info
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_info`;
CREATE TABLE `vc_order_info` (
  `order_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单号',
  `order_sn` varchar(32) NOT NULL COMMENT '订单号',
  `order_type` smallint(4) NOT NULL,
  `foregin_infos` text COMMENT '外部信息，如果为购买商品信息，则以订单号逗号隔开',
  `customer_id` int(10) unsigned NOT NULL,
  `coupon_id` int(10) NOT NULL DEFAULT '0' COMMENT '优惠券id',
  `pay_id` smallint(4) NOT NULL COMMENT '支付类型',
  `pay_name` varchar(32) NOT NULL COMMENT '支付名称',
  `state` smallint(4) NOT NULL COMMENT '订单状态，1为待支付，2为已支付',
  `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单总金额',
  `goods_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '商品总金额',
  `cash_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '余额支付额度',
  `coupon_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `date_add` int(10) unsigned NOT NULL COMMENT '添加日期',
  `date_pay` int(10) DEFAULT NULL,
  `out_trade_no` varchar(255) DEFAULT NULL COMMENT '外部流水号，如支付宝和微信支付',
  `ip` varchar(64) DEFAULT NULL COMMENT '购买时的ip地址',
  `ip_area` varchar(64) DEFAULT NULL COMMENT '购买时的ip地址区域',
  `full_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '满减金额',
  `integration_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '积分金额',
  `shopping_coin_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '购物币金额',
  `new_user_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '新用户金额',
  `transfer_id` int(10) DEFAULT NULL COMMENT '转账人id',
  `hongfu_amount` decimal(10,2) DEFAULT NULL COMMENT '鸿府积分',
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`) USING BTREE,
  KEY `order_sn` (`order_sn`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1084 DEFAULT CHARSET=utf8 COMMENT='订单支付信息，主要支付操作，如余额支付，支付宝，微信等';

-- ----------------------------
-- Table structure for vc_order_remind
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_remind`;
CREATE TABLE `vc_order_remind` (
  `remind_order_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(64) DEFAULT NULL COMMENT '提醒订单id',
  `customer_id` int(10) unsigned NOT NULL,
  `date_add` int(10) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否提醒',
  PRIMARY KEY (`remind_order_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_return
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_return`;
CREATE TABLE `vc_order_return` (
  `order_return_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `refund_sn` varchar(64) DEFAULT NULL COMMENT '退款订单号',
  `customer_id` int(10) unsigned NOT NULL,
  `reason` text NOT NULL,
  `images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` text NOT NULL,
  `type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '退款方式 1-退款 2-退货 3-换货或维修,4-自提退货',
  `order_id` int(10) unsigned NOT NULL,
  `goods_id` int(10) unsigned NOT NULL,
  `option_id` int(10) unsigned NOT NULL,
  `state` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '退款状态1：申请状态 2：同意退款 3：拒绝退款',
  `price` decimal(14,2) NOT NULL COMMENT '退款金额',
  `remark` varchar(255) DEFAULT NULL,
  `reply` varchar(64) DEFAULT NULL COMMENT '退款方式',
  `reply_remark` varchar(255) DEFAULT NULL COMMENT '回复内容',
  `date_add` int(10) unsigned NOT NULL,
  `date_deal` int(10) unsigned NOT NULL,
  `date_finish` int(10) unsigned NOT NULL,
  `date_audit` int(10) unsigned DEFAULT NULL,
  `order_state` smallint(4) unsigned NOT NULL DEFAULT '2' COMMENT '退款前的订单状态',
  `out_trade_no` varchar(64) DEFAULT NULL COMMENT '外部订单号',
  `express_sn` varchar(64) DEFAULT NULL COMMENT '快递单号',
  `express` varchar(32) DEFAULT NULL COMMENT '快递公司中文',
  `express_id` smallint(4) DEFAULT NULL COMMENT '快递公司代号',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除 0：否 1：是',
  PRIMARY KEY (`order_return_id`),
  KEY `order_return_customer` (`customer_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_return_message
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_return_message`;
CREATE TABLE `vc_order_return_message` (
  `order_return_id` int(10) NOT NULL,
  `title` varchar(128) DEFAULT NULL,
  `content` text,
  `date_add` int(10) NOT NULL,
  `type` varchar(1) NOT NULL DEFAULT '1' COMMENT '类型 1：买家发起 2：卖家发起'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_return_method
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_return_method`;
CREATE TABLE `vc_order_return_method` (
  `order_return_method_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`order_return_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_return_reason
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_return_reason`;
CREATE TABLE `vc_order_return_reason` (
  `order_return_reason_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`order_return_reason_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_return_remind
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_return_remind`;
CREATE TABLE `vc_order_return_remind` (
  `remind_return_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_return_id` varchar(64) DEFAULT NULL COMMENT '提醒订单id',
  `customer_id` int(10) unsigned NOT NULL,
  `date_add` int(10) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否提醒',
  PRIMARY KEY (`remind_return_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_return_state
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_return_state`;
CREATE TABLE `vc_order_return_state` (
  `order_return_state_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL COMMENT '订单状态',
  `date_add` int(10) unsigned NOT NULL,
  PRIMARY KEY (`order_return_state_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_return_type
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_return_type`;
CREATE TABLE `vc_order_return_type` (
  `order_return_type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `date_add` int(10) NOT NULL,
  PRIMARY KEY (`order_return_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_state
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_state`;
CREATE TABLE `vc_order_state` (
  `order_state_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL COMMENT '订单状态',
  `date_add` int(10) unsigned NOT NULL,
  PRIMARY KEY (`order_state_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_order_type
-- ----------------------------
DROP TABLE IF EXISTS `vc_order_type`;
CREATE TABLE `vc_order_type` (
  `order_type_id` smallint(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '类型名',
  `code` varchar(32) NOT NULL COMMENT '订单类型对应编号，表示使用哪个handler',
  PRIMARY KEY (`order_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_pay_type
-- ----------------------------
DROP TABLE IF EXISTS `vc_pay_type`;
CREATE TABLE `vc_pay_type` (
  `pay_id` smallint(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '支付名称',
  `code` varchar(32) NOT NULL COMMENT '支付的代码code',
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0为不可用，1为可用',
  `type` smallint(4) NOT NULL DEFAULT '0' COMMENT '0代表在普通购买服务时显示',
  `is_app` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0为都可以，1为app，2为网页',
  `is_mobile` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0为都可以，1为移动端，2为网页端',
  `is_weixin` tinyint(2) DEFAULT '0' COMMENT '0为都可以，1为微信端，2为网页端',
  `params` text NOT NULL COMMENT '支付参数',
  `mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '商户号',
  `pay_type` enum('alipay','weixinGz','weixinWeb','weixinApp') DEFAULT NULL COMMENT '微信支付类型',
  PRIMARY KEY (`pay_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_real_customer
-- ----------------------------
DROP TABLE IF EXISTS `vc_real_customer`;
CREATE TABLE `vc_real_customer` (
  `real_customer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL COMMENT '客户id',
  `name` varchar(16) DEFAULT NULL COMMENT '实际姓名',
  `phone` varchar(32) DEFAULT NULL COMMENT '电话号码',
  `trade_num` varchar(16) DEFAULT NULL COMMENT '交易商账号',
  `address` varchar(32) DEFAULT NULL COMMENT '详细地址',
  `goods_id` int(10) DEFAULT NULL COMMENT '商品id',
  PRIMARY KEY (`real_customer_id`),
  UNIQUE KEY `customer_id` (`customer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='实名信息表';

-- ----------------------------
-- Table structure for vc_recent_updates
-- ----------------------------
DROP TABLE IF EXISTS `vc_recent_updates`;
CREATE TABLE `vc_recent_updates` (
  `update_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(2000) DEFAULT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `type` tinyint(3) DEFAULT '1' COMMENT '1-更新，2上线',
  `date_add` bigint(10) DEFAULT NULL,
  `date_upd` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`update_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_refund_code
-- ----------------------------
DROP TABLE IF EXISTS `vc_refund_code`;
CREATE TABLE `vc_refund_code` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '验证码',
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL COMMENT '管理员id',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0代表未使用，1代表已使用',
  `order_id` int(10) DEFAULT NULL COMMENT '使用的订单',
  `date_add` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `code` (`code`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for vc_region
-- ----------------------------
DROP TABLE IF EXISTS `vc_region`;
CREATE TABLE `vc_region` (
  `region_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `region_name` varchar(120) NOT NULL DEFAULT '',
  `region_type` tinyint(1) NOT NULL DEFAULT '2',
  `agency_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`region_id`),
  KEY `parent_id` (`parent_id`),
  KEY `region_type` (`region_type`),
  KEY `agency_id` (`agency_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3409 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_role
-- ----------------------------
DROP TABLE IF EXISTS `vc_role`;
CREATE TABLE `vc_role` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL COMMENT '后台组名',
  `pid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `status` tinyint(1) unsigned DEFAULT '0' COMMENT '是否激活 1：是 0：否',
  `sort` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '排序权重',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注说明',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_role_user
-- ----------------------------
DROP TABLE IF EXISTS `vc_role_user`;
CREATE TABLE `vc_role_user` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` smallint(6) unsigned NOT NULL,
  KEY `group_id` (`role_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_seller_action
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_action`;
CREATE TABLE `vc_seller_action` (
  `action_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `id` int(10) DEFAULT NULL,
  `user_name` varchar(50) DEFAULT NULL,
  `action_type` smallint(4) NOT NULL,
  `action_name` varchar(50) NOT NULL,
  `comment` varchar(200) NOT NULL,
  `date_add` int(11) unsigned NOT NULL,
  `foreign_id` int(11) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `controller` varchar(50) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`action_id`)
) ENGINE=InnoDB AUTO_INCREMENT=258 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_seller_brand
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_brand`;
CREATE TABLE `vc_seller_brand` (
  `seller_brand_id` int(10) NOT NULL AUTO_INCREMENT,
  `seller_id` int(10) DEFAULT NULL COMMENT '店铺id',
  `image` varchar(255) DEFAULT NULL COMMENT '品牌图片',
  `brand_name` varchar(32) DEFAULT NULL COMMENT '品牌名称',
  `status` tinyint(3) DEFAULT '0' COMMENT '是否开启',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`seller_brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_seller_category
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_category`;
CREATE TABLE `vc_seller_category` (
  `seller_cat_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(90) NOT NULL DEFAULT '',
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `cat_desc` varchar(255) NOT NULL DEFAULT '',
  `pid` smallint(5) unsigned DEFAULT NULL,
  `sort_order` tinyint(1) unsigned NOT NULL DEFAULT '50',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `grade` tinyint(4) NOT NULL DEFAULT '0',
  `cat_img` varchar(100) NOT NULL COMMENT '分类图片',
  `seller_id` int(11) NOT NULL COMMENT '入驻商家id',
  `level` smallint(10) DEFAULT NULL COMMENT '分类等级',
  `date_add` bigint(10) DEFAULT NULL,
  `date_upd` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`seller_cat_id`),
  KEY `parent_id` (`pid`),
  KEY `sp` (`seller_cat_id`,`pid`),
  KEY `cat_name` (`cat_name`)
) ENGINE=MyISAM AUTO_INCREMENT=1137 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_seller_category_goods
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_category_goods`;
CREATE TABLE `vc_seller_category_goods` (
  `seller_cat_id` int(10) unsigned NOT NULL,
  `goods_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`seller_cat_id`,`goods_id`),
  KEY `goods_id` (`goods_id`),
  KEY `seller_cat_id` (`seller_cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_seller_check
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_check`;
CREATE TABLE `vc_seller_check` (
  `check_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL COMMENT '会员id',
  `company_name` varchar(64) NOT NULL COMMENT '公司名称',
  `province` int(10) NOT NULL COMMENT '省id',
  `city` int(10) NOT NULL COMMENT '市id',
  `district` int(10) NOT NULL COMMENT '区id',
  `address` text NOT NULL COMMENT '详细地址',
  `licence` varchar(255) NOT NULL COMMENT '营业执照',
  `contact_people_name` varchar(32) NOT NULL COMMENT '联系人',
  `phone` varchar(16) NOT NULL COMMENT '联系电话',
  `shop_name` varchar(64) NOT NULL COMMENT '店铺名称',
  `qq_wx` varchar(32) DEFAULT NULL COMMENT 'qq或者微信',
  `card_f` varchar(255) DEFAULT NULL COMMENT '身份证',
  `card_b` varchar(255) DEFAULT NULL COMMENT '身份证',
  `state` tinyint(3) DEFAULT '1' COMMENT '1-审核中，2-通过，3-拒绝',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  `reply_content` text COMMENT '反馈信息',
  `reply_user_id` int(10) DEFAULT NULL COMMENT '反馈人',
  `reply_date` bigint(10) DEFAULT NULL COMMENT '反馈日期',
  `cash_deposit` decimal(10,2) DEFAULT NULL COMMENT '保证金',
  `is_pay` tinyint(3) DEFAULT '1' COMMENT '1-未缴纳,2-缴纳',
  `seller_id` int(10) NOT NULL,
  PRIMARY KEY (`check_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COMMENT='商家审核表';

-- ----------------------------
-- Table structure for vc_seller_follow
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_follow`;
CREATE TABLE `vc_seller_follow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `date_add` int(10) unsigned NOT NULL,
  `date_upd` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=193 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_seller_nav
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_nav`;
CREATE TABLE `vc_seller_nav` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nav_name` varchar(50) NOT NULL COMMENT '导航名称',
  `nav_link` varchar(100) NOT NULL COMMENT '导航链接',
  `nav_order` smallint(5) NOT NULL DEFAULT '0' COMMENT '导航排序',
  `is_show` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示',
  `is_blank` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否新窗口打开',
  `nav_img` varchar(100) NOT NULL COMMENT '导航图标',
  `is_text` tinyint(2) NOT NULL DEFAULT '1' COMMENT '显示文字还是图片默认显示文字',
  `seller_id` int(11) NOT NULL,
  `s_cid` int(11) NOT NULL DEFAULT '0',
  `is_app` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='入驻商家店铺导航表';

-- ----------------------------
-- Table structure for vc_seller_shopinfo
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_shopinfo`;
CREATE TABLE `vc_seller_shopinfo` (
  `seller_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '入驻商家id',
  `shop_name` varchar(50) NOT NULL COMMENT '店铺名称',
  `type` tinyint(3) DEFAULT NULL COMMENT '1-普通店铺，2-旗舰店，3-自营',
  `shop_title` varchar(50) NOT NULL COMMENT '店铺标题',
  `shop_keyword` varchar(50) NOT NULL COMMENT '店铺关键字',
  `country` int(10) NOT NULL COMMENT '所在国家',
  `province` int(10) NOT NULL COMMENT '所在省份',
  `city` int(10) NOT NULL COMMENT '所在城市',
  `area_id` int(10) NOT NULL COMMENT '地区id',
  `address` varchar(255) NOT NULL COMMENT '详细地址',
  `shop_address` varchar(255) NOT NULL COMMENT '详细地址',
  `kf_qq` varchar(50) NOT NULL COMMENT '客服qq',
  `kf_ww` varchar(50) NOT NULL COMMENT '客服旺旺',
  `kf_tel` varchar(50) NOT NULL COMMENT '客服电话',
  `shop_logo` varchar(100) NOT NULL COMMENT '店铺logo',
  `street_logo` varchar(100) NOT NULL COMMENT '店铺街小logo',
  `street_spjpg` varchar(100) NOT NULL COMMENT '店铺街商品大图',
  `notice` varchar(100) NOT NULL COMMENT '店铺公告',
  `shop_header` text COMMENT '店铺头部',
  `action_id` tinyint(3) DEFAULT NULL,
  `params` varchar(255) DEFAULT NULL,
  `shop_color` varchar(20) DEFAULT NULL COMMENT '店铺整体色调',
  `shop_style` tinyint(1) NOT NULL DEFAULT '1' COMMENT '店铺样式1显示左侧信息和分类，0不显示左侧信息和分类',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '店铺状态0关闭,1开启',
  `is_street` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否以加入店铺街，0否，1是',
  `baicheng_apply` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否申请加入百城万店',
  `is_baicheng` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否加入百城万店',
  `remark` varchar(100) NOT NULL COMMENT '网站管理员备注信息',
  `street_cate` int(11) NOT NULL,
  `street_tags` varchar(10) NOT NULL,
  `street_order` tinyint(4) NOT NULL COMMENT '店铺在店铺街的排序',
  `seller_theme` varchar(20) NOT NULL,
  `store_style` varchar(20) NOT NULL,
  `two_code` tinyint(1) NOT NULL DEFAULT '0' COMMENT '店铺二维码',
  `two_code_logo` varchar(500) NOT NULL COMMENT '二维码logo',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `is_youxiu` tinyint(3) DEFAULT '0' COMMENT '1-优秀店铺，0-非优秀店铺',
  `baicheng_agree_time` bigint(10) DEFAULT NULL COMMENT '通过时间',
  `customer_id` int(10) DEFAULT NULL COMMENT '用户id',
  `over_time` bigint(10) DEFAULT NULL COMMENT '过期时间',
  `is_delete` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否删除 0 否 1 是',
  PRIMARY KEY (`seller_id`),
  UNIQUE KEY `customer_id` (`customer_id`),
  KEY `shop_name` (`shop_name`)
) ENGINE=MyISAM AUTO_INCREMENT=376 DEFAULT CHARSET=utf8 COMMENT='入驻商家店铺表';

-- ----------------------------
-- Table structure for vc_seller_shopslide
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_shopslide`;
CREATE TABLE `vc_seller_shopslide` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `seller_id` int(11) NOT NULL DEFAULT '0' COMMENT '入驻商家id',
  `img_url` varchar(100) NOT NULL COMMENT '图片地址',
  `action_id` tinyint(3) NOT NULL COMMENT '图片超链接',
  `name` varchar(50) NOT NULL COMMENT '图片描述',
  `img_order` smallint(6) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示',
  `params` varchar(64) DEFAULT NULL,
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加时间',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_seller_user
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_user`;
CREATE TABLE `vc_seller_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(16) DEFAULT NULL COMMENT '商家名',
  `phone` varchar(16) DEFAULT NULL COMMENT '电话号码',
  `seller_id` smallint(10) unsigned DEFAULT NULL COMMENT '商家id',
  `password` varchar(64) DEFAULT NULL,
  `ec_salt` varchar(10) DEFAULT NULL,
  `status` tinyint(3) DEFAULT '0',
  `last_login_time` bigint(10) DEFAULT NULL COMMENT '上次登录时间',
  `last_location` varchar(16) DEFAULT NULL COMMENT '登录地址',
  `last_login_ip` varchar(32) DEFAULT NULL,
  `customer_id` int(10) DEFAULT NULL COMMENT '会员id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=324 DEFAULT CHARSET=utf8 COMMENT='商家登录表';

-- ----------------------------
-- Table structure for vc_seller_user_profile
-- ----------------------------
DROP TABLE IF EXISTS `vc_seller_user_profile`;
CREATE TABLE `vc_seller_user_profile` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned NOT NULL,
  `nickname` char(60) DEFAULT NULL,
  `birthday` date NOT NULL,
  `sex` enum('保密','男','女') DEFAULT '保密',
  `url` varchar(255) DEFAULT NULL COMMENT '网址',
  `note` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_id` (`sid`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='商家资料表';

-- ----------------------------
-- Table structure for vc_settings
-- ----------------------------
DROP TABLE IF EXISTS `vc_settings`;
CREATE TABLE `vc_settings` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=356 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_shop_node
-- ----------------------------
DROP TABLE IF EXISTS `vc_shop_node`;
CREATE TABLE `vc_shop_node` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL COMMENT '节点名称',
  `title` varchar(50) NOT NULL COMMENT '菜单名称',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否激活 1：是 2：否',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注说明',
  `pid` smallint(6) unsigned NOT NULL COMMENT '父ID',
  `level` tinyint(1) unsigned NOT NULL COMMENT '节点等级',
  `data` varchar(255) DEFAULT NULL COMMENT '附加参数',
  `sort` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '排序权重',
  `display` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '菜单显示类型 0:不显示 1:导航菜单 2:左侧菜单',
  `deep` smallint(4) NOT NULL DEFAULT '0' COMMENT '节点深度',
  PRIMARY KEY (`id`),
  KEY `level` (`level`),
  KEY `pid` (`pid`),
  KEY `status` (`status`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=410 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Table structure for vc_sms
-- ----------------------------
DROP TABLE IF EXISTS `vc_sms`;
CREATE TABLE `vc_sms` (
  `sms_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sms_phone` varchar(31) NOT NULL,
  `sms_content` varchar(255) NOT NULL,
  `date_add` int(11) NOT NULL,
  PRIMARY KEY (`sms_id`),
  KEY `sms_phone` (`sms_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_special
-- ----------------------------
DROP TABLE IF EXISTS `vc_special`;
CREATE TABLE `vc_special` (
  `special_id` smallint(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) DEFAULT NULL COMMENT '1-一元抢购,2-舌尖美食，3-中国智造，4-家居百货，5-积分半价，6-购物币专区，7-百城万店',
  `status` tinyint(3) DEFAULT '1' COMMENT '0-关闭，1-开启',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加日期',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新日期',
  `sort` tinyint(3) DEFAULT '0' COMMENT '顺序',
  PRIMARY KEY (`special_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='活动首页图标';

-- ----------------------------
-- Table structure for vc_special_goods
-- ----------------------------
DROP TABLE IF EXISTS `vc_special_goods`;
CREATE TABLE `vc_special_goods` (
  `special_goods_id` int(10) NOT NULL AUTO_INCREMENT,
  `special_id` smallint(10) unsigned DEFAULT NULL COMMENT '活动id',
  `goods_id` int(10) unsigned DEFAULT NULL COMMENT '商品id',
  `image` text COMMENT '活动标题图',
  `quantity` int(10) unsigned DEFAULT NULL COMMENT '活动可卖商品数量',
  `status` tinyint(3) DEFAULT NULL COMMENT '是否上架',
  `date_start` bigint(10) DEFAULT NULL COMMENT '开始时间',
  `date_end` bigint(10) DEFAULT NULL COMMENT '结束时间',
  `sale_count` int(10) DEFAULT '0' COMMENT '秒杀商品销售数量',
  `shopping_coin` int(10) DEFAULT NULL COMMENT '所需购物币',
  `shopping_type` tinyint(3) DEFAULT NULL COMMENT '1-帅柏优选，2-私人订制，3-饮食，4-服饰，5-居家，6-出行',
  `integral` int(10) DEFAULT NULL COMMENT '使用积分',
  `new_full_cut` varchar(255) DEFAULT NULL COMMENT '满减',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加日期',
  `max_buy` int(10) DEFAULT NULL COMMENT '最大购买量',
  `sort` tinyint(3) DEFAULT NULL COMMENT '排序',
  PRIMARY KEY (`special_goods_id`),
  UNIQUE KEY `sg` (`special_id`,`goods_id`),
  KEY `special_id` (`special_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=MyISAM AUTO_INCREMENT=43 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_special_images
-- ----------------------------
DROP TABLE IF EXISTS `vc_special_images`;
CREATE TABLE `vc_special_images` (
  `special_images_id` smallint(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT '图片名称',
  `special_id` smallint(10) DEFAULT NULL COMMENT '活动id',
  `image` text COMMENT '活动标题图',
  `params` varchar(255) DEFAULT NULL COMMENT '参数',
  `sort` tinyint(3) DEFAULT NULL COMMENT '排序',
  `status` tinyint(3) DEFAULT NULL COMMENT '是否可用',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加日期',
  `action_id` int(10) DEFAULT NULL COMMENT '跳转id',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`special_images_id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_special_type
-- ----------------------------
DROP TABLE IF EXISTS `vc_special_type`;
CREATE TABLE `vc_special_type` (
  `type` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(16) DEFAULT NULL COMMENT '活动名称',
  `status` tinyint(3) DEFAULT '1' COMMENT '0-不可用，1-可用',
  `activities` varchar(64) DEFAULT NULL COMMENT '方法',
  `date_add` bigint(10) DEFAULT NULL COMMENT '添加日期',
  `date_upd` bigint(10) DEFAULT NULL COMMENT '更新日期',
  PRIMARY KEY (`type`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='活动类型';

-- ----------------------------
-- Table structure for vc_state
-- ----------------------------
DROP TABLE IF EXISTS `vc_state`;
CREATE TABLE `vc_state` (
  `state_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(11) unsigned NOT NULL,
  `zone_id` int(11) unsigned NOT NULL,
  `name` varchar(64) NOT NULL,
  `iso_code` varchar(7) NOT NULL,
  `tax_behavior` smallint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`state_id`),
  KEY `name` (`name`),
  KEY `country_id` (`country_id`) USING BTREE,
  KEY `zone_id` (`zone_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_tag
-- ----------------------------
DROP TABLE IF EXISTS `vc_tag`;
CREATE TABLE `vc_tag` (
  `tag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag_word` varchar(16) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '标签是否被删除(1为删除)',
  `tag_type` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for vc_terminal
-- ----------------------------
DROP TABLE IF EXISTS `vc_terminal`;
CREATE TABLE `vc_terminal` (
  `id` smallint(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for vc_trail
-- ----------------------------
DROP TABLE IF EXISTS `vc_trail`;
CREATE TABLE `vc_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `goods_id` int(11) NOT NULL,
  `date_add` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1605 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_user
-- ----------------------------
DROP TABLE IF EXISTS `vc_user`;
CREATE TABLE `vc_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` char(32) NOT NULL,
  `role` smallint(6) unsigned NOT NULL COMMENT '组ID',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1:启用 0:禁止',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注说明',
  `last_login_time` int(11) unsigned NOT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(15) DEFAULT NULL COMMENT '最后登录IP',
  `last_location` varchar(100) DEFAULT NULL COMMENT '最后登录位置',
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='用户表';

-- ----------------------------
-- Table structure for vc_user_action
-- ----------------------------
DROP TABLE IF EXISTS `vc_user_action`;
CREATE TABLE `vc_user_action` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(50) DEFAULT NULL,
  `action_type` smallint(4) NOT NULL,
  `action_name` varchar(50) NOT NULL,
  `comment` varchar(200) NOT NULL,
  `date_add` int(11) unsigned NOT NULL,
  `foreign_id` int(11) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `controller` varchar(50) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_user_profile
-- ----------------------------
DROP TABLE IF EXISTS `vc_user_profile`;
CREATE TABLE `vc_user_profile` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `nickname` char(60) DEFAULT NULL,
  `birthday` date NOT NULL,
  `sex` enum('保密','男','女') DEFAULT '保密',
  `url` varchar(255) DEFAULT NULL COMMENT '网址',
  `note` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_user_seller
-- ----------------------------
DROP TABLE IF EXISTS `vc_user_seller`;
CREATE TABLE `vc_user_seller` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '会员id',
  `is_check` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核状态,0未审核，1审核通过，2审核不通过 3.等待缴纳保证金',
  `checkout_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '结算类型0周，1月，2季度，3年',
  `use_fee` decimal(10,0) NOT NULL COMMENT '平台使用费',
  `deposit` decimal(10,0) NOT NULL COMMENT '商家保证金',
  `fencheng` float NOT NULL COMMENT '分成百分比，只填数字',
  `remark` varchar(100) NOT NULL COMMENT '商家的备注信息',
  `add_time` varchar(20) NOT NULL COMMENT '添加或者跟新时间',
  `log_id` int(10) unsigned NOT NULL COMMENT '支付id',
  `is_youxiu` tinyint(1) unsigned DEFAULT '0' COMMENT '是否是优秀店铺 0 不是 1是',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='会员入驻商家信息';

-- ----------------------------
-- Table structure for vc_user_statistics
-- ----------------------------
DROP TABLE IF EXISTS `vc_user_statistics`;
CREATE TABLE `vc_user_statistics` (
  `customer_id` int(10) unsigned NOT NULL,
  `oauth_type` smallint(4) unsigned NOT NULL,
  `islogin` smallint(4) unsigned NOT NULL DEFAULT '1',
  `date_add` int(10) unsigned NOT NULL,
  `ip` varchar(30) NOT NULL,
  `terminal` smallint(4) DEFAULT '0',
  `longitude` varchar(128) DEFAULT NULL COMMENT '经度',
  `latitude` varchar(128) DEFAULT NULL COMMENT '纬度'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_verify_code
-- ----------------------------
DROP TABLE IF EXISTS `vc_verify_code`;
CREATE TABLE `vc_verify_code` (
  `verify_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(31) NOT NULL,
  `email` varchar(64) DEFAULT NULL COMMENT '邮箱',
  `code` varchar(31) NOT NULL,
  `date_add` int(11) NOT NULL,
  `type` smallint(4) NOT NULL DEFAULT '0' COMMENT '0:注册，1找回密码，2短信登录，3更改手机号，4修改手机号, 5修改邮箱, 6修改密码, 7解绑邮箱，8绑定手机，9更改绑定手机,10-修改支付密码,11-修改转账密码',
  `state` smallint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`verify_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2020 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vc_zone
-- ----------------------------
DROP TABLE IF EXISTS `vc_zone`;
CREATE TABLE `vc_zone` (
  `zone_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL,
  `name` varchar(32) NOT NULL,
  `active` enum('0','1') NOT NULL DEFAULT '1',
  `sort` int(10) unsigned NOT NULL,
  `postcode` int(10) unsigned NOT NULL,
  `level` tinyint(1) unsigned NOT NULL,
  `area_group_id` smallint(4) NOT NULL,
  `first` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`zone_id`),
  KEY `pid` (`pid`),
  KEY `area_group_id` (`area_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4150 DEFAULT CHARSET=utf8;
