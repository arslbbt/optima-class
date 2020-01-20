$(document).ready(function () {
  // $('.multiselect').multiselect({ nonSelectedText: $(this).attr("data-placeholder") });
  $('.multiselect').multiselect({ allSelectedText: $('.multiselect').attr("data-allselectedtext"), nSelectedText: $('.multiselect').attr("data-nselectedtext") });
});
