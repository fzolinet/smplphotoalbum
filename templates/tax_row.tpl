<tr id='smpl_tax_tr{{stid}}'>
  <td id="smpl_tax_no{{stid}}" valign="top">{{stid}}</td>
  <td align='left' valign='top'>
    <input type='hidden' id='smpl_stid{{stid}}' name='smpl_stid[{{stid}}]' value='{{tid}}' />
    <div id='smpl_tax_del{{stid}}' class='smpl_tax_del' title='{{Delete}}'>&nbsp;&nbsp;&nbsp;&nbsp;</div>
    <div id='smpl_tax_add{{stid}}' class='smpl_tax_add' title='{{Edit}}'>&nbsp;&nbsp;&nbsp;&nbsp;</div>
  </td>
  <td align='left' valign='top'>
    <input id='smpl_tax{{stid}}' class='smpl_tax_mod' name='smpl_tax[{{stid}}]' value='{{tax}}'>
  </td>
  <td valign='top'>
    <textarea id='smpl_desc{{stid}}' class='smpl_desc_mod' name='smpl_desc[{{stid}}]'>{{desc}}</textarea>
  </td>
</tr>
