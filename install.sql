CREATE TABLE IF NOT EXISTS `capturegroups_groups` (
  `capturegroups_groups_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `v_exten` smallint(5) unsigned NOT NULL,
  `desc` varchar(20) NOT NULL,
  PRIMARY KEY (`capturegroups_groups_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `capturegroups_extens` (
   `capturegroups_groups_id` int(10) unsigned NOT NULL,
  `exten` varchar(20) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 ;



