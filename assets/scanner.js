/* jsQR is bundled locally. Camera frames never leave this browser. */
(function () {
  "use strict";
  var root = document.querySelector("[data-scanner]");
  if (!root) return;
  var video = root.querySelector("[data-video]");
  var start = root.querySelector("[data-start]");
  var stop = root.querySelector("[data-stop]");
  var idle = root.querySelector("[data-idle]");
  var guide = root.querySelector("[data-guide]");
  var status = root.querySelector("[data-camera-status]");
  var qrForm = root.querySelector("[data-qr-form]");
  var manual = document.querySelector("[data-manual-form]");
  var canvas = document.createElement("canvas");
  var context = canvas.getContext("2d", { willReadFrequently: true });
  var stream = null;
  var timer = null;
  var generation = 0;
  var submitting = false;

  function message(text, error) {
    status.textContent = text;
    status.classList.toggle("cb-camera-error", Boolean(error));
  }

  function closeCamera() {
    generation++;
    clearTimeout(timer);
    if (stream)
      stream.getTracks().forEach(function (track) {
        track.stop();
      });
    stream = null;
    canvas.width = 0;
    canvas.height = 0;
    video.pause();
    video.srcObject = null;
    video.hidden = true;
    guide.hidden = true;
    idle.hidden = false;
    start.hidden = false;
    start.disabled = !navigator.onLine;
    stop.hidden = true;
    stop.textContent = "Arrêter la caméra";
    root.removeAttribute("aria-busy");
  }

  function belongsHere(value) {
    try {
      var scanned = new URL(value);
      var home = new URL(root.dataset.home);
      return (
        scanned.origin === home.origin &&
        scanned.pathname.replace(/\/$/, "") ===
          home.pathname.replace(/\/$/, "") &&
        !scanned.username &&
        !scanned.password &&
        !scanned.hash &&
        /^[a-f0-9]{64}$/.test(scanned.searchParams.get("cooms_bon") || "")
      );
    } catch (ignore) {
      return false;
    }
  }

  function readFrame(id) {
    if (generation !== id || !stream || submitting) return;
    if (video.readyState >= 2 && video.videoWidth && video.videoHeight) {
      var scale = Math.min(
        1,
        800 / Math.max(video.videoWidth, video.videoHeight),
      );
      canvas.width = Math.round(video.videoWidth * scale);
      canvas.height = Math.round(video.videoHeight * scale);
      context.drawImage(video, 0, 0, canvas.width, canvas.height);
      var pixels = context.getImageData(0, 0, canvas.width, canvas.height);
      var found = window.jsQR(pixels.data, pixels.width, pixels.height, {
        inversionAttempts: "dontInvert",
      });
      if (found) {
        if (belongsHere(found.data)) {
          submitting = true;
          closeCamera();
          start.disabled = true;
          message("QR COOMS détecté. Vérification du bon…");
          qrForm.querySelector('[name="code"]').value = found.data;
          qrForm.submit();
          return;
        }
        message(
          "Ce QR code ne correspond pas à un bon COOMS de cette boutique. Présentez un autre bon.",
          true,
        );
      }
    }
    timer = setTimeout(function () {
      readFrame(id);
    }, 180);
  }

  start.addEventListener("click", async function () {
    if (submitting || start.disabled) return;
    if (
      !window.isSecureContext ||
      !navigator.mediaDevices ||
      !navigator.mediaDevices.getUserMedia
    ) {
      message(
        "La caméra nécessite une connexion HTTPS et un navigateur compatible. Saisissez la référence du bon ci-dessous.",
        true,
      );
      return;
    }
    if (!context || typeof window.jsQR !== "function") {
      message(
        "Le lecteur caméra n’a pas pu être chargé. Actualisez la page ou saisissez la référence ci-dessous.",
        true,
      );
      return;
    }
    var id = ++generation;
    start.disabled = true;
    root.setAttribute("aria-busy", "true");
    stop.hidden = false;
    stop.textContent = "Annuler l’ouverture";
    message(
      "Autorisez l’accès à la caméra dans votre navigateur. Aucun son n’est enregistré.",
    );
    try {
      var acquired = await navigator.mediaDevices.getUserMedia({
        audio: false,
        video: {
          facingMode: { ideal: "environment" },
          width: { ideal: 1280 },
          height: { ideal: 720 },
        },
      });
      if (id !== generation || document.hidden) {
        acquired.getTracks().forEach(function (track) {
          track.stop();
        });
        return;
      }
      stream = acquired;
      video.srcObject = stream;
      video.hidden = false;
      idle.hidden = true;
      await video.play();
      if (id !== generation) return;
      guide.hidden = false;
      start.hidden = true;
      stop.textContent = "Arrêter la caméra";
      root.removeAttribute("aria-busy");
      message(
        "Placez le QR code entier dans le cadre. La remise ne sera pas enregistrée à cette étape.",
      );
      readFrame(id);
    } catch (error) {
      if (id !== generation) return;
      closeCamera();
      var errors = {
        NotAllowedError:
          "Accès à la caméra refusé. Autorisez-le dans les réglages du navigateur ou saisissez la référence ci-dessous.",
        NotFoundError:
          "Aucune caméra détectée. Utilisez un appareil équipé ou saisissez la référence ci-dessous.",
        NotReadableError:
          "La caméra est indisponible ou déjà utilisée. Fermez l’autre application caméra, puis réessayez.",
        OverconstrainedError:
          "Cette caméra n’est pas compatible. Saisissez la référence du bon ci-dessous.",
      };
      message(
        errors[error.name] ||
          "Impossible d’ouvrir la caméra. Réessayez ou saisissez la référence du bon ci-dessous.",
        true,
      );
    }
  });

  stop.addEventListener("click", function () {
    closeCamera();
    message(
      "Caméra arrêtée. Vous pouvez la rouvrir ou saisir la référence du bon.",
    );
  });
  manual.addEventListener("submit", function (event) {
    if (submitting) {
      event.preventDefault();
      return;
    }
    submitting = true;
    closeCamera();
    var button = manual.querySelector("button");
    button.disabled = true;
    button.textContent = "Vérification…";
    manual.setAttribute("aria-busy", "true");
  });
  window.addEventListener("pagehide", closeCamera);
  window.addEventListener("offline", function () {
    closeCamera();
    message("Hors connexion. Reconnectez-vous et actualisez la page avant de continuer.", true);
  });
  document.addEventListener("visibilitychange", function () {
    if (document.hidden) {
      closeCamera();
      message(
        "Caméra mise en pause. Touchez « Ouvrir la caméra » pour reprendre.",
      );
    }
  });
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) window.location.reload();
  });
})();
