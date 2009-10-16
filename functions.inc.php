<?php


/********************************************************
*														*
* 					API FUNCTIONS						*
*														*
********************************************************/


define("CAPTUREGROUPS_PARAM_PREFIX", "capturegroups-");
define("CAPTUREGROUPS_CONTEXT", "ext-capturegroups");
define("CAPTUREGROUPS_CONTEXT_GROUPS_DEFAULT", "capturegroup");
function capturegroups_get_config($engine)
{
	global $db;
	global $ext;

	switch($engine) {
		case "asterisk":
			
			//$groups = capturegroups_get_data_of_group
			$groups = capturegroups_get_all_groups();
			if (!empty($groups))
			{
				$contexts = array();
				$ctx_capturegroups = CAPTUREGROUPS_CONTEXT;
				$ctx_custom_group = "";
				$ext->add(CAPTUREGROUPS_CONTEXT, "s", '' ,new ext_noop('Just including my capturegroups contexts') );
				$ctx_createds = array();
				foreach($groups as $group)
				{
					$virtual_extension = $group["v_exten"];
					$ctx_prefix = $group["desc"];
					$extension = $group["exten"];
					
					//nombre del grupo
					$ctx_custom_group = $ctx_prefix .   $virtual_extension;
					
					if (!isset($ctx_createds[$ctx_custom_group]))
					{             					
						
						$ext->add($ctx_custom_group, $virtual_extension, "", new ext_noop("Capture group: " . $ctx_custom_group));
						$ext->addInclude($ctx_custom_group, "ext-dnd-hints");
						$ext->addInclude($ctx_custom_group, "app-dnd-toggle");
						
						$ctx_createds[$ctx_custom_group]["virtual_extension"] =	$virtual_extension;
						$ctx_createds[$ctx_custom_group]["extensions"] = array( "SIP/". $extension);
					}
					else
					{
						array_push($ctx_createds[$ctx_custom_group]["extensions"], "SIP/". $extension);
					}
					$sql = "REPLACE INTO sip VALUES ($extension, 'subscribecontext', '$ctx_custom_group', 0)";
					sql($sql);
					$sql = "REPLACE INTO sip VALUES ($extension, 'callgroup', '$virtual_extension', 0)";
					sql($sql);
					$sql = "REPLACE INTO sip VALUES ($extension, 'pickupgroup', '$virtual_extension', 0)";
					sql($sql);
					$sql = "UPDATE sip set data=(SELECT name FROM users WHERE extension=$extension LIMIT 1) WHERE keyword='callerid' AND id='$extension' LIMIT 1";
					sql($sql);

					// creamos el hint para la extension, asi la podemos monitorear
					$hint = "SIP/". $extension . "&Custom:DND". $extension;
					$ext->add($ctx_custom_group, $extension, "", new ext_noop("$extension Hint"));
					$ext->addHint($ctx_custom_group, $extension, $hint);
					
					
				}	
			
				foreach ($ctx_createds as $ctx => $data)
				{
					// creamos el hint para la extension virtual
					$ext->addHint($ctx,$data["virtual_extension"], implode($data["extensions"], "&"));
					// incluimos el grupo al contexto global del modulo grupo de captura
					$ext->addInclude(CAPTUREGROUPS_CONTEXT, $ctx);
				}
					
           }
		break;
	}

}


/********************************************************
*														*
* 					DATABASE FUNCTIONS					*
*														*
********************************************************/

function capturegroups_get_groups()
{
	global $db;
	$sql = "SELECT * from capturegroups_groups ORDER BY v_exten ASC";
	$results = $db->getAll($sql);
    if(DB::IsError($results)) {
		$results = null;
	}
	return $results;
}

