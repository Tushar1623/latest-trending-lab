(function () {
  "use strict";
  function money(n) { return "$" + (n >= 100 ? Math.round(n).toLocaleString("en-US") : n.toFixed(2)); }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); }
  function el(tag, cls, html) { var e = document.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; }
  var uid = 0;

  function field(f, onChange) {
    var id = "lt-f" + (++uid), w = el("div", "lt-f");
    w.innerHTML = '<label for="' + id + '">' + esc(f.label) + ' <output></output></label>' +
      '<input type="range" id="' + id + '" min="' + f.min + '" max="' + f.max + '" step="' + f.step + '" value="' + f.value + '">' +
      '<input type="number" min="' + f.min + '" max="' + f.max + '" step="' + f.step + '" value="' + f.value + '" aria-label="' + esc(f.label) + '">';
    var r = w.querySelector("input[type=range]"), n = w.querySelector("input[type=number]"), o = w.querySelector("output");
    function show() { o.textContent = Number(r.value).toLocaleString("en-US") + (f.unit ? " " + f.unit : ""); }
    r.addEventListener("input", function () { n.value = r.value; show(); onChange(); });
    n.addEventListener("input", function () { var v = +n.value; if (v >= +f.min && v <= +f.max) { r.value = v; show(); onChange(); } });
    n.addEventListener("change", function () { var v = Math.min(+f.max, Math.max(+f.min, +n.value || +f.min)); n.value = v; r.value = v; show(); onChange(); });
    show();
    w.get = function () { return +r.value; };
    return w;
  }

  function copyText(text, btn) {
    function done() {
      btn.textContent = "Copied!";
      setTimeout(function () { btn.textContent = "Copy Result"; }, 1800);
    }
    function fallback() {
      var t = document.createElement("textarea");
      t.value = text; t.setAttribute("readonly", ""); t.style.position = "fixed"; t.style.opacity = "0";
      document.body.appendChild(t); t.select();
      try { if (document.execCommand("copy")) done(); } catch (e) {}
      document.body.removeChild(t);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(text).then(done, fallback);
    else fallback();
  }

  var sticky = null, stickyOff = false, stickyArmed = false;
  function stickyBar() {
    if (sticky) return sticky;
    sticky = document.createElement("div");
    sticky.className = "lt-sticky";
    sticky.setAttribute("role", "region");
    sticky.setAttribute("aria-label", "Current calculator result");
    sticky.innerHTML = '<div class="lt-sticky-txt"></div><a class="lt-btn lt-sticky-cta" rel="sponsored noopener" style="display:none"></a><button type="button" class="lt-sticky-x" aria-label="Dismiss result bar">&times;</button>';
    sticky.querySelector(".lt-sticky-x").addEventListener("click", function () {
      stickyOff = true;
      sticky.classList.remove("lt-on");
      document.body.classList.remove("lt-has-sticky");
    });
    document.body.appendChild(sticky);
    return sticky;
  }
  function updateSticky(win, unit, cta) {
    if (stickyOff) return;
    var bar = stickyBar(), a = bar.querySelector(".lt-sticky-cta");
    bar.querySelector(".lt-sticky-txt").innerHTML = '<span class="lt-sticky-l">' + esc(win.label) + '</span><span class="lt-sticky-v">' +
      (win.name ? '<span class="lt-sticky-n">' + esc(win.name) + '</span> ' : '') + '<b>' + money(win.cost) + '/' + unit + '</b></span>';
    if (cta && cta.url) { a.href = cta.url; a.textContent = cta.text || "See pricing"; a.style.display = ""; }
    else { a.removeAttribute("href"); a.style.display = "none"; }
    showSticky();
  }
  /* The bar stays hidden on first load. It appears after the visitor changes an input or scrolls past 320px. */
  function showSticky() {
    if (stickyOff || !stickyArmed || !sticky) return;
    sticky.classList.add("lt-on");
    document.body.classList.add("lt-has-sticky");
  }
  function armSticky() {
    if (stickyArmed) return;
    stickyArmed = true;
    showSticky();
  }
  function checkScroll() {
    var y = window.pageYOffset || (document.documentElement && document.documentElement.scrollTop) || 0;
    if (y > 320) armSticky();
  }
  function insideCalc(el) {
    return !!(el && el.closest && el.closest('[data-lt="calc"],[data-lt="breakeven"]'));
  }
  document.addEventListener("input", function (e) { if (insideCalc(e.target)) armSticky(); }, true);
  document.addEventListener("change", function (e) { if (insideCalc(e.target)) armSticky(); }, true);
  document.addEventListener("click", function (e) { if (insideCalc(e.target) && e.target.closest(".lt-seg")) armSticky(); }, true);
  window.addEventListener("scroll", checkScroll, { passive: true });

  function results(out, rows, title, sub, cta, note, opts) {
    opts = opts || {};
    var unit = opts.unit || "mo";
    var mx = Math.max.apply(null, rows.map(function (r) { return r.cost; })) || 1;
    var best = rows.reduce(function (a, b) { return b.cost < a.cost ? b : a; });
    var h = '<div class="lt-bars">';
    rows.forEach(function (r) {
      h += '<div class="lt-bar' + (r === best && !opts.noWin ? " win" : "") + '"><span>' + esc(r.name) + '</span><span class="lt-t"><i style="width:' +
        Math.max(2, r.cost / mx * 100).toFixed(1) + '%"></i></span><span>' + money(r.cost) + '/' + unit + '</span></div>';
    });
    h += '</div><div class="lt-verdict" aria-live="polite"><div><b>' + esc(title) + '</b><small>' + esc(sub) + '</small></div><div class="lt-actions">';
    if (cta && cta.url) h += '<a class="lt-btn" href="' + esc(cta.url) + '" rel="sponsored noopener">' + esc(cta.text || "See pricing") + '</a>';
    h += '<button type="button" class="lt-btn lt-btn-sec">Copy Result</button></div></div>';
    if (note) h += '<p class="lt-note">' + esc(note) + '</p>';
    out.innerHTML = h;
    updateSticky(opts.winner || { label: "Best option", name: best.name, cost: best.cost }, unit, cta);
    var sec = out.querySelector(".lt-btn-sec");
    if (sec) sec.addEventListener("click", function () {
      var L = [];
      if (opts.calcTitle) L.push(opts.calcTitle);
      if (opts.inputs) L.push(opts.inputs);
      L.push("");
      rows.forEach(function (r) { L.push(r.name + ": " + money(r.cost) + "/" + unit); });
      L.push("");
      L.push(title);
      L.push(window.location.href);
      copyText(L.join("\n"), sec);
    });
  }

  function prod(ids, v) { return ids.reduce(function (p, i) { return p * v[i]; }, 1); }
  function cost(o, v) {
    var c = o.fixed || 0, i;
    (o.terms || []).forEach(function (t) { c += t[0] * prod(t.slice(1), v); });
    if (o.tier) {
      var q = prod(o.tier["in"], v), s = o.tier.steps;
      for (i = 0; i < s.length; i++) { if (q <= s[i][0]) { c += s[i][1]; break; } }
      if (i === s.length) c += s[s.length - 1][1] + (q - s[s.length - 1][0]) * (o.tier.over || 0);
    }
    (o.extra || []).forEach(function (x) { c += x.rate * Math.max(0, v[x["in"]] - x.free); });
    if (o.min) c = Math.max(c, o.min);
    return c;
  }

  function buildCalc(box) {
    var cfg = JSON.parse(box.getAttribute("data-cfg")), fields = [], inp = el("div", "lt-in"), out = el("div", "lt-out");
    inp.appendChild(el("h2", "lt-h", esc(cfg.title)));
    function run() {
      var v = {};
      cfg.inputs.forEach(function (f, i) { v[f.id] = fields[i].get(); });
      var unit = cfg.unit || "mo";
      var inputsTxt = "Inputs: " + cfg.inputs.map(function (f) { return f.label + " " + v[f.id].toLocaleString("en-US") + (f.unit ? " " + f.unit : ""); }).join("; ");
      var ropts = { unit: unit, calcTitle: cfg.title, inputs: inputsTxt };
      var note = cfg.note || "Benchmark estimate based on published 2026 vendor tier rates. Actual billing varies by prompt caching, batch API discounts, and plan overages.";
      var rows = cfg.options.map(function (o) { return { name: o.name, cost: cost(o, v) }; });
      if (cfg.net) {
        var pos = 0, neg = 0;
        cfg.net.plus.forEach(function (i) { pos += rows[i].cost; });
        cfg.net.minus.forEach(function (i) { neg += rows[i].cost; });
        results(out, rows, "Estimated net: " + money(pos - neg) + "/" + unit, "Estimated revenue " + money(pos) + " minus overhead " + money(neg) + ". These are assumptions, not a forecast.", cfg.cta, note, { unit: unit, noWin: true, calcTitle: cfg.title, inputs: inputsTxt, winner: { label: "Estimated net", name: "", cost: pos - neg } });
        return;
      }
      var s = rows.slice().sort(function (a, b) { return a.cost - b.cost; }), lo = s[0], hi = s[s.length - 1];
      var sub = "Saves " + money(hi.cost - lo.cost) + "/" + unit + " versus " + hi.name + ".";
      if (cfg.perHour) {
        var hrs = v[cfg.perHour["in"]] * cfg.perHour.weeks * (cfg.perHour.perSeat ? v[cfg.perHour.perSeat] : 1);
        if (hrs > 0) sub += " That is " + money(lo.cost / hrs) + " per hour saved.";
      }
      results(out, rows, "Cheapest here: " + lo.name + " at " + money(lo.cost) + "/" + unit, sub, cfg.cta, note, ropts);
    }
    cfg.inputs.forEach(function (f) { var w = field(f, run); fields.push(w); inp.appendChild(w); });
    box.appendChild(inp); box.appendChild(out); run();
  }

  function buildBreakeven(box) {
    var tok = { 1: 500, 2: 3000, 3: 15000 }, ctx = 2;
    var P = [["Claude Sonnet 5.5 API", 2, 10], ["OpenAI GPT-6.1 Sol API", 2, 10], ["DeepSeek V4.1 Flash API", 0.3, 1.2]];
    var cta = { text: box.getAttribute("data-cta-text"), url: box.getAttribute("data-cta-url") };
    var inp = el("div", "lt-in"), out = el("div", "lt-out");
    inp.appendChild(el("h2", "lt-h", "AI API vs. Monthly Subscription Breakeven"));
    var words = field({ label: "Words generated per month", min: 10000, max: 2000000, step: 10000, value: 300000, unit: "words" }, run);
    inp.appendChild(words);
    inp.appendChild(el("p", "lt-note", "Prompt + context size per request"));
    var seg = el("div", "lt-seg");
    [["Small", 1], ["Medium", 2], ["Heavy RAG", 3]].forEach(function (x) {
      var b = el("button", "", x[0]); b.type = "button"; b.setAttribute("aria-pressed", x[1] === ctx);
      b.addEventListener("click", function () {
        ctx = x[1];
        Array.prototype.forEach.call(seg.children, function (c) { c.setAttribute("aria-pressed", c === b); });
        run();
      });
      seg.appendChild(b);
    });
    inp.appendChild(seg);
    var seats = field({ label: "Team seats", min: 1, max: 10, step: 1, value: 1, unit: "" }, run);
    inp.appendChild(seats);
    inp.appendChild(el("p", "lt-note", "Assumes one request per 1,000 words. Subscription = $20 per seat per month."));
    var NOTE = "Benchmark estimate based on published rates checked November 2026: Claude Sonnet 5.5 $2/$10, GPT-6.1 Sol $2/$10 and DeepSeek V4.1 Flash $0.30/$1.20 per 1M tokens (input/output), and $20 per seat plans. Actual billing varies by prompt caching, batch API discounts, and plan overages.";
    function run() {
      var w = words.get(), s = seats.get(), outTok = w * 1.333, inTok = w / 1000 * tok[ctx];
      var rows = [{ name: "ChatGPT Plus / Claude Pro", cost: 20 * s }];
      P.forEach(function (p) { rows.push({ name: p[0], cost: (inTok * p[1] + outTok * p[2]) / 1e6 }); });
      var api = Math.min(rows[1].cost, rows[2].cost), save = rows[0].cost - api;
      var bopts = { calcTitle: "AI API vs Monthly Subscription Breakeven", inputs: "Inputs: " + w.toLocaleString("en-US") + " words a month; " + ["small", "medium", "heavy RAG"][ctx - 1] + " context; " + s + (s > 1 ? " seats" : " seat") };
      var win = save > 0.5 ? (rows[1].cost <= rows[2].cost ? rows[1] : rows[2]) : rows[0];
      bopts.winner = { label: "Best option", name: win.name, cost: win.cost };
      if (save > 0.5) results(out, rows, "Switch to API & Save " + money(save) + "/mo", "Cheapest premium API beats " + s + " subscription seat" + (s > 1 ? "s" : "") + ". With DeepSeek you could save " + money(rows[0].cost - rows[3].cost) + "/mo.", cta, NOTE, bopts);
      else results(out, rows, "Stay on $20/mo Pro Plan", "At this volume the flat plan is " + money(Math.abs(save)) + "/mo cheaper than premium APIs.", cta, NOTE, bopts);
    }
    box.appendChild(inp); box.appendChild(out); run();
  }

  function buildDir(box) {
    var items = JSON.parse(box.getAttribute("data-items")), cat = box.getAttribute("data-cat") || "all";
    var L = { calc: "Cost Calculator", geo: "GEO & AI Visibility", vs: "Tool Comparison", wf: "No-Code Workflow" };
    var C = [["all", "All"], ["calc", "Cost Calculators"], ["geo", "GEO & AI Visibility"], ["vs", "Tool Comparisons"], ["wf", "No-Code Workflows"]];
    var q = el("input", "lt-q"); q.type = "search"; q.placeholder = "Search tools: make, n8n, claude, real estate..."; q.setAttribute("aria-label", "Search tools");
    var chips = el("div", "lt-chips"), grid = el("div", "lt-grid"), empty = el("p", "lt-note", "No tools match. Clear the search or choose another category.");
    empty.style.display = "none";
    function draw() {
      var t = q.value.toLowerCase().trim(), h = "", n = 0;
      items.forEach(function (i) {
        if (cat !== "all" && i.c !== cat) return;
        if (t && (i.t + " " + i.d + " " + L[i.c]).toLowerCase().indexOf(t) < 0) return;
        n++; h += '<a class="lt-card" href="' + esc(i.u) + '"><em>' + L[i.c] + '</em><b>' + esc(i.t) + '</b><span>' + esc(i.d) + '</span></a>';
      });
      grid.innerHTML = h; empty.style.display = n ? "none" : "block";
    }
    C.forEach(function (c) {
      var b = el("button", "", c[1]); b.type = "button"; b.setAttribute("aria-pressed", c[0] === cat);
      b.addEventListener("click", function () { cat = c[0]; Array.prototype.forEach.call(chips.children, function (x) { x.setAttribute("aria-pressed", x === b); }); draw(); });
      chips.appendChild(b);
    });
    q.addEventListener("input", draw);
    box.appendChild(q); box.appendChild(chips); box.appendChild(grid); box.appendChild(empty); draw();
  }

  function buildLlms(box) {
    var id = "lt-l" + (++uid);
    var inp = el("div", "lt-in", '<h2 class="lt-h">llms.txt generator</h2>' +
      '<div class="lt-f"><label for="' + id + 'n">Site name</label><input type="text" id="' + id + 'n" class="lt-q" value="Example Site"></div>' +
      '<div class="lt-f"><label for="' + id + 's">One-sentence summary</label><input type="text" id="' + id + 's" class="lt-q" value="A short description of what your site offers."></div>' +
      '<div class="lt-f"><label for="' + id + 'p">Key pages, one per line: Title | URL | Short description</label><textarea id="' + id + 'p" class="lt-q" rows="7">Pricing | https://example.com/pricing | Plans and prices\nGuides | https://example.com/guides | How-to articles</textarea></div>');
    var out = el("div", "lt-out", '<textarea class="lt-q lt-ta" rows="12" readonly aria-label="Generated llms.txt"></textarea><button type="button" class="lt-btn lt-cp">Copy llms.txt</button><p class="lt-note">Save the text as llms.txt in your site root. It is a proposed format and is not confirmed to affect AI citations.</p>');
    var n = inp.querySelector("#" + id + "n"), s = inp.querySelector("#" + id + "s"), p = inp.querySelector("#" + id + "p"), ta = out.querySelector("textarea");
    function run() {
      var L = ["# " + n.value.trim(), "", "> " + s.value.trim(), "", "## Key pages", ""];
      p.value.split("\n").forEach(function (l) {
        var a = l.split("|").map(function (x) { return x.trim(); });
        if (a.length < 2 || !a[0] || !a[1]) return;
        L.push("- [" + a[0] + "](" + a[1] + ")" + (a[2] ? ": " + a[2] : ""));
      });
      ta.value = L.join("\n") + "\n";
    }
    [n, s, p].forEach(function (x) { x.addEventListener("input", run); });
    out.querySelector(".lt-cp").addEventListener("click", function () { ta.select(); if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(ta.value); else document.execCommand("copy"); });
    box.appendChild(inp); box.appendChild(out); run();
  }

  Array.prototype.forEach.call(document.querySelectorAll("[data-lt]"), function (b) {
    var k = b.getAttribute("data-lt");
    try { if (k === "calc") buildCalc(b); else if (k === "breakeven") buildBreakeven(b); else if (k === "dir") buildDir(b); else if (k === "llms") buildLlms(b); } catch (e) { b.textContent = ""; }
  });

  checkScroll();

  document.addEventListener("click", function (e) {
    var b = e.target.closest ? e.target.closest(".lt-copy") : null;
    if (!b) return;
    var txt = b.closest(".lt-prompt").querySelector("pre").textContent;
    function done() { b.textContent = "Copied"; setTimeout(function () { b.textContent = "Copy Workflow Prompt"; }, 1800); }
    if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(txt).then(done);
    else { var t = document.createElement("textarea"); t.value = txt; document.body.appendChild(t); t.select(); try { document.execCommand("copy"); done(); } catch (x) {} document.body.removeChild(t); }
  });
})();
