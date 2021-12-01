/**
 * Javascript code for statistics. interface
 */

$j(document).ready(function() {
	 $j("input[name='smpl_stat']").click(function(){
		id = $j(this).val();		
		var url  = smpl.ajax + "/record/load/" + id + "/form";	
		$j.ajax({ 
			type: "GET",
			url: "school/record/load",
			data:{id: value},
			dataType: 'json',
			success:function(data){
				$j('input#edit-smpl-id')      .val(data.id);
				$j('input#edit-smpl-path')    .val(data.path);
				$j('input#edit-smpl-name')    .val(data.name);
				$j('input#edit-smpl-subtitle').val(data.subtitle);
				$j('input#edit-smpl-caption') .val(data.caption);
				$j('input#edit-smpl-type')    .val(data.type);
				$j('input#edit-smpl-rank')    .val(data.rank);
				$j('input#edit-smpl-size')    .val(data.size);      ///
				$j('input#edit-smpl-thdate')  .val(data.thdate);    ///
				$j('input#edit-smpl-deleted') .val(data.deleted);   ///
				$j('input#edit-smpl-fb')      .val(data.fb);
				$j('input#edit-smpl-url')     .val(data.url);
				$j('input#edit-smpl-target')  .val(data.target);
				$j('input#edit-smpl-price')   .val(data.price);
				$j('input#edit-smpl-currency').val(data.currency);
				$j('input#edit-smpl-download').val(data.download);
			},
			error: function(){
				alert("Ajax error");
			}
		});
	});
});