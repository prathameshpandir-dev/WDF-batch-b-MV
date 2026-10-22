(function () {
  try {
    const savedTheme = localStorage.getItem("bbu_theme");
    if (savedTheme === "dark") {
      document.documentElement.setAttribute("data-theme", "dark");
    } else {
      document.documentElement.removeAttribute("data-theme");
    }
  } catch (err) {}

  const page = window.location.pathname.split("/").pop().toLowerCase() || "index.html";
  const search = window.location.search || "";
  if (search.includes("msg=") || search.includes("error=")) {
    localStorage.removeItem("bbu_logged_in");
    sessionStorage.removeItem("bbu_logged_in");
  }
  const loggedIn = localStorage.getItem("bbu_logged_in") === "true" || sessionStorage.getItem("bbu_logged_in") === "true";
  const protectedPages = [
    "profile.html", "attendance.html", "academics.html", "timetable.html",
    "assignments.html", "fees.html", "library.html", "placements.html", "settings.html"
  ];
  if (!loggedIn && protectedPages.includes(page)) return window.location.replace("login.html");
})();

document.addEventListener("DOMContentLoaded", () => {
  const $ = id => document.getElementById(id);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const on = (el, evt, fn) => el && el.addEventListener(evt, fn);
  const loggedIn = localStorage.getItem("bbu_logged_in") === "true" || sessionStorage.getItem("bbu_logged_in") === "true";

  function logoutUser(e) {
    if (e) e.preventDefault();
    if (!confirm("Are you sure you want to sign out of the university portal?")) return;
    ["bbu_logged_in", "bbu_username", "bbu_role"].forEach(k => { localStorage.removeItem(k); sessionStorage.removeItem(k); });
    alert("Signed out successfully. Session terminated.");
    window.location.replace("login.html");
  }

  $$(".logout-btn, #logout-btn, [data-action='logout']").forEach(b => on(b, "click", logoutUser));

  if (loggedIn) {
    $$('nav a[href="login.html"], .footer-links a[href="login.html"]').forEach(link => {
      link.textContent = link.closest("nav") ? "Log Out" : "Log Out of Portal";
      link.href = "javascript:void(0)";
      link.style.color = "var(--accent-light)";
      on(link, "click", logoutUser);
    });
  }

  const preUser = sessionStorage.getItem("bbu_prefill_username");
  const prePass = sessionStorage.getItem("bbu_prefill_password");
  if (preUser && $("login-username")) { $("login-username").value = preUser; sessionStorage.removeItem("bbu_prefill_username"); }
  if (prePass && $("login-password")) { $("login-password").value = prePass; sessionStorage.removeItem("bbu_prefill_password"); }

  // Handle URL status parameters on login page (Practical 10: Server Messages)
  const authUrlParams = new URLSearchParams(window.location.search);
  const statusBox = $("login-status-msg");
  if (statusBox) {
    if (authUrlParams.has("msg")) {
      const msg = authUrlParams.get("msg");
      if (msg === "logged_out") {
        statusBox.style.display = "block";
        statusBox.style.backgroundColor = "#dcfce7";
        statusBox.style.color = "#166534";
        statusBox.style.border = "1px solid #86efac";
        statusBox.innerHTML = "✅ <b>Signed Out:</b> You have been logged out successfully. Session terminated.";
        localStorage.removeItem("bbu_logged_in");
        sessionStorage.removeItem("bbu_logged_in");
      } else if (msg === "timeout") {
        statusBox.style.display = "block";
        statusBox.style.backgroundColor = "#fef3c7";
        statusBox.style.color = "#92400e";
        statusBox.style.border = "1px solid #fcd34d";
        statusBox.innerHTML = "⏱️ <b>Session Expired:</b> Your session expired due to 15 minutes of inactivity. Please log in again.";
        localStorage.removeItem("bbu_logged_in");
        sessionStorage.removeItem("bbu_logged_in");
      }
    } else if (authUrlParams.has("error")) {
      const err = decodeURIComponent(authUrlParams.get("error"));
      statusBox.style.display = "block";
      statusBox.style.backgroundColor = "#fee2e2";
      statusBox.style.color = "#991b1b";
      statusBox.style.border = "1px solid #fca5a5";
      statusBox.innerHTML = "⚠️ <b>Error:</b> " + err;
      alert(err);
      localStorage.removeItem("bbu_logged_in");
      sessionStorage.removeItem("bbu_logged_in");
    }
  }

  const loginForm = $("login-form");
  if (loginForm) {
    on(loginForm, "submit", e => {
      const user = $("login-username")?.value.trim() || "";
      const pass = $("login-password")?.value || "";

      if (!user || !pass) {
        e.preventDefault();
        return alert("Please enter both your Roll Number/PRN/Email and Password.");
      }
      // Allow standard POST submission to PHP backend (Practical 10)
    });

    on($("toggle-password-visibility"), "click", () => {
      const p = $("login-password");
      if (!p) return;
      const isPass = p.type === "password";
      p.type = isPass ? "text" : "password";
      $("toggle-password-visibility").textContent = isPass ? "Hide" : "Show";
    });

    on($("forgot-password-link"), "click", () => {
      const id = prompt("Enter your registered PRN or University Email to reset password:", "");
      if (id) alert("Password reset instructions dispatched to email for: " + id);
    });
  }

  const regForm = $("registration-form");
  if (regForm) {
    $$(".toggle-pass-visibility-btn").forEach(btn => on(btn, "click", () => {
      const inp = $(btn.dataset.target);
      if (!inp) return;
      const isPass = inp.type === "password";
      inp.type = isPass ? "text" : "password";
      btn.textContent = isPass ? "Hide" : "Show";
    }));

    const bar = $("reg-strength-bar");
    const barLbl = $("reg-strength-label");
    const passInp = $("reg-password");

    const updateStrength = pass => {
      if (!bar || !barLbl) return;
      if (!pass) {
        bar.className = "password-strength-fill";
        barLbl.innerHTML = "<b>None</b>";
        barLbl.style.color = "var(--text-muted)";
        return;
      }
      const score = (pass.length >= 8 ? 1 : 0) + (/[a-z]/.test(pass) && /[A-Z]/.test(pass) ? 1 : 0) + (/\d/.test(pass) ? 1 : 0) + (/[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(pass) ? 1 : 0);
      const conf = score <= 1 ? { l: "Weak", c: "strength-weak", col: "var(--danger)" } :
                   score <= 3 ? { l: "Medium", c: "strength-medium", col: "var(--warning)" } :
                                { l: "Strong", c: "strength-strong", col: "var(--success)" };
      bar.className = `password-strength-fill ${conf.c}`;
      barLbl.innerHTML = `<b>${conf.l}</b>`;
      barLbl.style.color = conf.col;
    };

    if (passInp) on(passInp, "input", () => updateStrength(passInp.value));

    // Handle URL parameters for server responses (success or error)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("success")) {
      const succMsg = $("registration-success-msg");
      if (succMsg) succMsg.style.display = "block";
    }
    if (urlParams.has("error")) {
      const errBox = $("registration-server-error");
      const errTxt = $("registration-server-error-text");
      if (errBox && errTxt) {
        errTxt.textContent = "⚠️ " + decodeURIComponent(urlParams.get("error"));
        errBox.style.display = "block";
      }
    }

    // Helper: Clear validation error on a field
    const clearError = (input, errDiv) => {
      if (input) input.classList.remove("is-invalid");
      if (errDiv) {
        errDiv.textContent = "";
        errDiv.classList.remove("active");
      }
    };

    // Helper: Set validation error on a field
    const setError = (input, errDiv, message) => {
      if (input) input.classList.add("is-invalid");
      if (errDiv) {
        errDiv.textContent = message;
        errDiv.classList.add("active");
      }
    };

    // Real-time error clearing when user types or changes input
    const inputsToWatch = [
      ["reg-fullname", "reg-fullname-error"],
      ["reg-email", "reg-email-error"],
      ["reg-phone", "reg-phone-error"],
      ["reg-course", "reg-course-error"],
      ["reg-year", "reg-year-error"],
      ["reg-password", "reg-password-error"],
      ["reg-confirm-password", "reg-confirm-password-error"]
    ];

    inputsToWatch.forEach(([inpId, errId]) => {
      const el = $(inpId);
      const err = $(errId);
      if (el) {
        on(el, "input", () => clearError(el, err));
        on(el, "change", () => clearError(el, err));
      }
    });

    $$('input[name="reg-gender"]').forEach(r => {
      on(r, "change", () => clearError(null, $("reg-gender-error")));
    });

    const termsEl = $("reg-terms");
    if (termsEl) on(termsEl, "change", () => clearError(null, $("reg-terms-error")));

    // Practical 9 / Practical 5: Frontend Form Validation before POST
    on(regForm, "submit", e => {
      let hasError = false;
      let firstInvalid = null;

      const flagError = (inp, errDiv, msg) => {
        setError(inp, errDiv, msg);
        if (!firstInvalid && inp) firstInvalid = inp;
        hasError = true;
      };

      // 1. Full Name Validation
      const nameInp = $("reg-fullname");
      const nameVal = nameInp ? nameInp.value.trim() : "";
      if (!nameVal) {
        flagError(nameInp, $("reg-fullname-error"), "Full Name is required.");
      } else if (!/^[a-zA-Z\s]{3,100}$/.test(nameVal)) {
        flagError(nameInp, $("reg-fullname-error"), "Full Name must contain only letters and spaces (min 3 chars).");
      } else {
        clearError(nameInp, $("reg-fullname-error"));
      }

      // 2. Email Validation
      const emailInp = $("reg-email");
      const emailVal = emailInp ? emailInp.value.trim() : "";
      if (!emailVal) {
        flagError(emailInp, $("reg-email-error"), "Email Address is required.");
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
        flagError(emailInp, $("reg-email-error"), "Please enter a valid email address.");
      } else {
        clearError(emailInp, $("reg-email-error"));
      }

      // 3. Mobile Number Validation (10 digits starting with 6-9)
      const phoneInp = $("reg-phone");
      const phoneVal = phoneInp ? phoneInp.value.trim() : "";
      if (!phoneVal) {
        flagError(phoneInp, $("reg-phone-error"), "Mobile number is required.");
      } else if (!/^[6-9]\d{9}$/.test(phoneVal)) {
        flagError(phoneInp, $("reg-phone-error"), "Mobile number must be exactly 10 digits and start with 6, 7, 8, or 9.");
      } else {
        clearError(phoneInp, $("reg-phone-error"));
      }

      // 4. Course Selection
      const courseInp = $("reg-course");
      const courseVal = courseInp ? courseInp.value : "";
      if (!courseVal) {
        flagError(courseInp, $("reg-course-error"), "Please select your enrolled course.");
      } else {
        clearError(courseInp, $("reg-course-error"));
      }

      // 5. Academic Year Selection
      const yearInp = $("reg-year");
      const yearVal = yearInp ? yearInp.value : "";
      if (!yearVal) {
        flagError(yearInp, $("reg-year-error"), "Please select your academic year.");
      } else {
        clearError(yearInp, $("reg-year-error"));
      }

      // 6. Gender Selection
      const genderChecked = document.querySelector('input[name="reg-gender"]:checked');
      if (!genderChecked) {
        flagError(null, $("reg-gender-error"), "Please select your gender.");
      } else {
        clearError(null, $("reg-gender-error"));
      }

      // 7. Password Complexity (min 8 chars, uppercase, lowercase, digit, special char)
      const passVal = passInp ? passInp.value : "";
      if (!passVal) {
        flagError(passInp, $("reg-password-error"), "Password is required.");
      } else if (passVal.length < 8) {
        flagError(passInp, $("reg-password-error"), "Password must be at least 8 characters long.");
      } else if (!/[A-Z]/.test(passVal) || !/[a-z]/.test(passVal) || !/\d/.test(passVal) || !/[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(passVal)) {
        flagError(passInp, $("reg-password-error"), "Password must include uppercase, lowercase, number, and special character.");
      } else {
        clearError(passInp, $("reg-password-error"));
      }

      // 8. Confirm Password Match
      const confInp = $("reg-confirm-password");
      const confVal = confInp ? confInp.value : "";
      if (!confVal) {
        flagError(confInp, $("reg-confirm-password-error"), "Please confirm your password.");
      } else if (confVal !== passVal) {
        flagError(confInp, $("reg-confirm-password-error"), "Passwords do not match.");
      } else {
        clearError(confInp, $("reg-confirm-password-error"));
      }

      // 9. Terms Acceptance
      if (!termsEl || !termsEl.checked) {
        flagError(termsEl, $("reg-terms-error"), "You must agree to the university IT rules and code of conduct.");
      } else {
        clearError(termsEl, $("reg-terms-error"));
      }

      // If validation fails, halt submission and focus the first invalid input
      if (hasError) {
        e.preventDefault();
        if (firstInvalid && typeof firstInvalid.focus === "function") {
          firstInvalid.focus();
        }
        return false;
      }

      // Prevent duplicate submissions while request is processing
      const submitBtn = $("register-submit-btn");
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Processing Registration...";
      }
    });
  }

  const applyTheme = t => {
    const isDark = (t === "dark");
    if (isDark) {
      document.documentElement.setAttribute("data-theme", "dark");
    } else {
      document.documentElement.removeAttribute("data-theme");
    }
    $$(".theme-toggle-btn").forEach(b => {
      b.innerHTML = isDark ? "☀️ <span>Light</span>" : "🌙 <span>Dark</span>";
      b.setAttribute("aria-label", `Switch to ${isDark ? "light" : "dark"} theme`);
      b.setAttribute("title", `Switch to ${isDark ? "light" : "dark"} theme`);
    });
  };

  const initialTheme = localStorage.getItem("bbu_theme") || "light";
  applyTheme(initialTheme);

  $$(".theme-toggle-btn").forEach(b => on(b, "click", (e) => {
    if (e) e.preventDefault();
    const isCurrentDark = document.documentElement.getAttribute("data-theme") === "dark";
    const nextTheme = isCurrentDark ? "light" : "dark";
    localStorage.setItem("bbu_theme", nextTheme);
    applyTheme(nextTheme);
  }));

  on($("banner-close-btn"), "click", () => {
    const b = $("top-notification-banner");
    if (b) { b.classList.add("dismissed"); setTimeout(() => b.style.display = "none", 300); }
  });

  const slides = $$(".slider-container .slide");
  const dots = $$(".slider-dots .dot");
  if (slides.length) {
    let cur = 0, timer = null;
    const show = i => {
      cur = (i + slides.length) % slides.length;
      slides.forEach((s, idx) => s.classList.toggle("active", idx === cur));
      dots.forEach((d, idx) => d.classList.toggle("active", idx === cur));
    };
    const start = () => { clearInterval(timer); timer = setInterval(() => show(cur + 1), 5000); };
    on(document.querySelector(".slider-btn-prev"), "click", () => { show(cur - 1); start(); });
    on(document.querySelector(".slider-btn-next"), "click", () => { show(cur + 1); start(); });
    dots.forEach((d, i) => on(d, "click", () => { show(i); start(); }));
    start();
  }

  const navToggle = $("nav-toggle");
  const navMenu = $("nav-menu");
  if (navToggle && navMenu) {
    on(navToggle, "click", () => navToggle.setAttribute("aria-expanded", String(navMenu.classList.toggle("open"))));
    document.addEventListener("click", e => {
      if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
        navMenu.classList.remove("open");
        navToggle.setAttribute("aria-expanded", "false");
      }
    });
  }
  $$(".nav-dropdown").forEach(dd => on(dd.querySelector(".nav-dropdown-btn"), "click", e => {
    e.stopPropagation();
    dd.classList.toggle("open");
  }));
  document.addEventListener("click", () => $$(".nav-dropdown").forEach(dd => dd.classList.remove("open")));

  const curPage = window.location.pathname.split("/").pop() || "index.html";
  $$(".nav-links a").forEach(l => {
    if (l.getAttribute("href") === curPage) {
      l.classList.add("active");
      const ddBtn = l.closest(".nav-dropdown")?.querySelector(".nav-dropdown-btn");
      if (ddBtn) { ddBtn.style.color = "var(--accent-light)"; ddBtn.style.fontWeight = "700"; }
    }
  });

  const closeModal = m => { m.classList.remove("active"); document.body.style.overflow = ""; };
  const openModal = (id, title) => {
    const modal = $(id);
    if (!modal) return;
    if (title && modal.querySelector(".modal-dynamic-title")) modal.querySelector(".modal-dynamic-title").textContent = title;
    modal.classList.add("active");
    document.body.style.overflow = "hidden";
  };
  $$("[data-modal-target]").forEach(t => on(t, "click", e => {
    e.preventDefault();
    openModal(t.getAttribute("data-modal-target"), t.getAttribute("data-event-title"));
  }));
  $$(".modal-overlay").forEach(m => {
    $$(".modal-close-btn, [data-modal-close]", m).forEach(b => on(b, "click", () => closeModal(m)));
    on(m, "click", e => { if (e.target === m) closeModal(m); });
  });
  document.addEventListener("keydown", e => { if (e.key === "Escape") $$(".modal-overlay.active").forEach(closeModal); });

  const eventsGrid = $("events-grid");
  if (eventsGrid) {
    let all = [], cat = "all", q = "", sort = "date-asc", page = 1;
    const perPage = 6;
    const searchInp = $("event-search-input");
    const catSel = $("event-category-filter");
    const sortSel = $("event-sort-by");
    const filterBtns = $$("#events-quick-filters .filter-btn");

    async function loadEvents() {
      if ($("events-loading")) $("events-loading").style.display = "block";
      eventsGrid.style.display = "none";
      if ($("events-error")) $("events-error").style.display = "none";
      if ($("events-not-found")) $("events-not-found").style.display = "none";
      if ($("events-pagination")) $("events-pagination").style.display = "none";
      try {
        const res = await fetch("events.json");
        if (!res.ok) throw new Error();
        all = await res.json();
        if ($("events-loading")) $("events-loading").style.display = "none";
        render();
      } catch {
        if ($("events-loading")) $("events-loading").style.display = "none";
        if ($("events-error")) $("events-error").style.display = "block";
        if ($("events-count-info")) $("events-count-info").textContent = "Failed to load events.";
      }
    }

    function render() {
      const kw = q.toLowerCase().trim();
      const filtered = all.filter(ev => {
        const mCat = cat === "all" || ev.category.toLowerCase() === cat.toLowerCase();
        const mQ = !kw || [ev.title, ev.subtitle, ev.description, ev.venue, ev.categoryLabel].some(t => t && t.toLowerCase().includes(kw));
        return mCat && mQ;
      }).sort((a, b) => {
        if (sort === "date-asc") return new Date(a.date) - new Date(b.date);
        if (sort === "date-desc") return new Date(b.date) - new Date(a.date);
        return sort === "title-asc" ? a.title.localeCompare(b.title) : b.title.localeCompare(a.title);
      });

      const total = filtered.length;
      const totalPages = Math.ceil(total / perPage) || 1;
      page = Math.max(1, Math.min(page, totalPages));

      if (total === 0) {
        eventsGrid.style.display = "none";
        if ($("events-not-found")) $("events-not-found").style.display = "block";
        if ($("events-pagination")) $("events-pagination").style.display = "none";
        if ($("events-count-info")) $("events-count-info").innerHTML = "Found <b>0 events</b> matching your filters.";
        return;
      }

      if ($("events-not-found")) $("events-not-found").style.display = "none";
      eventsGrid.style.display = "grid";

      const start = (page - 1) * perPage;
      const end = Math.min(start + perPage, total);
      eventsGrid.innerHTML = filtered.slice(start, end).map(ev => `
        <div class="event-card" data-category="${ev.category}">
          <div class="event-image-wrap" style="background: ${ev.gradient || 'linear-gradient(135deg, var(--primary), var(--primary-light))'};">
            <span class="event-date-badge">${ev.dateBadge}</span>
            <span class="event-category-badge">${ev.categoryLabel}</span>
            <h3 style="color: var(--text-inverse); font-size: var(--font-lg); margin-top: 10px;">${ev.title}</h3>
            <p style="font-size: var(--font-xs); color: #cbd5e1;">${ev.subtitle || ""}</p>
          </div>
          <div class="event-card-body">
            <div class="event-meta"><span>📍 ${ev.venue}</span><span>⏰ ${ev.time}</span></div>
            <p>${ev.description}</p>
            <button class="btn btn-primary btn-block btn-sm dynamic-event-btn" data-modal-target="event-modal" data-event-title="${ev.title.replace(/"/g, '&quot;')}">Register Now</button>
          </div>
        </div>
      `).join("");

      eventsGrid.querySelectorAll(".dynamic-event-btn").forEach(btn => on(btn, "click", e => {
        e.preventDefault();
        openModal(btn.getAttribute("data-modal-target"), btn.getAttribute("data-event-title"));
      }));

      const pag = $("events-pagination");
      if (pag) {
        pag.style.display = "flex";
        if ($("current-page-num")) $("current-page-num").textContent = page;
        if ($("total-pages-num")) $("total-pages-num").textContent = totalPages;
        if ($("prev-page-btn")) $("prev-page-btn").disabled = page === 1;
        if ($("next-page-btn")) $("next-page-btn").disabled = page === totalPages;
      }
      if ($("events-count-info")) $("events-count-info").innerHTML = `Showing <b>${start + 1} - ${end}</b> of <b>${total}</b> campus events (Total: ${all.length})`;
    }

    const setCat = c => {
      cat = c; page = 1;
      if (catSel) catSel.value = c;
      filterBtns.forEach(b => b.classList.toggle("active", b.dataset.category === c));
      render();
    };

    on(searchInp, "input", () => { q = searchInp.value; page = 1; render(); });
    on(catSel, "change", () => setCat(catSel.value));
    filterBtns.forEach(b => on(b, "click", () => setCat(b.dataset.category)));
    on(sortSel, "change", () => { sort = sortSel.value; render(); });

    const reset = () => {
      q = ""; cat = "all"; sort = "date-asc"; page = 1;
      if (searchInp) searchInp.value = "";
      if (catSel) catSel.value = "all";
      if (sortSel) sortSel.value = "date-asc";
      filterBtns.forEach(b => b.classList.toggle("active", b.dataset.category === "all"));
      render();
    };
    on($("event-reset-btn"), "click", reset);
    on($("clear-search-btn"), "click", reset);

    on($("prev-page-btn"), "click", () => { if (page > 1) { page--; render(); eventsGrid.scrollIntoView({ behavior: "smooth" }); } });
    on($("next-page-btn"), "click", () => {
      const max = Math.ceil(all.filter(ev => (cat === "all" || ev.category === cat) && (!q || ev.title.toLowerCase().includes(q.toLowerCase()))).length / perPage);
      if (page < max) { page++; render(); eventsGrid.scrollIntoView({ behavior: "smooth" }); }
    });

    loadEvents();
  }

  const studentsTbody = $("students-tbody");
  if ($("student-directory-card") && studentsTbody) {
    let all = [], q = "", branch = "all", sort = "cgpa-desc", page = 1;
    const perPage = 5;
    const searchInp = $("student-search-input");
    const branchSel = $("student-branch-filter");
    const sortSel = $("student-sort-by");

    async function loadStudents() {
      if ($("students-loading")) $("students-loading").style.display = "block";
      if ($("students-table-wrap")) $("students-table-wrap").style.display = "none";
      if ($("students-error")) $("students-error").style.display = "none";
      if ($("students-not-found")) $("students-not-found").style.display = "none";
      if ($("students-pagination")) $("students-pagination").style.display = "none";
      try {
        const res = await fetch("students.json");
        if (!res.ok) throw new Error();
        all = await res.json();
        if ($("students-loading")) $("students-loading").style.display = "none";
        render();
      } catch {
        if ($("students-loading")) $("students-loading").style.display = "none";
        if ($("students-error")) $("students-error").style.display = "block";
        if ($("students-count-info")) $("students-count-info").textContent = "Failed to load student dataset.";
      }
    }

    function render() {
      const kw = q.toLowerCase().trim();
      const filtered = all.filter(s => {
        const mB = branch === "all" || s.branch.toLowerCase() === branch.toLowerCase();
        const mQ = !kw || [s.name, s.roll, s.prn, s.branch].some(t => t && t.toLowerCase().includes(kw));
        return mB && mQ;
      }).sort((a, b) => {
        if (sort === "cgpa-desc") return b.cgpa - a.cgpa;
        if (sort === "cgpa-asc") return a.cgpa - b.cgpa;
        return sort === "roll-asc" ? a.roll.localeCompare(b.roll) : a.name.localeCompare(b.name);
      });

      const total = filtered.length;
      const totalPages = Math.ceil(total / perPage) || 1;
      page = Math.max(1, Math.min(page, totalPages));

      if (total === 0) {
        if ($("students-table-wrap")) $("students-table-wrap").style.display = "none";
        if ($("students-not-found")) $("students-not-found").style.display = "block";
        if ($("students-pagination")) $("students-pagination").style.display = "none";
        if ($("students-count-info")) $("students-count-info").innerHTML = "Found <b>0 students</b> matching criteria.";
        return;
      }

      if ($("students-not-found")) $("students-not-found").style.display = "none";
      if ($("students-table-wrap")) $("students-table-wrap").style.display = "block";

      const start = (page - 1) * perPage;
      const end = Math.min(start + perPage, total);
      studentsTbody.innerHTML = filtered.slice(start, end).map(s => `
        <tr>
          <td><b>${s.roll}</b></td>
          <td>${s.name}</td>
          <td>${s.branch}</td>
          <td>${s.year}</td>
          <td><code>${s.prn}</code></td>
          <td><b style="color: var(--secondary-dark);">${s.cgpa.toFixed(2)}</b></td>
          <td>${s.attendance}</td>
          <td><span class="badge ${s.cgpa >= 8.5 ? 'badge-success' : 'badge-info'}">${s.status}</span></td>
        </tr>
      `).join("");

      const pag = $("students-pagination");
      if (pag) {
        pag.style.display = "flex";
        if ($("students-current-page")) $("students-current-page").textContent = page;
        if ($("students-total-pages")) $("students-total-pages").textContent = totalPages;
        if ($("students-prev-btn")) $("students-prev-btn").disabled = page === 1;
        if ($("students-next-btn")) $("students-next-btn").disabled = page === totalPages;
      }
      if ($("students-count-info")) $("students-count-info").innerHTML = `Showing <b>${start + 1} - ${end}</b> of <b>${total}</b> students (Total: ${all.length})`;
    }

    on(searchInp, "input", () => { q = searchInp.value; page = 1; render(); });
    on(branchSel, "change", () => { branch = branchSel.value; page = 1; render(); });
    on(sortSel, "change", () => { sort = sortSel.value; render(); });
    on($("student-reset-btn"), "click", () => {
      q = ""; branch = "all"; sort = "cgpa-desc"; page = 1;
      if (searchInp) searchInp.value = "";
      if (branchSel) branchSel.value = "all";
      if (sortSel) sortSel.value = "cgpa-desc";
      render();
    });

    on($("students-prev-btn"), "click", () => { if (page > 1) { page--; render(); } });
    on($("students-next-btn"), "click", () => {
      const max = Math.ceil(all.filter(s => branch === "all" || s.branch === branch).length / perPage);
      if (page < max) { page++; render(); }
    });

    loadStudents();
  }

  const faqWrap = $("faq-accordion-container");
  if (faqWrap) {
    let all = [], cat = "all", q = "";
    const searchInp = $("faq-search-input");
    const catSel = $("faq-category-filter");

    async function loadFaqs() {
      if ($("faq-loading")) $("faq-loading").style.display = "block";
      faqWrap.style.display = "none";
      if ($("faq-error")) $("faq-error").style.display = "none";
      if ($("faq-not-found")) $("faq-not-found").style.display = "none";
      try {
        const res = await fetch("faqs.json");
        if (!res.ok) throw new Error();
        all = await res.json();
        if ($("faq-loading")) $("faq-loading").style.display = "none";
        render();
      } catch {
        if ($("faq-loading")) $("faq-loading").style.display = "none";
        if ($("faq-error")) $("faq-error").style.display = "block";
        if ($("faq-count-info")) $("faq-count-info").textContent = "Failed to load FAQs.";
      }
    }

    function render() {
      const kw = q.toLowerCase().trim();
      const filtered = all.filter(f => {
        const mCat = cat === "all" || f.category.toLowerCase() === cat.toLowerCase();
        const mQ = !kw || [f.question, f.answer, f.keywords].some(t => t && t.toLowerCase().includes(kw));
        return mCat && mQ;
      });

      if (filtered.length === 0) {
        faqWrap.style.display = "none";
        if ($("faq-not-found")) $("faq-not-found").style.display = "block";
        if ($("faq-count-info")) $("faq-count-info").innerHTML = "Found <b>0 FAQs</b> matching your search.";
        return;
      }

      if ($("faq-not-found")) $("faq-not-found").style.display = "none";
      faqWrap.style.display = "block";

      faqWrap.innerHTML = filtered.map((f, i) => `
        <div class="faq-item" style="margin-bottom: var(--space-xs);">
          <button class="faq-question" aria-expanded="false" style="width: 100%; text-align: left; display: flex; justify-content: space-between; align-items: center;">
            <span><b>${i + 1}.</b> ${f.question}</span>
            <span class="faq-icon" style="font-size: 18px; margin-left: 10px;">+</span>
          </button>
          <div class="faq-answer"><p style="margin: 0; line-height: 1.6;">${f.answer}</p></div>
        </div>
      `).join("");

      const btns = $$(".faq-question", faqWrap);
      btns.forEach(btn => on(btn, "click", () => {
        const item = btn.closest(".faq-item");
        if (!item) return;
        const active = item.classList.contains("active");
        btns.forEach(b => {
          const it = b.closest(".faq-item");
          if (it && it !== item) {
            it.classList.remove("active");
            b.setAttribute("aria-expanded", "false");
            const ic = it.querySelector(".faq-icon");
            if (ic) ic.textContent = "+";
          }
        });
        item.classList.toggle("active", !active);
        btn.setAttribute("aria-expanded", String(!active));
        const icon = item.querySelector(".faq-icon");
        if (icon) icon.textContent = active ? "+" : "−";
      }));

      if ($("faq-count-info")) $("faq-count-info").innerHTML = `Showing <b>${filtered.length}</b> of <b>${all.length}</b> FAQs in Knowledge Base`;
    }

    on(searchInp, "input", () => { q = searchInp.value; render(); });
    on(catSel, "change", () => { cat = catSel.value; render(); });
    on($("faq-reset-btn"), "click", () => {
      q = ""; cat = "all";
      if (searchInp) searchInp.value = "";
      if (catSel) catSel.value = "all";
      render();
    });

    loadFaqs();
  }

  const eventModalForm = $("event-modal-form");
  if (eventModalForm) {
    on(eventModalForm, "submit", e => {
      e.preventDefault();
      const sName = $("modal-student-name")?.value || "Student";
      const eName = document.querySelector(".modal-dynamic-title")?.textContent || "the event";
      alert(`Registration Confirmed for ${sName} in ${eName}.`);
      eventModalForm.reset();
      const m = eventModalForm.closest(".modal-overlay");
      if (m) closeModal(m);
    });
  }

  $$(".star-rating").forEach(group => {
    const stars = $$("span", group);
    const input = group.parentElement.querySelector("input[type='hidden']");
    stars.forEach((star, idx) => on(star, "click", () => {
      if (input) input.value = idx + 1;
      stars.forEach((s, i) => s.style.color = i <= idx ? "var(--accent-light)" : "#cbd5e1");
    }));
  });

  const feedbackForm = $("student-feedback-form");
  if (feedbackForm) {
    on(feedbackForm, "submit", e => {
      e.preventDefault();
      alert("Feedback received. Thank you for your evaluation.");
      feedbackForm.reset();
      $$(".star-rating span").forEach(s => s.style.color = "#cbd5e1");
    });
  }

  const contactForm = $("contact-inquiry-form");
  if (contactForm) {
    on(contactForm, "submit", e => {
      e.preventDefault();
      alert(`Inquiry submitted. Our helpdesk team will respond soon, ${$("contact-name")?.value || "Student"}.`);
      contactForm.reset();
    });
  }

  on($("pay-fee-btn"), "click", () => {
    if (confirm("Confirm payment of pending semester fees: ₹12,500?")) {
      $$(".pending-fee-val, #pending-fee-val").forEach(el => { el.innerText = "₹0"; el.style.color = "var(--success)"; });
      $$(".fee-status-badge, #hostel-fee-status").forEach(b => { b.className = "badge badge-success"; b.textContent = "PAID"; });
      alert("Payment successful. E-Receipt generated.");
    }
  });

  on($("book-search"), "keyup", () => {
    const term = $("book-search").value.toLowerCase();
    $$("#issued-books-table tbody tr").forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(term) ? "" : "none";
    });
  });

  const uploadForm = $("assignment-form");
  if (uploadForm) {
    on(uploadForm, "submit", e => {
      e.preventDefault();
      const file = $("file-upload");
      if (!file || file.files.length === 0) return alert("Please select a file to upload.");
      alert(`Assignment submitted successfully. File: ${file.files[0].name}`);
      file.value = "";
    });
  }

  const pwdForm = $("password-form");
  if (pwdForm) {
    on(pwdForm, "submit", e => {
      e.preventDefault();
      const [cur, next, conf] = [ $("current-pass")?.value, $("new-pass")?.value, $("confirm-pass")?.value ];
      if (!cur || !next || !conf) return alert("Please fill in all password fields.");
      if (next !== conf) return alert("New password and confirm password do not match.");
      alert("Password updated successfully.");
      pwdForm.reset();
    });
  }

  on($("profile-settings-form"), "submit", e => { e.preventDefault(); alert("Profile settings saved successfully."); });

  const resumeForm = $("resume-form");
  if (resumeForm) {
    on(resumeForm, "submit", e => {
      e.preventDefault();
      const file = $("resume-file");
      if (!file || file.files.length === 0) return alert("Please select a PDF resume file.");
      alert(`Resume uploaded successfully: ${file.files[0].name}`);
      file.value = "";
    });
  }

  $$(".apply-btn").forEach(btn => on(btn, "click", () => {
    alert("Application submitted successfully.");
    btn.innerText = "Applied ✓";
    btn.disabled = true;
    btn.className = "btn btn-success btn-sm";
  }));
});
