(function () {
  "use strict";

  // Keine jQuery-Abhängigkeit – Vanilla JS only
  // Cookie- und Popup-Logik läuft komplett im Browser (Cache-safe)

  if (typeof nbpConfig === "undefined" || !nbpConfig.popups) return;

  /* ── Cookie Helpers ── */

  function getCookie(name) {
    var safeName = name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    var match = document.cookie.match(
      new RegExp("(^| )" + safeName + "=([^;]+)")
    );
    return match ? decodeURIComponent(match[2]) : null;
  }

  function setCookie(name, value, days) {
    var cookie =
      name + "=" + encodeURIComponent(value) + "; path=/; SameSite=Lax";
    if (days > 0) {
      var expires = new Date(Date.now() + days * 86400000).toUTCString();
      cookie += "; expires=" + expires;
    }
    document.cookie = cookie;
  }

  /* ── Debug Logger ── */

  function createLogger(popupId, enabled) {
    return function () {
      if (!enabled) return;
      var args = Array.prototype.slice.call(arguments);
      args.unshift("[NBP Popup #" + popupId + "]");
      console.log.apply(console, args);
    };
  }

  /* ── Page Matching ── */

  function matchesPage(pattern, path) {
    // Wenn pattern eine volle URL ist, nur den Pfad extrahieren
    try {
      var url = new URL(pattern, window.location.origin);
      pattern = url.pathname;
    } catch (e) {
      // pattern ist bereits ein Pfad
    }

    // Wildcard-Pattern zu Regex konvertieren
    var escaped = pattern.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    escaped = escaped.replace(/\\\*/g, ".*");
    return new RegExp("^" + escaped + "$").test(path);
  }

  function shouldShowOnPage(config) {
    if (config.pageRule === "all") return true;

    var currentPath = window.location.pathname;
    var pages = (config.pages || "")
      .split("\n")
      .map(function (p) {
        return p.trim();
      })
      .filter(Boolean);

    var matches = pages.some(function (pattern) {
      return matchesPage(pattern, currentPath);
    });

    if (config.pageRule === "include") return matches;
    if (config.pageRule === "exclude") return !matches;
    return true;
  }

  /* ── Date Checks (Browser-Zeit, Cache-safe) ── */

  function isWithinDateRange(config) {
    var now = new Date();

    if (config.activateDate) {
      var activateDate = new Date(config.activateDate);
      if (now < activateDate) return false;
    }

    if (config.deactivateDate) {
      var deactivateDate = new Date(config.deactivateDate);
      if (now > deactivateDate) return false;
    }

    return true;
  }

  /* ── Popup Display ── */

  function showPopup(el) {
    el.style.display = "flex";
    el.style.opacity = "0";

    var opacity = 0;
    var fadeIn = setInterval(function () {
      opacity = Math.min(opacity + 0.05, 1);
      el.style.opacity = opacity;
      if (opacity >= 1) {
        clearInterval(fadeIn);
        disableBodyScroll(el);
      }
    }, 16);
  }

  function hidePopup(el) {
    var opacity = 1;
    var fadeOut = setInterval(function () {
      opacity = Math.max(opacity - 0.05, 0);
      el.style.opacity = opacity;
      if (opacity <= 0) {
        clearInterval(fadeOut);
        el.style.display = "none";
        el.style.opacity = "";
        enableBodyScroll();
      }
    }, 16);
  }

  function disableBodyScroll(popupEl) {
    document.body.classList.add("nbp-popup-active");
    popupEl.removeAttribute("inert");
  }

  function enableBodyScroll() {
    document.body.classList.remove("nbp-popup-active");
  }

  /* ── Bind Close Events ── */

  function bindCloseEvents(el) {
    // Close button
    var closeBtn = el.querySelector(".close");
    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        hidePopup(el);
      });
    }

    // Click on backdrop (outside container)
    el.addEventListener("click", function (e) {
      if (e.target === el) {
        hidePopup(el);
      }
    });

    // ESC key
    var escHandler = function (e) {
      if (e.key === "Escape" && el.style.display === "flex") {
        hidePopup(el);
        document.removeEventListener("keydown", escHandler);
      }
    };
    document.addEventListener("keydown", escHandler);
  }

  /* ── Borlabs Integration ── */

  function waitForBorlabs(callback, log) {
    if (typeof window.BorlabsCookie !== "undefined") {
      log("Borlabs Cookie erkannt");

      if (getCookie("borlabs-cookie")) {
        log("Borlabs-Entscheidung bereits vorhanden, fahre fort");
        callback();
      } else {
        log("Warte auf Borlabs Cookie-Consent...");
        document.addEventListener(
          "borlabs-cookie-consent-saved",
          function () {
            log("Borlabs Consent gespeichert, fahre fort");
            callback();
          },
          { once: true }
        );
      }
    } else {
      log("Borlabs nicht aktiv, fahre ohne Prüfung fort");
      callback();
    }
  }

  /* ── Init Single Popup ── */

  function initPopup(config) {
    var log = createLogger(config.id, config.debug);
    var el = document.querySelector(".nbp-popup-" + config.id);

    if (!el) {
      log("Popup-Element nicht im DOM gefunden");
      return;
    }

    log("Initialisiere Popup...");
    log("Config:", JSON.stringify(config));

    // Check: aktiv?
    if (!config.active) {
      log("Popup ist deaktiviert");
      return;
    }

    // Check: Zeitraum
    if (!isWithinDateRange(config)) {
      log("Popup außerhalb des Zeitraums");
      return;
    }

    // Check: Seitenregel
    if (!shouldShowOnPage(config)) {
      log(
        "Popup wird auf dieser Seite nicht angezeigt (Regel: " +
          config.pageRule +
          ")"
      );
      return;
    }

    var cookieName = "nbp_popup_" + config.id;
    var isClickTrigger = config.triggerType === "click";
    var skipCookie = config.showAlways || isClickTrigger;

    // Cookie prüfen: überspringen bei "showAlways" oder Click-Trigger
    if (!skipCookie && getCookie(cookieName)) {
      log("Cookie vorhanden – Popup wurde bereits angezeigt");
      el.parentNode.removeChild(el);
      return;
    }

    log("Alle Prüfungen bestanden, warte auf Trigger...");

    function triggerPopup() {
      log("Popup wird angezeigt");

      // Cookie nur setzen wenn nicht "showAlways" und nicht Click-Trigger
      if (!skipCookie) {
        var now = new Date().toISOString();
        setCookie(cookieName, now, config.cookieDays);
        log("Cookie gesetzt für " + config.cookieDays + " Tage");
      }

      showPopup(el);
      bindCloseEvents(el);
    }

    // Borlabs abwarten, dann Trigger aktivieren
    waitForBorlabs(function () {
      if (isClickTrigger) {
        if (!config.triggerSelector) {
          log("Kein Click-Selektor definiert");
          return;
        }
        log("Warte auf Klick auf: " + config.triggerSelector);
        document.addEventListener("click", function (e) {
          if (
            e.target.matches(config.triggerSelector) ||
            e.target.closest(config.triggerSelector)
          ) {
            triggerPopup();
          }
        });
      } else {
        // Timer-Trigger
        var delay = (config.triggerDelay || 0) * 1000;
        log("Timer-Trigger: " + config.triggerDelay + "s Verzögerung");
        if (delay > 0) {
          setTimeout(triggerPopup, delay);
        } else {
          triggerPopup();
        }
      }
    }, log);
  }

  /* ── Boot ── */

  function boot() {
    nbpConfig.popups.forEach(initPopup);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
