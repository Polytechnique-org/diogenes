-- phpMyAdmin SQL Dump
-- version 2.6.1-pl3
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Apr 09, 2005 at 01:25 AM
-- Server version: 4.0.24
-- PHP Version: 4.3.10-10
-- 
-- Database: `diogenes`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `diogenes_auth`
-- 

CREATE TABLE `diogenes_auth` (
  `user_id` int(10) unsigned NOT NULL auto_increment,
  `username` varchar(32) NOT NULL default '',
  `firstname` varchar(127) NOT NULL default '',
  `lastname` varchar(127) NOT NULL default '',
  `password` varchar(32) NOT NULL default '',
  `perms` enum('user','admin') NOT NULL default 'user',
  `email` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `username` (`username`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `diogenes_logactions`
-- 

CREATE TABLE `diogenes_logactions` (
  `id` int(2) NOT NULL auto_increment,
  `text` varchar(32) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `diogenes_logevents`
-- 

CREATE TABLE `diogenes_logevents` (
  `stamp` timestamp(14) NOT NULL,
  `session` int(6) NOT NULL default '0',
  `action` int(2) NOT NULL default '0',
  `data` varchar(255) NOT NULL default '',
  KEY `session` (`session`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `diogenes_logsessions`
-- 

CREATE TABLE `diogenes_logsessions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `auth` enum('native','external') NOT NULL default 'native',
  `uid` int(10) unsigned NOT NULL default '0',
  `start` timestamp(14) NOT NULL,
  `ip` varchar(64) NOT NULL default '',
  `host` varchar(128) NOT NULL default '',
  `sauth` enum('native','external') NOT NULL default 'native',
  `suid` int(10) unsigned NOT NULL default '0',
  `browser` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `uid` (`uid`),
  KEY `start` (`start`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `diogenes_option`
-- 

CREATE TABLE `diogenes_option` (
  `barrel` VARCHAR( 16 ) NOT NULL default '',
  `name` varchar(32) NOT NULL default '',
  `value` text NOT NULL,
  PRIMARY KEY  (`barrel`, `name`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `diogenes_perm`
-- 

CREATE TABLE `diogenes_perm` (
  `alias` varchar(16) NOT NULL default '',
  `auth` enum('native','external') NOT NULL default 'native',
  `uid` int(10) unsigned NOT NULL default '0',
  `perms` enum('admin','user') NOT NULL default 'user',
  PRIMARY KEY  (`alias`,`auth`,`uid`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `diogenes_plugin`
-- 

CREATE TABLE `diogenes_plugin` (
  `plugin` varchar(32) NOT NULL default '',
  `barrel` varchar(16) NOT NULL default '',
  `page` int(10) unsigned NOT NULL default '0',
  `pos` int(10) unsigned NOT NULL default '0',
  `params` text NOT NULL,
  `status` int(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`plugin`,`barrel`,`page`),
  KEY `pos` (`pos`)
) TYPE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `diogenes_site`
-- 

CREATE TABLE `diogenes_site` (
  `alias` varchar(16) NOT NULL default '',
  `vhost` varchar(255) NOT NULL default '',
  `flags` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`alias`)
) TYPE=MyISAM;

-- --------------------------------------------------------

--
-- Set the database format version
--

INSERT INTO `diogenes_option` SET name='dbversion', value='0.9.16+0.9.17pre21';
