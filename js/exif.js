/**
 * Description of an image
 */
$j(document).ready(function(){
	/*
	 * Description & Exif info window show / hide
	*/
	$j('a.smpl_desc_a').click(function(){
		var id  = $j(this).attr('id').substring(5);
		var url = smpl.ajax + "/exif/" + id ;
		var pos = $j(this).position(); 
		obj = $j("div#DescSub"+id);
		obj.css('left',pos.left + 'px');
		obj.css('top',pos.top+ 'px');
		$j("div.smpl_desc_info").hide();
		$j.ajax({
			url: url,
			type: "GET",
			success: function(data){				
				$j("div#Exif" + id).html(data);
				obj.show();
				smpl.desc_info_show = true;
			}
		});
	});

	/*
	 * Desc info hide
	 */
	$j('div.smpl_desc_info').click( function (){
		$j(this).hide();
		smpl.desc_info_show = false;
	});

	//If click on some element of body the windows will close
	$j('body').click( function (event){
		var x = event.target.className;
		if(x != "smpl_desc_a" ){
			$j('div.smpl_desc_info').hide();
			smpl.desc_info_show = false;
		}
	});
	
});