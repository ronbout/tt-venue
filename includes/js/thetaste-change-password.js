(function ($) {
  $(document).ready(function () {
    $("#venue-password-change-form").length && tasteLoadPassChangeBtn();
    $("from").length && tasteLoadFormSubmits();
  });

  const tasteLoadPassChangeBtn = () => {
    $("#venue-password-change-form").submit(function (e) {
      const pass1 = $("#password_1").val();
      const pass2 = $("#password_2").val();
      if (pass1 !== pass2) {
        $("#venue-pass-match-error-msg").text(
          "New Password and Confirm Password must match."
        );
        e.preventDefault();
      }
    });
  };

  const tasteLoadFormSubmits = () => {
    $(form).on("submit", function () {
      $('button[type="submit"]', this).attr("disabled", "disabled");
    });
  };
})(jQuery);
