(() => {
  const p = (window.location.pathname.split("/").pop() || "index.html").toLowerCase();
  const logged = localStorage.getItem("bbu_logged_in") === "true" || sessionStorage.getItem("bbu_logged_in") === "true";
  const guest = p === "login.html" || p === "registration.html";
  if (!logged && !guest) window.location.replace("login.html");
  if (logged && guest) window.location.replace("dashboard.html");
})();

document.addEventListener("DOMContentLoaded", () => {
  const isLogged = localStorage.getItem("bbu_logged_in") === "true" || sessionStorage.getItem("bbu_logged_in") === "true";

  function logoutUser() {
    if (confirm("Are you sure you want to sign out of the university portal?")) {
      ["bbu_logged_in", "bbu_username", "bbu_role"].forEach((k) => {
        localStorage.removeItem(k);
        sessionStorage.removeItem(k);
      });
      alert("Signed Out Safely! 🔒\nAll secure portal sessions have been terminated.");
      window.location.replace("login.html");
    }
  }

  document.querySelectorAll(".logout-btn, #logout-btn, [data-action='logout']").forEach((b) => {
    b.addEventListener("click", (e) => {
      e.preventDefault();
      logoutUser();
    });
  });

  if (isLogged) {
    document.querySelectorAll('nav a[href="login.html"], .footer-links a[href="login.html"]').forEach((a) => {
      a.innerHTML = "🚪 Log Out";
      a.href = "javascript:void(0)";
      a.style.color = "var(--accent-light)";
      a.addEventListener("click", (e) => {
        e.preventDefault();
        logoutUser();
      });
    });
  }

  const uInput = document.getElementById("login-username");
  const pInput = document.getElementById("login-password");
  if (uInput && sessionStorage.getItem("bbu_prefill_username")) {
    uInput.value = sessionStorage.getItem("bbu_prefill_username");
    if (pInput) pInput.value = sessionStorage.getItem("bbu_prefill_password") || "";
    sessionStorage.removeItem("bbu_prefill_username");
    sessionStorage.removeItem("bbu_prefill_password");
  }

  const loginForm = document.getElementById("login-form");
  if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
      e.preventDefault();
      let user = uInput ? uInput.value.trim() : "";
      const pass = pInput ? pInput.value : "";
      const role = document.getElementById("login-role")?.value || "student";
      const remember = document.getElementById("login-remember")?.checked;

      if (!user || !pass) return alert("Please enter both your Roll Number/PRN and Password.");

      const users = JSON.parse(localStorage.getItem("bbu_registered_users") || "[]");
      const matched = users.find((u) => u.prn.toLowerCase() === user.toLowerCase() || u.email.toLowerCase() === user.toLowerCase());

      if (matched) {
        if (matched.password !== pass) return alert("Invalid Credentials! ❌ Incorrect password.");
        user = matched.name + " (" + matched.prn + ")";
      }

      ["bbu_logged_in", "bbu_username", "bbu_role"].forEach((k, i) => {
        const val = ["true", user, role][i];
        sessionStorage.setItem(k, val);
        if (remember) localStorage.setItem(k, val);
      });

      alert("Access Granted! 🔓\nWelcome back, " + user + " (" + role.toUpperCase() + ").");
      window.location.replace("dashboard.html");
    });

    document.getElementById("toggle-password-visibility")?.addEventListener("click", function () {
      pInput.type = pInput.type === "password" ? "text" : "password";
      this.textContent = pInput.type === "password" ? "👁️" : "🙈";
    });

    document.getElementById("forgot-password-link")?.addEventListener("click", () => {
      const id = prompt("Enter your registered PRN or University Email to reset password:", "");
      if (id) alert("Password Reset Dispatched! 📧\nReset link sent for " + id);
    });
  }

  const regForm = document.getElementById("registration-form");
  if (regForm) {
    document.querySelectorAll(".toggle-pass-visibility-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const inp = document.getElementById(btn.getAttribute("data-target"));
        if (inp) {
          inp.type = inp.type === "password" ? "text" : "password";
          btn.textContent = inp.type === "password" ? "👁️" : "🙈";
        }
      });
    });

    regForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const role = document.getElementById("reg-role")?.value || "student";
      const name = document.getElementById("reg-fullname")?.value.trim();
      const prn = document.getElementById("reg-prn")?.value.trim().toUpperCase();
      const dept = document.getElementById("reg-dept")?.value;
      const email = document.getElementById("reg-email")?.value.trim();
      const pass = document.getElementById("reg-password")?.value;
      const confirmPass = document.getElementById("reg-confirm-password")?.value;

      if (!name || !prn || !dept || !email || !pass) return alert("Please fill all required fields!");
      if (!email.includes("@")) return alert("Please enter a valid email address!");
      if (pass.length < 6) return alert("Password must be at least 6 characters long!");
      if (pass !== confirmPass) return alert("Passwords do not match!");
      if (!document.getElementById("reg-terms")?.checked) return alert("Please agree to the university IT rules!");

      const users = JSON.parse(localStorage.getItem("bbu_registered_users") || "[]");
      if (users.some((u) => u.prn.toUpperCase() === prn || u.email.toLowerCase() === email.toLowerCase())) {
        alert("Account with PRN " + prn + " or email " + email + " already exists! Please sign in.");
        return (window.location.href = "login.html");
      }

      users.push({ name, prn, dept, role, email, password: pass });
      localStorage.setItem("bbu_registered_users", JSON.stringify(users));
      sessionStorage.setItem("bbu_prefill_username", prn);
      sessionStorage.setItem("bbu_prefill_password", pass);

      alert("Registration Successful! 🎉\n\nWelcome, " + name + "!\nRedirecting to login page...");
      window.location.href = "login.html";
    });
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    if (theme !== "dark") document.documentElement.removeAttribute("data-theme");
    document.querySelectorAll(".theme-toggle-btn").forEach((btn) => {
      btn.innerHTML = theme === "dark" ? "☀️ <span>Light</span>" : "🌙 <span>Dark</span>";
    });
  }
  applyTheme(localStorage.getItem("bbu_theme") || "light");

  document.querySelectorAll(".theme-toggle-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const next = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
      applyTheme(next);
      localStorage.setItem("bbu_theme", next);
    });
  });

  document.getElementById("banner-close-btn")?.addEventListener("click", () => {
    const banner = document.getElementById("top-notification-banner");
    if (banner) {
      banner.classList.add("dismissed");
      setTimeout(() => (banner.style.display = "none"), 300);
    }
  });

  const slides = document.querySelectorAll(".slider-container .slide");
  const dots = document.querySelectorAll(".slider-dots .dot");
  if (slides.length > 0) {
    let current = 0;
    function showSlide(idx) {
      current = (idx + slides.length) % slides.length;
      slides.forEach((s, i) => s.classList.toggle("active", i === current));
      dots.forEach((d, i) => d.classList.toggle("active", i === current));
    }

    let timer = setInterval(() => showSlide(current + 1), 5000);
    const resetTimer = () => {
      clearInterval(timer);
      timer = setInterval(() => showSlide(current + 1), 5000);
    };

    document.querySelector(".slider-btn-next")?.addEventListener("click", () => { showSlide(current + 1); resetTimer(); });
    document.querySelector(".slider-btn-prev")?.addEventListener("click", () => { showSlide(current - 1); resetTimer(); });
    dots.forEach((dot, i) => dot.addEventListener("click", () => { showSlide(i); resetTimer(); }));
  }

  document.querySelectorAll(".faq-question").forEach((btn) => {
    btn.addEventListener("click", () => {
      const item = btn.closest(".faq-item");
      if (!item) return;
      const isOpen = item.classList.contains("active");
      document.querySelectorAll(".faq-item").forEach((el) => el.classList.remove("active"));
      if (!isOpen) item.classList.add("active");
    });
  });

  const navToggle = document.getElementById("nav-toggle");
  const navMenu = document.getElementById("nav-menu");
  if (navToggle && navMenu) {
    navToggle.addEventListener("click", () => {
      navToggle.setAttribute("aria-expanded", navMenu.classList.toggle("open"));
    });

    document.addEventListener("click", (e) => {
      if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
        navMenu.classList.remove("open");
        navToggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  const dropdowns = document.querySelectorAll(".nav-dropdown");
  dropdowns.forEach((dd) => {
    dd.querySelector(".nav-dropdown-btn")?.addEventListener("click", (e) => {
      e.stopPropagation();
      dd.classList.toggle("open");
    });
  });

  document.addEventListener("click", () => dropdowns.forEach((dd) => dd.classList.remove("open")));

  const activePage = window.location.pathname.split("/").pop() || "index.html";
  document.querySelectorAll(".nav-links a").forEach((link) => {
    if (link.getAttribute("href") === activePage) {
      link.classList.add("active");
      const btn = link.closest(".nav-dropdown")?.querySelector(".nav-dropdown-btn");
      if (btn) {
        btn.style.color = "var(--accent-light)";
        btn.style.fontWeight = "700";
      }
    }
  });

  document.querySelectorAll("[data-modal-target]").forEach((trigger) => {
    trigger.addEventListener("click", (e) => {
      e.preventDefault();
      const modal = document.getElementById(trigger.getAttribute("data-modal-target"));
      if (modal) {
        const title = trigger.getAttribute("data-event-title");
        const titleElem = modal.querySelector(".modal-dynamic-title");
        if (title && titleElem) titleElem.textContent = title;
        modal.classList.add("active");
        document.body.style.overflow = "hidden";
      }
    });
  });

  function closeModal(m) {
    m.classList.remove("active");
    document.body.style.overflow = "";
  }

  document.querySelectorAll(".modal-overlay").forEach((modal) => {
    modal.addEventListener("click", (e) => {
      if (e.target === modal || e.target.closest(".modal-close-btn, [data-modal-close]")) closeModal(modal);
    });
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") document.querySelectorAll(".modal-overlay").forEach(closeModal);
  });

  const filterBtns = document.querySelectorAll(".filter-btn");
  const eventCards = document.querySelectorAll(".event-card");
  if (filterBtns.length && eventCards.length) {
    filterBtns.forEach((btn) => {
      btn.addEventListener("click", () => {
        filterBtns.forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        const cat = btn.getAttribute("data-category");
        eventCards.forEach((card) => {
          card.style.display = cat === "all" || card.getAttribute("data-category") === cat ? "flex" : "none";
        });
      });
    });
  }

  document.getElementById("event-modal-form")?.addEventListener("submit", function (e) {
    e.preventDefault();
    const name = document.getElementById("modal-student-name")?.value || "Student";
    const title = document.querySelector(".modal-dynamic-title")?.textContent || "Event";
    alert("Registration Confirmed! 🎉\nThank you, " + name + ". Registered for " + title + ".");
    this.reset();
    const modal = this.closest(".modal-overlay");
    if (modal) closeModal(modal);
  });

  document.querySelectorAll(".star-rating").forEach((rating) => {
    const stars = rating.querySelectorAll("span");
    const input = rating.parentElement?.querySelector("input[type='hidden']");
    stars.forEach((star, idx) => {
      star.addEventListener("click", () => {
        if (input) input.value = idx + 1;
        stars.forEach((s, i) => (s.style.color = i <= idx ? "var(--accent-light)" : "#cbd5e1"));
      });
    });
  });

  document.getElementById("student-feedback-form")?.addEventListener("submit", function (e) {
    e.preventDefault();
    alert("Feedback Received! 🙏\nTicket #" + Math.floor(1000 + Math.random() * 9000));
    this.reset();
    document.querySelectorAll(".star-rating span").forEach((s) => (s.style.color = "#cbd5e1"));
  });

  document.getElementById("contact-inquiry-form")?.addEventListener("submit", function (e) {
    e.preventDefault();
    alert("Inquiry Sent! 📬\nThank you, " + (document.getElementById("contact-name")?.value || "User") + ".");
    this.reset();
  });

  document.getElementById("pay-fee-btn")?.addEventListener("click", () => {
    if (confirm("Confirm payment of pending semester fees: ₹12,500?")) {
      document.querySelectorAll(".pending-fee-val, #pending-fee-val").forEach((el) => {
        el.innerText = "₹0";
        el.style.color = "var(--success)";
      });
      document.querySelectorAll(".fee-status-badge, #hostel-fee-status").forEach((b) => {
        b.className = "badge badge-success";
        b.textContent = "PAID";
      });
      alert("Payment Successful! E-Receipt: TXN-" + Math.floor(10000000 + Math.random() * 90000000));
    }
  });

  const bookSearch = document.getElementById("book-search");
  if (bookSearch) {
    bookSearch.addEventListener("keyup", () => {
      const q = bookSearch.value.toLowerCase();
      document.querySelectorAll("#issued-books-table tbody tr").forEach((row) => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? "" : "none";
      });
    });
  }

  ["assignment-form", "password-form", "profile-settings-form", "resume-form"].forEach((id) => {
    document.getElementById(id)?.addEventListener("submit", function (e) {
      e.preventDefault();
      alert("Action Completed Successfully! ✓");
      this.reset?.();
    });
  });

  document.querySelectorAll(".apply-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      alert("Application submitted successfully!");
      btn.innerText = "Applied ✓";
      btn.disabled = true;
      btn.className = "btn btn-success btn-sm";
    });
  });
});
