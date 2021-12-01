<form id="smpl_order" method="{{method}}" action="{{URI}}" >
  <div id="smpl_orderbox" class="smpl_order_box" >{{order}}:<select id="smpl_sortsource" class="smpl_sortsource" name="smpl_sortsource" title="{{source_of_order}}">
			<option value="-">-</option>
			<option value="filename" {{ord_filename}}>{{Filename}}</option>
			<option value="sub"  {{ord_sub}}>{{Sub}}</option>
			<option value="size" {{ord_size}}>{{Size}}</option>
			<option value="date" {{ord_date}}>{{Date}}</option>
			<option value="vote" {{ord_votes}}>{{Votes}}</option>
			<option value="view" {{ord_views}}>{{Views}}</option>
		</select><select id="smpl_sort" class="smpl_sort" name="smpl_sort" title="{{Direction_of_order}}" width="40">
			<option value="-">-</option>
			<option value="asc" {{ord_Ascending}}>{{Ascending}}</option>
			<option value="desc" {{ord_Descending}}>{{Descending}}</option>
			<option value="rand" {{ord_Random}}>{{Random}}</option>
		</select>
{{filterform}}{{Filter}}:
<select id="smpl_filtersource" class="smpl_filtersource" name="smpl_filtersource" title="{{Source_of_filter}}">
			<option value="-">-</option>
			<option value="filename" {{fil_Filename}}>{{Filename}}</option>
			<option value="sub" {{fil_Sub}}>{{Sub}}</option>
			<option value="date" {{fil_Date}}>{{Date}}</option>
			<option value="vote" {{fil_Votes}}>{{Votes}}</option>
			<option value="view" {{fil_Views}}>{{Views}}</option>
		</select>
		{{hiddens}}
		<input type="text" name="smpl_filtertext" id="smpl_filtertext"   value="{{filtertext}}" /><input type="submit" name="smpl_sortfilter" class="form-submit" id="smpl_sortfilter" value="{{Filter_send}}" />
{{/filterform}}</div>
</form>