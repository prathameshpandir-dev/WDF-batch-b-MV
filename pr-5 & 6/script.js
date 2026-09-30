(function () {
  const page = window.location.pathname.split("/").pop().toLowerCase() || "index.html";
  const loggedIn = localStorage.getItem("bbu_logged_in") === "true" || sessionStorage.getItem("bbu_logged_in") === "true";
  const protectedPages = [
    "dashboard.html", "profile.html", "attendance.html", "academics.html", "timetable.html",
    "assignments.html", "fees.html", "library.html", "placements.html", "settings.html"
  ];
  if (!loggedIn && protectedPages.includes(page)) return window.location.replace("login.html");
  if (loggedIn && page === "login.html") return window.location.replace("dashboard.html");
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

  const loginForm = $("login-form");
  if (loginForm) {
    on(loginForm, "submit", e => {
      e.preventDefault();
      let user = $("login-username")?.value.trim() || "";
      const pass = $("login-password")?.value || "";
      let role = $("login-role")?.value || "student";
      const remember = $("login-remember")?.checked || false;

      if (!user || !pass) return alert("Please enter both your Roll Number/PRN and Password.");

      const users = JSON.parse(localStorage.getItem("bbu_registered_users") || "[]");
      const matched = users.find(u => u.prn.toLowerCase() === user.toLowerCase() || u.email.toLowerCase() === user.toLowerCase());
      if (matched) {
        if (matched.password !== pass) return alert("Invalid credentials. Incorrect password for account: " + user);
        user = `${matched.name} (${matched.prn})`;
        role = matched.role || role;
      }

      sessionStorage.setItem("bbu_logged_in", "true");
      sessionStorage.setItem("bbu_username", user);
      sessionStorage.setItem("bbu_role", role);
      if (remember) {
        localStorage.setItem("bbu_logged_in", "true");
        localStorage.setItem("bbu_username", user);
        localStorage.setItem("bbu_role", role);
      }
      alert("Sign-in successful. Redirecting to dashboard...");
      window.location.replace("dashboard.html");
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

    const fields = {
      fullname: { el: $("reg-fullname"), err: $("reg-fullname-error"), test: v => /^[A-Za-z\s]{3,50}$/.test(v), msg: "Name must contain only alphabets and spaces (min 3 characters)." },
      email: { el: $("reg-email"), err: $("reg-email-error"), test: v => /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(v), msg: "Please enter a valid email address." },
      phone: { el: $("reg-phone"), err: $("reg-phone-error"), test: v => /^[6-9]\d{9}$/.test(v), msg: "Enter a valid 10-digit Indian mobile number." },
      prn: { el: $("reg-prn"), err: $("reg-prn-error"), test: v => /^[A-Za-z0-9]{4,15}$/.test(v), msg: "PRN must be 4 to 15 alphanumeric characters." },
      course: { el: $("reg-course"), err: $("reg-course-error"), test: v => v !== "", msg: "Please select your enrolled course." },
      year: { el: $("reg-year"), err: $("reg-year-error"), test: v => v !== "", msg: "Please select your academic year." }
    };

    const passInp = $("reg-password");
    const passErr = $("reg-password-error");
    const bar = $("reg-strength-bar");
    const barLbl = $("reg-strength-label");
    const confInp = $("reg-confirm-password");
    const confErr = $("reg-confirm-password-error");
    const termsInp = $("reg-terms");
    const termsErr = $("reg-terms-error");
    const genderInps = $$('input[name="reg-gender"]');
    const genderErr = $("reg-gender-error");

    const setError = (inp, errEl, msg) => {
      if (errEl) { errEl.textContent = msg; errEl.classList.add("active"); }
      if (inp) { inp.classList.add("is-invalid"); inp.classList.remove("is-valid"); inp.setAttribute("aria-invalid", "true"); }
    };
    const clearError = (inp, errEl) => {
      if (errEl) { errEl.textContent = ""; errEl.classList.remove("active"); }
      if (inp) { inp.classList.remove("is-invalid"); inp.classList.add("is-valid"); inp.setAttribute("aria-invalid", "false"); }
    };

    const validateField = k => {
      const f = fields[k];
      if (!f || !f.el) return true;
      const v = f.el.value.trim();
      if (!v) { setError(f.el, f.err, `${f.el.labels?.[0]?.textContent?.replace(/\*/g, "").trim() || "Field"} is required.`); return false; }
      if (!f.test(v)) { setError(f.el, f.err, f.msg); return false; }
      clearError(f.el, f.err);
      return true;
    };

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

    const validatePass = () => {
      const v = passInp.value;
      updateStrength(v);
      if (!v) { setError(passInp, passErr, "Password is required."); return false; }
      const ok = v.length >= 8 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /\d/.test(v) && /[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(v);
      if (!ok) { setError(passInp, passErr, "Password must be at least 8 characters with uppercase, lowercase, number, and special character."); return false; }
      clearError(passInp, passErr);
      return true;
    };

    const validateConf = () => {
      if (!confInp.value) { setError(confInp, confErr, "Please confirm your password."); return false; }
      if (confInp.value !== passInp.value) { setError(confInp, confErr, "Passwords do not match."); return false; }
      clearError(confInp, confErr);
      return true;
    };

    const validateGender = () => {
      const ok = genderInps.some(r => r.checked);
      if (!ok) { if (genderErr) { genderErr.textContent = "Please select your gender."; genderErr.classList.add("active"); } return false; }
      if (genderErr) { genderErr.textContent = ""; genderErr.classList.remove("active"); }
      return true;
    };

    const validateTerms = () => {
      if (!termsInp || !termsInp.checked) {
        if (termsErr) { termsErr.textContent = "You must accept the IT rules and student code of conduct."; termsErr.classList.add("active"); }
        if (termsInp) { termsInp.classList.add("is-invalid"); termsInp.setAttribute("aria-invalid", "true"); }
        return false;
      }
      if (termsErr) { termsErr.textContent = ""; termsErr.classList.remove("active"); }
      if (termsInp) { termsInp.classList.remove("is-invalid"); termsInp.setAttribute("aria-invalid", "false"); }
      return true;
    };

    Object.keys(fields).forEach(k => {
      const el = fields[k].el;
      on(el, el?.tagName === "SELECT" ? "change" : "input", () => validateField(k));
    });
    genderInps.forEach(r => on(r, "change", validateGender));
    on(passInp, "input", () => { validatePass(); if (confInp.value) validateConf(); });
    on(confInp, "input", validateConf);
    on(termsInp, "change", validateTerms);

    on(regForm, "submit", e => {
      e.preventDefault();
      const valid = Object.keys(fields).every(k => validateField(k)) & validateGender() & validatePass() & validateConf() & validateTerms();
      if (!valid) return regForm.querySelector(".is-invalid")?.focus();

      const prn = fields.prn.el.value.trim().toUpperCase();
      const email = fields.email.el.value.trim();
      const registered = JSON.parse(localStorage.getItem("bbu_registered_users") || "[]");
      if (registered.some(u => u.prn.toUpperCase() === prn || u.email.toLowerCase() === email.toLowerCase())) {
        setError(fields.prn.el, fields.prn.err, "An account with this PRN or Email already exists. Please sign in.");
        return fields.prn.el.focus();
      }

      const fullname = fields.fullname.el.value.trim();
      const course = fields.course.el.value;
      const year = fields.year.el.value;
      registered.push({
        name: fullname, prn, email, phone: fields.phone.el.value.trim(),
        course, dept: course, year, gender: genderInps.find(r => r.checked)?.value || "",
        role: $("reg-role")?.value || "student", password: passInp.value, registeredAt: new Date().toLocaleDateString()
      });
      localStorage.setItem("bbu_registered_users", JSON.stringify(registered));
      sessionStorage.setItem("bbu_prefill_username", prn);
      sessionStorage.setItem("bbu_prefill_password", passInp.value);

      const succ = $("registration-success-msg");
      if (succ) {
        $("registration-success-details").innerHTML = `Welcome, <b>${fullname}</b>! Account registered for <b>${course} (${year})</b> with PRN: <b>${prn}</b>.<br>Redirecting to login portal in 3 seconds...`;
        succ.style.display = "block";
        succ.scrollIntoView({ behavior: "smooth" });
      }
      setTimeout(() => window.location.href = "login.html", 3000);
    });
  }

  const applyTheme = t => {
    document.documentElement.toggleAttribute("data-theme", t === "dark");
    if (t !== "dark") document.documentElement.removeAttribute("data-theme");
    $$(".theme-toggle-btn").forEach(b => {
      b.innerHTML = `<span>${t === "dark" ? "Light" : "Dark"} Mode</span>`;
      b.setAttribute("aria-label", `Switch to ${t === "dark" ? "light" : "dark"} theme`);
    });
  };
  applyTheme(localStorage.getItem("bbu_theme") || "light");
  $$(".theme-toggle-btn").forEach(b => on(b, "click", () => {
    const next = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
    applyTheme(next);
    localStorage.setItem("bbu_theme", next);
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
