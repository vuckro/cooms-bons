/* Connected webapp shell: no worker, no cached voucher state, no offline mutation. */
(function () {
  "use strict";
  var notice = document.querySelector("[data-connection]");
  if (!notice) return;
  var message = notice.querySelector("[data-connection-message]");
  var refresh = notice.querySelector("[data-connection-refresh]");
  var needsRefresh = false;
  var wasHidden = false;
  var installPrompt = null;
  var installButton = document.querySelector("[data-install-button]");
  var installHelp = document.querySelector("[data-install-help]");

  function blockActions() {
    document.querySelectorAll(".cb-redeem, [data-start], [data-manual-form] button").forEach(function (button) {
      button.disabled = true;
    });
  }
  function offline() {
    needsRefresh = true;
    document.body.classList.add("cb-is-offline");
    notice.hidden = false;
    refresh.hidden = true;
    message.textContent = "Hors connexion. Les retraits sont suspendus. Reconnectez-vous, puis actualisez le statut avant de continuer.";
    var available = document.querySelector(".cb-state-available .cb-verdict");
    if (available) {
      available.querySelector("h1").textContent = "Vérification en pause";
      available.querySelector("p").textContent = "Actualisez le statut après le retour de la connexion avant de remettre ce cadeau.";
    }
    blockActions();
  }
  function online() {
    if (!needsRefresh) return;
    notice.hidden = false;
    refresh.hidden = false;
    message.textContent = "Connexion rétablie. Actualisez la page pour vérifier votre accès et le statut du bon.";
    blockActions();
  }
  refresh.addEventListener("click", function () { window.location.reload(); });
  document.addEventListener("submit", function (event) {
    if ((!navigator.onLine || needsRefresh) && event.target.matches("[data-cb-submit], [data-manual-form], [data-qr-form]")) {
      event.preventDefault();
      event.stopImmediatePropagation();
      if (!navigator.onLine) offline(); else online();
      notice.scrollIntoView({ block: "nearest" });
    }
  }, true);
  window.addEventListener("offline", offline);
  window.addEventListener("online", online);
  document.addEventListener("visibilitychange", function () {
    if (document.hidden) { wasHidden = true; return; }
    if (!wasHidden) return;
    wasHidden = false;
    if (!navigator.onLine) { offline(); return; }
    // Never replay a pending lookup/redemption. A returned GET must revalidate on the server.
    if (!document.querySelector('form[aria-busy="true"]')) window.location.reload();
  });
  if (!navigator.onLine) offline();

  function installed() {
    return Boolean(navigator.standalone) || window.matchMedia("(display-mode: standalone)").matches;
  }
  if (installed() && installHelp) installHelp.hidden = true;
  window.addEventListener("beforeinstallprompt", function (event) {
    if (!installButton || installed()) return;
    event.preventDefault();
    installPrompt = event;
    installButton.hidden = false;
  });
  if (installButton) installButton.addEventListener("click", async function () {
    if (!installPrompt) return;
    installButton.disabled = true;
    try { await installPrompt.prompt(); await installPrompt.userChoice; }
    catch (error) { if (installHelp) installHelp.open = true; }
    finally { installPrompt = null; installButton.hidden = true; installButton.disabled = false; }
  });
  window.addEventListener("appinstalled", function () {
    installPrompt = null;
    if (installHelp) installHelp.hidden = true;
  });
})();
