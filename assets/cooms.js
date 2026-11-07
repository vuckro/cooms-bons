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
  // Preserve the host's accent and foreground; darken/lighten only unsafe button backgrounds.
  var app = document.querySelector("body.adminkit .cb-app");
  if (app) {
    var pixel = document.createElement("canvas");
    pixel.width = pixel.height = 1;
    var painter = pixel.getContext("2d", { willReadFrequently: true });
    var probe = document.createElement("span");
    probe.hidden = true;
    app.appendChild(probe);
    function rgb(color) {
      probe.style.color = color;
      painter.clearRect(0, 0, 1, 1);
      painter.fillStyle = getComputedStyle(probe).color;
      painter.fillRect(0, 0, 1, 1);
      return Array.from(painter.getImageData(0, 0, 1, 1).data).slice(0, 3);
    }
    function luminance(channels) {
      return channels.map(function (v) {
        v /= 255;
        return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
      }).reduce(function (sum, v, i) { return sum + v * [0.2126, 0.7152, 0.0722][i]; }, 0);
    }
    function contrast(a, b) { return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05); }
    function accessibleBackground(background, foreground) {
      var original = rgb(background), ink = luminance(rgb(foreground));
      if (contrast(luminance(original), ink) >= 4.5) return background;
      var target = ink > 0.179 ? 0 : 255;
      for (var amount = 0.01; amount <= 1.01; amount += 0.01) {
        var mixed = original.map(function (v) { return Math.round(v + (target - v) * Math.min(1, amount)); });
        if (contrast(luminance(mixed), ink) >= 4.55) return "rgb(" + mixed.join(",") + ")";
      }
      return background;
    }
    function syncContrast() {
      if (!painter) return;
      var tokens = getComputedStyle(app);
      var normal = tokens.getPropertyValue("--ak-primary").trim();
      var hover = tokens.getPropertyValue("--ak-primary-hover").trim();
      var ink = tokens.getPropertyValue("--ak-on-accent").trim();
      var hoverInk = tokens.getPropertyValue("--ak-on-accent-hover").trim() || ink;
      if (normal && ink) app.style.setProperty("--cb-button-bg", accessibleBackground(normal, ink));
      if (hover && hoverInk) app.style.setProperty("--cb-button-hover-bg", accessibleBackground(hover, hoverInk));
    }
    syncContrast();
    var paletteObserver = new MutationObserver(function () { requestAnimationFrame(syncContrast); });
    paletteObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["data-adminkit-theme", "data-adminkit-accent", "style", "class"] });
    paletteObserver.observe(document.body, { attributes: true, attributeFilter: ["class", "style"] });
  }
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });
})();
