<script>
	function setDefaultContext()
	{
		var chBoxContext = document.getElementById("chBoxContext");
		var inputContext = document.getElementById("inputContext");
		if (chBoxContext.checked)
		{
			inputContext.value = "{context_default}";
		}
		inputContext.readOnly = chBoxContext.checked;
	}

	function setDefaultVirtualExtension()
	{
		var chBoxVExt = document.getElementById("chBoxVExt");
		var inputVExt = document.getElementById("inputVExt");
		var vExtenRelease = document.getElementById("inputVExtenRelease")
		var nextVExt = {next_v_exten}; 
		if (chBoxVExt.checked && nextVExt <= parseInt(inputVExt.value))
		{
			vExtenRelease.value = inputVExt.value;			
			inputVExt.value = nextVExt;
		}
		else
		{
			str = inputVExt.value;
			if (str.replace(/\s/g,"") != "")
			{						
				if (chBoxVExt.checked)
				{			
					if (confirm('¿La extension virtual actual es menor que la proxima disponible aún así quiere asignarla?'))
					{
						vExtenRelease.value = inputVExt.value;					
						inputVExt.value = nextVExt;
					}
					else
					{
						chBoxVExt.checked= false;
					}
				}
			}
		}
		inputVExt.readOnly = chBoxVExt.checked;
	}

</script>

{messages}
<form method="post" name=capturegroups action="{form_url}">
<table>
			<tr>
				<td colspan="2"><h5>{form_title}</h5> <hr /> </td>
			</tr>			
			<tr>
				<td><label>Context:</label></td>
				<td><input type="text" name= "desc" value="{desc}" id="inputContext" />  <input type="checkbox" name="contextDefault" value="" onclick="setDefaultContext();" id="chBoxContext" {check_context}> Use default</td>
				
			</tr>			
			<tr>
				<td><label>Virtual Extension:</label></td>
				<td><input type="text" name= "v_exten" value="{v_exten}" id="inputVExt" /> <input type="checkbox" name="contextDefault" value="" id="chBoxVExt" {check_v_exten} onclick="setDefaultVirtualExtension();"> Use next virtual extension available</td>				
			</tr>
			<tr>
				<td colspan="2"><hr /></td>
			</tr>
			<tr>
				<td colspan="2" align="center"><a href="#" class="info"><span>Put extensions here</span>Extensions</a> </td>
			</tr>
			<tr>
				<td colspan="2" align="center"><textarea name="client_extensions">{client_extensions}</textarea></td>
			</tr>
			<tr>
				<td colspan="2"><hr /></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
<input type="hidden" name="v_exten_release" value="{v_exten_release}" id="inputVExtenRelease" />
<input type="hidden" name="captgroup_desc_edit" value="{captgroup_desc_edit}" /> <input type="submit" name="submit{action}" value="Save" /> {delete_button}</td>
			</tr>
</table>
</form> 
