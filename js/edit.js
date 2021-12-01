/**
 * Edit properties of image
 */

$j(document).ready(function(){
	//Edit button
	$j('.smpl_edit_plus').mouseover(function(){
		$j(this).css('cursor','pointer');
	});

	$j('.smpl_edit_plus').mouseout(function(){
		$j(this).css('cursor','default');
	});
	
 	/**
 	 * Edit command to show / hide the Edit form of properties
 	 * with parameters of actual item
 	 */
	var SmplEditForm   = $j("div#SmplEditForm");
    var SmplEditFormInner = $j("div#SmplEditFormInner");
	// Draggable window
	$j(function(){
		SmplEditForm.draggable();	
	})
	
	$j(".smpl_edit_plus").click(function(e){
		var id  = $j(this).attr('id').substring(6);
		var visible = SmplEditForm.css('display');
	
		pos = $j(this).position();
		if(visible =='none'){	// Load form from server with ajax			
			var url  = smpl.ajax + "/edit/" + id + "/form";
			$j.ajax({
				url: url,
				type: "GET",
				success: function(data){
					SmplEditFormInner.html(data);
					setDivInWindow(SmplEditForm, pos , e.pageX, e.pageY);
				},
				error :  function(data){
					alert(data);
				},
			});
		}else{
			/**
			 * Are you sure to cancel all modify
			 */
			var id = $j('input#smpl_id').val();
			if(id.length >0 && window.confirm("Are you sure to cancel the modifies?")){
				$j("div#smpl_new_tax").unbind('click');
				SmplEditForm.hide(smpl_eff);				
				SmplEditFormInner.html("&nbsp;");
			}
		}
	});

	/**
	 * click on cancel button of windows of properties
 	*/
	$j("input#SmplEditFormCancel").click(function(){
		var i = $j('input[type="hidden"]').filter(function(index){
			val = $j(this).val();
			ok = (val == "n"|| val=="m" || val =="d");
			return ok;
		});

		if(i.length >0){
			if(window.confirm("Are you sure to cancel the modifies?")){
				$j("div#smpl_new_tax").unbind('click');
				$j("div#SmplEditForm").hide(smpl_eff);				
				SmplEditFormInner.html("&nbsp;");
			}
		}else{
			$j("div#smpl_new_tax").unbind('click');
			$j("div#SmplEditForm").hide(smpl_eff);
			SmplEditFormInner.html("&nbsp;");
		}
	});
		
	/**
	 * Edit form send to server
	 */
	$j("input#SmplEditFormSubmit").click(function(){
		//Save the datas of form to server
		var formData = "";	
		var input    = "";
		var id = "";
		$j('form#SmplEditForm input, form#SmplEditForm textarea').each( function(){
			input = $j(this);
			if(input.attr("name") == "smpl_id"){
				id = input.val().trim();
			}
			formData += input.attr("name") + "=";
			formData += (input.val()).trim()+"&";
		});

		var url = smpl.ajax + "/edit/" + id;		
		$j.ajax({
			url: url,
			type: "GET",
			dataType: "text",
			data: formData,
			success: function(data){
				var obj = JSON.parse(data);
				$j("div#smpl_new_tax").unbind('click');
				$j("div#SmplEditForm").hide(smpl_eff);
				SmplEditFormInner.html("&nbsp;");
				d = data.trim();
				
				if(typeof obj.caption != "undefined"){
					$j("a#"+_q(id)).attr('title',obj.caption);
					$j("img#img"+_q(id)).attr('title',obj.caption);
				}
				if(typeof obj.sub != "undefined" && obj.sub !="-1"){
					$j("#Sub" + _q(id)).text(obj.sub);
				}
				
				if(typeof obj.del != "undefined" && obj.del != "-1"){
					$j('td#td'+id).hide(smpl_eff);
				}	
				
				if( typeof obj.urlurl != "undefined"){
					if(obj.e !="-1" ){
						$j("a#LinkBtn" + id).attr("href", obj.e);
						$j("div#link" + id).show(smpl_eff);
					}else{
						$j("a#LinkBtn" + id).attr("href","");
						$j("div#link" + id).hide(smpl_eff);
					}
				}

				if (typeof obj.target != "undefined" && obj.target != "-1" ){
					$j("a#linkBtn"+id).attr("target",obj.target);
				}				
			},
			error :  function(result){				
				$j("div#smpl_new_tax").unbind('click');
				$j("div#SmplEditForm").hide(smpl_eff);
				SmplEditFormInner.html("&nbsp;");
				alert('JSON error: '+result.toString());
			},
		});
		return false;	
	});	
});