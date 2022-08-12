(function ($) {
  $(document).ready(function () {
    tasteLoadPassChangeBtn();
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
})(jQuery);
