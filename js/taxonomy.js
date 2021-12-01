/**
 * Taxonomy functions in it
 */
var smpl_tax_max=0;

 // Taxonomy related code
/**
 * New taxonomy row - when push the New Taxonomy button
 * calling from smpl_edit_form.tpl -> when Edit properties
 */
function smpl_new_taxonomy(){
	$j("div.smpl_new_tax").click(function(){
		db = $j("div.smpl_tax_del").length;
		smpl_tax_max++;
		var new_row = $j("div#smpl_new_row").html();		
		var row = new_row.replace(/{{stid}}/g,smpl_tax_max);
		row = row.replace(/<!--/g,"");
		row = row.replace(/-->/g,"");		

		visible = $j("select#smpl_sel_tax").css('display');
		tax = "";
		desc = "";
		if(visible !== "none"){
			_tax = $j("#smpl_sel_tax option:selected").text();
			tid = $j("#smpl_sel_tax option:selected").val();
			if(tid > 0 ){			
				t = _tax.split("|");
				tax   = t[0];
				desc  = t[1];
			}
			row = row.replace("{{tid}}", tid);
		}else{
			row = row.replace("{{tid}}", "n");
		}		
		row = row.replace("{{tax}}",tax);
		row = row.replace("{{desc}}",desc);
		
		$j("table#SmplTaxonomyTable >tbody:last").append(row);
		smpl_delete_taxonomy();		//defined in smpl.js
	});
}

/**
 * Modify the taxonomy
 * calling from smpl_edit_form.tpl -> when Edit properties
 */

function smpl_modify_taxonomy(){
	$j('input.smpl_tax_mod').change(function(){
		//alert("modify taxonomies");
		id = $j(this).attr('id').substr(8);
		$j(this).css('border','red 1px solid');
		$j("input#smpl_tid"+id).val('m');
	});

	$j('textarea.smpl_desc_mod').change(function(){
		var id = $j(this).attr('id').substr(9);
		$j(this).css('border','red 1px solid');
		$j("input#smpl_tid"+id).val('m');
	});
}

/*
 * Delete the actual taxonomy
 * calling from smpl_edit_form.tpl -> whenEdit properties
 */ 

function smpl_delete_taxonomy(){
	$j("div.smpl_tax_del").click(function(){		
		id = $j(this).attr('id').substring(12);
		$ip = $j("input#smpl_tid"+id); 
		var v = $ip.val();
		$ip.val("-"+v);		
		$j('tr#smpl_tax_tr'+ id).hide();
	});
}
/**
 * Show or hide select listof taxonomies (when push the Taxonomies button)
 * calling from smpl_edit_form.tpl -> whenEdit properties
 */
function smpl_select_taxonomies(){
	$j("#smpl_sel").click(function (){
		visible = $j("select#smpl_sel_tax").css('display');
		if(visible == "none"){
			var url = smpl.ajax + "/taxes/";
			$j.get(url, function (data){
				$j("select#smpl_sel_tax").html(data)
				$j("select#smpl_sel_tax").show();
			});
		}else{
			$j("select#smpl_sel_tax").hide();
		}
	});
}

/**
 * Selected taxonomy copy to input fields
 */
function smpl_copy_selected_taxonomy(){	
	$j(".smpl_tax_add").click(function(){
		var tax = $j("#smpl_sel_tax option:selected").text();
		var desc = $j("#smpl_sel_tax option:selected").val();
		var id= $j(this).attr('id').substr(12);
		$j("input#smpl_tax"+id).val(tax);
		$j("textarea#smpl_desc"+id).val(desc);
		$j("input#smpl_tid"+id).css('border','red 1px solid');
		$j("input#smpl_tid"+id).val('m');		
	});
}
