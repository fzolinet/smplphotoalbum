/**
 * Delete image
 */

$j(document).ready(function(){
	/**
	 * Send delete command to the server
	 */
	$j(".smpl_delete_plus").click(function(){
		var id     = $j(this).attr('id').substring(6);
		var parnt  = $j(this).parent().parent();
		var subdiv = parnt.find('a');
		var name   = subdiv.attr('title');
		var ok = window.confirm(smpl.del +": '" +name +"'?");
		if (ok) {
			var url = smpl.ajax + "/del/" + id ;
			$j.get(url, function (data){
				if(!data){
					alert(smpl.delete_not_success + ": " + name);
				}else{
					parnt.html("<td class='smpl_td'>&nbsp;</td>");
				}
			});
		}
	});	
});