(() => {
  const page = (window.location.pathname.split("/").pop() || "index.html").toLowerCase();
  const isLoggedIn =
    localStorage.getItem("bbu_logged_in") === "true" ||
    sessionStorage.getItem("bbu_logged_in") === "true";
  const isGuestPage = page === "login.html" || page === "registration.html";

  if (!isLoggedIn && !isGuestPage) {
    window.location.replace("login.html");
  } else if (isLoggedIn && isGuestPage) {
    window.location.replace("dashboard.html");
  }
})();

document.addEventListener("DOMContentLoaded", () => {
  const AUTH_KEYS = ["bbu_logged_in", "bbu_username", "bbu_role"];
  const getIsLoggedIn = () =>
    localStorage.getItem("bbu_logged_in") === "true" ||
    sessionStorage.getItem("bbu_logged_in") === "true";

  const clearAuth = () => {
    AUTH_KEYS.forEach((key) => {
      localStorage.removeItem(key);
      sessionStorage.removeItem(key);
    });
  };

  const logoutUser = () => {
    if (confirm("Are you sure you want to sign out of the university portal?")) {
      clearAuth();
      alert("Signed Out Safely! 🔒\nAll secure portal sessions have been terminated.");
      window.location.replace("login.html");
    }
  };

  document.querySelectorAll(".logout-btn, #logout-btn, [data-action='logout']").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      logoutUser();
    });
  });

  if (getIsLoggedIn()) {
    document.querySelectorAll('nav a[href="login.html"]').forEach((link) => {
      link.innerHTML = "🚪 Log Out";
      link.href = "javascript:void(0)";
      link.style.color = "var(--accent-light)";
      link.addEventListener("click", (e) => {
        e.preventDefault();
        logoutUser();
      });
    });

    document.querySelectorAll('.footer-links a[href="login.html"]').forEach((link) => {
      link.innerHTML = "Log Out of Portal";
      link.href = "javascript:void(0)";
      link.addEventListener("click", (e) => {
        e.preventDefault();
        logoutUser();
      });
    });
  }

  const loginUser = document.getElementById("login-username");
  const loginPass = document.getElementById("login-password");
  const prefillUser = sessionStorage.getItem("bbu_prefill_username");
  const prefillPass = sessionStorage.getItem("bbu_prefill_password");

  if (prefillUser && loginUser) {
    loginUser.value = prefillUser;
    sessionStorage.removeItem("bbu_prefill_username");
  }
  if (prefillPass && loginPass) {
    loginPass.value = prefillPass;
    sessionStorage.removeItem("bbu_prefill_password");
  }

  const loginForm = document.getElementById("login-form");
  if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
      e.preventDefault();
      let username = loginUser.value.trim();
      const password = loginPass.value;
      const roleSelect = document.getElementById("login-role");
      let role = roleSelect ? roleSelect.value : "student";
      const remember = document.getElementById("login-remember")?.checked || false;

      if (!username || !password) {
        alert("Please enter both your Roll Number/PRN and Password.");
        return;
      }

      const registeredUsers = JSON.parse(localStorage.getItem("bbu_registered_users") || "[]");
      const matchedUser = registeredUsers.find(
        (u) =>
          u.prn.toLowerCase() === username.toLowerCase() ||
          u.email.toLowerCase() === username.toLowerCase()
      );

      if (matchedUser) {
        if (matchedUser.password !== password) {
          alert(`Invalid Credentials! ❌\nIncorrect password for registered account ${username}`);
          return;
        }
        username = `${matchedUser.name} (${matchedUser.prn})`;
        role = matchedUser.role || role;
      }

      sessionStorage.setItem("bbu_logged_in", "true");
      sessionStorage.setItem("bbu_username", username);
      sessionStorage.setItem("bbu_role", role);

      if (remember) {
        localStorage.setItem("bbu_logged_in", "true");
        localStorage.setItem("bbu_username", username);
        localStorage.setItem("bbu_role", role);
      }

      alert(`Access Granted! 🔓\nWelcome back, ${username} (${role.toUpperCase()}).\nUnlocking student portal dashboard...`);
      window.location.replace("dashboard.html");
    });

    const togglePassBtn = document.getElementById("toggle-password-visibility");
    if (togglePassBtn && loginPass) {
      togglePassBtn.addEventListener("click", () => {
        const isPass = loginPass.getAttribute("type") === "password";
        loginPass.setAttribute("type", isPass ? "text" : "password");
        togglePassBtn.textContent = isPass ? "🙈" : "👁️";
        togglePassBtn.setAttribute("aria-label", isPass ? "Hide password" : "Show password");
      });
    }

    const forgotPassLink = document.getElementById("forgot-password-link");
    if (forgotPassLink) {
      forgotPassLink.addEventListener("click", () => {
        const studentId = prompt("Enter your registered PRN or University Email to reset password:", "");
        if (studentId) {
          alert(`Password Reset Dispatched! 📧\nA password reset link has been sent to the email associated with ${studentId}`);
        }
      });
    }
  }

  const regForm = document.getElementById("registration-form");
  if (regForm) {
    document.querySelectorAll(".toggle-pass-visibility-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const targetInput = document.getElementById(btn.getAttribute("data-target"));
        if (targetInput) {
          const isPass = targetInput.getAttribute("type") === "password";
          targetInput.setAttribute("type", isPass ? "text" : "password");
          btn.textContent = isPass ? "🙈" : "👁️";
        }
      });
    });

    regForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const role = document.getElementById("reg-role").value;
      const fullname = document.getElementById("reg-fullname").value.trim();
      const prn = document.getElementById("reg-prn").value.trim().toUpperCase();
      const dept = document.getElementById("reg-dept").value;
      const email = document.getElementById("reg-email").value.trim();
      const password = document.getElementById("reg-password").value;
      const confirmPassword = document.getElementById("reg-confirm-password").value;
      const terms = document.getElementById("reg-terms")?.checked || false;

      const validateField = (condition, message, elementId) => {
        if (condition) {
          alert(message);
          if (elementId) document.getElementById(elementId).focus();
          return false;
        }
        return true;
      };

      if (!validateField(!fullname, "Please enter your full name!", "reg-fullname")) return;
      if (!validateField(!prn, "Please enter your PRN / Roll Number!", "reg-prn")) return;
      if (!validateField(!dept, "Please select your department / branch!", "reg-dept")) return;
      if (!validateField(!email, "Please enter your email address!", "reg-email")) return;

      const atIndex = email.indexOf("@");
      const dotIndex = email.lastIndexOf(".");
      if (
        !validateField(
          atIndex < 1 || dotIndex <= atIndex + 1 || dotIndex >= email.length - 1,
          "Please enter a valid email address (e.g. name@example.com)!",
          "reg-email"
        )
      ) {
        return;
      }

      if (!validateField(!password, "Please enter your password!", "reg-password")) return;
      if (!validateField(password.length < 6, "Password must be at least 6 characters long!", "reg-password")) return;
      if (!validateField(!confirmPassword, "Please confirm your password!", "reg-confirm-password")) return;
      if (!validateField(password !== confirmPassword, "Passwords do not match! Please re-enter your password correctly.", "reg-confirm-password")) return;
      if (!validateField(!terms, "Please agree to the university IT rules and code of conduct!")) return;

      const registeredUsers = JSON.parse(localStorage.getItem("bbu_registered_users") || "[]");
      const exists = registeredUsers.some(
        (u) => u.prn.toUpperCase() === prn || u.email.toLowerCase() === email.toLowerCase()
      );

      if (exists) {
        alert(`An account with PRN ${prn} or email ${email} already exists! Please sign in.`);
        window.location.href = "login.html";
        return;
      }

      registeredUsers.push({
        name: fullname,
        prn,
        dept,
        role,
        email,
        password,
        registeredAt: new Date().toLocaleDateString(),
      });
      localStorage.setItem("bbu_registered_users", JSON.stringify(registeredUsers));

      sessionStorage.setItem("bbu_prefill_username", prn);
      sessionStorage.setItem("bbu_prefill_password", password);

      alert(`Registration Successful! 🎉\n\nWelcome, ${fullname}!\nYour account (${prn}) has been registered.\nRedirecting to login page...`);
      window.location.href = "login.html";
    });
  }

  const themeToggleBtns = document.querySelectorAll(".theme-toggle-btn");
  const applyTheme = (theme) => {
    const isDark = theme === "dark";
    if (isDark) {
      document.documentElement.setAttribute("data-theme", "dark");
    } else {
      document.documentElement.removeAttribute("data-theme");
    }
    themeToggleBtns.forEach((btn) => {
      btn.innerHTML = isDark ? "☀️ <span>Light</span>" : "🌙 <span>Dark</span>";
      btn.setAttribute("aria-label", isDark ? "Switch to light theme" : "Switch to dark theme");
    });
  };

  applyTheme(localStorage.getItem("bbu_theme") || "light");

  themeToggleBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const nextTheme = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
      applyTheme(nextTheme);
      localStorage.setItem("bbu_theme", nextTheme);
    });
  });

  const bannerCloseBtn = document.getElementById("banner-close-btn");
  const topBanner = document.getElementById("top-notification-banner");
  if (bannerCloseBtn && topBanner) {
    bannerCloseBtn.addEventListener("click", () => {
      topBanner.classList.add("dismissed");
      setTimeout(() => {
        topBanner.style.display = "none";
      }, 300);
    });
  }

  const slides = document.querySelectorAll(".slider-container .slide");
  const dots = document.querySelectorAll(".slider-dots .dot");
  if (slides.length > 0) {
    let currentSlide = 0;
    let slideInterval = null;

    const showSlide = (index) => {
      currentSlide = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => slide.classList.toggle("active", i === currentSlide));
      dots.forEach((dot, i) => dot.classList.toggle("active", i === currentSlide));
    };

    const startAutoSlide = () => {
      slideInterval = setInterval(() => showSlide(currentSlide + 1), 5000);
    };

    const resetAutoSlide = () => {
      clearInterval(slideInterval);
      startAutoSlide();
    };

    document.querySelector(".slider-btn-next")?.addEventListener("click", () => {
      showSlide(currentSlide + 1);
      resetAutoSlide();
    });

    document.querySelector(".slider-btn-prev")?.addEventListener("click", () => {
      showSlide(currentSlide - 1);
      resetAutoSlide();
    });

    dots.forEach((dot, idx) => {
      dot.addEventListener("click", () => {
        showSlide(idx);
        resetAutoSlide();
      });
    });

    startAutoSlide();
  }

  const faqQuestions = document.querySelectorAll(".faq-question");
  faqQuestions.forEach((questionBtn) => {
    questionBtn.addEventListener("click", () => {
      const faqItem = questionBtn.closest(".faq-item");
      if (!faqItem) return;

      const willOpen = !faqItem.classList.contains("active");
      document.querySelectorAll(".faq-item").forEach((item) => {
        item.classList.remove("active");
        item.querySelector(".faq-question")?.setAttribute("aria-expanded", "false");
      });

      if (willOpen) {
        faqItem.classList.add("active");
        questionBtn.setAttribute("aria-expanded", "true");
      }
    });
  });

  const navToggleBtn = document.getElementById("nav-toggle");
  const navMenu = document.getElementById("nav-menu");
  if (navToggleBtn && navMenu) {
    navToggleBtn.addEventListener("click", () => {
      const isOpen = navMenu.classList.toggle("open");
      navToggleBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    document.addEventListener("click", (e) => {
      if (!navToggleBtn.contains(e.target) && !navMenu.contains(e.target)) {
        navMenu.classList.remove("open");
        navToggleBtn.setAttribute("aria-expanded", "false");
      }
    });
  }

  const dropdowns = document.querySelectorAll(".nav-dropdown");
  dropdowns.forEach((dropdown) => {
    dropdown.querySelector(".nav-dropdown-btn")?.addEventListener("click", (e) => {
      e.stopPropagation();
      dropdown.classList.toggle("open");
    });
  });

  document.addEventListener("click", () => {
    dropdowns.forEach((dd) => dd.classList.remove("open"));
  });

  const activePage = window.location.pathname.split("/").pop() || "index.html";
  document.querySelectorAll(".nav-links a").forEach((link) => {
    if (link.getAttribute("href") === activePage) {
      link.classList.add("active");
      const parentDropdown = link.closest(".nav-dropdown");
      if (parentDropdown) {
        const ddBtn = parentDropdown.querySelector(".nav-dropdown-btn");
        if (ddBtn) {
          ddBtn.style.color = "var(--accent-light)";
          ddBtn.style.fontWeight = "700";
        }
      }
    }
  });

  document.querySelectorAll("[data-modal-target]").forEach((trigger) => {
    trigger.addEventListener("click", (e) => {
      e.preventDefault();
      const modal = document.getElementById(trigger.getAttribute("data-modal-target"));
      if (modal) {
        const eventTitle = trigger.getAttribute("data-event-title");
        if (eventTitle) {
          const titleElem = modal.querySelector(".modal-dynamic-title");
          if (titleElem) titleElem.textContent = eventTitle;
        }
        modal.classList.add("active");
        document.body.style.overflow = "hidden";
      }
    });
  });

  const modals = document.querySelectorAll(".modal-overlay");
  const closeModal = (m) => {
    m.classList.remove("active");
    document.body.style.overflow = "";
  };

  modals.forEach((modal) => {
    modal.querySelectorAll(".modal-close-btn, [data-modal-close]").forEach((btn) => {
      btn.addEventListener("click", () => closeModal(modal));
    });

    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal(modal);
    });
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      modals.forEach(closeModal);
    }
  });

  const filterButtons = document.querySelectorAll(".filter-btn");
  const eventCards = document.querySelectorAll(".event-card");
  if (filterButtons.length && eventCards.length) {
    filterButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        filterButtons.forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        const filter = btn.getAttribute("data-category");
        eventCards.forEach((card) => {
          const cardCategory = card.getAttribute("data-category");
          card.style.display = filter === "all" || cardCategory === filter ? "flex" : "none";
        });
      });
    });
  }

  const eventModalForm = document.getElementById("event-modal-form");
  if (eventModalForm) {
    eventModalForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const studentName = document.getElementById("modal-student-name").value;
      const titleElem = document.querySelector(".modal-dynamic-title");
      const eventName = titleElem ? titleElem.textContent : "the event";
      alert(`Registration Confirmed! 🎉\nThank you, ${studentName}. You are registered for ${eventName}.\nA confirmation email with your entry pass has been sent.`);
      eventModalForm.reset();
      const modal = eventModalForm.closest(".modal-overlay");
      if (modal) closeModal(modal);
    });
  }

  document.querySelectorAll(".star-rating").forEach((group) => {
    const stars = group.querySelectorAll("span");
    const ratingInput = group.parentElement.querySelector("input[type='hidden']");
    stars.forEach((star, index) => {
      star.addEventListener("click", () => {
        if (ratingInput) ratingInput.value = index + 1;
        stars.forEach((s, i) => {
          s.style.color = i <= index ? "var(--accent-light)" : "#cbd5e1";
        });
      });
    });
  });

  const feedbackForm = document.getElementById("student-feedback-form");
  if (feedbackForm) {
    feedbackForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const ticketNum = Math.floor(1000 + Math.random() * 9000);
      alert(`Feedback Received! 🙏\nThank you for sharing your thoughts. Your feedback has been forwarded to the Academic Committee with Ticket #${ticketNum}`);
      feedbackForm.reset();
      document.querySelectorAll(".star-rating span").forEach((s) => {
        s.style.color = "#cbd5e1";
      });
    });
  }

  const contactForm = document.getElementById("contact-inquiry-form");
  if (contactForm) {
    contactForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const name = document.getElementById("contact-name").value;
      alert(`Inquiry Sent! 📬\nThank you, ${name}. The admissions & helpdesk team will respond within 24 business hours.`);
      contactForm.reset();
    });
  }

  const payFeeBtn = document.getElementById("pay-fee-btn");
  if (payFeeBtn) {
    payFeeBtn.addEventListener("click", () => {
      if (confirm("Confirm payment of pending semester fees: ₹12,500?")) {
        document.querySelectorAll(".pending-fee-val, #pending-fee-val").forEach((elem) => {
          elem.innerText = "₹0";
          elem.style.color = "var(--success)";
        });
        document.querySelectorAll(".fee-status-badge, #hostel-fee-status").forEach((b) => {
          b.className = "badge badge-success";
          b.textContent = "PAID";
        });
        const txnId = Math.floor(10000000 + Math.random() * 90000000);
        alert(`Payment Successful! E-Receipt generated: TXN-${txnId}`);
      }
    });
  }

  const bookSearchInput = document.getElementById("book-search");
  if (bookSearchInput) {
    bookSearchInput.addEventListener("keyup", () => {
      const filter = bookSearchInput.value.toLowerCase();
      document.querySelectorAll("#issued-books-table tbody tr").forEach((row) => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
      });
    });
  }

  const uploadForm = document.getElementById("assignment-form");
  if (uploadForm) {
    uploadForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const subject = document.getElementById("select-subject")?.value || "Assignment";
      const fileInput = document.getElementById("file-upload");
      if (fileInput && fileInput.files.length === 0) {
        alert("Please select a file to upload!");
        return;
      }
      const fileName = fileInput?.files.length ? fileInput.files[0].name : "document.pdf";
      alert(`Success! ${subject} uploaded successfully.\nFile: ${fileName}`);
      if (fileInput) fileInput.value = "";
    });
  }

  const passwordForm = document.getElementById("password-form");
  if (passwordForm) {
    passwordForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const currentPass = document.getElementById("current-pass").value;
      const newPass = document.getElementById("new-pass").value;
      const confirmPass = document.getElementById("confirm-pass").value;

      if (!currentPass || !newPass || !confirmPass) {
        alert("Please fill in all password fields!");
        return;
      }
      if (newPass !== confirmPass) {
        alert("Error: New password and confirm password do not match!");
        return;
      }
      alert("Success: Password updated successfully!");
      passwordForm.reset();
    });
  }

  const profileForm = document.getElementById("profile-settings-form");
  if (profileForm) {
    profileForm.addEventListener("submit", (e) => {
      e.preventDefault();
      alert("Profile settings saved successfully!");
    });
  }

  const resumeForm = document.getElementById("resume-form");
  if (resumeForm) {
    resumeForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const fileInput = document.getElementById("resume-file");
      if (!fileInput || fileInput.files.length === 0) {
        alert("Please select a PDF resume file!");
        return;
      }
      alert(`Resume uploaded successfully: ${fileInput.files[0].name}`);
      fileInput.value = "";
    });
  }

  document.querySelectorAll(".apply-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      alert("Application submitted successfully!");
      btn.innerText = "Applied ✓";
      btn.disabled = true;
      btn.className = "btn btn-success btn-sm";
    });
  });
});
