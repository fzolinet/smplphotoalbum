<form id="SmplImgEditForm">
  <div>
    <div id="SmplImgEditFormCont">
      <div class="smpl_imgedit_title">{{Edit}} : <div id="smpl_imgedit_filename">Filename</div></div>
      <div id="smpl_imgedit_actions">
        <input type="hidden" value="" name="imgid" id="imgid" />
        <div class="smpl_imgeditborder">
          <label for="img_rotate">{{Rotate}}:</label>
          <select id="img_rotate" name="img_rotate">
            <option value="-">-</option>
            <option value="-90">{{Left}} 90&deg; </option>
            <option value="90">{{Right}} 90&deg; </option>
            <option value="180">180&deg;</option>
          </select>&nbsp;&nbsp;
          <input type="number" name="img_rotate_number" id="img_rotate_number" min="-180" max="180" step="1" value="0" class="smpl_imgedit_cl">&deg;
          <input type="checkbox" name="img_rotate_number_chk" id="img_rotate_number_chk">&nbsp;&nbsp;
          <label for="smpl_flip_vertical">{{FlipVertical}}:</label>&nbsp;<input type="checkbox" id="smpl_flip_vertical" name="smpl_flip_vertical" class="smpl_imgeditborder">&nbsp;
          <label for="smpl_flip_horizontal">{{FlipHorizontal}}:</label>&nbsp;<input type="checkbox" id="smpl_flip_horizontal" name="smpl_flip_horizontal" class="smpl_imgeditborder">
        </div>
        <div class="smpl_imgeditborder">
          <label for="smpl_width" >{{Size}}:</label>
          <div id="smpl_width" style="display: inline;"></div>&nbsp;x&nbsp;<div id="smpl_height" style="display: inline;"></div> px&nbsp;
          <label for="smpl_x1">{{Left top}}:</label>
            <input id="smpl_x1" name="smpl_x1" value="" class="smpl_imgedit_cl">
            <input id="smpl_y1" name="smpl_y1" value="" class="smpl_imgedit_cl">
          <label  for="smpl_x2">&nbsp;{{Right bottom}}:</label>
            <input id="smpl_x2" name="smpl_x2" value="" class="smpl_imgedit_cl">
            <input id="smpl_y2" name="smpl_y2" value="" class="smpl_imgedit_cl">
            <br/>

          <label for="smpl_aspect">{{Aspect ratio}}:&nbsp;</label>
          <label for="smpl_w">
            <input id="smpl_w" name="smpl_w" value="" class="smpl_imgedit_cl">&nbsp;x&nbsp;
            <input id="smpl_h" name="smpl_h" value="" class="smpl_imgedit_cl">
            {{pixels}}
          </label>;
          <label for="smpl_wp" class="smpl_imgeditborder"></label>
            <input id="smpl_wp" name="smpl_wp" value="" class="smpl_imgedit_cl">&nbsp;x&nbsp;
            <input id="smpl_hp" name="smpl_hp" value="" class="smpl_imgedit_cl">%
            <input type="checkbox" id="smpl_aspect" name="smpl_resize">
          <br/>
          <label for="smpl_crop">{{Crop}}:&nbsp;</label><input type="checkbox" id="smpl_crop" name="smpl_crop">&nbsp;
          <label for="smpl_resize">{{Resize}}:&nbsp;</label><input type="checkbox" id="smpl_resize" name="smpl_resize">
        </div>
        <div class="smpl_imgeditborder">
          <label for="smpl_gamma">{{Gamma}}:</label>
            &nbsp;{{Input}}:
            <input id="smpl_gammain"  name="smpl_gammain"  type="range" min="0" max ="4" step="0.1" value="2.2" class="smpl_slider">
            <div id="smpl_gammain_val" style="display:inline; vertical-align: middle;">2.2</div>
            {{Output}}:<input id="smpl_gammaout" name="smpl_gammaout"  type="range" min="0" max ="4"step="0.1" value="1.0" class="smpl_slider">
          <div id="smpl_gammaout_val" style="display:inline;">1.0</div>
          <input type="checkbox" id="smpl_gamma_chk" name="smpl_gamma_chk">
          <br/>
        </div>
        <div class="smpl_imgeditborder">
          <label for="smpl_contrast">{{Contrast}}:</label>
          <input id="smpl_contrast" name="smpl_contrast" type="number" step="1" min="-100" max ="100" value="0" class="smpl_imgedit_max">
          <input type="checkbox" name="smpl_contrast_chk" id="smpl_contrast_chk" class="smpl_imgedit_cl">&nbsp;&nbsp;

          <label for="smpl_brightness">{{Brightness}}:</label>
          <input id="smpl_brightness" name="smpl_brightness" type="number" step="1" min="-255" max ="255" value="0" class="smpl_imgedit_max">
          <input type="checkbox" name="smpl_brightness_chk" id="smpl_brightness_chk" class="smpl_imgedit_cl">&nbsp;&nbsp;

          <label for="smpl_rgb_chk">{{RGB}}:</label>
          <input id="smpl_red"   name="smpl_red"   type="number" step="1" min="-255" max ="255" value="0" class="smpl_imgedit_max">
          <input id="smpl_green" name="smpl_green" type="number" step="1" min="-255" max ="255" value="0" class="smpl_imgedit_max">
          <input id="smpl_blue"  name="smpl_blue"  type="number" step="1" min="-255" max ="255" value="0" class="smpl_imgedit_max">
          <input type="checkbox" id="smpl_rgb_chk" name="smpl_rgb_chk" class="smpl_imgedit_cl">
        </div>
        <div class="smpl_imgeditborder">
          <label for="smpl_denoise_chk">&nbsp;{{DeNoise}}:</label>
          <input id="smpl_maskWidth" name="smpl_maskWidth"  type="number" step="1" min="3" max ="7" value="3" class="smpl_imgedit_max">
          <input id="smpl_maskHeight" name="smpl_maskHeight" type="number" step="1" min="3" max ="7" value="3" class="smpl_imgedit_max">
          <input type="checkbox" id="smpl_denoise_chk" name="smpl_denoise_chk" class="smpl_imgedit_cl">
        </div>
      </div>

      <div class="smpl_imgeditform_container">
        <img src="{{404}}" id="SmplImgtmp" class="smpl_imgedit_img" />
      </div>
    </div>
    <div style="clear:both; max-width: 100%; margin:auto;">
      <div id="smpl_idx" style="display:inline; max-width: 55px;"></div>:<div id="smpl_queue" style="display:inline; max-width: 55px;"></div>
      <input type="button" name="SmplImgEditFormUndo"   id="SmplImgEditFormUndo"   class="smpl_imgedit_form_undo"   value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{Undo}}" disabled="disabled" />
      <input type="button" name="SmplImgEditFormRedo"   id="SmplImgEditFormRedo"   class="smpl_imgedit_form_redo"   value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{Redo}}" disabled="disabled" />
      <input type="button" name="SmplImgEditFormSubmit" id="SmplImgEditFormSubmit" class="smpl_imgedit_form_save"   value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{Save}}" disabled="disabled" />
      <input type="button" name="SmplImgEditFormThumb"  id="SmplImgEditFormThumb"  class="smpl_imgedit_form_thumb"  value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{Thumbnail}}" />
      <input type="button" name="SmplImgEditFormCancel" id="SmplImgEditFormCancel" class="smpl_imgedit_form_cancel" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{Cancel}}"/>
    </div>
  </div>
</form>