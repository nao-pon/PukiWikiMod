# --------------------------------------------------------

CREATE TABLE pukiwikimod_count (
  `name` varchar(255) NOT NULL default '',
  `count` int(10) NOT NULL default '0',
  `today` varchar(10) NOT NULL default '',
  `today_count` int(10) NOT NULL default '0',
  `yesterday_count` int(10) NOT NULL default '0',
  `ip` varchar(15) NOT NULL default '',
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM;

# --------------------------------------------------------

CREATE TABLE pukiwikimod_pginfo (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(255) binary NOT NULL default '',
  `buildtime` int(10) NOT NULL default '0',
  `editedtime` int(10) NOT NULL default '0',
  `aids` text NOT NULL,
  `gids` varchar(255) NOT NULL default '',
  `vaids` text NOT NULL,
  `vgids` varchar(255) NOT NULL default '',
  `lastediter` mediumint(8) NOT NULL default '0',
  `uid` mediumint(8) NOT NULL default '0',
  `freeze` tinyint(1) NOT NULL default '0',
  `unvisible` tinyint(1) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `update` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM;

# --------------------------------------------------------

CREATE TABLE pukiwikimod_plain (
  `pgid` int(10) NOT NULL default '0',
  `plain` text NOT NULL,
  PRIMARY KEY  (`pgid`)
) TYPE=MyISAM;

# --------------------------------------------------------

CREATE TABLE pukiwikimod_tb (
  `last_time` int(10) NOT NULL default '0',
  `url` text NOT NULL,
  `title` varchar(255) NOT NULL default '',
  `excerpt` text NOT NULL,
  `blog_name` varchar(255) NOT NULL default '',
  `tb_id` varchar(32) NOT NULL default '',
  `page_name` varchar(255) NOT NULL default '',
  `ip` varchar(15) NOT NULL default '',
  KEY `page_id` (`tb_id`),
  KEY `page_name` (`page_name`)
) TYPE=MyISAM;
