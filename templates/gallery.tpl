<div class='smpl_table_all'>
<div>
{{smpl_edit_all}}
{{smpl_help}}
</div>
{{smpl_pager}}
{{smpl_order}}
{{smpl_list}}

{{smpl_table}}

{{smpl_pager}}

{{smpl_list}}
{{smpl_edit_all}}
<EditForm>
<div id="SmplEditForm" class="smpl_edit_form">
  <div id="SmplEditFormContainer">
    <form id="SmplEditForm">
      <div id="SmplEditFormInner"></div>
      <input id="SmplEditFormSubmit" name="Save"   type="submit" class="smpl_edit_form_submit" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{Save}}">
      <input id="SmplEditFormCancel" name="Cancel" type="button" class="smpl_edit_form_cancel" value="&nbsp;&nbsp;&nbsp;&nbsp;{{Cancel}}">
    </form>
  </div>
</div>
</EditForm>

<ImgEditForm>
<div id="SmplImgEditForm" class="smpl_imgedit_form">
{{ImgEditForm}}
</div>
</ImgEditForm>
<script type='text/javascript'>

  var smpl      = new Array();
  smpl.ajax     = "{{smpl.ajax}}";
  smpl.subtitle ="{{Subtitle}}";
  smpl.del      ="{{Delete}}";
  smpl.kill     ="{{Trash}}";
  smpl.subtitle_rename    = "{{Rename}}";
  smpl.delete_not_success = "{{Delete_not}}";
  smpl.kill_not_success   = "{{Kill}}";
  smpl.order    = '';
  smpl.updown   = "{{updown}}";
  smpl.view     = '';
  smpl.id       ='';
  smpl.pars     = '';
  smpl.url      = '';
  smpl.exif_info_shoe = false;
  smpl.keywords = {{keywords}};
  smpl.i        = 1;
</script>