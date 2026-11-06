(function () {
  "use strict";
  document.querySelectorAll("[data-cb-submit]").forEach(function (form) {
    form.addEventListener("submit", function (event) {
      if (form.dataset.submitting === "true") {
        event.preventDefault();
        return;
      }
      form.dataset.submitting = "true";
      var button = form.querySelector('button[type="submit"]');
      button.disabled = true;
      button.textContent = button.dataset.busy;
      form.setAttribute("aria-busy", "true");
      var feedback = form.querySelector("[data-cb-feedback]");
      if (feedback) {
        feedback.textContent =
          "Attendez la confirmation. En cas de coupure, rechargez la page pour vérifier le résultat.";
      }
    });
  });
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });
})();
