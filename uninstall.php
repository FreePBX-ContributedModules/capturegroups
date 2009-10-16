<?php


global $db;
$sql = "SELECT capturegroups_groups_id AS id FROM capturegroups_groups AS g";
$results = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
if(DB::IsError($results)) {
	$results = null;
}

// unsubscribe all capturegroups
foreach ($results as $row)
{
	capturegroups_unsubscribe($row["id"]);
}

$sql = "
DROP TABLE `capturegroups_extens`;
";

$check = $db->query($sql);

if(DB::IsError($check)) {
        die_freepbx("Cannot drop capturegroups_extens table");
}

$sql = "
DROP TABLE `capturegroups_groups`;
";

$check = $db->query($sql);

if(DB::IsError($check)) {
        die_freepbx("Cannot drop capturegroups_groups table");
}

$sql = "
DROP TABLE `capturegroups_v_extens_free`;
";

$check = $db->query($sql);

if(DB::IsError($check)) {
        die_freepbx("Cannot drop capturegroups_v_extens_free table");
}
?>