function capturegroups_search( $extensions)
{
	global $db;

        $extensions = explode(",", $extensions);
        foreach ($extensions as $extension)
        {
                if (is_numeric($extension))
                {
                   $valid[]= trim($extension);
                }

        }
        if (!empty($valid))
        {
                $extensions = implode(",", $valid);
		$sql = "SELECT g.desc, g.v_exten, e.exten FROM capturegroups_groups AS g INNER JOIN capturegroups_extens AS e ON g.capturegroups_groups_id = e.capturegroups_groups_id WHERE e.exten IN ($extensions) ORDER BY e.exten ASC";

                $results = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
                if(DB::IsError($results)) {
                        $results = null;
                }
                return $results;
        }
        return null;



}

function capturegroups_get_all_groups()
{
	global $db;
	$sql = "SELECT g.desc, g.v_exten, e.exten FROM capturegroups_groups AS g INNER JOIN capturegroups_extens AS e ON g.capturegroups_groups_id = e.capturegroups_groups_id;";
	$results = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
    if(DB::IsError($results)) {
		$results = null;
	}
	return $results;
}

function capturegroups_group_add ( $group_desc, $v_exten, array $client_extens)
{
	global $db;
	$errors= array();
	$group = trim($group_desc);
	if (empty($group))
	{
		array_push($errors, "Context is an empty string");
	}
	if (capturegroups_virtual_exten_exists($v_exten))
	{
		array_push($errors, "Virtual Extension already exists");
	}
	if (empty($client_extens))
	{
		array_push($errors, "There must be at least one extension");
	}
	if (empty($errors))
	{
		$sql = "INSERT INTO capturegroups_groups VALUES('', '$v_exten', '$group_desc')";
		sql($sql);
		$lastID = mysql_insert_id();
		// just in case
		capturegroups_remove_virtual_extension_free($v_exten);
		foreach ($client_extens as $exten)	
		{
			$exten = trim($exten);					
			$sql = "INSERT INTO capturegroups_extens VALUES ('$lastID', '$exten')";
			sql($sql);
		}
	}
	return $errors;
}


function capturegroups_group_edit ( $group_desc, $v_exten, $v_exten_release, array $client_extens, $id)
{
	global $db;
	$errors= array();
	if (isset($v_exten_release) and $v_exten!= $v_exten_release and  !capturegroups_virtual_exten_exists($v_exten_release, $id) and is_numeric($v_exten_release))
	{
		capturegroups_set_virtual_extension_free($v_exten_release);
	}
	$group = trim($group_desc);	
	if (!is_numeric($id))
	{
		array_push($errors, "Group doesn't exist");
	}

	if (empty($group))
	{
		array_push($errors, "Context is an empty string");
	}
	if (capturegroups_virtual_exten_exists($v_exten, $id))
	{
		array_push($errors, "Virtual Extension already exists");
	}



	if (empty($client_extens))
	{
		array_push($errors, "There must be at least one extension");
	}
	
			
	if (empty($errors))
	{
		capturegroups_unsubscribe ($id);		
		$sql = "UPDATE capturegroups_groups SET v_exten='" . $db->escapeSimple($v_exten) . "', `desc` = '".$db->escapeSimple($group_desc)."' WHERE capturegroups_groups_id = '$id'";
		sql($sql);
		$sql = "DELETE FROM `capturegroups_extens` WHERE capturegroups_groups_id = '$id'";
		sql($sql);
		// just in case
		capturegroups_remove_virtual_extension_free($v_exten);
		$context = $group_desc . $v_exten;
		foreach ($client_extens as $exten)	
		{
			$exten = trim($exten);					
			$sql = "INSERT INTO capturegroups_extens VALUES ('$id', '$exten')";
			sql($sql);
			$sql = "REPLACE INTO sip VALUES ($exten, 'subscribecontext', '$context', 0)";
			sql($sql);
			$sql = "REPLACE INTO sip VALUES ($exten, 'callgroup', '$v_exten', 0)";
			sql($sql);
			$sql = "REPLACE INTO sip VALUES ($exten, 'pickupgroup', '$v_exten', 0)";
			sql($sql);
		}
	}
	
	return $errors;
}


