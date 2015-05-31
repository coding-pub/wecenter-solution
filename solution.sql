CREATE TABLE IF NOT EXISTS `aws_solution` (
	`solution_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`question_id` int(11) unsigned NOT NULL,
	`solution_content` text NOT NULL,
	`add_time` int(11) DEFAULT '0',
	`update_time` int(11) DEFAULT '0',
	`published_uid` int(11)  unsigned DEFAULT '0',
	`has_attach` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`view_cost` int(10) unsigned NOT NULL DEFAULT '0',
	`view_count` int(11) unsigned NOT NULL DEFAULT '0',
	`agree_count` int(11) unsigned NOT NULL DEFAULT '0',
	`against_count` int(11) unsigned NOT NULL DEFAULT '0',
	`ip` bigint(11),
	`status` tinyint(1) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`solution_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `aws_user_solution` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`user_id` int(11) unsigned NOT NULL,
	`solution_id` int(11) unsigned NOT NULL,
	`add_time` int(11) DEFAULT '0',
	PRIMARY KEY (`solution_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

