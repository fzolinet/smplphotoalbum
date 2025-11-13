(function ($, smpl) {
  $("#edit-aiclarifai").change(function () {
    $checked = $(this).prop('checked');
    $('#edit-aigemini').prop('checked', !$checked)
  });

  $("#edit-aigemini").change(function () {
    $checked = $(this).prop('checked');
    $('#edit-aiclarifai').prop('checked', !$checked)
  });
})(jQuery, smpl);