function capturegroups_unsubscribe ( $id)
{
	global $db;
	$sql = "SELECT e.exten, g.v_exten, g.desc FROM capturegroups_extens as e RIGHT JOIN capturegroups_groups as g ON e.capturegroups_groups_id = g.capturegroups_groups_id WHERE e.capturegroups_groups_id = $id";
	$results = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
    if(DB::IsError($results)) {
		$results = null;
	}

	foreach ($results as $row)
	{
		// unsubscribe $extension extension
		$extension = $row["exten"];
		$context = $row["desc"] . $row["v_exten"];
		$sql = "DELETE FROM sip WHERE id = '$extension' AND keyword = 'subscribecontext' and data='$context' AND flags = '0'";
		sql($sql);
		$sql = "UPDATE sip SET data='' WHERE id = '$extension' AND keyword = 'callgroup' AND flags = '0'";
		sql($sql);
		$sql = "UPDATE sip SET data='' WHERE id = '$extension' AND keyword = 'pickupgroup' AND flags = '0'";
		sql($sql);
	}
	return true;
	
}

function capturegroups_group_delete($id)
{
	global $db;
	$sql = "SELECT v_exten FROM capturegroups_groups WHERE _rowid = '" . $db->escapeSimple($id) . "' LIMIT 1";
	$results = $db->getAll($sql);
    if(DB::IsError($results)) {
		$results = null;
	}
	capturegroups_unsubscribe($id);		
	capturegroups_set_virtual_extension_free($results[0][0]);

	$sql = "DELETE FROM `capturegroups_groups` WHERE _rowid = '" . $db->escapeSimple($id) . "'";
	$group = sql($sql);
				
	$sql = "DELETE FROM `capturegroups_extens` WHERE capturegroups_groups_id = '". $db->escapeSimple($id) . "'";
	$extens = sql($sql);
	return ($group === $extens);
}

function capturegroups_group_exists($group, $id = '')
{
	global $db;
	if (!empty($id))
	{
		$sqlEdit = " AND _rowid <> '$id'";
	}
	else
	{
		$sqlEdit = "AND 1"; 
	}
	$sql = "SELECT 'true' FROM capturegroups_groups WHERE `desc`='" . $db->escapeSimple($group) . "' $sqlEdit LIMIT 1";
	$results = $db->getAll($sql);
    if(DB::IsError($results)) {
		$results = null;
	}
	return count($results) == 1;
}


function capturegroups_virtual_exten_exists( $v_exten, $id= '')
{
	global $db;
	if (!empty($id))
	{
		$sqlEdit = " AND _rowid <> '$id'";
	}
	else
	{
		$sqlEdit = "AND 1"; 
	}
	$sql = "SELECT 'true' from capturegroups_groups WHERE v_exten='" . $db->escapeSimple($v_exten) . "' $sqlEdit LIMIT 1";
	$results = $db->getAll($sql);
    if(DB::IsError($results)) {
		$results = null;
	}
	return count($results) == 1;
}







function capturegroups_get_data_of_group($attempt)
{
	global $db;

	// we use LEFT JOIN because at least we wanna to know info group (id_group and label)!
	$sql = "SELECT g.*, e.exten  FROM capturegroups_groups AS g 
			LEFT JOIN capturegroups_extens AS e ON e.capturegroups_groups_id = g.capturegroups_groups_id 
			WHERE g.v_exten= '" . $db->escapeSimple($attempt) . "'";
	$results = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
    if(DB::IsError($results)) {
		$results = null;
	}
	return $results;
}

function capturegroups_clean_extensions($client_extensions, $id = "")
{
	if (!empty($client_extensions))
	{	
		global $db;
		if (is_numeric($id))
		{
			$sql = "SELECT e.exten FROM capturegroups_extens AS e WHERE exten IN (".implode($client_extensions, ",").") AND capturegroups_groups_id<>'$id'";
		}
		else
		{	
			$sql = "SELECT e.exten FROM capturegroups_extens AS e WHERE exten IN (".implode($client_extensions, ",").")";
		}
		$results = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
    	if(DB::IsError($results)) {
			$results = null;
		}
		foreach ($results as $row)
		{
			if (FALSE !== ($key = array_search($row["exten"],$client_extensions)))
			{
				unset($client_extensions[$key]);
			}
		}
	}
	return $client_extensions;
}

