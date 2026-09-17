(function () {
  var rawPath = window.location.pathname.split("/").pop();
  var currentPage = rawPath ? rawPath.toLowerCase() : "index.html";
  if (!currentPage || currentPage === "") currentPage = "index.html";

  var isLoggedIn =
    localStorage.getItem("bbu_logged_in") === "true" ||
    sessionStorage.getItem("bbu_logged_in") === "true";

  var isGuestPage =
    currentPage === "login.html" || currentPage === "registration.html";

  if (!isLoggedIn && !isGuestPage) {
    window.location.replace("login.html");
    return;
  }

  if (isLoggedIn && isGuestPage) {
    window.location.replace("dashboard.html");
    return;
  }
})();

document.addEventListener("DOMContentLoaded", function () {
  var isLoggedIn =
    localStorage.getItem("bbu_logged_in") === "true" ||
    sessionStorage.getItem("bbu_logged_in") === "true";

  function logoutUser() {
    if (
      confirm("Are you sure you want to sign out of the university portal?")
    ) {
      localStorage.removeItem("bbu_logged_in");
      sessionStorage.removeItem("bbu_logged_in");
      localStorage.removeItem("bbu_username");
      sessionStorage.removeItem("bbu_username");
      localStorage.removeItem("bbu_role");
      sessionStorage.removeItem("bbu_role");
      alert(
        "Signed Out Safely! 🔒\nAll secure portal sessions have been terminated.",
      );
      window.location.replace("login.html");
    }
  }

  var logoutTriggers = document.querySelectorAll(
    ".logout-btn, #logout-btn, [data-action='logout']",
  );
  logoutTriggers.forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      logoutUser();
    });
  });

  if (isLoggedIn) {
    var navLoginLinks = document.querySelectorAll('nav a[href="login.html"]');
    navLoginLinks.forEach(function (link) {
      link.innerHTML = "🚪 Log Out";
      link.href = "javascript:void(0)";
      link.style.color = "var(--accent-light)";
      link.addEventListener("click", function (e) {
        e.preventDefault();
        logoutUser();
      });
    });

    var footerLoginLinks = document.querySelectorAll(
      '.footer-links a[href="login.html"]',
    );
    footerLoginLinks.forEach(function (link) {
      link.innerHTML = "Log Out of Portal";
      link.href = "javascript:void(0)";
      link.addEventListener("click", function (e) {
        e.preventDefault();
        logoutUser();
      });
    });
  }

  var prefillUser = sessionStorage.getItem("bbu_prefill_username");
  var prefillPass = sessionStorage.getItem("bbu_prefill_password");
  if (prefillUser && document.getElementById("login-username")) {
    document.getElementById("login-username").value = prefillUser;
    sessionStorage.removeItem("bbu_prefill_username");
  }
  if (prefillPass && document.getElementById("login-password")) {
    document.getElementById("login-password").value = prefillPass;
    sessionStorage.removeItem("bbu_prefill_password");
  }

  var loginForm = document.getElementById("login-form");
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var username = document.getElementById("login-username").value.trim();
      var password = document.getElementById("login-password").value;
      var roleSelect = document.getElementById("login-role");
      var role = roleSelect ? roleSelect.value : "student";
      var rememberCheck = document.getElementById("login-remember");
      var remember = rememberCheck ? rememberCheck.checked : false;

      if (!username || !password) {
        alert("Please enter both your Roll Number/PRN and Password.");
        return;
      }

      var registeredUsers = JSON.parse(
        localStorage.getItem("bbu_registered_users") || "[]",
      );
      var matchedUser = registeredUsers.find(function (u) {
        return (
          u.prn.toLowerCase() === username.toLowerCase() ||
          u.email.toLowerCase() === username.toLowerCase()
        );
      });

      if (matchedUser) {
        if (matchedUser.password !== password) {
          alert(
            "Invalid Credentials! ❌\nIncorrect password for registered account " +
              username,
          );
          return;
        }
        username = matchedUser.name + " (" + matchedUser.prn + ")";
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

      alert(
        "Access Granted! 🔓\nWelcome back, " +
          username +
          " (" +
          role.toUpperCase() +
          ").\nUnlocking student portal dashboard...",
      );
      window.location.replace("dashboard.html");
    });

    var togglePassBtn = document.getElementById("toggle-password-visibility");
    var passInput = document.getElementById("login-password");
    if (togglePassBtn && passInput) {
      togglePassBtn.addEventListener("click", function () {
        var isPass = passInput.getAttribute("type") === "password";
        passInput.setAttribute("type", isPass ? "text" : "password");
        togglePassBtn.textContent = isPass ? "🙈" : "👁️";
        togglePassBtn.setAttribute(
          "aria-label",
          isPass ? "Hide password" : "Show password",
        );
      });
    }

    var forgotPassLink = document.getElementById("forgot-password-link");
    if (forgotPassLink) {
      forgotPassLink.addEventListener("click", function () {
        var studentId = prompt(
          "Enter your registered PRN or University Email to reset password:",
          "",
        );
        if (studentId) {
          alert(
            "Password Reset Dispatched! 📧\nA password reset link has been sent to the email associated with " +
              studentId,
          );
        }
      });
    }
  }

  var regForm = document.getElementById("registration-form");
  if (regForm) {
    var passToggles = document.querySelectorAll(".toggle-pass-visibility-btn");
    passToggles.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var targetId = btn.getAttribute("data-target");
        var targetInput = document.getElementById(targetId);
        if (targetInput) {
          var isPass = targetInput.getAttribute("type") === "password";
          targetInput.setAttribute("type", isPass ? "text" : "password");
          btn.textContent = isPass ? "🙈" : "👁️";
        }
      });
    });

    regForm.addEventListener("submit", function (e) {
      e.preventDefault();

      var role = document.getElementById("reg-role").value;
      var fullname = document.getElementById("reg-fullname").value.trim();
      var prn = document.getElementById("reg-prn").value.trim().toUpperCase();
      var dept = document.getElementById("reg-dept").value;
      var email = document.getElementById("reg-email").value.trim();
      var password = document.getElementById("reg-password").value;
      var confirmPassword = document.getElementById(
        "reg-confirm-password",
      ).value;
      var terms = document.getElementById("reg-terms")
        ? document.getElementById("reg-terms").checked
        : false;

      if (fullname === "") {
        alert("Please enter your full name!");
        document.getElementById("reg-fullname").focus();
        return;
      }

      if (prn === "") {
        alert("Please enter your PRN / Roll Number!");
        document.getElementById("reg-prn").focus();
        return;
      }

      if (!dept || dept === "") {
        alert("Please select your department / branch!");
        document.getElementById("reg-dept").focus();
        return;
      }

      if (email === "") {
        alert("Please enter your email address!");
        document.getElementById("reg-email").focus();
        return;
      }

      var atIndex = email.indexOf("@");
      var dotIndex = email.lastIndexOf(".");
      if (
        atIndex < 1 ||
        dotIndex <= atIndex + 1 ||
        dotIndex >= email.length - 1
      ) {
        alert("Please enter a valid email address (e.g. name@example.com)!");
        document.getElementById("reg-email").focus();
        return;
      }

      if (password === "") {
        alert("Please enter your password!");
        document.getElementById("reg-password").focus();
        return;
      }

      if (password.length < 6) {
        alert("Password must be at least 6 characters long!");
        document.getElementById("reg-password").focus();
        return;
      }

      if (confirmPassword === "") {
        alert("Please confirm your password!");
        document.getElementById("reg-confirm-password").focus();
        return;
      }

      if (password !== confirmPassword) {
        alert(
          "Passwords do not match! Please re-enter your password correctly.",
        );
        document.getElementById("reg-confirm-password").focus();
        return;
      }

      if (!terms) {
        alert("Please agree to the university IT rules and code of conduct!");
        return;
      }

      var registeredUsers = JSON.parse(
        localStorage.getItem("bbu_registered_users") || "[]",
      );
      var exists = registeredUsers.some(function (u) {
        return (
          u.prn.toUpperCase() === prn.toUpperCase() ||
          u.email.toLowerCase() === email.toLowerCase()
        );
      });

      if (exists) {
        alert(
          "An account with PRN " +
            prn +
            " or email " +
            email +
            " already exists! Please sign in.",
        );
        window.location.href = "login.html";
        return;
      }

      registeredUsers.push({
        name: fullname,
        prn: prn,
        dept: dept,
        role: role,
        email: email,
        password: password,
        registeredAt: new Date().toLocaleDateString(),
      });
      localStorage.setItem(
        "bbu_registered_users",
        JSON.stringify(registeredUsers),
      );

      sessionStorage.setItem("bbu_prefill_username", prn);
      sessionStorage.setItem("bbu_prefill_password", password);

      alert(
        "Registration Successful! 🎉\n\nWelcome, " +
          fullname +
          "!\nYour account (" +
          prn +
          ") has been registered.\nRedirecting to login page...",
      );
      window.location.href = "login.html";
    });
  }

  var savedTheme = localStorage.getItem("bbu_theme") || "light";
  var themeToggleBtns = document.querySelectorAll(".theme-toggle-btn");

  function applyTheme(theme) {
    if (theme === "dark") {
      document.documentElement.setAttribute("data-theme", "dark");
      themeToggleBtns.forEach(function (btn) {
        btn.innerHTML = "☀️ <span>Light</span>";
        btn.setAttribute("aria-label", "Switch to light theme");
      });
    } else {
      document.documentElement.removeAttribute("data-theme");
      themeToggleBtns.forEach(function (btn) {
        btn.innerHTML = "🌙 <span>Dark</span>";
        btn.setAttribute("aria-label", "Switch to dark theme");
      });
    }
  }

  applyTheme(savedTheme);

  themeToggleBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var currentTheme =
        document.documentElement.getAttribute("data-theme") === "dark"
          ? "dark"
          : "light";
      var newTheme = currentTheme === "dark" ? "light" : "dark";
      applyTheme(newTheme);
      localStorage.setItem("bbu_theme", newTheme);
    });
  });

  var bannerCloseBtn = document.getElementById("banner-close-btn");
  var topBanner = document.getElementById("top-notification-banner");

  if (bannerCloseBtn && topBanner) {
    bannerCloseBtn.addEventListener("click", function () {
      topBanner.classList.add("dismissed");
      setTimeout(function () {
        topBanner.style.display = "none";
      }, 300);
    });
  }

  var slides = document.querySelectorAll(".slider-container .slide");
  var prevBtn = document.querySelector(".slider-btn-prev");
  var nextBtn = document.querySelector(".slider-btn-next");
  var dots = document.querySelectorAll(".slider-dots .dot");
  var currentSlide = 0;
  var slideInterval = null;

  function showSlide(index) {
    if (slides.length === 0) return;

    if (index >= slides.length) {
      currentSlide = 0;
    } else if (index < 0) {
      currentSlide = slides.length - 1;
    } else {
      currentSlide = index;
    }

    slides.forEach(function (slide, i) {
      slide.classList.toggle("active", i === currentSlide);
    });

    dots.forEach(function (dot, i) {
      dot.classList.toggle("active", i === currentSlide);
    });
  }

  if (slides.length > 0) {
    if (nextBtn) {
      nextBtn.addEventListener("click", function () {
        showSlide(currentSlide + 1);
        resetAutoSlide();
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener("click", function () {
        showSlide(currentSlide - 1);
        resetAutoSlide();
      });
    }

    dots.forEach(function (dot, idx) {
      dot.addEventListener("click", function () {
        showSlide(idx);
        resetAutoSlide();
      });
    });

    function startAutoSlide() {
      slideInterval = setInterval(function () {
        showSlide(currentSlide + 1);
      }, 5000);
    }

    function resetAutoSlide() {
      clearInterval(slideInterval);
      startAutoSlide();
    }

    startAutoSlide();
  }

  var faqQuestions = document.querySelectorAll(".faq-question");

  faqQuestions.forEach(function (questionBtn) {
    questionBtn.addEventListener("click", function () {
      var faqItem = questionBtn.closest(".faq-item");
      if (!faqItem) return;

      var isActive = faqItem.classList.contains("active");

      var allItems = document.querySelectorAll(".faq-item");
      allItems.forEach(function (item) {
        if (item !== faqItem) {
          item.classList.remove("active");
          var btn = item.querySelector(".faq-question");
          if (btn) btn.setAttribute("aria-expanded", "false");
        }
      });

      if (isActive) {
        faqItem.classList.remove("active");
        questionBtn.setAttribute("aria-expanded", "false");
      } else {
        faqItem.classList.add("active");
        questionBtn.setAttribute("aria-expanded", "true");
      }
    });
  });

  var navToggleBtn = document.getElementById("nav-toggle");
  var navMenu = document.getElementById("nav-menu");

  if (navToggleBtn && navMenu) {
    navToggleBtn.addEventListener("click", function () {
      var isOpen = navMenu.classList.toggle("open");
      navToggleBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    document.addEventListener("click", function (event) {
      if (
        !navToggleBtn.contains(event.target) &&
        !navMenu.contains(event.target)
      ) {
        navMenu.classList.remove("open");
        navToggleBtn.setAttribute("aria-expanded", "false");
      }
    });
  }

  var dropdowns = document.querySelectorAll(".nav-dropdown");
  dropdowns.forEach(function (dropdown) {
    var btn = dropdown.querySelector(".nav-dropdown-btn");
    if (btn) {
      btn.addEventListener("click", function (e) {
        e.stopPropagation();
        dropdown.classList.toggle("open");
      });
    }
  });

  document.addEventListener("click", function () {
    dropdowns.forEach(function (dd) {
      dd.classList.remove("open");
    });
  });

  var currentPage = window.location.pathname.split("/").pop();
  if (!currentPage || currentPage === "") {
    currentPage = "index.html";
  }

  var navLinks = document.querySelectorAll(".nav-links a");
  navLinks.forEach(function (link) {
    var href = link.getAttribute("href");
    if (href === currentPage) {
      link.classList.add("active");
      var parentDropdown = link.closest(".nav-dropdown");
      if (parentDropdown) {
        var ddBtn = parentDropdown.querySelector(".nav-dropdown-btn");
        if (ddBtn) {
          ddBtn.style.color = "var(--accent-light)";
          ddBtn.style.fontWeight = "700";
        }
      }
    }
  });

  var modalTriggers = document.querySelectorAll("[data-modal-target]");
  modalTriggers.forEach(function (trigger) {
    trigger.addEventListener("click", function (e) {
      e.preventDefault();
      var targetId = trigger.getAttribute("data-modal-target");
      var modal = document.getElementById(targetId);
      if (modal) {
        var eventTitle = trigger.getAttribute("data-event-title");
        if (eventTitle) {
          var titleElem = modal.querySelector(".modal-dynamic-title");
          if (titleElem) titleElem.textContent = eventTitle;
        }
        modal.classList.add("active");
        document.body.style.overflow = "hidden";
      }
    });
  });

  var modals = document.querySelectorAll(".modal-overlay");
  modals.forEach(function (modal) {
    var closeButtons = modal.querySelectorAll(
      ".modal-close-btn, [data-modal-close]",
    );
    closeButtons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        modal.classList.remove("active");
        document.body.style.overflow = "";
      });
    });

    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        modal.classList.remove("active");
        document.body.style.overflow = "";
      }
    });
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      modals.forEach(function (m) {
        m.classList.remove("active");
      });
      document.body.style.overflow = "";
    }
  });

  var filterButtons = document.querySelectorAll(".filter-btn");
  var eventCards = document.querySelectorAll(".event-card");

  if (filterButtons.length > 0 && eventCards.length > 0) {
    filterButtons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        filterButtons.forEach(function (b) {
          b.classList.remove("active");
        });
        btn.classList.add("active");

        var filter = btn.getAttribute("data-category");
        eventCards.forEach(function (card) {
          var cardCategory = card.getAttribute("data-category");
          if (filter === "all" || cardCategory === filter) {
            card.style.display = "flex";
          } else {
            card.style.display = "none";
          }
        });
      });
    });
  }

  var eventModalForm = document.getElementById("event-modal-form");
  if (eventModalForm) {
    eventModalForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var studentName = document.getElementById("modal-student-name").value;
      var eventName = document.querySelector(".modal-dynamic-title")
        ? document.querySelector(".modal-dynamic-title").textContent
        : "the event";
      alert(
        "Registration Confirmed! 🎉\nThank you, " +
          studentName +
          ". You are registered for " +
          eventName +
          ".\nA confirmation email with your entry pass has been sent.",
      );
      eventModalForm.reset();
      var modal = eventModalForm.closest(".modal-overlay");
      if (modal) modal.classList.remove("active");
      document.body.style.overflow = "";
    });
  }

  var starGroups = document.querySelectorAll(".star-rating");
  starGroups.forEach(function (group) {
    var stars = group.querySelectorAll("span");
    var ratingInput = group.parentElement.querySelector("input[type='hidden']");

    stars.forEach(function (star, index) {
      star.addEventListener("click", function () {
        var selectedValue = index + 1;
        if (ratingInput) ratingInput.value = selectedValue;

        stars.forEach(function (s, i) {
          if (i <= index) {
            s.style.color = "var(--accent-light)";
          } else {
            s.style.color = "#cbd5e1";
          }
        });
      });
    });
  });

  var feedbackForm = document.getElementById("student-feedback-form");
  if (feedbackForm) {
    feedbackForm.addEventListener("submit", function (e) {
      e.preventDefault();
      alert(
        "Feedback Received! 🙏\nThank you for sharing your thoughts. Your feedback has been forwarded to the Academic Committee with Ticket #" +
          Math.floor(1000 + Math.random() * 9000),
      );
      feedbackForm.reset();
      document.querySelectorAll(".star-rating span").forEach(function (s) {
        s.style.color = "#cbd5e1";
      });
    });
  }

  var contactForm = document.getElementById("contact-inquiry-form");
  if (contactForm) {
    contactForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var name = document.getElementById("contact-name").value;
      alert(
        "Inquiry Sent! 📬\nThank you, " +
          name +
          ". The admissions & helpdesk team will respond within 24 business hours.",
      );
      contactForm.reset();
    });
  }

  var payFeeBtn = document.getElementById("pay-fee-btn");
  if (payFeeBtn) {
    payFeeBtn.addEventListener("click", function () {
      var confirmPay = confirm(
        "Confirm payment of pending semester fees: ₹12,500?",
      );
      if (confirmPay) {
        var pendingFeeElems = document.querySelectorAll(
          ".pending-fee-val, #pending-fee-val",
        );
        pendingFeeElems.forEach(function (elem) {
          elem.innerText = "₹0";
          elem.style.color = "var(--success)";
        });
        var feeBadges = document.querySelectorAll(
          ".fee-status-badge, #hostel-fee-status",
        );
        feeBadges.forEach(function (b) {
          b.className = "badge badge-success";
          b.textContent = "PAID";
        });
        alert(
          "Payment Successful! E-Receipt generated: TXN-" +
            Math.floor(10000000 + Math.random() * 90000000),
        );
      }
    });
  }

  var bookSearchInput = document.getElementById("book-search");
  if (bookSearchInput) {
    bookSearchInput.addEventListener("keyup", function () {
      var filter = bookSearchInput.value.toLowerCase();
      var rows = document.querySelectorAll("#issued-books-table tbody tr");
      rows.forEach(function (row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.indexOf(filter) > -1 ? "" : "none";
      });
    });
  }

  var uploadForm = document.getElementById("assignment-form");
  if (uploadForm) {
    uploadForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var subjectElem = document.getElementById("select-subject");
      var subject = subjectElem ? subjectElem.value : "Assignment";
      var fileInput = document.getElementById("file-upload");
      if (fileInput && fileInput.files.length === 0) {
        alert("Please select a file to upload!");
        return;
      }
      var fileName =
        fileInput && fileInput.files.length > 0
          ? fileInput.files[0].name
          : "document.pdf";
      alert(
        "Success! " + subject + " uploaded successfully.\nFile: " + fileName,
      );
      if (fileInput) fileInput.value = "";
    });
  }

  var passwordForm = document.getElementById("password-form");
  if (passwordForm) {
    passwordForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var currentPass = document.getElementById("current-pass").value;
      var newPass = document.getElementById("new-pass").value;
      var confirmPass = document.getElementById("confirm-pass").value;

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

  var profileForm = document.getElementById("profile-settings-form");
  if (profileForm) {
    profileForm.addEventListener("submit", function (e) {
      e.preventDefault();
      alert("Profile settings saved successfully!");
    });
  }

  var resumeForm = document.getElementById("resume-form");
  if (resumeForm) {
    resumeForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var fileInput = document.getElementById("resume-file");
      if (!fileInput || fileInput.files.length === 0) {
        alert("Please select a PDF resume file!");
        return;
      }
      alert("Resume uploaded successfully: " + fileInput.files[0].name);
      fileInput.value = "";
    });
  }

  var applyBtns = document.querySelectorAll(".apply-btn");
  applyBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      alert("Application submitted successfully!");
      btn.innerText = "Applied ✓";
      btn.disabled = true;
      btn.className = "btn btn-success btn-sm";
    });
  });
});
