
-- SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

DROP TABLE IF EXISTS `TMS_user`;
CREATE TABLE `TMS_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` int(11) DEFAULT NULL,
  `uname` varchar(32) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `admin` int(11) DEFAULT '0',
  `upass` text,
  `company` varchar(255) DEFAULT NULL,
  `division` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `fullname_rubi` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `zip` varchar(8) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `town` varchar(255) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `tel` varchar(13) DEFAULT NULL,
  `fax` varchar(13) DEFAULT NULL,
  `contract` date DEFAULT NULL,
  `expire` int(11) DEFAULT NULL,
  `closing` int(11) DEFAULT NULL,
  `pay` int(11) DEFAULT NULL,
  `forward` int(11) DEFAULT NULL,
  `type` char(1) DEFAULT NULL,
  `restriction` varchar(32) DEFAULT NULL,
  `free1` text,
  `free2` text,
  `free3` text,
  `create_date` datetime NOT NULL,
  `modify_date` datetime DEFAULT NULL,
  `lft` double unsigned DEFAULT '0',
  `rgt` double unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uname` (`uname`),
  UNIQUE KEY `email` (`email`,`admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `TMS_user_preference`;
CREATE TABLE `TMS_user_preference` (
  `userkey` int(11) NOT NULL DEFAULT '0',
  `section` varchar(32) NOT NULL DEFAULT '',
  `config` varchar(32) NOT NULL DEFAULT '',
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`userkey`,`section`,`config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `TMS_permission`;
CREATE TABLE `TMS_permission` (
  `userkey` int(11) NOT NULL,
  `filter1` int(11) NOT NULL DEFAULT '0',
  `filter2` int(11) NOT NULL DEFAULT '0',
  `application` varchar(32) NOT NULL DEFAULT '',
  `class` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '',
  `priv` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`userkey`,`filter1`,`filter2`,`application`,`class`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

COMMIT;
