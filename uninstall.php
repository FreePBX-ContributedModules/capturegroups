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

?>
