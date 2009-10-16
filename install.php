<?php

$sql = "
CREATE TABLE IF NOT EXISTS `capturegroups_groups` (
  `capturegroups_groups_id` int(10) unsigned NOT NULL auto_increment,
  `v_exten` smallint(5) unsigned NOT NULL,
  `desc` varchar(20) NOT NULL,
  PRIMARY KEY  (`capturegroups_groups_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
";

$check = $db->query($sql);

if(DB::IsError($check)) {
	die_freepbx("Can not create capturegroups_groups table");
}

$sql = "
CREATE TABLE IF NOT EXISTS  `capturegroups_extens` (
  `capturegroups_groups_id` int(10) unsigned NOT NULL,
  `exten` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
";

$check = $db->query($sql);

if(DB::IsError($check)) {
        die_freepbx("Can not create capturegroups_extens table");
}

$sql = "
CREATE TABLE IF NOT EXISTS `capturegroups_v_extens_free` (
  `v_exten` smallint(5) unsigned NOT NULL,
  PRIMARY KEY  (`v_exten`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
";

$check = $db->query($sql);

if(DB::IsError($check)) {
        die_freepbx("Can not create capturegroups_v_extens_free table");
}
?>
