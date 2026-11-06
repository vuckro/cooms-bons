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
  var gift = document.querySelector("#cb-gift");
  var date = document.querySelector("#cb-valid-until");
  function updatePreview() {
    if (!gift || !date) return;
    document.querySelector("[data-gift-preview]").textContent =
      gift.value.trim() || "Votre cadeau";
    var parts = date.value.split("-");
    document.querySelector("[data-date-preview]").textContent =
      parts.length === 3
        ? "Valable jusqu’au " +
          parts[2] +
          "/" +
          parts[1] +
          "/" +
          parts[0] +
          " inclus"
        : "Choisissez une date de validité";
  }
  if (gift && date) {
    gift.addEventListener("input", updatePreview);
    date.addEventListener("input", updatePreview);
  }
  var copyScanner = document.querySelector('[data-copy-scanner]');
  if (copyScanner) {
    copyScanner.addEventListener('click', async function () {
      var link = document.querySelector('#cb-scanner-link');
      var feedback = document.querySelector('[data-copy-feedback]');
      try {
        await navigator.clipboard.writeText(link.value);
        feedback.textContent = 'Lien copié. Vous pouvez le transmettre à l’employé.';
      } catch (error) {
        link.focus();
        link.select();
        feedback.textContent = 'Lien sélectionné. Utilisez la commande Copier de votre appareil.';
      }
    });
  }
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });
})();
