/**
 * Edit properties of a path
 */
( function($, smpl ){
	//Edit button
	$('#EditPath').mouseover(function(){
		$(this).css('cursor','pointer');
	});
	
	$('#EditPath').mouseout(function(){
		$(this).css('cursor','default');
	});
	
	/**
	 * Edit the properties of this path
	 */
	$('#EditPath').click(function(e){
		let id  = $('div.smpl_item_container:first').attr('id').substring(4);
		smpl.progress(true);
		let url  = smpl.ajax + "/updatepath/" + id;		
		$.ajax({
			url: url,
			type: "GET",
			success: function(response){
				let d = JSON.parse(response[0].data);
				smpl.progress(false);
				window.location.reload();
			},
			error: function(response){
				smpl.progress(false);
				alert("Error on server side: " + response[0].data);
			},
		});
	})
})( jQuery, smpl );
