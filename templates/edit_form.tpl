<input type="hidden" value="{{id}}" name="smpl_id" id="smpl_id" />
<div id="SmplFormTable">
  <div><strong>{{Edit}}: {{Properties}}</strong></div>
  <table id="SmplFormTable">
    <tr><th colspan='2'>{{Name}}</th><th>{{Value}}</th></tr>
    <tr><td colspan='2' align="left" valign="top"><label for="smpl_name">{{Filename}} </label></td>
        <td align="left" valign="top"><input type='text' id="smpl_name" name="smpl_name" value='{{smpl_name}}' size="50" readonly="readonly" disabled="disabled"/></td>
    </tr>
    <tr><td colspan='2' align="left" valign="top"><label for="smpl_sub">{{Subtitle}}</label></td>
      <td align="left" valign="top"><input type='text' id="smpl_sub" name="smpl_sub" value='{{smpl_sub}}' size="50" /></td>
    </tr>
    <tr><td colspan='2' align="left" valign="top"><label for="smpl_capt">{{Caption}}</label></td>
        <td align="left" valign="top">
        <textarea id="smpl_capt" name="smpl_capt" cols="50" wrap="hard" maxlength="16000" placeholder="Some description of item">{{smpl_capt}}</textarea>
      </td>
    </tr>
    <tr>
      <td colspan='2' align="left" valign="top"><label for="smpl_deleted">{{Deleted}}</label></td>
      <td align="left" valign="top"><input type="checkbox" id="smpl_deleted" name="smpl_deleted" value='{{smpl_deleted}}' /></td>
    </tr>
    <tr>
      <td colspan='2' align="left" valign="top"><label for="smpl_url">{{Url}}</label></td>
      <td align="left" valign="top"><input type='text' id="smpl_url" name="smpl_url" value='{{smpl_url}}' /></td>
    </tr>
    <tr>
      <td colspan='2' align="left" valign="top"><label for="smpl_target">{{Target}}</label></td>
      <td align="left" valign="top"><input type='text' id="smpl_target" name="smpl_target" value='{{smpl_target}}' /></td>
    </tr>
  </table>
</div>
<div class="smpl_div_taxonomy">
  <table id="SmplTaxonomyTable">
    <tr><th>No.</th><th>{{Edit}}</th><th>{{Taxonomy}}</th><th>{{Tax_descr}}</th></tr>
    {{smpl_taxonomies}}
  </table>
  <div id='smpl_sel' class='smpl_sel' title='{{Taxestitle}}'>&nbsp;&nbsp;&nbsp;&nbsp;{{Taxonomies}}</div>
  <select name='smpl_sel_tax' id='smpl_sel_tax' style='display: none;'><option value="">&nbsp;</option></select>
  <div id="smpl_new_tax" class="smpl_new_tax" title='{{Addtaxtitle}}' >&nbsp;&nbsp;&nbsp;&nbsp;{{Add_tax}}</div>
  <div style="display:block" id="smpl_new_row"><!-- {{smpl_new_row}}--></div>
</div>

<script>
var smpl_tax_max={{smpl_max_tax}};
$j(document).ready(function(){
  smpl_new_taxonomy();    //defined in taxonomy.js
  smpl_modify_taxonomy();
  smpl_delete_taxonomy();
  smpl_select_taxonomies();
  smpl_copy_selected_taxonomy();
});
</script>
