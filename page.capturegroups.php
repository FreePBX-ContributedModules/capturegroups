<?php /* $Id: page.capturegroups.php   $ */
//Copyright (C) 2008 TI Soluciones (msalazar at solucionesit dot com dot ve)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

/*include_once ( dirname(__FILE__) . "/../../extensions.class.php");
$ext = new extensions();


capturegroups_get_config("asterisk");

echo "<pre>";
print_r($ext->generateConf());
echo "</pre>";

exit;*/


if (isset($_GET["extensions"], $_GET["ajax"]))
{
	$result = capturegroups_search($_GET["extensions"]);
	if (isset($result))
	{
		if (!empty($result))
		{
			foreach ($result as $extension)
			{
				echo $extension["exten"] . " extension is member of  " . $extension["desc"] . $extension["v_exten"]. " group <br />";
			}

		}
		else
		{
			echo "Not matches";
		}
	}
	else
	{
		echo "Critery Invalid!";
	}
	exit(1);
}

	$dispnum = 'capturegroups'; //used for switch on config.php
	$extensionsCleaned = array();
	$title = _("Capture Groups");
	$messages	= "";
	$params = array();
	if (isset($_POST["submitAdd"])) 
	{
		$client_extensions = capturegroups_str_extensions_to_array($_POST["client_extensions"]);
		$client_extensions = capturegroups_clean_extensions($client_extensions);
		$errors = capturegroups_group_add($_POST["desc"], $_POST["v_exten"],$client_extensions);
		$params["message_title"] = "";
		$params["message_details"] = array();

		if (empty($errors))
		{
			$_GET["bsgroupdisplay"] = "";
			$params["desc"] = "";
			$params["v_exten"] = "";
			$params["client_extensions"] = array();
			$params["message_title"] = "Group Added";
			$params["message_details"] = array("Group " . $_POST["desc"] . " (" . $_POST["v_exten"] . ") was added successfully");
			needreload();
		}
		else
		{
			$params["desc"] = $_POST["desc"];
			$params["v_exten"] = $_POST["v_exten"];
			$params["client_extensions"] = $client_extensions;			
			$params["message_title"] = "Errors were encountered, details";
			$params["message_details"] = $errors;	
		}
		$content = capturegroups_get_form_add( $params);
	}
	elseif(isset($_POST["submitEdit"]))
	{
		$client_extensions = capturegroups_str_extensions_to_array($_POST["client_extensions"]);
		$client_extensions = capturegroups_clean_extensions($client_extensions, $_POST["captgroup_desc_edit"]);
		$errors = capturegroups_group_edit($_POST["desc"], $_POST["v_exten"],$_POST["v_exten_release"],$client_extensions, $_POST["captgroup_desc_edit"]);
		if (empty($errors))
		{
			$params["message_title"] = "Group Edited";
			$params["message_details"] = array("Group " . $_POST["desc"] . " (" . $_POST["v_exten"] . ") was edited successfully");
			needreload();
		}
		else
		{
			$params["message_title"] = "Errors were encountered, details";
			$params["message_details"] = $errors;
		}
		$params["desc"] = $_POST["desc"];
		$params["v_exten"] = $_POST["v_exten"];
		$params["captgroup_desc_edit"] = $_POST["captgroup_desc_edit"];
		$params["client_extensions"] = $client_extensions;
		$content = capturegroups_get_form_edit( $params);
	}
	elseif (isset($_GET["capturegroupsdisplay"]))
  	{
		$group =  capturegroups_extract_group_from_request($_GET["capturegroupsdisplay"]);
		if ($group == "add")
		{
			
			$content = capturegroups_get_form_add($params);
		}
		else
		{
			$params = capturegroups_set_params_to_edit(capturegroups_get_data_of_group($group));
			$content = capturegroups_get_form_edit($params);
		}
	}
	elseif (isset($_GET["captgroupdelete"]))
	{
		$group =  capturegroups_extract_group_from_request($_GET["captgroupdelete"]);
		if (capturegroups_group_delete($group))
		{
			$content = "<br /> Group was deleted successfully <br /> <br /> <br /><h3>Choose a group or add one:</h3> ";
			needreload();
		}
		else
		{
			$content = "<br /> Group was not deleted, please try it again <br /> <br /> <br /><h3>Choose a group or add one:</h3>";
		}
	}	
	else
	{
		$content = "<br /> <br /> <br /><h3>Choose a group or add one:</h3>";
	}
	

	$groups = capturegroups_get_groups();
	$linksGroups = capturegroups_create_nav_groups_links($groups, $dispnum);

	
	capturegroups_show_nav_users($linksGroups);
	capturegroups_content($title, $content, $messages);
?>
