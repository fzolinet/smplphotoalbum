/**
 * Vote
 */
$j(document).ready(function(){
	//vote
	$j('.smpl_vote_plus').mouseover(function(){
		$j(this).css('cursor','pointer');
	});

	$j('.smpl_vote_plus').mouseout(function(){
		$j(this).css('cursor','default');
	});
	
	//Voting javascript code
	$j('.smpl_vote_plus').click(function(){
		var id   = $j(this).attr('id').substring(4);
		
		var url  = smpl.ajax + "/vote/" + id + "/";
		var urlp = url;		
		var rank = $j('div#Vote'+id+".smpl_vote_number");
		var old = rank.html();
		var txt = '<select id=rnk class=smpl_vote_select><option>-</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option><option>7</option><option>8</option><option>9</option><option>10</option></select>';
		var original = false;
		rank.html(txt);
		rank.change(function(){
			if(!original){
				$j('select#rnk option:selected').each(function(){
					str = $j(this).text();
					if(str =="-"){
						original = true;
						rank.html(old);
					}else{
						rank.load(urlp+ str,function(data){
							original = true;
							this.html(data);
						});
					}
				});
			}
		});
	});

	
});