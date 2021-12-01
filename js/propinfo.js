/**
 * Load properties of image
 */

$j(document).ready(function(){
	$j('a.smpl_desc_a').click(function(){
		id  = $j(this).attr('id').substring(5);
		id = id.replace("'","");
		obj = $j("div[name=DescSub"+id+"]");
		t = obj.html();
		if((0+t.length)>0) {
			ob1 = $j("div[name^=DescSub]");
			ob1.hide();
			pos = $j(this).position();
			obj.css('left',pos.left + 'px');
			obj.css('top',pos.top+ 'px');
			obj.show();
			smpl.desc_info_show = true;
		}
	});

});