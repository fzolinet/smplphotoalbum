/**
 * Edit properties of a path
 */
( function($, smpl ){
	//Edit button
	$('#EditAll').mouseover(function(){
		$(this).css('cursor','pointer');
	});
	
	$('#EditAll').mouseout(function(){
		$(this).css('cursor','default');
	});
	
	$('#EditAll').click(function(e){
		let id  = $('div.smpl_item_container:first').attr('id').substring(4);				
		smpl.progress(true);		
		let url  = smpl.ajax + "/update/" + id;		
		$.ajax({
			url: url,
			type: "GET",
			success: function(response){
				let d = JSON.parse(response[0].data);
				window.location.reload();
				smpl.progress(false);
			},
			error: function(response){
				smpl.progress(false);
				alert("Error on server side: " + response[0].data);
			},
		});
	})
})( jQuery, smpl );
