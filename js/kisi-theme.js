(function () {
    "use strict";

    var SDG_CATALOG = {
        1:  { name: "No Poverty", color: "#E5243B" },
        2:  { name: "Zero Hunger", color: "#DDA63A" },
        3:  { name: "Good Health and Well-Being", color: "#4C9F38" },
        4:  { name: "Quality Education", color: "#C5192D" },
        5:  { name: "Gender Equality", color: "#FF3A21" },
        6:  { name: "Clean Water and Sanitation", color: "#26BDE2" },
        7:  { name: "Affordable and Clean Energy", color: "#FCC30B" },
        8:  { name: "Decent Work and Economic Growth", color: "#A21942" },
        9:  { name: "Industry, Innovation and Infrastructure", color: "#FD6925" },
        10: { name: "Reduced Inequalities", color: "#DD1367" },
        11: { name: "Sustainable Cities and Communities", color: "#FD9D24" },
        12: { name: "Responsible Consumption and Production", color: "#BF8B2E" },
        13: { name: "Climate Action", color: "#3F7E44" },
        14: { name: "Life Below Water", color: "#0A97D9" },
        15: { name: "Life on Land", color: "#56C02B" },
        16: { name: "Peace, Justice and Strong Institutions", color: "#00689D" },
        17: { name: "Partnerships for the Goals", color: "#19486A" }
    };

    function readThemeData() {
        var el = document.getElementById("kisi-theme-data");
        if (!el) return {};
        try {
            return JSON.parse(el.textContent || "{}") || {};
        } catch (e) {
            return {};
        }
    }

    function escapeHtml(val) {
        return String(val == null ? "" : val)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function sanitizeUrl(rawUrl, fallback) {
        var fb = fallback || "#";
        if (!rawUrl) return fb;
        var trimmed = String(rawUrl).trim();
        if (!trimmed) return fb;
        if (trimmed.charAt(0) === "#" || (trimmed.charAt(0) === "/" && trimmed.charAt(1) !== "/")) {
            return trimmed;
        }
        try {
            var parsed = new URL(trimmed, window.location.origin);
            if (parsed.protocol === "https:" || parsed.protocol === "http:") {
                return parsed.href;
            }
        } catch (e) {
            return fb;
        }
        return fb;
    }

    function formatInt(n) {
        return (parseInt(n, 10) || 0).toLocaleString("en-US");
    }

    function pad2(num) {
        return num < 10 ? "0" + num : String(num);
    }

    function findArticleTitleLink(cardEl) {
        if (!cardEl) return null;
        return cardEl.querySelector(".title a, h3.title a, h4.title a, h2.title a")
            || cardEl.querySelector('a[href*="/article/view/"]:not(.cover):not(.file)');
    }

    function extractSdgsFromSubjects(combinedSubjects) {
        var detected = [];
        if (!combinedSubjects || !combinedSubjects.trim()) return detected;
        var sdgRegex = /(?:sdgs?|goals?|tujuan)\s*(?:goals?|#|:|-)?\s*(1[0-7]|0?[1-9])\b/gi;
        var match;
        while ((match = sdgRegex.exec(combinedSubjects)) !== null) {
            var num = parseInt(match[1], 10);
            if (SDG_CATALOG[num] && detected.indexOf(num) === -1) {
                detected.push(num);
            }
        }
        if (detected.length === 0) {
            var lowerSubjects = combinedSubjects.toLowerCase();
            Object.keys(SDG_CATALOG).forEach(function (k) {
                var n = parseInt(k, 10);
                if (lowerSubjects.indexOf(SDG_CATALOG[n].name.toLowerCase()) !== -1 && detected.indexOf(n) === -1) {
                    detected.push(n);
                }
            });
        }
        return detected;
    }

    function createSafeRorSvg() {
        var ns = "http://www.w3.org/2000/svg";
        var svg = document.createElementNS(ns, "svg");
        svg.setAttribute("viewBox", "0 0 23.4225 16.696501");
        svg.setAttribute("width", "23.4");
        svg.setAttribute("height", "16.7");
        svg.setAttribute("aria-hidden", "true");
        svg.setAttribute("class", "kisi-ror-icon");

        var g = document.createElementNS(ns, "g");
        g.setAttribute("transform", "matrix(0.15,0,0,0.15,-0.7305,-0.624)");

        [
            "68.65,4.16 56.52,22.74 44.38,4.16",
            "119.41,4.16 107.28,22.74 95.14,4.16",
            "44.38,115.47 56.52,96.88 68.65,115.47",
            "95.14,115.47 107.28,96.88 119.41,115.47"
        ].forEach(function (pts) {
            var p = document.createElementNS(ns, "polygon");
            p.setAttribute("points", pts);
            p.setAttribute("fill", "#53baa1");
            g.appendChild(p);
        });

        var path = document.createElementNS(ns, "path");
        path.setAttribute("fill", "#202826");
        path.setAttribute("d", "m 145.53,63.71 c 4.3,-0.8 7.57,-2.71 9.8,-5.72 2.24,-3.01 3.35,-6.67 3.35,-10.96 0,-3.56 -0.62,-6.52 -1.85,-8.9 -1.23,-2.38 -2.9,-4.27 -4.99,-5.68 -2.09,-1.4 -4.53,-2.41 -7.31,-3.01 -2.78,-0.6 -5.72,-0.9 -8.81,-0.9 h -23.56 v 18.83 c -0.19,-0.55 -0.39,-1.09 -0.61,-1.63 -1.63,-3.95 -3.91,-7.32 -6.84,-10.1 -2.93,-2.78 -6.39,-4.92 -10.41,-6.41 -4.01,-1.49 -8.4,-2.24 -13.16,-2.24 -4.76,0 -9.14,0.75 -13.16,2.24 -4.01,1.49 -7.48,3.63 -10.41,6.41 -2.62,2.49 -4.72,5.46 -6.3,8.9 -0.23,-2.47 -0.81,-4.61 -1.74,-6.41 -1.23,-2.38 -2.9,-4.27 -4.99,-5.68 -2.09,-1.4 -4.53,-2.41 -7.31,-3.01 -2.78,-0.6 -5.72,-0.9 -8.81,-0.9 H 4.87 V 89.42 H 18.28 V 65.08 H 24.9 L 37.63,89.42 H 53.71 L 38.24,63.71 c 4.3,-0.8 7.57,-2.71 9.8,-5.72 0.1,-0.14 0.19,-0.29 0.29,-0.43 -0.02,0.47 -0.03,0.94 -0.03,1.42 0,4.87 0.82,9.29 2.45,13.24 1.63,3.95 3.91,7.32 6.84,10.1 2.92,2.78 6.39,4.92 10.41,6.41 4.01,1.49 8.4,2.24 13.16,2.24 4.76,0 9.14,-0.75 13.16,-2.24 4.01,-1.49 7.48,-3.63 10.41,-6.41 2.92,-2.78 5.2,-6.15 6.84,-10.1 0.22,-0.53 0.42,-1.08 0.61,-1.63 v 18.83 h 13.41 V 65.08 h 6.62 l 12.73,24.34 h 16.08 z M 36.39,50.81 c -0.72,0.92 -1.62,1.59 -2.71,2.02 -1.09,0.43 -2.31,0.69 -3.65,0.77 -1.35,0.09 -2.62,0.13 -3.83,0.13 H 18.29 V 39.89 h 8.77 c 1.2,0 2.44,0.09 3.7,0.26 1.26,0.17 2.38,0.5 3.35,0.99 0.97,0.49 1.78,1.19 2.41,2.11 0.63,0.92 0.95,2.15 0.95,3.7 0,1.65 -0.36,2.94 -1.08,3.86 z m 62.35,16.04 c -0.89,2.38 -2.16,4.44 -3.83,6.19 -1.66,1.75 -3.65,3.11 -5.98,4.09 -2.32,0.98 -4.92,1.46 -7.78,1.46 -2.87,0 -5.46,-0.49 -7.78,-1.46 -2.32,-0.97 -4.31,-2.34 -5.98,-4.09 -1.66,-1.75 -2.94,-3.81 -3.83,-6.19 -0.89,-2.38 -1.33,-5 -1.33,-7.87 0,-2.81 0.44,-5.42 1.33,-7.83 0.89,-2.41 2.16,-4.48 3.83,-6.23 1.66,-1.75 3.65,-3.11 5.98,-4.09 2.32,-0.97 4.91,-1.46 7.78,-1.46 2.87,0 5.46,0.49 7.78,1.46 2.32,0.97 4.31,2.34 5.98,4.09 1.66,1.75 2.94,3.83 3.83,6.23 0.89,2.41 1.33,5.02 1.33,7.83 0,2.87 -0.44,5.49 -1.33,7.87 z m 44.94,-16.04 c -0.72,0.92 -1.62,1.59 -2.71,2.02 -1.09,0.43 -2.31,0.69 -3.65,0.77 -1.35,0.09 -2.62,0.13 -3.83,0.13 h -7.91 V 39.89 h 8.77 c 1.2,0 2.44,0.09 3.7,0.26 1.26,0.17 2.38,0.5 3.35,0.99 0.97,0.49 1.78,1.19 2.41,2.11 0.63,0.92 0.95,2.15 0.95,3.7 0,1.65 -0.36,2.94 -1.08,3.86 z");
        g.appendChild(path);

        svg.appendChild(g);
        return svg;
    }

    function cloneSafeRichTextFragment(sourceEl) {
        var frag = document.createDocumentFragment();
        if (!sourceEl) return frag;
        var allowedTags = {
            a: true, em: true, i: true, strong: true, b: true,
            sup: true, sub: true, span: true, small: true, br: true, p: true
        };

        function walk(node, targetParent) {
            if (node.nodeType === Node.TEXT_NODE) {
                targetParent.appendChild(document.createTextNode(node.nodeValue || ""));
                return;
            }
            if (node.nodeType !== Node.ELEMENT_NODE) return;

            var tag = node.tagName.toLowerCase();
            if (tag === "script" || tag === "style" || tag === "svg" || tag === "iframe" || tag === "object" || tag === "metadata") {
                return;
            }
            if (tag === "img") {
                var safeImgSrc = sanitizeUrl(node.getAttribute("src"), "");
                if (safeImgSrc && safeImgSrc !== "#") {
                    var cleanImg = document.createElement("img");
                    cleanImg.setAttribute("src", safeImgSrc);
                    cleanImg.setAttribute("alt", node.getAttribute("alt") || "Logo");
                    cleanImg.setAttribute("loading", "lazy");
                    targetParent.appendChild(cleanImg);
                }
                return;
            }
            if (!allowedTags[tag]) {
                Array.from(node.childNodes).forEach(function (ch) { walk(ch, targetParent); });
                return;
            }

            var cleanEl = document.createElement(tag);
            var isRorLink = false;
            if (tag === "a") {
                var safeHref = sanitizeUrl(node.getAttribute("href"), "");
                if (safeHref && safeHref !== "#") {
                    cleanEl.setAttribute("href", safeHref);
                    isRorLink = /^https?:\/\/(?:www\.)?ror\.org\//i.test(safeHref);
                    if (isRorLink || node.getAttribute("target") === "_blank") {
                        cleanEl.setAttribute("target", "_blank");
                        cleanEl.setAttribute("rel", "noopener noreferrer");
                    }
                    if (isRorLink) {
                        cleanEl.className = "kisi-ror-link";
                        cleanEl.setAttribute("title", "ROR: " + safeHref);
                        cleanEl.setAttribute("aria-label", "Research Organization Registry: " + safeHref);
                    }
                }
            }
            Array.from(node.childNodes).forEach(function (ch) { walk(ch, cleanEl); });
            if (isRorLink && !cleanEl.querySelector("img, svg")) {
                cleanEl.appendChild(createSafeRorSvg());
                if (!cleanEl.textContent.trim()) {
                    var rorUrlSpan = document.createElement("span");
                    rorUrlSpan.className = "kisi-ror-url";
                    rorUrlSpan.textContent = cleanEl.getAttribute("href") || "";
                    cleanEl.appendChild(rorUrlSpan);
                }
            }
            targetParent.appendChild(cleanEl);
        }

        Array.from(sourceEl.childNodes).forEach(function (ch) { walk(ch, frag); });
        return frag;
    }

    function optimizeImagesForPageSpeed() {
        var logo = document.querySelector(".pkp_site_name img");
        if (logo) {
            if (!logo.getAttribute("width")) logo.setAttribute("width", "139");
            if (!logo.getAttribute("height")) logo.setAttribute("height", "52");
            logo.setAttribute("fetchpriority", "high");
            logo.setAttribute("decoding", "async");
        }
        document.querySelectorAll('img[src*="sdg-publishers-compact"]').forEach(function (img) {
            if (!img.getAttribute("width")) img.setAttribute("width", "80");
            if (!img.getAttribute("height")) img.setAttribute("height", "80");
            img.setAttribute("loading", "lazy");
            img.setAttribute("decoding", "async");
        });
        var hiddenIssueCover = document.querySelector(".elife-native-toc .issue-toc > .cover img, .page_index_journal .obj_issue_toc > .heading .cover img");
        if (hiddenIssueCover) {
            hiddenIssueCover.removeAttribute("src");
            hiddenIssueCover.removeAttribute("srcset");
        }
        document.querySelectorAll(".obj_article_summary .cover img, .elife-or-card img").forEach(function (img, idx) {
            if (!img.getAttribute("width")) img.setAttribute("width", "269");
            if (!img.getAttribute("height")) img.setAttribute("height", "150");
            img.setAttribute("decoding", "async");
            if (idx >= 3) img.setAttribute("loading", "lazy");
        });
    }

    function deduplicateCardMetricNodes(cardContainer) {
        if (!cardContainer) return;
        var candidates = Array.from(
            cardContainer.querySelectorAll(".stats, .statistics, .article-metrics, .article_metrics, .views, .downloads, .view_counter, .download_counter, .view-count, .download-count")
        );
        var seen = [];
        candidates.forEach(function (el) {
            var clone = el.cloneNode(true);
            clone.querySelectorAll("svg, title, desc, style, script, noscript").forEach(function (n) { n.remove(); });
            var txt = (clone.textContent || "").replace(/\s+/g, " ").trim();
            if (!txt || !/\d/.test(txt) || txt.length > 80) return;
            var nums = txt.match(/\d+(?:[.,]\d+)*/g);
            if (!nums || nums.length === 0) return;
            var sig = nums.map(function (v) { return v.replace(/\s+/g, ""); }).join("|");
            if (sig && seen.indexOf(sig) !== -1) {
                if (el.parentNode) el.parentNode.removeChild(el);
                return;
            }
            seen.push(sig);
        });
    }

    function initStatisticsPage(themeData) {
        var existingStatsDiv = document.getElementById("kisi-journal-stats-page");
        var mainPageDiv =
            existingStatsDiv ||
            document.querySelector(".pkp_structure_main > .page") ||
            document.querySelector(".pkp_structure_main .page") ||
            document.querySelector(".pkp_structure_main");

        if (!mainPageDiv || document.getElementById("kisi-stat-total-visitors")) return;

        var pageText = (mainPageDiv.textContent || "").replace(/\s+/g, " ").trim();
        var isStatsPage =
            themeData.pageType === "statistics" ||
            Boolean(existingStatsDiv) ||
            /\/(statistics|statistik|metrics)\b/i.test(window.location.href) ||
            /Journal Usage.*Impact Statistics/i.test(pageText) ||
            /Synchronizing real-time journal usage statistics/i.test(pageText);

        if (!isStatsPage) return;

        var fullStats = themeData.fullStats || {};
        var launchLabel = (existingStatsDiv && existingStatsDiv.getAttribute("data-launch-label"))
            || themeData.launchLabel
            || "January 2024";
        var baselineVisitors = parseInt((existingStatsDiv && existingStatsDiv.getAttribute("data-visitor-baseline")) || "0", 10) || 0;

        var now = new Date();
        var formattedNow = now.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });

        var totalVisitors = baselineVisitors + (parseInt(fullStats.totalSiteTraffic, 10) || 0);
        var totalViews = parseInt(fullStats.totalAbstractViews, 10) || 0;
        var totalDownloads = parseInt(fullStats.totalDownloads, 10) || 0;
        var combinedImpact = parseInt(fullStats.combinedImpact, 10) || 0;
        var issuesCount = parseInt(fullStats.issuesCount, 10) || 0;
        var articlesCount = parseInt(fullStats.articlesCount, 10) || 0;
        var avgViews = parseInt(fullStats.avgViews, 10) || 0;
        var conversionRate = String(fullStats.conversionRate || "0.0");

        var targetRenderContainer = document.querySelector(".pkp_structure_main > .page") || mainPageDiv;
        targetRenderContainer.innerHTML =
            '<div id="kisi-journal-stats-page" class="kisi-stats-wrap" data-kisi-rendered="1">' +
                '<div class="kisi-stats-header-banner">' +
                    '<div class="kisi-stats-header-info">' +
                        '<h2>Journal Usage &amp; Impact Statistics</h2>' +
                        '<p>Verified metrics aggregated from OJS COUNTER Release 5 usage statistics across all published issues and articles.</p>' +
                    '</div>' +
                    '<div class="kisi-stats-period-badge">' +
                        '<span class="kisi-stats-period-label">Cumulative Tracking Period</span>' +
                        '<span class="kisi-stats-period-value">' + escapeHtml(launchLabel) + ' &ndash; ' + escapeHtml(formattedNow) + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="kisi-stats-kpi-grid">' +
                    '<div class="kisi-stats-kpi-card" style="--kpi-accent: #ff7a18;">' +
                        '<div class="kisi-stats-kpi-top"><span class="kisi-stats-kpi-standard">Recorded OJS Traffic</span></div>' +
                        '<div id="kisi-stat-total-visitors" class="kisi-stats-kpi-number">' + formatInt(totalVisitors) + '</div>' +
                        '<div class="kisi-stats-kpi-title">Cumulative Recorded Visits</div>' +
                        '<div class="kisi-stats-kpi-desc">Combined journal homepage views, abstract investigations, and full-text galley downloads.</div>' +
                    '</div>' +
                    '<div class="kisi-stats-kpi-card" style="--kpi-accent: #d9531e;">' +
                        '<div class="kisi-stats-kpi-top"><span class="kisi-stats-kpi-standard">Item Investigations</span></div>' +
                        '<div id="kisi-stat-total-views" class="kisi-stats-kpi-number">' + formatInt(totalViews) + '</div>' +
                        '<div class="kisi-stats-kpi-title">Total Abstract Views</div>' +
                        '<div class="kisi-stats-kpi-desc">Cumulative abstract and article landing page views across all published issues.</div>' +
                    '</div>' +
                    '<div class="kisi-stats-kpi-card" style="--kpi-accent: #4C9F38;">' +
                        '<div class="kisi-stats-kpi-top"><span class="kisi-stats-kpi-standard">Item Requests</span></div>' +
                        '<div id="kisi-stat-total-downloads" class="kisi-stats-kpi-number">' + formatInt(totalDownloads) + '</div>' +
                        '<div class="kisi-stats-kpi-title">Total Full-Text Downloads</div>' +
                        '<div class="kisi-stats-kpi-desc">Cumulative PDF and full-text galley requests across all published issues.</div>' +
                    '</div>' +
                    '<div class="kisi-stats-kpi-card" style="--kpi-accent: #c9203a;">' +
                        '<div class="kisi-stats-kpi-top"><span class="kisi-stats-kpi-standard">Article Usage Events</span></div>' +
                        '<div id="kisi-stat-total-impact" class="kisi-stats-kpi-number">' + formatInt(combinedImpact) + '</div>' +
                        '<div class="kisi-stats-kpi-title">Combined Readership Impact</div>' +
                        '<div class="kisi-stats-kpi-desc">Total combined abstract views and full-text galley downloads recorded by OJS.</div>' +
                    '</div>' +
                '</div>' +
                '<div class="kisi-stats-secondary-grid">' +
                    '<div class="kisi-stats-mini-card"><span class="kisi-stats-mini-val">' + formatInt(issuesCount) + '</span><span class="kisi-stats-mini-lbl">Published Issues</span></div>' +
                    '<div class="kisi-stats-mini-card"><span class="kisi-stats-mini-val">' + formatInt(articlesCount) + '</span><span class="kisi-stats-mini-lbl">Published Articles</span></div>' +
                    '<div class="kisi-stats-mini-card"><span class="kisi-stats-mini-val">' + formatInt(avgViews) + '</span><span class="kisi-stats-mini-lbl">Avg. Views / Article</span></div>' +
                    '<div class="kisi-stats-mini-card"><span class="kisi-stats-mini-val">' + escapeHtml(conversionRate) + '%</span><span class="kisi-stats-mini-lbl">Download-to-View Ratio</span></div>' +
                '</div>' +
                '<div class="kisi-daily-analytics-panel">' +
                    '<div class="kisi-daily-panel-header">' +
                        '<div class="kisi-daily-title-group">' +
                            '<h3>Daily Compiled Readership &amp; Traffic (Last 30 Days)</h3>' +
                            '<p><span class="kisi-live-dot"></span>Compiled daily by OJS Usage Statistics scheduled tasks (COUNTER R5).</p>' +
                        '</div>' +
                        '<div class="kisi-daily-range-tabs">' +
                            '<button type="button" class="kisi-daily-tab-btn" data-days="7">Last 7 Days</button>' +
                            '<button type="button" class="kisi-daily-tab-btn active" data-days="14">Last 14 Days</button>' +
                            '<button type="button" class="kisi-daily-tab-btn" data-days="30">Last 30 Days</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="kisi-daily-summary-strip">' +
                        '<div class="kisi-daily-stat-box">' +
                            '<span class="kisi-daily-stat-lbl">Latest Compiled Day</span>' +
                            '<span id="kisi-daily-val-today" class="kisi-daily-stat-val" style="color:#c9203a;">0</span>' +
                            '<span id="kisi-daily-sub-today" class="kisi-daily-stat-sub">Compiled by OJS cron</span>' +
                        '</div>' +
                        '<div class="kisi-daily-stat-box">' +
                            '<span class="kisi-daily-stat-lbl">Previous Day</span>' +
                            '<span id="kisi-daily-val-yesterday" class="kisi-daily-stat-val">0</span>' +
                            '<span id="kisi-daily-sub-yesterday" class="kisi-daily-stat-sub" style="color:#6b5344;">Full 24h cycle</span>' +
                        '</div>' +
                        '<div class="kisi-daily-stat-box">' +
                            '<span class="kisi-daily-stat-lbl">Daily Average (30d)</span>' +
                            '<span id="kisi-daily-val-avg" class="kisi-daily-stat-val" style="color:#d9531e;">0</span>' +
                            '<span class="kisi-daily-stat-sub" style="color:#6b5344;">Events / day</span>' +
                        '</div>' +
                        '<div class="kisi-daily-stat-box">' +
                            '<span class="kisi-daily-stat-lbl">Peak Daily Traffic (30d)</span>' +
                            '<span id="kisi-daily-val-peak" class="kisi-daily-stat-val" style="color:#4C9F38;">0</span>' +
                            '<span id="kisi-daily-sub-peak" class="kisi-daily-stat-sub">Highest single-day events</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="kisi-daily-chart-container">' +
                        '<div id="kisi-daily-bars-stage" class="kisi-daily-bars-Stage"></div>' +
                        '<div class="kisi-daily-chart-legend">' +
                            '<div class="kisi-legend-items">' +
                                '<span class="kisi-legend-item"><span class="kisi-legend-swatch" style="background:#e22d48;"></span>Current / Latest Day</span>' +
                                '<span class="kisi-legend-item"><span class="kisi-legend-swatch" style="background:#ff7a18;"></span>Total Compiled Daily Events</span>' +
                                '<span class="kisi-legend-item"><span class="kisi-legend-swatch" style="background:#ffb884; border:1px solid #d9531e;"></span>Article Views &amp; PDF Reads</span>' +
                            '</div>' +
                            '<span>Hover/tap any bar for daily breakdown</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="kisi-geo-live-grid">' +
                    '<div class="kisi-geo-panel">' +
                        '<div class="kisi-subpanel-header">' +
                            '<h3 class="kisi-subpanel-title">Top Reader Countries</h3>' +
                            '<span class="kisi-subpanel-badge">OJS Geo Metrics</span>' +
                        '</div>' +
                        '<ul id="kisi-country-ranking-list" class="kisi-country-list"></ul>' +
                    '</div>' +
                    '<div class="kisi-stats-panel">' +
                        '<div class="kisi-stats-panel-title">Usage Breakdown by Issue</div>' +
                        '<div id="kisi-stat-issues-table-box"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="kisi-stats-panel" style="margin-top:24px;">' +
                    '<div class="kisi-stats-panel-title">Top 10 Most Read &amp; Downloaded Articles</div>' +
                    '<div id="kisi-stat-top-articles-box"></div>' +
                '</div>' +
            '</div>';

        var dailySeries30 = Array.isArray(fullStats.dailySeries30) ? fullStats.dailySeries30 : [];
        if (dailySeries30.length > 0) {
            var lastDay = dailySeries30[dailySeries30.length - 1];
            var prevDay = dailySeries30.length >= 2 ? dailySeries30[dailySeries30.length - 2] : lastDay;
            var sum30 = 0;
            var peakObj = { visitors: 0, fullLabel: "-" };
            dailySeries30.forEach(function (d) {
                var v = parseInt(d.visitors, 10) || 0;
                sum30 += v;
                if (v >= peakObj.visitors) peakObj = { visitors: v, fullLabel: d.fullLabel || d.date || "-" };
            });

            document.getElementById("kisi-daily-val-today").textContent = formatInt(lastDay.visitors);
            document.getElementById("kisi-daily-sub-today").textContent = formatInt(lastDay.views) + " views \u00B7 " + formatInt(lastDay.downloads) + " downloads";
            document.getElementById("kisi-daily-val-yesterday").textContent = formatInt(prevDay.visitors);
            document.getElementById("kisi-daily-sub-yesterday").textContent = formatInt(prevDay.views) + " views \u00B7 " + formatInt(prevDay.downloads) + " downloads";
            document.getElementById("kisi-daily-val-avg").textContent = formatInt(Math.round(sum30 / dailySeries30.length));
            document.getElementById("kisi-daily-val-peak").textContent = formatInt(peakObj.visitors);
            document.getElementById("kisi-daily-sub-peak").textContent = peakObj.visitors > 0 ? ("Recorded on " + peakObj.fullLabel) : "No daily records yet";

            function renderDailyBars(daysCount) {
                var sliceData = dailySeries30.slice(Math.max(0, dailySeries30.length - daysCount));
                var maxVis = 1;
                sliceData.forEach(function (d) { if ((d.visitors || 0) > maxVis) maxVis = d.visitors; });
                var html = "";
                sliceData.forEach(function (d, idx) {
                    var vis = parseInt(d.visitors, 10) || 0;
                    var rds = parseInt(d.reads, 10) || 0;
                    var heightPct = vis > 0 ? Math.max(12, Math.round((vis / maxVis) * 100)) : 4;
                    var readsHeightPct = vis > 0 ? Math.min(100, Math.round((rds / vis) * 100)) : 0;
                    var showLbl = (daysCount <= 14) || (idx % 2 === 0) || Boolean(d.isToday);
                    html +=
                        '<div class="kisi-daily-bar-col' + (d.isToday ? ' is-today' : '') + '">' +
                            '<div class="kisi-daily-tooltip">' +
                                '<strong>' + escapeHtml(d.fullLabel || d.date) + '</strong><br>' +
                                'Total Events: <strong>' + formatInt(vis) + '</strong><br>' +
                                'Abstract Views: <strong>' + formatInt(d.views) + '</strong><br>' +
                                'Galley Downloads: <strong>' + formatInt(d.downloads) + '</strong>' +
                            '</div>' +
                            '<span class="kisi-daily-bar-val-top">' + formatInt(vis) + '</span>' +
                            '<div class="kisi-daily-bar-stack" style="height:' + heightPct + '%;">' +
                                '<div class="kisi-daily-bar-reads" style="height:' + readsHeightPct + '%;"></div>' +
                            '</div>' +
                            '<span class="kisi-daily-bar-date">' + (showLbl ? escapeHtml(d.shortLabel || "") : "&middot;") + '</span>' +
                        '</div>';
                });
                var stageEl = document.getElementById("kisi-daily-bars-stage");
                if (stageEl) stageEl.innerHTML = html;
            }

            renderDailyBars(14);
            document.querySelectorAll(".kisi-daily-tab-btn").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    document.querySelectorAll(".kisi-daily-tab-btn").forEach(function (b) { b.classList.remove("active"); });
                    btn.classList.add("active");
                    renderDailyBars(parseInt(btn.getAttribute("data-days") || "14", 10));
                });
            });
        } else {
            var stageEl = document.getElementById("kisi-daily-bars-stage");
            if (stageEl) stageEl.innerHTML = '<div style="padding:20px; color:#666; font-size:0.86rem;">No compiled daily statistics available yet.</div>';
        }

        var countryListEl = document.getElementById("kisi-country-ranking-list");
        var topCountries = Array.isArray(fullStats.topCountries) ? fullStats.topCountries : [];
        if (countryListEl) {
            if (topCountries.length === 0) {
                countryListEl.innerHTML = '<li style="font-size:0.84rem; color:#666; line-height:1.5;">Geographic reader data is not yet available (enable OJS GeoIP database in Usage Statistics settings and run the scheduled stats task).</li>';
            } else {
                var maxCountryCount = Math.max(1, parseInt(topCountries[0].count, 10) || 1);
                var cHtml = "";
                topCountries.forEach(function (c) {
                    var cnt = parseInt(c.count, 10) || 0;
                    var barW = Math.max(8, Math.round((cnt / maxCountryCount) * 100));
                    var isoUpper = escapeHtml(String(c.code || "").toUpperCase());
                    cHtml +=
                        '<li class="kisi-country-item">' +
                            '<div class="kisi-country-top">' +
                                '<span class="kisi-country-name-wrap">' +
                                    '<span class="kisi-country-iso-pill">' + isoUpper + '</span>' +
                                    '<span>' + escapeHtml(c.name || isoUpper) + '</span>' +
                                '</span>' +
                                '<span class="kisi-country-metrics">' +
                                    '<span class="kisi-country-count">' + formatInt(cnt) + '</span>' +
                                    '<span class="kisi-country-pct">(' + escapeHtml(String(c.percent || 0)) + '%)</span>' +
                                '</span>' +
                            '</div>' +
                            '<div class="kisi-country-bar-track">' +
                                '<div class="kisi-country-bar-fill" style="width:' + Math.min(100, barW) + '%;"></div>' +
                            '</div>' +
                        '</li>';
                });
                countryListEl.innerHTML = cHtml;
            }
        }

        var issuesBox = document.getElementById("kisi-stat-issues-table-box");
        var issuesBreakdown = Array.isArray(fullStats.issuesBreakdown) ? fullStats.issuesBreakdown : [];
        if (issuesBox) {
            if (issuesBreakdown.length === 0) {
                issuesBox.innerHTML = '<p style="font-size:0.85rem; color:#666;">No published issues found.</p>';
            } else {
                var issHtml = '<table class="kisi-stats-table"><thead><tr><th>Issue / Volume</th><th style="text-align:center;">Articles</th><th style="text-align:right;">Views</th><th style="text-align:right;">Downloads</th></tr></thead><tbody>';
                issuesBreakdown.forEach(function (iss) {
                    issHtml += '<tr><td><a href="' + escapeHtml(sanitizeUrl(iss.url)) + '">' + escapeHtml(iss.title) + '</a></td><td style="text-align:center; font-weight:600;">' + formatInt(iss.articleCount) + '</td><td class="kisi-stats-num-cell" style="color:#d9531e;">' + formatInt(iss.views) + '</td><td class="kisi-stats-num-cell" style="color:#4C9F38;">' + formatInt(iss.downloads) + '</td></tr>';
                });
                issHtml += '</tbody></table>';
                issuesBox.innerHTML = issHtml;
            }
        }

        var topBox = document.getElementById("kisi-stat-top-articles-box");
        var topArticles = Array.isArray(fullStats.topArticles10) ? fullStats.topArticles10 : [];
        if (topBox) {
            if (topArticles.length === 0) {
                topBox.innerHTML = '<p style="font-size:0.85rem; color:#666;">No article usage metrics recorded yet.</p>';
            } else {
                var topHtml = '<table class="kisi-stats-table"><thead><tr><th style="width:32px;">#</th><th>Article Title</th><th style="text-align:right;">Views</th><th style="text-align:right;">Downloads</th></tr></thead><tbody>';
                topArticles.forEach(function (art, idx) {
                    topHtml += '<tr><td><span class="kisi-stats-rank-badge">' + (idx + 1) + '</span></td><td><a href="' + escapeHtml(sanitizeUrl(art.url)) + '">' + escapeHtml(art.title) + '</a><div style="font-size:0.74rem; color:#777; margin-top:2px;">' + escapeHtml(art.issueTitle) + '</div></td><td class="kisi-stats-num-cell" style="color:#d9531e;">' + formatInt(art.views) + '</td><td class="kisi-stats-num-cell" style="color:#4C9F38;">' + formatInt(art.downloads) + '</td></tr>';
                });
                topHtml += '</tbody></table>';
                topBox.innerHTML = topHtml;
            }
        }
    }

    function buildXmlInlineFragment(xmlNode) {
        var frag = document.createDocumentFragment();
        if (!xmlNode) return frag;

        Array.from(xmlNode.childNodes).forEach(function (child) {
            if (child.nodeType === Node.TEXT_NODE) {
                frag.appendChild(document.createTextNode(child.nodeValue || ""));
                return;
            }
            if (child.nodeType !== Node.ELEMENT_NODE) return;

            var tag = child.tagName.toLowerCase();
            var innerFrag = buildXmlInlineFragment(child);

            if (tag === "italic" || tag === "i") {
                var em = document.createElement("em");
                em.appendChild(innerFrag);
                frag.appendChild(em);
            } else if (tag === "bold" || tag === "b" || tag === "title") {
                var strong = document.createElement("strong");
                strong.appendChild(innerFrag);
                frag.appendChild(strong);
                if (tag === "title") frag.appendChild(document.createTextNode(" "));
            } else if (tag === "sup") {
                var sup = document.createElement("sup");
                sup.appendChild(innerFrag);
                frag.appendChild(sup);
            } else if (tag === "sub") {
                var sub = document.createElement("sub");
                sub.appendChild(innerFrag);
                frag.appendChild(sub);
            } else if (tag === "ext-link" || tag === "uri" || tag === "a") {
                var rawHref = child.getAttribute("xlink:href")
                    || child.getAttributeNS("http://www.w3.org/1999/xlink", "href")
                    || child.getAttribute("href")
                    || child.textContent;
                var safeHref = sanitizeUrl(rawHref, "");
                if (safeHref && safeHref !== "#") {
                    var a = document.createElement("a");
                    a.href = safeHref;
                    a.target = "_blank";
                    a.rel = "noopener noreferrer";
                    a.appendChild(innerFrag);
                    frag.appendChild(a);
                } else {
                    frag.appendChild(innerFrag);
                }
            } else if (tag === "xref") {
                var refType = (child.getAttribute("ref-type") || "").toLowerCase();
                var rid = (child.getAttribute("rid") || "").replace(/[^A-Za-z0-9_.:-]/g, "");
                if ((refType === "fig" || refType === "table") && rid) {
                    var prefix = refType === "fig" ? "#fig-" : "#tab-";
                    var xrefLink = document.createElement("a");
                    xrefLink.href = prefix + rid;
                    xrefLink.className = "kisi-xref";
                    xrefLink.appendChild(innerFrag);
                    frag.appendChild(xrefLink);
                } else {
                    frag.appendChild(innerFrag);
                }
            } else if (tag === "p") {
                if (!child.textContent.trim()) return;
                var p = document.createElement("p");
                p.appendChild(innerFrag);
                frag.appendChild(p);
            } else {
                frag.appendChild(innerFrag);
            }
        });

        return frag;
    }

    function buildSafeXmlTable(xmlTableEl) {
        var scrollDiv = document.createElement("div");
        scrollDiv.className = "kisi-xml-table-scroll";
        var table = document.createElement("table");
        table.className = "kisi-xml-table";

        function convertSection(sourceSec, targetSecName, cellTagFallback) {
            if (!sourceSec) return;
            var secEl = document.createElement(targetSecName);
            Array.from(sourceSec.querySelectorAll(":scope > tr")).forEach(function (trNode) {
                var tr = document.createElement("tr");
                Array.from(trNode.children).forEach(function (cellNode) {
                    var cTag = cellNode.tagName.toLowerCase();
                    var safeTag = (cTag === "th" || cTag === "td") ? cTag : cellTagFallback;
                    var cell = document.createElement(safeTag);
                    var cs = parseInt(cellNode.getAttribute("colspan") || "", 10);
                    var rs = parseInt(cellNode.getAttribute("rowspan") || "", 10);
                    if (cs > 1) cell.colSpan = cs;
                    if (rs > 1) cell.rowSpan = rs;
                    cell.appendChild(buildXmlInlineFragment(cellNode));
                    tr.appendChild(cell);
                });
                secEl.appendChild(tr);
            });
            table.appendChild(secEl);
        }

        var thead = xmlTableEl.querySelector(":scope > thead");
        var tbody = xmlTableEl.querySelector(":scope > tbody");
        if (thead) convertSection(thead, "thead", "th");
        if (tbody) convertSection(tbody, "tbody", "td");
        if (!thead && !tbody) {
            convertSection(xmlTableEl, "tbody", "td");
        }

        scrollDiv.appendChild(table);
        return scrollDiv;
    }

    function buildXmlBlockNodes(parentXmlNode, secPrefix, outlineItems, extraGalleyFileMap) {
        var frag = document.createDocumentFragment();
        var secCounter = 0;

        var nonBodyTags = {
            front: true,
            back: true,
            "article-meta": true,
            "journal-meta": true,
            "ref-list": true,
            abstract: true,
            "trans-abstract": true,
            "kwd-group": true,
            "fn-group": true,
            "author-notes": true
        };

        Array.from(parentXmlNode.children).forEach(function (el) {
            var tag = el.tagName.toLowerCase();
            if (nonBodyTags[tag]) return;
            if (tag === "sec") {
                var secType = (el.getAttribute("sec-type") || "").trim().toLowerCase();
                var titleNode = el.querySelector(":scope > title");
                var titleText = titleNode ? titleNode.textContent.trim() : "";
                var cleanHeading = titleText.replace(/^\s*[\dIVX]+[\s.)\-:]*/i, "").trim();
                if (/^(abstract|trans-abstract|keywords?|kwd|ref|references?|bibliography|author-notes|publisher-note)$/i.test(secType)
                    || /^(abstract|abstrak|ringkasan|keywords?|kata\s+kunci|references?|referensi|daftar\s+pustaka|bibliografi|bibliography|kepustakaan)$/i.test(cleanHeading)
                    || el.querySelector(":scope > ref-list")) {
                    return;
                }
                secCounter++;
                var secId = (secPrefix ? secPrefix + "-" : "xml-sec-") + secCounter;
                var isTopLevel = !secPrefix;

                var section = document.createElement("section");
                section.className = "kisi-xml-sec";
                section.id = secId;
                if (titleText) {
                    var heading = document.createElement(isTopLevel ? "h2" : "h3");
                    heading.className = isTopLevel ? "article-section-title" : "article-subsection-title";
                    heading.textContent = titleText;
                    section.appendChild(heading);
                    if (isTopLevel) outlineItems.push({ id: secId, title: titleText });
                }
                section.appendChild(buildXmlBlockNodes(el, secId, outlineItems, extraGalleyFileMap));
                frag.appendChild(section);
            } else if (tag === "p") {
                var pFrag = buildXmlInlineFragment(el);
                if (el.textContent.trim() !== "") {
                    var p = document.createElement("p");
                    p.appendChild(pFrag);
                    frag.appendChild(p);
                }
            } else if (tag === "list") {
                var listEl = document.createElement(el.getAttribute("list-type") === "order" ? "ol" : "ul");
                el.querySelectorAll(":scope > list-item").forEach(function (liNode) {
                    var li = document.createElement("li");
                    li.appendChild(buildXmlInlineFragment(liNode));
                    listEl.appendChild(li);
                });
                frag.appendChild(listEl);
            } else if (tag === "fig") {
                var figId = (el.getAttribute("id") || ("F" + Math.floor(Math.random() * 1000))).replace(/[^A-Za-z0-9_.:-]/g, "");
                var labelEl = el.querySelector("label");
                var captionEl = el.querySelector("caption");
                var graphicEl = el.querySelector("graphic, img");
                var rawHref = graphicEl
                    ? (graphicEl.getAttribute("xlink:href") || graphicEl.getAttributeNS("http://www.w3.org/1999/xlink", "href") || graphicEl.getAttribute("href") || graphicEl.getAttribute("src") || "")
                    : "";
                var isDirectUrl = /^https?:\/\//i.test(rawHref) || rawHref.charAt(0) === "/";
                var cleanFileName = rawHref.split("/").pop().trim();
                var resolvedSrc = isDirectUrl
                    ? sanitizeUrl(rawHref, "")
                    : sanitizeUrl(extraGalleyFileMap[cleanFileName.toLowerCase()], "");

                var figure = document.createElement("figure");
                figure.className = "kisi-xml-figure";
                figure.id = "fig-" + figId;

                var rawLabel = labelEl ? labelEl.textContent.trim() : "";
                if (rawLabel && !/[.:]$/.test(rawLabel)) rawLabel += ".";

                if (resolvedSrc && resolvedSrc !== "#") {
                    var figImg = document.createElement("img");
                    figImg.src = resolvedSrc;
                    figImg.alt = rawLabel || "Figure";
                    figImg.loading = "lazy";
                    figImg.decoding = "async";
                    figure.appendChild(figImg);
                }

                if (rawLabel || captionEl) {
                    var figCap = document.createElement("figcaption");
                    figCap.className = "kisi-xml-figcaption";
                    if (rawLabel) {
                        var lblSpan = document.createElement("span");
                        lblSpan.className = "kisi-xml-figlabel";
                        lblSpan.textContent = rawLabel;
                        figCap.appendChild(lblSpan);
                    }
                    if (captionEl) {
                        figCap.appendChild(buildXmlInlineFragment(captionEl));
                    }
                    figure.appendChild(figCap);
                }
                frag.appendChild(figure);
            } else if (tag === "table-wrap") {
                var tabId = (el.getAttribute("id") || ("T" + Math.floor(Math.random() * 1000))).replace(/[^A-Za-z0-9_.:-]/g, "");
                var tLabelEl = el.querySelector("label");
                var tCapEl = el.querySelector("caption");
                var tableEl = el.querySelector("table");
                var tFootEl = el.querySelector("table-wrap-foot");

                var wrapDiv = document.createElement("div");
                wrapDiv.className = "kisi-xml-table-wrap";
                wrapDiv.id = "tab-" + tabId;

                var rawTabLabel = tLabelEl ? tLabelEl.textContent.trim() : "";
                if (rawTabLabel && !/[.:]$/.test(rawTabLabel)) rawTabLabel += ".";

                if (rawTabLabel || tCapEl) {
                    var capDiv = document.createElement("div");
                    capDiv.className = "kisi-xml-table-caption";
                    if (rawTabLabel) {
                        var tLblSpan = document.createElement("span");
                        tLblSpan.className = "kisi-xml-figlabel";
                        tLblSpan.textContent = rawTabLabel;
                        capDiv.appendChild(tLblSpan);
                    }
                    if (tCapEl) capDiv.appendChild(buildXmlInlineFragment(tCapEl));
                    wrapDiv.appendChild(capDiv);
                }

                if (tableEl) wrapDiv.appendChild(buildSafeXmlTable(tableEl));

                if (tFootEl) {
                    var footDiv = document.createElement("div");
                    footDiv.className = "kisi-xml-table-foot";
                    footDiv.appendChild(buildXmlInlineFragment(tFootEl));
                    wrapDiv.appendChild(footDiv);
                }
                frag.appendChild(wrapDiv);
            }
        });

        return frag;
    }

    function initArticleDetailsPage(themeData) {
        var articleDetails = document.querySelector(".obj_article_details");
        if (!articleDetails) return;

        function parseMetricInt(val) {
            if (typeof val === "number" && isFinite(val)) return Math.max(0, Math.round(val));
            var str = String(val || "").trim();
            var match = str.match(/(\d{1,3}(?:[.,]\d{3})+|\d+)/);
            if (!match) return 0;
            var digits = match[1].replace(/[^\d]/g, "");
            return digits ? (parseInt(digits, 10) || 0) : 0;
        }

        function extractArticleSlug(rawUrl) {
            if (!rawUrl) return "";
            try {
                var u = new URL(rawUrl, window.location.origin);
                var m = u.pathname.match(/\/article\/view\/([^\/?#]+)/i);
                return m && m[1] ? m[1].toLowerCase() : "";
            } catch (e) {
                var m2 = String(rawUrl).match(/\/article\/view\/([^\/?#]+)/i);
                return m2 && m2[1] ? m2[1].toLowerCase() : "";
            }
        }

        function extractArticleMetricsFromContainer(containerEl, isSummaryCard) {
            var res = { views: 0, downloads: 0 };
            if (!containerEl) return res;

            function isSafeMetricNode(el) {
                if (!el) return false;
                if (el.closest && el.closest(".abstract, .description, .title, h1, h2, h3, h4")) return false;
                if (el.querySelector && el.querySelector("a[href]")) return false;
                if (el.tagName === "A" && !/stat|metric|pill|count/i.test(typeof el.className === "string" ? el.className : "")) return false;
                return true;
            }

            containerEl.querySelectorAll(".viewcounter-sr").forEach(function (el) {
                var srTxt = (el.textContent || "").trim();
                var mV = srTxt.match(/^views?\s*:\s*([\d.,]+)/i);
                if (mV) res.views = Math.max(res.views, parseMetricInt(mV[1]));
                var mD = srTxt.match(/^downloads?\s*:\s*([\d.,]+)/i);
                if (mD) res.downloads = Math.max(res.downloads, parseMetricInt(mD[1]));
            });

            containerEl.querySelectorAll(".viewcounter-visible[title]").forEach(function (el) {
                var titleAttr = (el.getAttribute("title") || "").toLowerCase();
                var num = parseMetricInt(el.textContent);
                if (!num) return;
                if (titleAttr.indexOf("download") !== -1) {
                    res.downloads = Math.max(res.downloads, num);
                } else if (titleAttr.indexOf("view") !== -1) {
                    res.views = Math.max(res.views, num);
                }
            });

            var metricNodes = containerEl.querySelectorAll(".kisi-article-metric, .kisi-metric-pill, .kisi-monthly-stat-pill, [data-kisi-metric]");
            metricNodes.forEach(function (el) {
                if (!isSafeMetricNode(el)) return;
                var cls = ((typeof el.className === "string" ? el.className : "") + " " + (el.getAttribute("title") || "") + " " + (el.getAttribute("data-kisi-metric") || "")).toLowerCase();
                var txt = (el.textContent || "").trim();
                var num = parseMetricInt(txt);
                if (!num) return;
                if (/download|pdf|galley|request/.test(cls) || /download/i.test(txt)) {
                    res.downloads = Math.max(res.downloads, num);
                } else if (/view|abstract|read|investig/.test(cls) || /view/i.test(txt)) {
                    res.views = Math.max(res.views, num);
                }
            });

            containerEl.querySelectorAll(".views, .abstract-views, [class*='view-counter'], [class*='view_counter']").forEach(function (el) {
                if (!isSafeMetricNode(el)) return;
                res.views = Math.max(res.views, parseMetricInt(el.textContent));
            });

            containerEl.querySelectorAll(".downloads, .pdf-downloads, .galley-downloads, [class*='download-counter'], [class*='download_counter']").forEach(function (el) {
                if (!isSafeMetricNode(el)) return;
                res.downloads = Math.max(res.downloads, parseMetricInt(el.textContent));
            });

            if (isSummaryCard) {
                var cardClone = containerEl.cloneNode(true);
                cardClone.querySelectorAll(".title, .authors, .published, .pages, .description, .abstract, h1, h2, h3, h4, .viewcounter-sr").forEach(function (n) { n.remove(); });
                cardClone.querySelectorAll("span[title*='view' i], span[aria-label*='view' i], div[title*='view' i]").forEach(function (el) {
                    if (/^\s*[\d,.\s]+(?:views?)?\s*$/i.test(el.textContent || "")) {
                        res.views = Math.max(res.views, parseMetricInt(el.textContent));
                    }
                });
                cardClone.querySelectorAll("span[title*='download' i], span[aria-label*='download' i], div[title*='download' i]").forEach(function (el) {
                    if (/^\s*[\d,.\s]+(?:downloads?)?\s*$/i.test(el.textContent || "")) {
                        res.downloads = Math.max(res.downloads, parseMetricInt(el.textContent));
                    }
                });

                var text = (cardClone.textContent || "").replace(/\s+/g, " ");
                var mViewsAfter = text.match(/(\d[\d,.]*)\s*(?:abstract\s+)?views?\b/i);
                var mViewsBefore = text.match(/\b(?:abstract\s+)?views?\s*[:\-]\s*(\d[\d,.]*)/i);
                if (mViewsAfter) res.views = Math.max(res.views, parseMetricInt(mViewsAfter[1]));
                if (mViewsBefore) res.views = Math.max(res.views, parseMetricInt(mViewsBefore[1]));
                var mDlAfter = text.match(/(\d[\d,.]*)\s*(?:pdf\s+|full-text\s+)?downloads?\b/i);
                var mDlBefore = text.match(/\b(?:pdf\s+|full-text\s+)?downloads?\s*[:\-]\s*(\d[\d,.]*)/i);
                if (mDlAfter) res.downloads = Math.max(res.downloads, parseMetricInt(mDlAfter[1]));
                if (mDlBefore) res.downloads = Math.max(res.downloads, parseMetricInt(mDlBefore[1]));
            }

            return res;
        }

        try {
            articleDetails.classList.add("frontiers-layout");
            var urls = themeData.urls || {};
            var articleData = themeData.articleData || {};

            var domInitMetrics = extractArticleMetricsFromContainer(articleDetails, false);
            var issueLinks = [];
            document.querySelectorAll('.cmp_breadcrumbs a[href*="/issue/view/"], .item.issue a[href*="/issue/view/"]').forEach(function (a) {
                var href = a.getAttribute("href");
                if (href && issueLinks.indexOf(href) === -1) issueLinks.push(href);
            });
            if (urls.home && issueLinks.indexOf(urls.home) === -1) {
                issueLinks.push(urls.home);
            }

            var currentSlugs = [];
            [
                window.location.href,
                (document.querySelector('meta[name="citation_abstract_html_url"]') || {}).content,
                (document.querySelector('meta[property="og:url"]') || {}).content
            ].forEach(function (u) {
                var s = extractArticleSlug(u);
                if (s && currentSlugs.indexOf(s) === -1) currentSlugs.push(s);
            });

            var leftRail = document.createElement("aside");
            leftRail.className = "frontiers-left-rail";
            var centerMain = document.createElement("div");
            centerMain.className = "frontiers-center-main";
            var rightRail = document.createElement("aside");
            rightRail.className = "frontiers-right-rail";

            var rawTitleEl = articleDetails.querySelector(".page_title, h1") || document.querySelector("h1.page_title, .page_title");
            var cleanArticleTitle = "";
            var titleMeta = document.querySelector('meta[name="citation_title"], meta[name="DC.Title"], meta[name="dc.title"], meta[property="og:title"]');

            if (titleMeta && titleMeta.getAttribute("content")) {
                cleanArticleTitle = titleMeta.getAttribute("content").replace(/\s+/g, " ").trim();
            }
            if (!cleanArticleTitle && rawTitleEl) {
                var titleClone = rawTitleEl.cloneNode(true);
                titleClone.querySelectorAll("svg, .fa, .views, .downloads, [class*='view-counter'], [class*='view_counter'], [class*='download-counter'], [class*='download_counter'], [class*='viewcount'], [class*='downloadcount']").forEach(function (n) { n.remove(); });
                cleanArticleTitle = (titleClone.textContent || "").replace(/\s+/g, " ").trim();
            }

            var titleEl = null;
            if (cleanArticleTitle) {
                titleEl = document.createElement("h1");
                titleEl.className = "page_title kisi-clean-title";
                titleEl.textContent = cleanArticleTitle;
                if (rawTitleEl) {
                    var adj = rawTitleEl.nextElementSibling;
                    if (adj) {
                        var adjId = ((typeof adj.className === "string" ? adj.className : "") + " " + (adj.id || "")).toLowerCase();
                        var adjTxt = (adj.textContent || "").replace(/\s+/g, "").trim();
                        if ((/view.?counter|download.?counter|view.?count|download.?count/.test(adjId) || (adj.querySelector("svg") && /^[\d,.]+$/.test(adjTxt)))
                            && !adj.classList.contains("subtitle") && !adj.classList.contains("authors")) {
                            adj.remove();
                        }
                    }
                    rawTitleEl.remove();
                }
            }

            var subtitleEl = articleDetails.querySelector(".subtitle");
            var authorsUl = articleDetails.querySelector(".authors");
            var abstractItem = articleDetails.querySelector(".item.abstract");
            var keywordsItem = articleDetails.querySelector(".item.keywords");
            var subjectsItem = articleDetails.querySelector(".item.subjects, .item.subjects_and_disciplines, .item.keywords");
            var referencesItem = articleDetails.querySelector(".item.references");
            var doiItem = articleDetails.querySelector(".item.doi");
            var publishedItem = articleDetails.querySelector(".item.published");
            var citationItem = articleDetails.querySelector(".item.citation");
            var licenseItem = articleDetails.querySelector(".item.copyright, .item.license");
            var coverImg = articleDetails.querySelector(".cover_image");
            var issueItem = articleDetails.querySelector(".item.issue");
            var fullTextBlock = document.querySelector(".jatsParser__center-article-block, .article-fulltext");

            if (titleEl) centerMain.appendChild(titleEl);
            if (subtitleEl) centerMain.appendChild(subtitleEl);

            if (authorsUl) {
                var newAuthorsList = document.createElement("ul");
                newAuthorsList.className = "frontiers-authors-list";
                var affilKeys = [];
                var affilElements = [];
                var orcidIconSvg = '<svg class="frontiers-orcid-icon" viewBox="0 0 256 256" aria-hidden="true"><path fill="#A6CE39" d="M256 128c0 70.7-57.3 128-128 128S0 198.7 0 128 57.3 0 128 0s128 57.3 128 128z"/><path fill="#FFFFFF" d="M86.3 186.2H70.9V79.1h15.4v107.1zM78.6 47.2c-5.7 0-10.3 4.6-10.3 10.3s4.6 10.3 10.3 10.3 10.3-4.6 10.3-10.3-4.6-10.3-10.3-10.3zm108.9 139h-41.6V79.1h41.6c39.6 0 57 28.3 57 53.6 0 27.5-21.5 53.5-57 53.5zm-26.2-93.3v79.5h24.6c34.9 0 42.9-26.5 42.9-39.7 0-21.5-13.7-39.8-43.7-39.8h-23.8z"/></svg>';

                authorsUl.querySelectorAll("li").forEach(function (li) {
                    var nameEl = li.querySelector(".name");
                    var affilEl = li.querySelector(".affiliation");
                    if (!nameEl) return;

                    var nameText = nameEl.textContent.trim();
                    var parts = nameText.split(/\s+/);
                    var initial = parts.length > 1 ? parts[parts.length - 1].charAt(0).toUpperCase() : nameText.charAt(0).toUpperCase();

                    var orcidUrl = "";
                    var orcidAnchor = li.querySelector('.orcid a, a[href*="orcid.org"]');
                    if (orcidAnchor) {
                        orcidUrl = (orcidAnchor.getAttribute("href") || orcidAnchor.textContent || "").trim();
                    } else {
                        var orcidSpan = li.querySelector(".orcid");
                        if (orcidSpan) {
                            var oMatch = orcidSpan.textContent.match(/https?:\/\/orcid\.org\/[\d-]+X?/i);
                            if (oMatch) orcidUrl = oMatch[0];
                        }
                    }
                    if (orcidUrl && !/^https?:\/\//i.test(orcidUrl)) {
                        var idMatch = orcidUrl.match(/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i);
                        if (idMatch) orcidUrl = "https://orcid.org/" + idMatch[1];
                    }
                    var safeOrcidUrl = sanitizeUrl(orcidUrl, "");

                    var rolesArr = [];
                    li.querySelectorAll(".role, [class*='role'], [class*='credit']").forEach(function (rEl) {
                        var rText = rEl.textContent.replace(/roles?:?/i, "").trim();
                        if (rText && rolesArr.indexOf(rText) === -1 && rText.toLowerCase().indexOf("orcid.org") === -1) rolesArr.push(rText);
                    });
                    if (rolesArr.length === 0) {
                        Array.from(li.childNodes).filter(function (node) { return node.nodeType === Node.TEXT_NODE && node.textContent.trim().length > 0; }).forEach(function (tn) {
                            var tStr = tn.textContent.trim();
                            if (tStr !== nameText && tStr.length > 2 && tStr.toLowerCase().indexOf("orcid.org") === -1) rolesArr.push(tStr);
                        });
                    }

                    var affilIdx = "";
                    if (affilEl) {
                        var cloneAff = affilEl.cloneNode(true);
                        cloneAff.querySelectorAll("metadata, title, desc, style").forEach(function (m) { m.remove(); });
                        var affLinks = Array.from(cloneAff.querySelectorAll("a[href]")).map(function (a) { return a.getAttribute("href") || ""; }).filter(Boolean);
                        var cleanTextKey = cloneAff.textContent.replace(/\s+/g, " ").trim();
                        if (cleanTextKey !== "" || affLinks.length > 0) {
                            var keyWithLinks = cleanTextKey + (affLinks.length ? " | " + affLinks.join(",") : "");
                            var foundIdx = affilKeys.indexOf(keyWithLinks);
                            if (foundIdx === -1 && cleanTextKey !== "") {
                                for (var ak = 0; ak < affilKeys.length; ak++) {
                                    var existingBase = affilKeys[ak].split(" | ")[0];
                                    if (existingBase === cleanTextKey) {
                                        foundIdx = ak;
                                        if (affLinks.length > 0 && affilKeys[ak].indexOf(" | ") === -1) {
                                            affilKeys[ak] = keyWithLinks;
                                            affilElements[ak] = cloneAff;
                                        }
                                        break;
                                    }
                                }
                            }
                            if (foundIdx === -1) {
                                affilKeys.push(keyWithLinks);
                                affilElements.push(cloneAff);
                                affilIdx = String(affilKeys.length);
                            } else {
                                affilIdx = String(foundIdx + 1);
                            }
                        }
                    }

                    var chip = document.createElement("li");
                    chip.className = "frontiers-author-chip";

                    var avatarSpan = document.createElement("span");
                    avatarSpan.className = "frontiers-author-avatar";
                    avatarSpan.textContent = initial;

                    var infoDiv = document.createElement("div");
                    infoDiv.className = "frontiers-author-info";

                    var nameSpan = document.createElement("span");
                    nameSpan.className = "frontiers-author-name";
                    nameSpan.appendChild(document.createTextNode(nameText));
                    if (affilIdx) {
                        var sup = document.createElement("sup");
                        sup.textContent = affilIdx;
                        nameSpan.appendChild(sup);
                    }
                    infoDiv.appendChild(nameSpan);

                    if (rolesArr.length > 0) {
                        var roleSpan = document.createElement("span");
                        roleSpan.className = "frontiers-author-role";
                        roleSpan.textContent = rolesArr.join(", ");
                        infoDiv.appendChild(roleSpan);
                    }

                    if (safeOrcidUrl && safeOrcidUrl !== "#") {
                        var oLink = document.createElement("a");
                        oLink.href = safeOrcidUrl;
                        oLink.target = "_blank";
                        oLink.rel = "noopener noreferrer";
                        oLink.className = "frontiers-author-orcid";
                        oLink.innerHTML = orcidIconSvg;
                        var oTxt = document.createElement("span");
                        oTxt.textContent = safeOrcidUrl;
                        oLink.appendChild(oTxt);
                        infoDiv.appendChild(oLink);
                    }

                    chip.appendChild(avatarSpan);
                    chip.appendChild(infoDiv);
                    newAuthorsList.appendChild(chip);
                });

                centerMain.appendChild(newAuthorsList);

                if (affilElements.length > 0) {
                    var affilUl = document.createElement("ul");
                    affilUl.className = "frontiers-affiliations-list";
                    affilElements.forEach(function (affEl, idx) {
                        var aLi = document.createElement("li");
                        var numSpan = document.createElement("span");
                        numSpan.className = "affil-num";
                        numSpan.textContent = (idx + 1) + ".";
                        var txtSpan = document.createElement("span");
                        txtSpan.appendChild(cloneSafeRichTextFragment(affEl));
                        aLi.appendChild(numSpan);
                        aLi.appendChild(document.createTextNode(" "));
                        aLi.appendChild(txtSpan);
                        affilUl.appendChild(aLi);
                    });
                    centerMain.appendChild(affilUl);
                }
            }

            var pdfUrl = "#";
            var pdfBtnLabel = "Download PDF (Bahasa Indonesia)";
            var xmlGalleyUrl = null;
            var extraGalleyFileMap = Object.assign({}, articleData.xmlDependentImages || {});

            articleDetails.querySelectorAll(".obj_galley_link, .galleys_links a").forEach(function (gLink) {
                var rawText = gLink.textContent.replace(/\s+/g, " ").trim();
                var href = gLink.getAttribute("href") || "";
                if (/\b(xml|jats)\b/i.test(rawText) || /\.xml$/i.test(href)) {
                    xmlGalleyUrl = sanitizeUrl(href.replace("/article/view/", "/article/download/"), null);
                    var parentLi = gLink.closest("li");
                    if (parentLi) parentLi.style.display = "none";
                    else gLink.style.display = "none";
                } else if (/\.(png|jpe?g|gif|webp|svg)$/i.test(rawText) || /\.(png|jpe?g|gif|webp|svg)$/i.test(href)) {
                    extraGalleyFileMap[rawText.toLowerCase()] = sanitizeUrl(href.replace("/article/view/", "/article/download/"), "");
                    var imgLi = gLink.closest("li");
                    if (imgLi) imgLi.style.display = "none";
                } else if (pdfUrl === "#") {
                    pdfUrl = sanitizeUrl(href, "#");
                    if (rawText && rawText.toLowerCase().indexOf("bahasa indonesia") !== -1) {
                        pdfBtnLabel = "Download PDF (Bahasa Indonesia)";
                    } else if (rawText && rawText.toLowerCase() !== "pdf") {
                        pdfBtnLabel = /^download/i.test(rawText) ? rawText : ("Download " + rawText);
                    }
                }
            });

            if (keywordsItem) {
                var kwVal = keywordsItem.querySelector(".value");
                var keywordText = kwVal ? kwVal.textContent.replace(/\s+/g, " ").trim() : "";
                if (keywordText) {
                    var kwBlock = document.createElement("div");
                    kwBlock.className = "frontiers-meta-block frontiers-keywords-block";
                    var kwHeading = document.createElement("h4");
                    kwHeading.textContent = "Keywords";
                    var kwParagraph = document.createElement("p");
                    kwParagraph.textContent = keywordText;
                    kwBlock.appendChild(kwHeading);
                    kwBlock.appendChild(kwParagraph);
                    centerMain.appendChild(kwBlock);
                }
            }

            var infoBanner = document.createElement("div");
            infoBanner.className = "frontiers-info-banner";
            var bannerTextDiv = document.createElement("div");
            bannerTextDiv.className = "frontiers-info-banner-text";
            bannerTextDiv.appendChild(document.createTextNode("Official published version: "));
            var doiAnchor = doiItem ? doiItem.querySelector("a") : null;
            if (doiAnchor) {
                var safeDoiUrl = sanitizeUrl(doiAnchor.getAttribute("href"), "");
                if (safeDoiUrl && safeDoiUrl !== "#") {
                    var newDoiA = document.createElement("a");
                    newDoiA.href = safeDoiUrl;
                    newDoiA.target = "_blank";
                    newDoiA.rel = "noopener noreferrer";
                    newDoiA.textContent = doiAnchor.textContent.trim();
                    bannerTextDiv.appendChild(newDoiA);
                } else {
                    bannerTextDiv.appendChild(document.createTextNode("Full-text available"));
                }
            } else {
                bannerTextDiv.appendChild(document.createTextNode("Full-text available"));
            }
            infoBanner.appendChild(bannerTextDiv);

            if (pdfUrl !== "#") {
                var pdfBtn = document.createElement("a");
                pdfBtn.href = pdfUrl;
                pdfBtn.className = "frontiers-info-banner-btn";
                pdfBtn.textContent = pdfBtnLabel + " \u2193";
                infoBanner.appendChild(pdfBtn);
            }

            var outlineItems = [];
            if (abstractItem && abstractItem.textContent.replace(/abstract/i, "").trim().length > 10) {
                abstractItem.id = "section-abstract";
                var absLabel = abstractItem.querySelector(".label");
                if (absLabel) absLabel.textContent = "Abstract";
                centerMain.appendChild(abstractItem);
                outlineItems.push({ id: "section-abstract", title: "Abstract" });
            }

            var fullTextWrapper = document.createElement("div");
            fullTextWrapper.id = "section-fulltext";
            fullTextWrapper.className = "kisi-xml-fulltext";
            centerMain.appendChild(fullTextWrapper);

            var bottomMeta = document.createElement("div");
            bottomMeta.className = "frontiers-bottom-meta";
            var copyrightBlock = document.createElement("div");
            copyrightBlock.className = "frontiers-meta-block";
            var cHead = document.createElement("h4");
            cHead.textContent = "Copyright & License";
            var cBody = document.createElement("div");

            if (licenseItem) {
                var licVal = licenseItem.querySelector(".value") || licenseItem;
                var cloneLic = licVal.cloneNode(true);
                var lHeading = cloneLic.querySelector(".label, h2, h3, h4");
                if (lHeading) lHeading.remove();
                var paragraphs = Array.from(cloneLic.querySelectorAll("p"));
                if (paragraphs.length > 1) {
                    var seenTexts = [];
                    paragraphs.forEach(function (p) {
                        var norm = p.textContent.replace(/\s+/g, " ").trim().toLowerCase();
                        var isDup = seenTexts.some(function (s) { return s === norm || (norm.indexOf("creative commons") !== -1 && s.indexOf("creative commons") !== -1); });
                        if (isDup) p.remove();
                        else if (norm !== "") seenTexts.push(norm);
                    });
                }
                if (cloneLic.textContent.trim() !== "") {
                    cBody.appendChild(cloneSafeRichTextFragment(cloneLic));
                }
            }
            if (!cBody.textContent.trim()) {
                cBody.innerHTML = 'This is an open-access article distributed under the terms of the <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer">Creative Commons Attribution-ShareAlike 4.0 International License (CC BY-SA 4.0)</a>.';
            }
            copyrightBlock.appendChild(cHead);
            copyrightBlock.appendChild(cBody);
            bottomMeta.appendChild(copyrightBlock);

            var disclaimerBlock = document.createElement("div");
            disclaimerBlock.className = "frontiers-meta-block";
            disclaimerBlock.innerHTML = "<h4>Disclaimer</h4><p>All claims expressed in this article are solely those of the authors and do not necessarily represent those of their affiliated organizations, or those of the publisher, the editors and the reviewers.</p>";
            bottomMeta.appendChild(disclaimerBlock);

            if (referencesItem) {
                referencesItem.id = "section-references";
                centerMain.appendChild(referencesItem);
                outlineItems.push({ id: "section-references", title: "References" });
            }
            centerMain.appendChild(bottomMeta);

            var issueTitleText = issueItem && issueItem.querySelector(".value")
                ? issueItem.querySelector(".value").textContent.trim()
                : (themeData.journalName || "Kisi Berkelanjutan");
            var topicCard = document.createElement("div");
            topicCard.className = "frontiers-card";
            topicCard.innerHTML =
                '<div class="frontiers-card-title">Published in Issue</div>' +
                '<span class="frontiers-topic-link">' + escapeHtml(issueTitleText) + '</span>' +
                '<div class="frontiers-badges"><span class="frontiers-badge-pill">Open Access</span><span class="frontiers-badge-pill">Peer Reviewed</span></div>';
            leftRail.appendChild(topicCard);

            if (publishedItem) {
                var pubVal = publishedItem.querySelector(".value");
                if (pubVal) {
                    var publicationCard = document.createElement("div");
                    publicationCard.className = "frontiers-card";
                    publicationCard.innerHTML = '<div class="frontiers-card-title">Publication Details</div><div class="frontiers-meta-block"><h4>Published</h4><p></p></div>';
                    publicationCard.querySelector(".frontiers-meta-block p").textContent = pubVal.textContent.replace(/\s+/g, " ").trim();
                    leftRail.appendChild(publicationCard);
                }
            }

            var editorialPlaceHolder = document.createElement("div");
            leftRail.appendChild(editorialPlaceHolder);

            var subjectCandidateTags = [];
            function addSubjectCandidate(rawValue) {
                if (!rawValue) return;
                String(rawValue).split(/[;,\n|]/).forEach(function (part) {
                    var clean = part.replace(/\s+/g, " ").trim();
                    if (clean && clean.length <= 180 && subjectCandidateTags.indexOf(clean) === -1) subjectCandidateTags.push(clean);
                });
            }
            document.querySelectorAll('meta[name="DC.Subject"], meta[name="dc.subject"], meta[name="citation_keywords"]').forEach(function (m) {
                addSubjectCandidate(m.content);
            });
            if (subjectsItem) {
                var subjectVal = subjectsItem.querySelector(".value");
                if (subjectVal) addSubjectCandidate(subjectVal.textContent);
            }

            var hasEditorCandidateTag = subjectCandidateTags.some(function (t) {
                if (/(?:\bsdg|\bsdgs|\bgoal\s*\d|\btarget\s*\d)/i.test(t)) return false;
                var cleanTag = t.replace(/^(?:review\s*editors?|editors?|reviewers?)\s*:\s*/i, "").trim();
                return cleanTag.length >= 3;
            });

            var editorialTeamUrl = sanitizeUrl(urls.editorialTeam, "");
            if (editorialTeamUrl && editorialTeamUrl !== "#" && hasEditorCandidateTag) {
                var cacheKeyTeam = "kisi_card_editor_v9";
                var teamCacheTtlMs = 3600000;
                function getCorePersonName(str) {
                    var base = (str || "").replace(/^(?:review\s*editors?|editors?|reviewers?)\s*:\s*/i, "").trim();
                    return base.split(",")[0].trim().toLowerCase().replace(/^(prof|dr|drg|ir|hj|h|ns|ners)\.?\s+/gi, "").replace(/[^a-z\s]/g, "").replace(/\s+/g, " ").trim();
                }
                function isSamePerson(nameA, nameB) {
                    var coreA = getCorePersonName(nameA);
                    var coreB = getCorePersonName(nameB);
                    if (!coreA || !coreB) return false;
                    if (coreA === coreB || coreA.indexOf(coreB) !== -1 || coreB.indexOf(coreA) !== -1) {
                        var wordsA = coreA.split(" ").filter(Boolean);
                        var wordsB = coreB.split(" ").filter(Boolean);
                        return wordsA.some(function (w) { return w.length > 2 && wordsB.indexOf(w) !== -1; });
                    }
                    return false;
                }
                function readCachedDirectory() {
                    var storages = [window.localStorage, window.sessionStorage];
                    for (var sIdx = 0; sIdx < storages.length; sIdx++) {
                        try {
                            var store = storages[sIdx];
                            if (!store) continue;
                            var raw = store.getItem(cacheKeyTeam);
                            if (!raw) continue;
                            var entry = JSON.parse(raw);
                            if (entry && Array.isArray(entry.dir) && typeof entry.ts === "number" && (Date.now() - entry.ts) < teamCacheTtlMs) {
                                return entry.dir;
                            }
                        } catch (e) {}
                    }
                    return null;
                }
                function writeCachedDirectory(dir) {
                    var payload = JSON.stringify({ ts: Date.now(), dir: dir });
                    try { if (window.localStorage) window.localStorage.setItem(cacheKeyTeam, payload); } catch (e) {}
                    try { if (window.sessionStorage) window.sessionStorage.setItem(cacheKeyTeam, payload); } catch (e) {}
                }
                function loadDirectory() {
                    var cachedDir = readCachedDirectory();
                    if (cachedDir !== null) {
                        return Promise.resolve(cachedDir);
                    }
                    return fetch(editorialTeamUrl, { credentials: "same-origin" })
                        .then(function (r) { return r.text(); })
                        .then(function (html) {
                            var doc = new DOMParser().parseFromString(html, "text/html");
                            var dir = [];
                            doc.querySelectorAll("article.cardEditor, .cardEditor").forEach(function (card) {
                                var imgEl = card.querySelector("img.cardEditor__mask, img");
                                var nameEl = card.querySelector(".cardEditor__name, h3, h4, h2");
                                var affilEl = card.querySelector(".cardEditor__affiliation");
                                var roleEl = card.querySelector(".cardEditor__role");
                                var linkEl = card.querySelector("a");
                                var memberName = nameEl ? nameEl.textContent.replace(/\s+/g, " ").trim() : "";
                                if (memberName) {
                                    dir.push({
                                        name: memberName,
                                        affil: (affilEl ? affilEl.textContent.replace(/\s+/g, " ").trim() : "") || (roleEl ? roleEl.textContent.replace(/\s+/g, " ").trim() : "") || "Review Editor Board",
                                        photo: imgEl ? (imgEl.getAttribute("src") || "") : "",
                                        link: linkEl && linkEl.getAttribute("href") ? linkEl.getAttribute("href") : ""
                                    });
                                }
                            });
                            writeCachedDirectory(dir);
                            return dir;
                        })
                        .catch(function () { return []; });
                }

                loadDirectory().then(function (directory) {
                    var matchedEditors = [];
                    var addedNames = [];
                    subjectCandidateTags.forEach(function (tag) {
                        if (/(?:\bsdg|\bsdgs|\bgoal\s*\d|\btarget\s*\d)/i.test(tag)) return;
                        var cleanTag = tag.replace(/^(?:review\s*editors?|editors?|reviewers?)\s*:\s*/i, "").trim();
                        if (!cleanTag || cleanTag.length < 3) return;
                        for (var i = 0; i < directory.length; i++) {
                            if (isSamePerson(cleanTag, directory[i].name)) {
                                if (addedNames.indexOf(directory[i].name) === -1) {
                                    addedNames.push(directory[i].name);
                                    matchedEditors.push(directory[i]);
                                }
                                break;
                            }
                        }
                    });

                    if (matchedEditors.length === 0) {
                        editorialPlaceHolder.remove();
                        return;
                    }

                    var edCard = document.createElement("div");
                    edCard.className = "frontiers-card";
                    var edTitle = document.createElement("div");
                    edTitle.className = "frontiers-card-title";
                    edTitle.textContent = "Review Editor";
                    var edList = document.createElement("div");
                    edList.className = "frontiers-editor-list";

                    matchedEditors.forEach(function (edObj) {
                        var words = getCorePersonName(edObj.name).split(" ").filter(Boolean);
                        var initials = words.length > 1
                            ? (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase()
                            : edObj.name.charAt(0).toUpperCase();

                        var itemDiv = document.createElement("div");
                        itemDiv.className = "frontiers-editor-item";

                        var avatarDiv = document.createElement("div");
                        avatarDiv.className = "frontiers-editor-avatar";
                        var safePhoto = sanitizeUrl(edObj.photo, "");
                        if (safePhoto && safePhoto !== "#") {
                            var edImg = document.createElement("img");
                            edImg.src = safePhoto;
                            edImg.alt = edObj.name;
                            edImg.className = "frontiers-editor-photo";
                            edImg.width = 44;
                            edImg.height = 44;
                            edImg.loading = "lazy";
                            edImg.referrerPolicy = "no-referrer";
                            edImg.addEventListener("error", function () {
                                avatarDiv.textContent = initials;
                            });
                            avatarDiv.appendChild(edImg);
                        } else {
                            avatarDiv.textContent = initials;
                        }

                        var infoDiv = document.createElement("div");
                        infoDiv.className = "frontiers-editor-info";
                        var nameDiv = document.createElement("div");
                        nameDiv.className = "frontiers-editor-name";
                        var edLink = document.createElement("a");
                        edLink.href = sanitizeUrl(edObj.link, editorialTeamUrl);
                        edLink.target = "_blank";
                        edLink.rel = "noopener noreferrer";
                        edLink.textContent = edObj.name;
                        nameDiv.appendChild(edLink);

                        var affilDiv = document.createElement("div");
                        affilDiv.className = "frontiers-editor-affil";
                        affilDiv.textContent = edObj.affil || "Review Editor / Peer Reviewer";

                        infoDiv.appendChild(nameDiv);
                        infoDiv.appendChild(affilDiv);
                        itemDiv.appendChild(avatarDiv);
                        itemDiv.appendChild(infoDiv);
                        edList.appendChild(itemDiv);
                    });

                    edCard.appendChild(edTitle);
                    edCard.appendChild(edList);
                    editorialPlaceHolder.replaceWith(edCard);
                });
            } else {
                editorialPlaceHolder.remove();
            }

            var metricsCard = document.createElement("div");
            metricsCard.className = "frontiers-card";
            var artMetrics = articleData.metrics || {};
            var phpViews = parseMetricInt(artMetrics.views);
            var phpDownloads = parseMetricInt(artMetrics.downloads);
            var hasServerMetrics = (phpViews > 0 || phpDownloads > 0);
            var initialViews = hasServerMetrics ? phpViews : domInitMetrics.views;
            var initialDownloads = hasServerMetrics ? phpDownloads : domInitMetrics.downloads;
            metricsCard.innerHTML =
                '<div class="frontiers-card-title">Article Metrics</div>' +
                '<div class="frontiers-metrics-box"><div class="frontiers-metrics-content"><span id="kisi-metric-views-val" class="frontiers-metrics-number">' + formatInt(initialViews) + '</span><span class="frontiers-metrics-label">Total Article Views</span></div></div>' +
                '<div class="frontiers-metrics-box"><div class="frontiers-metrics-content"><span id="kisi-metric-downloads-val" class="frontiers-metrics-number">' + formatInt(initialDownloads) + '</span><span class="frontiers-metrics-label">Total PDF Downloads</span></div></div>';

            var outlinePlaceHolder = document.createElement("div");
            leftRail.appendChild(outlinePlaceHolder);

            function renderOutlineCard() {
                if (outlineItems.length === 0) {
                    outlinePlaceHolder.innerHTML = "";
                    return;
                }
                var card = document.createElement("div");
                card.className = "frontiers-card";
                var cTitle = document.createElement("div");
                cTitle.className = "frontiers-card-title";
                cTitle.textContent = "Outline";
                var ul = document.createElement("ul");
                ul.className = "frontiers-outline-list";
                outlineItems.forEach(function (item, idx) {
                    var li = document.createElement("li");
                    var a = document.createElement("a");
                    a.href = "#" + item.id;
                    if (idx === 0) a.className = "active";
                    a.textContent = item.title;
                    li.appendChild(a);
                    ul.appendChild(li);
                });
                card.appendChild(cTitle);
                card.appendChild(ul);
                outlinePlaceHolder.innerHTML = "";
                outlinePlaceHolder.appendChild(card);
            }

            if (citationItem) {
                var citeCard = document.createElement("div");
                citeCard.className = "frontiers-card";
                citeCard.appendChild(citationItem);
                leftRail.appendChild(citeCard);
            }

            if (fullTextBlock) {
                document.querySelectorAll("#kisi-sec-abstract, .kisi-jats-abstract-box, #kisi-sec-references").forEach(function (n) {
                    n.remove();
                });

                fullTextBlock.querySelectorAll(
                    ".kisi-jats-metabar, .kisi-jats-toc, #kisi-sec-abstract, .kisi-jats-abstract-box, .kisi-abs-keywords, " +
                    "#kisi-sec-references, .kisi-jats-ref-list, .kisi-jats-publishers-note, .kisi-jats-retypeset-box, " +
                    ".article-meta, .journal-meta, front, back, ref-list, [class*='view-counter'], [class*='view_counter'], .fa-eye"
                ).forEach(function (n) {
                    n.remove();
                });

                fullTextBlock.querySelectorAll("section, .kisi-jats-section").forEach(function (secEl) {
                    var secIdAttr = (secEl.getAttribute("id") || "").toLowerCase();
                    if (secIdAttr.indexOf("abstract") !== -1 || secIdAttr.indexOf("ref") !== -1 || secEl.querySelector(".kisi-jats-ref-list, ref-list")) {
                        secEl.remove();
                        return;
                    }
                    var hEl = secEl.querySelector(":scope > h1, :scope > h2, :scope > h3, :scope > .kisi-jats-h2, :scope > .kisi-jats-h3");
                    if (hEl) {
                        var hClean = hEl.textContent.replace(/^\s*[\dIVX]+[\s.)\-:]*/i, "").replace(/\s+/g, " ").trim();
                        if (/^(abstract|abstrak|ringkasan|keywords?|kata\s+kunci|references?|referensi|daftar\s+pustaka|bibliografi|bibliography|kepustakaan)$/i.test(hClean)) {
                            secEl.remove();
                        }
                    }
                });

                var bodyOnlyContainer = fullTextBlock.querySelector(".kisi-jats-article-body");
                if (bodyOnlyContainer) {
                    fullTextBlock.innerHTML = "";
                    fullTextBlock.appendChild(bodyOnlyContainer);
                }

                fullTextBlock.querySelectorAll("img[src]").forEach(function (imgEl) {
                    var rawSrc = (imgEl.getAttribute("src") || "").trim();
                    if (!rawSrc || /^https?:\/\//i.test(rawSrc) || rawSrc.charAt(0) === "/" || rawSrc.indexOf("data:") === 0) {
                        return;
                    }
                    var fname = rawSrc.split("/").pop().trim().toLowerCase();
                    var mappedUrl = sanitizeUrl(extraGalleyFileMap[fname], "");
                    if (mappedUrl && mappedUrl !== "#") {
                        imgEl.setAttribute("src", mappedUrl);
                    }
                });

                fullTextBlock.querySelectorAll("a.kisi-jats-cite[href^='#kisi-ref-'], a[href^='#ref-']").forEach(function (citeA) {
                    if (referencesItem) {
                        citeA.setAttribute("href", "#section-references");
                    } else {
                        citeA.replaceWith(document.createTextNode(citeA.textContent || ""));
                    }
                });

                var refIdxBefore = outlineItems.findIndex(function (it) { return it.id === "section-references"; });
                var refOutlineSaved = null;
                if (refIdxBefore !== -1) {
                    refOutlineSaved = outlineItems.splice(refIdxBefore, 1)[0];
                }

                fullTextWrapper.appendChild(fullTextBlock);
                fullTextBlock.querySelectorAll("h2, .kisi-jats-h2, h3.article-section-title").forEach(function (secH, sIdx) {
                    var sTitle = secH.textContent.replace(/\s+/g, " ").trim();
                    if (!sTitle) return;
                    var sId = secH.id || ("ft-sec-" + sIdx);
                    secH.id = sId;
                    outlineItems.push({ id: sId, title: sTitle });
                });

                if (refOutlineSaved) {
                    outlineItems.push(refOutlineSaved);
                }
                renderOutlineCard();
            } else if (xmlGalleyUrl) {
                fullTextWrapper.innerHTML = '<p style="color:#666; font-style:italic;">Loading full-text article...</p>';
                var fetchTargetUrl = xmlGalleyUrl;
                var xmlGalleyMeta = (articleData.galleyMeta || []).find(function (gm) {
                    return /\b(xml|jats)\b/i.test(gm.label || "") && gm.fileId && gm.fileId !== "0";
                });
                if (xmlGalleyMeta) {
                    fetchTargetUrl = xmlGalleyUrl.replace(/\/+$/, "") + "/" + xmlGalleyMeta.fileId;
                }

                var xmlCacheKey = "kisi_xml_v2_" + fetchTargetUrl;
                var xmlPromise = null;
                try {
                    var cachedXml = window.sessionStorage ? window.sessionStorage.getItem(xmlCacheKey) : null;
                    if (cachedXml) xmlPromise = Promise.resolve(cachedXml);
                } catch (e) {}

                if (!xmlPromise) {
                    xmlPromise = fetch(fetchTargetUrl, { credentials: "same-origin" })
                        .then(function (res) {
                            if (!res.ok && xmlGalleyMeta) return fetch(xmlGalleyUrl, { credentials: "same-origin" });
                            return res;
                        })
                        .then(function (res) { return res.text(); })
                        .then(function (xmlStr) {
                            if (xmlStr && xmlStr.length < 450000 && xmlStr.indexOf("<article") !== -1) {
                                try { if (window.sessionStorage) window.sessionStorage.setItem(xmlCacheKey, xmlStr); } catch (e) {}
                            }
                            return xmlStr;
                        });
                }

                xmlPromise
                    .then(function (xmlStr) {
                        var xmlDoc = new DOMParser().parseFromString(xmlStr, "text/xml");
                        var bodyNode = xmlDoc.querySelector("article > body, body");
                        if (bodyNode) {
                            var hasRefOutline = outlineItems.some(function (it) { return it.id === "section-references"; });
                            if (hasRefOutline) {
                                outlineItems = outlineItems.filter(function (it) { return it.id !== "section-references"; });
                            }

                            fullTextWrapper.innerHTML = "";
                            fullTextWrapper.appendChild(buildXmlBlockNodes(bodyNode, "", outlineItems, extraGalleyFileMap));

                            if (hasRefOutline) {
                                outlineItems.push({ id: "section-references", title: "References" });
                            }
                            renderOutlineCard();
                        } else {
                            fullTextWrapper.innerHTML = "";
                            renderOutlineCard();
                        }
                    })
                    .catch(function () {
                        fullTextWrapper.innerHTML = "";
                        renderOutlineCard();
                    });
            } else {
                renderOutlineCard();
            }

            var rightCard = document.createElement("div");
            rightCard.className = "frontiers-card";
            if (coverImg) rightCard.appendChild(coverImg);
            var promoInfo = document.createElement("div");
            promoInfo.innerHTML = '<h4 class="frontiers-promo-title">Rigorous peer review makes science stronger</h4><p class="frontiers-promo-text">Behind every published article in Kisi Berkelanjutan stands an editorial team safeguarding scientific quality and health impact.</p>';
            rightCard.appendChild(promoInfo);
            rightRail.appendChild(rightCard);
            rightRail.appendChild(infoBanner);
            rightRail.appendChild(metricsCard);

            var detectedSdgs = Array.isArray(articleData.sdgNumbers) && articleData.sdgNumbers.length > 0
                ? articleData.sdgNumbers
                : extractSdgsFromSubjects(subjectCandidateTags.join(" ; "));

            if (detectedSdgs.length > 0) {
                var sdgCard = document.createElement("div");
                sdgCard.className = "frontiers-card";
                var sdgTitle = document.createElement("div");
                sdgTitle.className = "frontiers-card-title";
                sdgTitle.textContent = "UN SDGs Contribution";
                var sdgGrid = document.createElement("div");
                sdgGrid.className = "frontiers-sdg-grid";

                var sdgBase = urls.sdgIconBase || "";
                detectedSdgs.forEach(function (num) {
                    var s = SDG_CATALOG[num];
                    if (!s) return;

                    var aItem = document.createElement("a");
                    aItem.href = "https://sdgs.un.org/goals/goal" + num;
                    aItem.target = "_blank";
                    aItem.rel = "noopener noreferrer";
                    aItem.className = "frontiers-sdg-item";

                    var logoDiv = document.createElement("div");
                    logoDiv.className = "frontiers-sdg-logo";
                    logoDiv.style.backgroundColor = s.color;

                    if (sdgBase) {
                        var sImg = document.createElement("img");
                        sImg.src = sdgBase + "sdg-" + pad2(num) + ".svg";
                        sImg.alt = "SDG " + num;
                        sImg.width = 52;
                        sImg.height = 52;
                        sImg.loading = "lazy";
                        sImg.addEventListener("error", function () {
                            logoDiv.textContent = "SDG " + num;
                        });
                        logoDiv.appendChild(sImg);
                    } else {
                        logoDiv.textContent = "SDG " + num;
                    }

                    var infoDiv = document.createElement("div");
                    infoDiv.className = "frontiers-sdg-info";
                    var codeSpan = document.createElement("span");
                    codeSpan.className = "frontiers-sdg-code";
                    codeSpan.style.color = s.color;
                    codeSpan.textContent = "SDG " + num;
                    var nameSpan = document.createElement("span");
                    nameSpan.className = "frontiers-sdg-name";
                    nameSpan.textContent = s.name;

                    infoDiv.appendChild(codeSpan);
                    infoDiv.appendChild(nameSpan);
                    aItem.appendChild(logoDiv);
                    aItem.appendChild(infoDiv);
                    sdgGrid.appendChild(aItem);
                });

                sdgCard.appendChild(sdgTitle);
                sdgCard.appendChild(sdgGrid);
                rightRail.appendChild(sdgCard);
            }

            articleDetails.innerHTML = "";
            articleDetails.appendChild(leftRail);
            articleDetails.appendChild(centerMain);
            articleDetails.appendChild(rightRail);

            if (initialViews === 0 && initialDownloads === 0) {
                (function syncMetricsFromIssueToc(targetUrls, slugs, baseViews, baseDownloads) {
                    if (!targetUrls.length || !slugs.length || typeof window.fetch !== "function") return;
                    var candidates = targetUrls.filter(function (u) {
                        try {
                            return new URL(u, window.location.origin).origin === window.location.origin;
                        } catch (e) {
                            return false;
                        }
                    });
                    if (!candidates.length) return;

                    function tryFetchAt(idx) {
                        if (idx >= candidates.length) return;
                        var candidateAbs = new URL(candidates[idx], window.location.origin).toString();
                        var issueHtmlPromise = null;
                        var cacheKeyToc = "kisi_issue_toc_html_v1_" + candidateAbs;
                        try {
                            var cachedEntry = window.sessionStorage ? JSON.parse(window.sessionStorage.getItem(cacheKeyToc) || "null") : null;
                            if (cachedEntry && cachedEntry.html && typeof cachedEntry.ts === "number" && (Date.now() - cachedEntry.ts) < 600000) {
                                issueHtmlPromise = Promise.resolve(cachedEntry.html);
                            }
                        } catch (e) {}

                        if (!issueHtmlPromise) {
                            issueHtmlPromise = fetch(candidateAbs, { credentials: "same-origin" })
                                .then(function (resp) {
                                    if (!resp.ok) throw new Error("HTTP " + resp.status);
                                    return resp.text();
                                })
                                .then(function (html) {
                                    if (html && html.length < 350000) {
                                        try {
                                            if (window.sessionStorage) {
                                                window.sessionStorage.setItem(cacheKeyToc, JSON.stringify({ ts: Date.now(), html: html }));
                                            }
                                        } catch (e) {}
                                    }
                                    return html;
                                });
                        }

                        issueHtmlPromise
                            .then(function (html) {
                                if (!html || typeof DOMParser === "undefined") {
                                    tryFetchAt(idx + 1);
                                    return;
                                }
                                var doc = new DOMParser().parseFromString(html, "text/html");
                                var syncedViews = baseViews;
                                var syncedDownloads = baseDownloads;
                                var matchedCard = false;

                                var dataNode = doc.getElementById("kisi-theme-data");
                                var subMap = null;
                                if (dataNode && dataNode.textContent) {
                                    try {
                                        var parsedData = JSON.parse(dataNode.textContent);
                                        if (parsedData && parsedData.submissionMetrics && typeof parsedData.submissionMetrics === "object") {
                                            subMap = parsedData.submissionMetrics;
                                        }
                                    } catch (e) {}
                                }

                                doc.querySelectorAll(".obj_article_summary, .kisi-monthly-card, .cmp_article_list > li").forEach(function (card) {
                                    var cardSlugs = [];
                                    var isMatch = false;
                                    card.querySelectorAll('a[href*="/article/view/"]').forEach(function (a) {
                                        var s = extractArticleSlug(a.getAttribute("href"));
                                        if (s) {
                                            cardSlugs.push(s);
                                            if (slugs.indexOf(s) !== -1) isMatch = true;
                                        }
                                    });
                                    if (!isMatch) return;
                                    matchedCard = true;

                                    var cardMetrics = extractArticleMetricsFromContainer(card, true);
                                    if (cardMetrics.views > syncedViews) syncedViews = cardMetrics.views;
                                    if (cardMetrics.downloads > syncedDownloads) syncedDownloads = cardMetrics.downloads;

                                    var subIdAttr = card.getAttribute("data-submission-id") || card.getAttribute("data-id") || "";
                                    if (subMap) {
                                        slugs.concat(cardSlugs, subIdAttr ? [String(subIdAttr)] : []).forEach(function (k) {
                                            if (k && subMap[k]) {
                                                syncedViews = Math.max(syncedViews, parseMetricInt(subMap[k].views));
                                                syncedDownloads = Math.max(syncedDownloads, parseMetricInt(subMap[k].downloads));
                                            }
                                        });
                                    }
                                });

                                var viewsEl = document.getElementById("kisi-metric-views-val");
                                var dlEl = document.getElementById("kisi-metric-downloads-val");
                                if (viewsEl && syncedViews > baseViews) viewsEl.textContent = formatInt(syncedViews);
                                if (dlEl && syncedDownloads > baseDownloads) dlEl.textContent = formatInt(syncedDownloads);

                                if (!matchedCard && syncedViews === 0 && syncedDownloads === 0) {
                                    tryFetchAt(idx + 1);
                                }
                            })
                            .catch(function () {
                                tryFetchAt(idx + 1);
                            });
                    }

                    tryFetchAt(0);
                })(issueLinks, currentSlugs, initialViews, initialDownloads);
            }
        } catch (err) {
        }
    }

    function initCatalogCategoryPage(themeData) {
        var catalogPage = document.querySelector("div.page.page_catalog_category");
        if (!catalogPage) return;

        var articlesList = Array.from(catalogPage.querySelectorAll("ul.cmp_article_list.articles > li"));
        var totalArticlesCount = articlesList.length;
        if (totalArticlesCount === 0) return;

        var oldDashboard = catalogPage.querySelector(".sdg-analytics-dashboard");
        if (oldDashboard) oldDashboard.remove();

        var analyticsPanel = document.createElement("div");
        analyticsPanel.className = "sdg-analytics-dashboard";
        analyticsPanel.innerHTML =
            '<div class="kisi-sdg-header-banner">' +
                '<div class="kisi-stats-header-info">' +
                    '<h2>Research Impact &amp; SDGs Contribution Analytics</h2>' +
                    '<p>Real-time mapping of published research supporting the United Nations Sustainable Development Goals (UN SDGs).</p>' +
                '</div>' +
                '<div class="kisi-stats-period-badge">' +
                    '<span class="kisi-stats-period-label">Global Impact Framework</span>' +
                    '<span class="kisi-stats-period-value">UN 2030 Agenda Alignment</span>' +
                '</div>' +
            '</div>' +
            '<div class="sdg-metrics-grid">' +
                '<div class="sdg-metric-card"><div class="sdg-metric-value" style="color: #d9531e;">' + formatInt(totalArticlesCount) + '</div><div class="sdg-metric-label">Total SDG-Mapped Articles</div></div>' +
                '<div class="sdg-metric-card"><div id="sdg-active-count" class="sdg-metric-value" style="color: #4C9F38;">0 Goals</div><div class="sdg-metric-label">Active SDGs Pillars</div></div>' +
                '<div class="sdg-metric-card"><div class="sdg-metric-value" style="color: #c9203a;">100%</div><div class="sdg-metric-label">Open Access Commitment</div></div>' +
            '</div>' +
            '<div class="sdg-progress-container">' +
                '<div class="sdg-progress-title">SDGs Pillar Distribution Breakdown</div>' +
                '<div id="sdg-progress-bars-box" style="font-size: 0.85rem; color: #666666;"></div>' +
            '</div>';

        var targetInsertPos = catalogPage.querySelector(".about_section") || catalogPage.querySelector("h1");
        if (targetInsertPos && targetInsertPos.nextSibling) {
            targetInsertPos.parentNode.insertBefore(analyticsPanel, targetInsertPos.nextSibling);
        } else {
            catalogPage.insertBefore(analyticsPanel, catalogPage.firstChild);
        }

        var categoryMap = themeData.categoryArticleSdgs || {};
        var sdgCounts = {};
        Object.keys(SDG_CATALOG).forEach(function (k) { sdgCounts[k] = 0; });

        articlesList.forEach(function (li) {
            var titleEl = li.querySelector(".title");
            var linkEl = findArticleTitleLink(li);
            if (!titleEl || !linkEl) return;

            var href = linkEl.getAttribute("href") || "";
            var idMatch = href.match(/\/article\/view\/([^\/?#]+)/i);
            var artKey = idMatch ? idMatch[1] : "";
            var foundSdgs = Array.isArray(categoryMap[artKey])
                ? categoryMap[artKey]
                : (Array.isArray(categoryMap[String(artKey).toLowerCase()]) ? categoryMap[String(artKey).toLowerCase()] : []);

            var oldBadge = li.querySelector(".category-sdg-badge-wrapper");
            if (oldBadge) oldBadge.remove();

            if (foundSdgs.length > 0) {
                var badgeWrapper = document.createElement("div");
                badgeWrapper.className = "category-sdg-badge-wrapper";
                foundSdgs.forEach(function (num) {
                    var s = SDG_CATALOG[num];
                    if (!s) return;
                    sdgCounts[num] = (sdgCounts[num] || 0) + 1;
                    var pill = document.createElement("span");
                    pill.className = "category-sdg-pill";
                    pill.style.backgroundColor = s.color;
                    pill.title = "SDG " + num + ": " + s.name;
                    pill.textContent = "SDG " + num;
                    badgeWrapper.appendChild(pill);
                });
                titleEl.parentNode.insertBefore(badgeWrapper, titleEl);
            }
        });

        var sortedSdgNums = Object.keys(sdgCounts)
            .filter(function (num) { return sdgCounts[num] > 0; })
            .sort(function (a, b) { return sdgCounts[b] - sdgCounts[a]; });

        var activeCountEl = document.getElementById("sdg-active-count");
        if (activeCountEl) {
            activeCountEl.textContent = sortedSdgNums.length + (sortedSdgNums.length === 1 ? " Goal" : " Goals");
        }

        var progressBarsHtml = "";
        sortedSdgNums.forEach(function (num) {
            var count = sdgCounts[num];
            var percentage = Math.round((count / totalArticlesCount) * 100);
            var s = SDG_CATALOG[num];
            progressBarsHtml +=
                '<div class="sdg-bar-item">' +
                    '<div class="sdg-bar-info"><span>SDG ' + escapeHtml(num) + ': ' + escapeHtml(s.name) + '</span><span>' + formatInt(count) + ' Articles (' + percentage + '%)</span></div>' +
                    '<div class="sdg-bar-track"><div class="sdg-bar-fill" style="width: ' + percentage + '%; background-color: ' + escapeHtml(s.color) + ';"></div></div>' +
                '</div>';
        });

        var barsBox = document.getElementById("sdg-progress-bars-box");
        if (barsBox) {
            barsBox.innerHTML = progressBarsHtml || '<span style="color:#888;">No SDGs metadata detected in article subjects.</span>';
        }
    }

    function initSimilarArticles() {
        var simUl = null;
        var simHeading = null;
        var byId = document.getElementById("articlesBySimilarity") || document.getElementById("articlesBySameAuthor");
        if (byId) {
            simUl = byId.querySelector("ul");
            simHeading = byId.querySelector("h1, h2, h3, h4, .label");
        }
        if (!simUl) {
            document.querySelectorAll("h1, h2, h3, h4, h5, .label, strong").forEach(function (h) {
                if (h.textContent.replace(/\s+/g, " ").trim().toLowerCase().indexOf("similar articles") !== -1) {
                    simHeading = h;
                    var next = h.nextElementSibling;
                    while (next) {
                        if (next.tagName === "UL") { simUl = next; break; }
                        if (next.querySelector && next.querySelector("ul")) { simUl = next.querySelector("ul"); break; }
                        next = next.nextElementSibling;
                    }
                    if (!simUl && h.parentElement) simUl = h.parentElement.querySelector("ul");
                }
            });
        }
        if (!simUl) return;

        simUl.classList.add("elife-similar-list");
        var parentBox = simUl.parentElement;
        if (parentBox && !parentBox.classList.contains("obj_article_details")) parentBox.classList.add("elife-similar-wrapper");
        if (parentBox && !parentBox.querySelector(".section-header-elife")) {
            var simHeader = document.createElement("div");
            simHeader.className = "section-header-elife";
            simHeader.innerHTML = '<span class="section-eyebrow">Related Research</span><h2 class="elife-section-title"><span class="title-bold">Similar</span> <span class="title-italic">Articles</span></h2>';
            if (simHeading) {
                simHeading.style.setProperty("display", "none", "important");
                simHeading.parentNode.insertBefore(simHeader, simHeading);
            } else {
                simUl.parentNode.insertBefore(simHeader, simUl);
            }
        }

        var simItems = Array.from(simUl.querySelectorAll("li"));
        var maxVisible = 6;
        simItems.forEach(function (li, index) {
            var articleLink = li.querySelector('a[href*="/article/view/"]');
            var issueLink = li.querySelector('a[href*="/issue/view/"]');
            if (!articleLink) {
                var allLinks = li.querySelectorAll("a");
                if (allLinks.length > 0) articleLink = allLinks[0];
                if (allLinks.length > 1) issueLink = allLinks[1];
            }
            if (!articleLink) return;

            var cloneLi = li.cloneNode(true);
            cloneLi.querySelectorAll("a").forEach(function (a) { a.remove(); });
            var authorsText = cloneLi.textContent.replace(/^[\s,.:;-]+|[\s,.:;-]+$/g, "").replace(/\s*,\s*$/, "").trim();
            var issueText = "Published Issue";
            var issueHref = "#";
            if (issueLink) {
                issueHref = sanitizeUrl(issueLink.getAttribute("href"), "#");
                issueText = issueLink.textContent.replace(/Kisi Berkelanjutan\s*:\s*Sains Medis dan Kesehatan\s*:?\s*/i, "").trim();
            }
            var titleText = articleLink.textContent.trim();
            var titleHref = sanitizeUrl(articleLink.getAttribute("href"), "#");

            li.className = "similar-card" + (index >= maxVisible ? " is-hidden-card" : "");
            li.innerHTML =
                '<div>' +
                    (issueLink ? '<a href="' + escapeHtml(issueHref) + '" class="similar-card-issue">' + escapeHtml(issueText) + '</a>' : '') +
                    '<a href="' + escapeHtml(titleHref) + '" class="similar-card-title" title="' + escapeHtml(titleText) + '">' + escapeHtml(titleText) + '</a>' +
                '</div>' +
                '<div class="similar-card-authors">' + escapeHtml(authorsText) + '</div>';
        });

        if (simItems.length > maxVisible && parentBox && !parentBox.querySelector(".similar-toggle-wrapper")) {
            var toggleWrap = document.createElement("div");
            toggleWrap.className = "similar-toggle-wrapper";
            var toggleBtn = document.createElement("button");
            toggleBtn.type = "button";
            toggleBtn.className = "btn-similar-toggle";
            toggleBtn.textContent = "Show all " + simItems.length + " similar articles \u2193";
            toggleBtn.addEventListener("click", function () {
                simUl.querySelectorAll(".is-hidden-card").forEach(function (c) { c.classList.remove("is-hidden-card"); });
                toggleBtn.style.display = "none";
            });
            toggleWrap.appendChild(toggleBtn);
            simUl.parentNode.insertBefore(toggleWrap, simUl.nextSibling);
        }
    }

    function initIssueAndHomePages(themeData) {
        var urls = themeData.urls || {};

        var issuePage = document.querySelector("div.page.page_issue");
        if (issuePage) {
            issuePage.querySelectorAll(".sections .section").forEach(function (sec) {
                var headingEl = sec.querySelector("h2, h3, .label");
                var categoryName = headingEl ? headingEl.textContent.replace(/\s+/g, " ").trim() : "Article";
                sec.querySelectorAll("ul.cmp_article_list > li, .articles > li").forEach(function (li) {
                    li.querySelectorAll(".article-category-label, .kisi-category-badge").forEach(function (old) { old.remove(); });
                    var summary = li.querySelector(".obj_article_summary") || li;
                    var catBadge = document.createElement("div");
                    catBadge.className = "kisi-category-badge";
                    var catSpan = document.createElement("span");
                    catSpan.textContent = categoryName;
                    catBadge.appendChild(catSpan);
                    summary.insertBefore(catBadge, summary.firstChild);

                    var titleBox = summary.querySelector(".title");
                    var pagesEl = summary.querySelector(".meta .pages, .pages");
                    if (titleBox && pagesEl && !summary.querySelector(".kisi-pages-badge")) {
                        var pageText = pagesEl.textContent.replace(/\s+/g, " ").trim();
                        if (pageText) {
                            var pageBadge = document.createElement("div");
                            pageBadge.className = "kisi-pages-badge";
                            var pSpan = document.createElement("span");
                            pSpan.textContent = pageText;
                            pageBadge.appendChild(pSpan);
                            titleBox.parentNode.insertBefore(pageBadge, titleBox.nextSibling);
                        }
                        pagesEl.style.setProperty("display", "none", "important");
                    }

                    var titleLink = findArticleTitleLink(summary);
                    var articleUrl = sanitizeUrl(titleLink ? titleLink.getAttribute("href") : "#", "#");
                    var galleysUl = summary.querySelector(".galleys_links");
                    if (galleysUl) {
                        galleysUl.style.setProperty("display", "block", "important");
                        galleysUl.innerHTML = '<li><a href="' + escapeHtml(articleUrl) + '" class="kisi-fulltext-btn">FULL-TEXT</a></li>';
                    } else if (articleUrl !== "#") {
                        var newFtUl = document.createElement("ul");
                        newFtUl.className = "galleys_links";
                        newFtUl.innerHTML = '<li><a href="' + escapeHtml(articleUrl) + '" class="kisi-fulltext-btn">FULL-TEXT</a></li>';
                        summary.appendChild(newFtUl);
                    }
                });
            });
        }

        var homePage = document.querySelector(".page_index_journal, .pkp_page_index");
        if (!homePage) return;

        homePage.querySelectorAll('a.obj_galley_link, .galleys_links a, a[href*="/article/view/"]').forEach(function (link) {
            var text = link.textContent.replace(/\s+/g, " ").trim();
            if (/^pdf\b/i.test(text) || link.classList.contains("obj_galley_link") || link.classList.contains("pdf")) {
                var rawHref = link.getAttribute("href") || "";
                var fullTextUrl = rawHref.replace(/(\/article\/view\/[^\/]+)\/.*$/, "$1");
                var parentCard = link.closest("li, .obj_article_summary, .article-card, div");
                if (parentCard) {
                    var titleAnchor = findArticleTitleLink(parentCard);
                    if (titleAnchor && titleAnchor.getAttribute("href")) fullTextUrl = titleAnchor.getAttribute("href");
                }
                link.setAttribute("href", sanitizeUrl(fullTextUrl, "#"));
                link.classList.remove("pdf");
                link.textContent = "FULL-TEXT";
            }
        });

        var heroContentBox =
            homePage.querySelector(".elife-hero-content") ||
            homePage.querySelector(".elife-hero-section");

        var homeStats = themeData.homeStats || {};
        var statsPageUrl = sanitizeUrl(urls.statistics, "#");
        var archivePageUrl = sanitizeUrl(urls.archive, "#");
        var sdgCategoryUrl = sanitizeUrl(urls.sdgCategory, "#");

        if (themeData.heroShowStatsBar !== false && heroContentBox && !document.getElementById("kisi-home-frontiers-stats")) {
            var statsBar = document.createElement("div");
            statsBar.id = "kisi-home-frontiers-stats";
            statsBar.className = "kisi-frontiers-stats-bar";
            statsBar.innerHTML =
                '<a href="' + escapeHtml(archivePageUrl) + '" class="kisi-frontiers-stat-item">' +
                    '<div id="kisi-home-stat-researchers" class="kisi-frontiers-stat-value">' + formatInt(homeStats.researchersCount) + '</div>' +
                    '<div class="kisi-frontiers-stat-label">researchers</div>' +
                '</a>' +
                '<a href="' + escapeHtml(statsPageUrl) + '" class="kisi-frontiers-stat-item">' +
                    '<div id="kisi-home-stat-usage" class="kisi-frontiers-stat-value">' + formatInt(homeStats.combinedImpact) + '</div>' +
                    '<div class="kisi-frontiers-stat-label">article views and downloads</div>' +
                '</a>' +
                '<a href="' + escapeHtml(sdgCategoryUrl) + '" class="kisi-frontiers-stat-item">' +
                    '<div id="kisi-home-stat-sdgs" class="kisi-frontiers-stat-value">' + formatInt(homeStats.sdgArticlesCount) + '</div>' +
                    '<div class="kisi-frontiers-stat-label">SDG-contributing articles</div>' +
                '</a>';
            heroContentBox.appendChild(statsBar);
        }

        var monthlyTop5 = Array.isArray(homeStats.monthlyTop5) ? homeStats.monthlyTop5 : [];
        if (monthlyTop5.length > 0 && !document.getElementById("kisi-home-monthly-top5")) {
            var periodLabel = homeStats.periodLabel || new Date().toLocaleDateString("en-US", { month: "long", year: "numeric" });
            var monthlySection = document.createElement("section");
            monthlySection.id = "kisi-home-monthly-top5";
            monthlySection.className = "kisi-monthly-top-section";

            var cardsHtml = "";
            monthlyTop5.forEach(function (item, idx) {
                var rankNum = idx + 1;
                var isFirst = rankNum === 1;
                var cleanUrl = sanitizeUrl(item.url, "#");
                cardsHtml +=
                    '<article class="kisi-monthly-card' + (isFirst ? ' rank-1' : '') + '">' +
                        '<div class="kisi-monthly-card-main">' +
                            '<div class="kisi-monthly-rank-badge">0' + rankNum + '</div>' +
                            '<div class="kisi-monthly-info">' +
                                '<div class="kisi-monthly-issue-tag">' +
                                    (isFirst ? '<span class="kisi-monthly-top-crown">#1 Most Read</span>' : '') +
                                    '<span>' + escapeHtml(item.issueTitle || "Published Issue") + '</span>' +
                                '</div>' +
                                '<a href="' + escapeHtml(cleanUrl) + '" class="kisi-monthly-title" title="' + escapeHtml(item.title) + '">' +
                                    escapeHtml(item.title) +
                                '</a>' +
                            '</div>' +
                        '</div>' +
                        '<div class="kisi-monthly-metrics-footer">' +
                            '<div class="kisi-monthly-pills-group">' +
                                '<span class="kisi-monthly-stat-pill views" title="Abstract Views">' + formatInt(item.periodViews) + ' views</span>' +
                                '<span class="kisi-monthly-stat-pill pdf" title="Full-Text Downloads">' + formatInt(item.periodDownloads) + ' downloads</span>' +
                            '</div>' +
                            '<span class="kisi-monthly-alltime-note">All-time: <strong>' + formatInt(item.allTimeTotal) + '</strong> reads</span>' +
                        '</div>' +
                    '</article>';
            });

            monthlySection.innerHTML =
                '<div class="kisi-monthly-header-row">' +
                    '<div class="section-header-elife">' +
                        '<span class="section-eyebrow">Readership Impact</span>' +
                        '<h2 class="elife-section-title">' +
                            '<span class="title-bold">Top Read</span> <span class="title-italic">Articles</span>' +
                        '</h2>' +
                    '</div>' +
                    '<div class="kisi-monthly-header-meta">' +
                        '<span class="kisi-monthly-period-pill">' + escapeHtml(periodLabel) + '</span>' +
                        '<a href="' + escapeHtml(statsPageUrl) + '" class="kisi-monthly-all-link">Full Impact Statistics &rarr;</a>' +
                    '</div>' +
                '</div>' +
                '<div id="kisi-monthly-bento-box" class="kisi-monthly-bento-grid">' + cardsHtml + '</div>';

            var nativeToc = homePage.querySelector(".elife-native-toc");
            var announcementsSec = homePage.querySelector(".elife-latest-announcements");
            var contentWrap = homePage.querySelector(".elife-content-container") || homePage;
            if (nativeToc && nativeToc.parentNode) {
                nativeToc.parentNode.insertBefore(monthlySection, nativeToc.nextSibling);
            } else if (announcementsSec && announcementsSec.parentNode) {
                announcementsSec.parentNode.insertBefore(monthlySection, announcementsSec);
            } else {
                contentWrap.appendChild(monthlySection);
            }
        }
    }

    function enhancePasswordInputs(rootEl) {
        var eyeOpenSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        var eyeClosedSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

        rootEl.querySelectorAll("input[type='password']").forEach(function (pwdInput) {
            if (pwdInput.parentElement && pwdInput.parentElement.classList.contains("kisi-reg-pwd-wrap")) return;
            var wrapSpan = document.createElement("span");
            wrapSpan.className = "kisi-reg-pwd-wrap";
            pwdInput.parentNode.insertBefore(wrapSpan, pwdInput);
            wrapSpan.appendChild(pwdInput);

            var toggleBtn = document.createElement("button");
            toggleBtn.type = "button";
            toggleBtn.className = "kisi-pwd-toggle-btn";
            toggleBtn.setAttribute("aria-label", "Toggle password visibility");
            toggleBtn.innerHTML = eyeOpenSvg;
            toggleBtn.addEventListener("click", function () {
                var isPwd = pwdInput.getAttribute("type") === "password";
                pwdInput.setAttribute("type", isPwd ? "text" : "password");
                toggleBtn.innerHTML = isPwd ? eyeClosedSvg : eyeOpenSvg;
            });
            wrapSpan.appendChild(toggleBtn);
        });
    }

    function initAuthAndCleanupPages(themeData) {
        var urls = themeData.urls || {};

        var archiveOrIssuePage = document.querySelector(".page_issue, .page_issue_archive");
        if (archiveOrIssuePage) {
            archiveOrIssuePage.querySelectorAll(".cmp_breadcrumbs, .cmp_breadcrumbs *, h1, .obj_issue_toc, .heading").forEach(function (el) {
                el.style.setProperty("border", "none", "important");
                el.style.setProperty("border-top", "none", "important");
                el.style.setProperty("border-bottom", "none", "important");
                el.style.setProperty("box-shadow", "none", "important");
            });
            archiveOrIssuePage.querySelectorAll("hr").forEach(function (hr) {
                hr.style.setProperty("display", "none", "important");
            });
        }

        if (urls.signOut) {
            document.querySelectorAll('a[href*="/index/login/signOut"]').forEach(function (a) {
                a.setAttribute("href", sanitizeUrl(urls.signOut, a.getAttribute("href")));
            });
        }
        if (urls.login) {
            document.querySelectorAll('a[href*="/index/login"]').forEach(function (a) {
                var href = a.getAttribute("href") || "";
                if (/signOut|logout/i.test(href)) return;
                try {
                    if (!/\/login\/?$/i.test(new URL(href, window.location.origin).pathname)) return;
                } catch (e) {
                    return;
                }
                a.setAttribute("href", sanitizeUrl(urls.login, href));
            });
        }
        if (urls.register) {
            document.querySelectorAll('a[href*="/index/user/register"]').forEach(function (a) {
                a.setAttribute("href", sanitizeUrl(urls.register, a.getAttribute("href")));
            });
        }

        var loginPage = document.querySelector("div.page.page_login");
        if (loginPage && !loginPage.getAttribute("data-frontiers-login")) {
            loginPage.setAttribute("data-frontiers-login", "1");
            var loginH1 = loginPage.querySelector("h1");
            var registerLinkEl = loginPage.querySelector(".buttons a.register, a[href*='/user/register']");
            if (loginH1 && !loginPage.querySelector(".kisi-login-register-prompt")) {
                var regHref = sanitizeUrl(registerLinkEl ? registerLinkEl.getAttribute("href") : urls.register, "#");
                var promptDiv = document.createElement("div");
                promptDiv.className = "kisi-login-register-prompt";
                promptDiv.innerHTML = 'Don\'t have an account? <a href="' + escapeHtml(regHref) + '">Register</a>';
                loginH1.parentNode.insertBefore(promptDiv, loginH1.nextSibling);
            }
            var userInp = loginPage.querySelector("input[name='username'], #username");
            if (userInp) userInp.setAttribute("placeholder", "Username or Email*");
            var passInp = loginPage.querySelector("input[name='password'], #password");
            var passContainer = loginPage.querySelector(".password");
            if (passInp) passInp.setAttribute("placeholder", "Password*");
            enhancePasswordInputs(loginPage);

            var rememberDiv = loginPage.querySelector(".remember");
            var forgotLink = passContainer ? passContainer.querySelector("a") : null;
            if (rememberDiv && forgotLink) {
                forgotLink.className = "kisi-forgot-pwd-link";
                forgotLink.textContent = "Forgot Password";
                var optionsRow = document.createElement("div");
                optionsRow.className = "kisi-login-options-row";
                rememberDiv.parentNode.insertBefore(optionsRow, rememberDiv);
                optionsRow.appendChild(rememberDiv);
                optionsRow.appendChild(forgotLink);
            }
        }

        var registerPage = document.querySelector("div.page.page_register");
        if (registerPage && !registerPage.getAttribute("data-frontiers-register")) {
            registerPage.setAttribute("data-frontiers-register", "1");
            var regH1 = registerPage.querySelector("h1");
            var loginLinkEl = registerPage.querySelector(".buttons a.login, a[href*='/login']");
            if (regH1 && !registerPage.querySelector(".kisi-register-login-prompt")) {
                var logHref = sanitizeUrl(loginLinkEl ? loginLinkEl.getAttribute("href") : urls.login, "#");
                var regPrompt = document.createElement("div");
                regPrompt.className = "kisi-register-login-prompt";
                regPrompt.innerHTML = 'Already have an account? <a href="' + escapeHtml(logHref) + '">Login</a>';
                regH1.parentNode.insertBefore(regPrompt, regH1.nextSibling);
            }

            var orcidBtn = registerPage.querySelector("#connect-orcid-button, [id*='orcid']");
            var aboutOrcid = registerPage.querySelector("#about-orcid, a[href*='orcid.org']");
            if (orcidBtn) {
                var existingBox = orcidBtn.closest(".orcidProfile");
                if (existingBox) {
                    existingBox.classList.add("kisi-orcid-connect-box");
                } else if (!orcidBtn.closest(".kisi-orcid-connect-box")) {
                    var orcidWrap = document.createElement("div");
                    orcidWrap.className = "kisi-orcid-connect-box";
                    orcidBtn.parentNode.insertBefore(orcidWrap, orcidBtn);
                    orcidWrap.appendChild(orcidBtn);
                    if (aboutOrcid) orcidWrap.appendChild(aboutOrcid);
                }
            }
            enhancePasswordInputs(registerPage);
        }
    }

    function initKisiTheme() {
        if (window.__kisiEnhancementsInitialized) return;
        window.__kisiEnhancementsInitialized = true;

        var themeData = readThemeData();
        optimizeImagesForPageSpeed();
        document.querySelectorAll(".obj_article_summary").forEach(deduplicateCardMetricNodes);

        initStatisticsPage(themeData);
        initArticleDetailsPage(themeData);
        initCatalogCategoryPage(themeData);
        initSimilarArticles();
        initIssueAndHomePages(themeData);
        initAuthAndCleanupPages(themeData);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initKisiTheme);
    } else {
        initKisiTheme();
    }
})();
