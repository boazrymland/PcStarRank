/*
* Ranking table
*/
DROP TABLE IF EXISTS `rankings`;
CREATE TABLE `rankings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `model_name` varchar(128) NOT NULL,
  `model_id` int(11) unsigned NOT NULL,
  `status` tinyint(2) unsigned NOT NULL DEFAULT '1',
  `average_score` tinyint(2) NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_on` timestamp NULL DEFAULT NULL,
  `lock_version` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `single_model_object_record` (`model_name`,`model_id`),
  KEY `model_id` (`model_id`),
  KEY `model_name` (`model_name`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

/*
* Ranking votes table
*/
DROP TABLE IF EXISTS `ranking_votes`;
CREATE TABLE `ranking_votes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `score_ranked` tinyint(2) NOT NULL,
  `ranking_id` int(11) unsigned NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_on` timestamp NULL DEFAULT NULL,
  `lock_version` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `raking_id` (`ranking_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_ranking_id` FOREIGN KEY (`ranking_id`) REFERENCES `rankings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;