function capturegroups_get_extension_data($ext){
	global $db;
	$sql = " SELECT extension, name FROM `users` u WHERE extension = $ext LIMIT 1;";
	$results = $db->getAll($sql);
	
	if(DB::IsError($results)) {
		$results = array();
	}
	else{
		$results = current($results);
	}
	return $results;
}

function capturegroups_extension_exists($attempt){
	global $db;
	$ext = mysql_real_escape_string($attempt);
	$sql = "SELECT 'true' FROM `users` WHERE extension = '$ext' LIMIT 1";
	$result = $db->getAll($sql);
	return count($result[0]) === 1;
} 

function capturegroups_remove_virtual_extension_free($v_exten)
{
	global $db;
	// tratando de recuperar indices
	$sql = " DELETE FROM `capturegroups_v_extens_free` WHERE v_exten NOT IN (SELECT v_exten FROM capturegroups_groups) AND v_exten > (SELECT MAX(v_exten) FROM capturegroups_groups)";
	sql($sql);

	if (is_numeric($v_exten) )
	{
		$sql = "DELETE FROM `capturegroups_v_extens_free` WHERE v_exten = '$v_exten' LIMIT 1";
		sql($sql);
		return true;
	}
	return false;
}


function capturegroups_virtual_extension_free_exists($attempt){
	global $db;
	$ext = mysql_real_escape_string($attempt);
	$sql = "SELECT 'true' FROM `capturegroups_v_extens_free` WHERE v_exten = '$ext' LIMIT 1";
	$result = $db->getAll($sql);
	return isset($result[0][0]);
}


function capturegroups_set_virtual_extension_free($v_exten)
{
	global $db;

	if (!capturegroups_virtual_extension_free_exists($v_exten))
	{
		$sql = "INSERT INTO capturegroups_v_extens_free VALUES('$v_exten')";
		sql($sql);
	}
	return true;
}

function capturegroups_get_next_virtual_extension()
{
	global $db;
	$sql = "SELECT MIN(v_exten) FROM `capturegroups_v_extens_free` AS v_exten";
	$result = $db->getAll($sql);
	if(DB::IsError($result)) {
		$result = null;
	}
	if (!isset($result[0][0]))
	{
		$sql = "SELECT MAX(v_exten)+1 FROM `capturegroups_groups` AS g";
		$result = $db->getAll($sql);
		if(DB::IsError($result)) {
			$result = null;
		}
		
		if (!isset($result[0][0]))
		{
			$v_exten=0;
		}
		else
		{
			$v_extent = $result[0][0];
		}
	}
	else
	{
		$v_extent = $result[0][0];
	}
	return $v_extent;
}


/********************************************************
*														*
* 					UTILS FUNCTIONS						*
*														*
********************************************************/

/*

$haystack = array('a','b','c', 'd');
$needle = array('b','c', 'd','e');

$result = capturegroups_array_diff($haystack, $needle);

$result is equal to array('a').

Yeah! I know! array_diff is a php function BUT is broken since v4.0.4 and we need it!
http://www.php.net/array_diff

*/


function capturegroups_array_diff(array $haystack, array $needle)
{
	foreach ($needle as $value)
	{
		if (($key = array_search($value, $haystack)) !== FALSE)
		{
			unset($haystack[$key]);
		}
	}
	return $haystack;
}


function capturegroups_str_extensions_to_array($strExtensions)
{
	$strExtensions = trim($strExtensions);
	$strExtensions = str_replace(" ", "\n", $strExtensions);
	$arrExtensions = explode("\n", $strExtensions);
	foreach ($arrExtensions as $key => &$ext)
	{
		$ext = str_replace('\n','', $ext);		
		$ext = trim($ext);
		if (empty($ext) and $ext != '0') // 0 is considered an empty string by some versions of php  
			unset($arrExtensions[$key]);
	}
	return $arrExtensions;
}

