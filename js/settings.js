(function ($) {
  $("#edit-aiclarifai").change(function () {
    $checked = $(this).prop('checked');
    if ($checked) {
      $('#edit-aigemini').prop('checked', false)
    }
  });

  $("#edit-aigemini").change(function () {
    $checked = $(this).prop('checked');
    if ($checked) {
      $('#edit-aiclarifai').prop('checked', false)
    }
  });
})(jQuery);