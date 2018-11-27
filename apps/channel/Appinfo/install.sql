/*
Navicat MySQL Data Transfer

Source Server         : 127.0.0.1
Source Server Version : 50516
Source Host           : 127.0.0.1:3306
Source Database       : thinksns

Target Server Type    : MYSQL
Target Server Version : 50516
File Encoding         : 65001

Date: 2012-10-23 16:57:34
*/

SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for `ts_channel`
-- ----------------------------
DROP TABLE IF EXISTS `ts_channel`;
CREATE TABLE `ts_channel` (
  `feed_channel_link_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `feed_id` int(11) NOT NULL COMMENT '分享ID',
  `channel_category_id` int(11) NOT NULL COMMENT '频道分类ID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '审核状态 1审核 0未审核',
  `width` int(11) NOT NULL DEFAULT '0' COMMENT '图片宽度',
  `height` int(11) NOT NULL DEFAULT '0' COMMENT '图片高度',
  `uid` int(11) NOT NULL COMMENT '用户UID',
  PRIMARY KEY (`feed_channel_link_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ts_channel
-- ----------------------------

-- ----------------------------
-- Table structure for `ts_channel_category`
-- ----------------------------
DROP TABLE IF EXISTS `ts_channel_category`;
CREATE TABLE `ts_channel_category` (
  `channel_category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '频道分类ID',
  `title` varchar(225) NOT NULL COMMENT '频道分类名称',
  `pid` int(11) NOT NULL COMMENT '父分类ID',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序字段',
  `ext` text COMMENT '分类配置相关信息序列化',
  PRIMARY KEY (`channel_category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ts_channel_category
-- ----------------------------

-- ----------------------------
-- Table structure for `ts_channel_follow`
-- ----------------------------
DROP TABLE IF EXISTS `ts_channel_follow`;
CREATE TABLE `ts_channel_follow` (
  `channel_follow_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '频道关注主键',
  `uid` int(11) NOT NULL COMMENT '关注用户ID',
  `channel_category_id` int(11) NOT NULL COMMENT '频道分类ID',
  PRIMARY KEY (`channel_follow_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='频道关注表';

-- ----------------------------
-- Records of ts_channel_follow
-- ----------------------------


-- ----------------------------
-- 语言包
-- ----------------------------
DELETE FROM `ts_lang` WHERE `key` = 'PUBLIC_APPNAME_CHANNEL';
INSERT INTO `ts_lang` (`key`, `appname`, `filetype`, `zh-cn`, `en`, `zh-tw`) VALUES ('PUBLIC_APPNAME_CHANNEL', 'PUBLIC', '0', '频道', 'Channel', '頻道');

-- ----------------------------
-- 任务
-- ----------------------------
INSERT INTO `ts_task` VALUES ('34', '3', '达人任务', '2', '向频道投稿并收录2条以上', '向频道投稿并至少有2条收录到频道', '{\"channelcontribute\":2}', '', null, '{\"exp\":6,\"score\":6,\"medal\":{\"id\":85,\"name\":\"\\u9891\\u9053\\u5148\\u950b\",\"src\":\"2012\\/1226\\/10\\/50da60fb03a21.png\"}}');
INSERT INTO `ts_task` VALUES ('42', '4', '高手任务', '2', '向频道投稿有100条以上被收录', '向频道投稿并有100条以上收录到频道', '{\"channelcontribute\":100}', '', null, '{\"exp\":6,\"score\":6,\"medal\":{\"id\":92,\"name\":\"\\u9891\\u9053\\u52b3\\u6a21\",\"src\":\"2012\\/1226\\/10\\/50da62609cf75.png\"}}');
INSERT INTO `ts_task` VALUES ('51', '5', '终极任务', '2', '向频道投稿被收录1000条以上', '向频道投稿并有1000条以上收录到频道', '{\"channelcontribute\":1000}', '', null, '{\"exp\":6,\"score\":6,\"medal\":{\"id\":99,\"name\":\"\\u5fa1\\u7528\\u53d1\\u8a00\\u4eba\",\"src\":\"2012\\/1226\\/10\\/50da644fd373e.png\"}}');

-- ----------------------------
-- Records of ts_channel_category
-- ----------------------------
INSERT INTO `ts_channel_category` VALUES ('1', '频道1', '0', '1', null);
INSERT INTO `ts_channel_category` VALUES ('2', '频道2', '0', '2', null);
INSERT INTO `ts_channel_category` VALUES ('3', '频道3', '0', '3', null);