function capturegroups_create_nav_groups_links($groups, $dispnum)
{
	$links = array();
	$link["url"] = "config.php?display=$dispnum&capturegroupsdisplay=".CAPTUREGROUPS_PARAM_PREFIX. "add";
	$link["text"] = "Add Group";
	array_push($links, $link);
	if (!empty($groups))
	{
		foreach ($groups as $group)
		{
			$link["url"] = "config.php?display=$dispnum&capturegroupsdisplay=".CAPTUREGROUPS_PARAM_PREFIX . $group[1];
			$link["text"] = $group[1] . " (". $group[2].")";
			array_push($links, $link);
		}
	}
	return $links;
}

function capturegroups_extract_group_from_request($param)
{
	return ltrim(str_replace(CAPTUREGROUPS_PARAM_PREFIX, ' ', $param)); // easy, isn't it?
}


function capturegroups_set_params_to_edit( $records)
{
	$vars = array();
	$first = current($records);
	$vars["captgroup_desc_edit"] = $first["capturegroups_groups_id"];
	$vars["desc"] =  $first["desc"];
	$vars["v_exten"] =  $first["v_exten"];
	$vars["bosses_extensions"] = "";
	$vars["secretaries_extensions"] = "";
	$s = array();
	$extensions = array();
	foreach ($records as $record)
	{
		
		if (!empty($record["exten"]))
		{
			array_push($extensions, $record["exten"]);
		}

	}
	$vars["client_extensions"] = array_unique($extensions);
	return $vars;
}

/******************

 GUI FUNCTIONS
******************/

function capturegroups_content($title, $content, $messages){
	echo <<<OUTPUT


<div class="content">
	<h2>$title</h2>
<script>
// AJAX to the SubCategory DropDown
function getExtensions(extensions)
{
	
	var url = "config.php?sid=" + Math.random() + "&display=capturegroups&extensions=" + extensions + "&ajax=true";
	xmlHttp=GetXmlHttpObject(setExtensions);
	xmlHttp.open("GET", url , true);
	xmlHttp.send(null);
	document.getElementById('divExtensions').innerHTML = "Searching";
	return true;
}

function setExtensions()
{
	if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete")
	{
		var datos;
		datos = (xmlHttp.responseText);
		document.getElementById('divExtensions').innerHTML = datos;
	}
}


function GetXmlHttpObject(handler){
	var objXmlHttp=null
	if (navigator.userAgent.indexOf("Opera")>=0){
		alert("This doesn't work in Opera")
		return
	}
	if (navigator.userAgent.indexOf("MSIE")>=0){
		var strName="Msxml2.XMLHTTP"
		if (navigator.appVersion.indexOf("MSIE 5.5")>=0){
			strName="Microsoft.XMLHTTP"
		}
		try{
			objXmlHttp=new ActiveXObject(strName)
			objXmlHttp.onreadystatechange=handler
			return objXmlHttp
		}catch(e){
			alert("Error. Scripting for ActiveX might be disabled")
			return
		}
	}
	if (navigator.userAgent.indexOf("Mozilla")>=0){
		objXmlHttp=new XMLHttpRequest()
		objXmlHttp.onload=handler
		objXmlHttp.onerror=handler
		return objXmlHttp
	}
}

</script>
<form method="post" name=searchcapturegroups action="config.php?display=capturegroups" onsubmit="getExtensions(document.getElementById('extensions').value); return false;">
<table>
			<tr>
				<td colspan="2"><h5>Buscar grupo</h5> <hr /> </td>
			</tr>			
			<tr>
				<td colspan="2"><label>Extension:</label> <input type="text" id="extensions" name= "extension" value=""/> <input type="button" name="submitSearch" onclick="getExtensions(document.getElementById('extensions').value);" value="Search" /></td>				
			</tr>

			<tr>
				<td colspan="2"><div id="divExtensions"></div></td>
			</tr>
			<tr>
				<td colspan="2"><hr /></td>
			</tr>
			</tr>			
</table>
</form>



	$messages
	$content

</div>

OUTPUT;

}


