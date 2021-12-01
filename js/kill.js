/**
 * Kill an image from server
 */

$j(document).ready(function(){
	/**
	 * Send kill commandto the server
	 */
	$j(".smpl_kill_plus").click(function(){
		var id     = $j(this).attr('id').substring(4);
		var parnt  = $j(this).parent().parent();
		var subdiv = parnt.find('a');
		var name   = subdiv.attr('title');		
		//var old_subtitle = subdiv.html();
		var ok = window.confirm(smpl.kill +": '" +name +"'?");
		if (ok) {
			var url = smpl.ajax + "/kill/" + id;
			$j.get(url, function (data){
				if(!data){
					alert(smpl.kill_not_success + ": " + name);
				}else{
					$j("div#div"+id).hide();
					parnt.html("<td class='smpl_td'>&nbsp;</td>");
				}
			});
		}
	});
});