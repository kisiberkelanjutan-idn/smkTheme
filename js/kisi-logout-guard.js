(function () {
    "use strict";

    if (window.__kisiLogoutGuardActive) return;
    window.__kisiLogoutGuardActive = true;

    function resolveContextSignOutUrl(rawHref) {
        if (!rawHref || !/\/login\/signOut(\b|\/|\?|$)/i.test(rawHref)) {
            return null;
        }
        try {
            var parsed = new URL(rawHref, window.location.origin);
            if (parsed.origin !== window.location.origin) {
                return null;
            }
            var ctxMatch = window.location.pathname.match(/^(.*\/index\.php\/)([^\/?#]+)(?:\/|$)/i);
            if (ctxMatch && ctxMatch[2] && ctxMatch[2].toLowerCase() !== "index") {
                if (/\/index\.php\/index\/login\/signOut\b/i.test(parsed.pathname)) {
                    parsed.pathname = ctxMatch[1] + ctxMatch[2] + "/login/signOut";
                }
            }
            parsed.search = "";
            parsed.hash = "";
            return parsed;
        } catch (e) {
            return null;
        }
    }

    document.addEventListener("click", function (ev) {
        if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) {
            return;
        }
        var target = ev.target;
        var anchor = target && typeof target.closest === "function" ? target.closest("a[href]") : null;
        if (!anchor || anchor.getAttribute("data-kisi-logging-out") === "1") {
            return;
        }

        var signOutUrlObj = resolveContextSignOutUrl(anchor.getAttribute("href"));
        if (!signOutUrlObj) {
            return;
        }

        ev.preventDefault();
        ev.stopPropagation();
        anchor.setAttribute("data-kisi-logging-out", "1");

        try {
            if (window.sessionStorage) {
                window.sessionStorage.clear();
            }
        } catch (e) {}

        var stamp = String(Date.now()) + Math.random().toString(36).slice(2, 6);
        var cleanSignOutHref = signOutUrlObj.toString();
        var uncachedSignOutHref = cleanSignOutHref + "?_logout=" + stamp;
        var uncachedLoginHref = cleanSignOutHref.replace(/\/login\/signOut.*$/i, "/login") + "?_out=" + stamp;

        if (typeof window.fetch === "function") {
            window.fetch(uncachedSignOutHref, {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store"
            }).then(function () {
                window.location.replace(uncachedLoginHref);
            }).catch(function () {
                window.location.replace(uncachedSignOutHref);
            });
        } else {
            window.location.replace(uncachedSignOutHref);
        }
    }, true);
})();