function capturegroups_get_form_add( array $params)
{
	$vars["form_title"] = "Add Group";
	$vars["form_url"] = "config.php?display=capturegroups&capturegroupdisplay=".CAPTUREGROUPS_PARAM_PREFIX. "add";
	$vars["delete_button"] = "";
	$vars["context_default"] = CAPTUREGROUPS_CONTEXT_GROUPS_DEFAULT;
	$vars["client_extensions"] = implode($params["client_extensions"] , "\n");
	$vars["next_v_exten"] = capturegroups_get_next_virtual_extension();
	$vars["v_exten"] = $params["v_exten"];
	$vars["desc"] = $params["desc"];
	$vars["action"] = "Add";
	if (!isset($params["submitAdd"]))
	{
		$vars["check_v_exten"] = 'checked="checked"';
		$vars["check_context"] = 'checked="checked"';
		$vars["v_exten"] = $vars["next_v_exten"];
		$vars["desc"] = $vars["context_default"];
	}
	$vars["message_details"] = $params["message_details"];
	$vars["message_title"] = $params["message_title"];
	return capturegroups_get_form($vars);
}


function capturegroups_get_form_edit( array $params)
{
	$vars["form_title"] = "Edit Group";
	$vars["form_url"] = "config.php?display=capturegroups&bsgroupdisplay=".CAPTUREGROUPS_PARAM_PREFIX. $params["v_exten"];
	$vars["delete_button"] = capturegroups_get_delete_button();
	$vars["v_exten"] = $params["v_exten"];
	$vars["next_v_exten"] = capturegroups_get_next_virtual_extension();
	$vars["desc"] = $params["desc"];
	$vars["context_default"] = CAPTUREGROUPS_CONTEXT_GROUPS_DEFAULT; 
	$vars["action"] = "Edit";
	$vars["captgroup_desc_edit"] = $params["captgroup_desc_edit"];
	$vars["client_extensions"] = implode($params["client_extensions"] , "\n");
	$vars["message_details"] = $params["message_details"];
	$vars["message_title"] = $params["message_title"];
	$vars["delete_question"] = "Do you really to want delete " . $vars["v_exten"] . " (" .$vars["desc"] . ") group?"; 
	$vars["delete_url"] = "config.php?display=capturegroups&captgroupdelete=".CAPTUREGROUPS_PARAM_PREFIX. $params["captgroup_desc_edit"];
	return capturegroups_get_form($vars);
}


function capturegroups_get_form ( array $vars)
{
	$sForm = file_get_contents(dirname(__FILE__). "/form_template.tpl");

	
	$vars["messages"] = "";
	if (!empty($vars["message_details"]))
	{
		$vars["messages"] = "<h5>".$vars["message_title"] . "</h5>";
		$vars["messages"] .= "<ul>";
		foreach ($vars["message_details"] as $details)
		{
			$vars["messages"] .= "<li>$details</li>";
		}
		$vars["messages"] .= "</ul>";
		unset($vars["message_details"]);
		unset($vars["message_title"]);
	}
	

	foreach ($vars as $var => $value)
	{
		$sForm = str_replace("{".$var. "}", $value, $sForm);
	}
	return $sForm;
}


function capturegroups_get_delete_button()
{
	$sForm = file_get_contents(dirname(__FILE__). "/delete_button.tpl");
	return str_replace("{delete_button_label}", "Delete Group", $sForm);
}



function capturegroups_show_nav_users($links){
	echo <<<OUTPUT

<div class="rnav">
	<ul>

OUTPUT;
	foreach ($links as $link){
		$url  = $link['url'];
		$text = $link['text'];

		echo <<<OUTPUT
		<li><a href="{$url}">{$text}</a></li>
	
OUTPUT;
	}
	echo <<<OUTPUT
	</ul>
</div>

OUTPUT;
}

?>
