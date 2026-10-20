/**
 * Practical 10: Visible Inactivity Countdown Timer (Client-Side)
 * The Backbenchers University - StudentHub
 * 
 * Features:
 * - Real-time countdown in MM:SS format.
 * - Starts from server-synchronized remaining seconds.
 * - Detects genuine user activity (click, keydown, touch).
 * - Throttles keep-alive pings to server (at most once every 15 seconds).
 * - Alerts and redirects to login when 15 minutes of inactivity elapse.
 */
document.addEventListener("DOMContentLoaded", () => {
  const clockEl = document.getElementById("session-timer-clock");
  const allClocks = document.querySelectorAll("#session-timer-clock, .session-timer-clock");
  if (!clockEl && allClocks.length === 0) return;

  // 1. Read initial remaining seconds from the server attribute
  let initialRemaining = clockEl ? parseInt(clockEl.getAttribute("data-remaining"), 10) : 900;
  if (isNaN(initialRemaining) || initialRemaining < 0) {
    initialRemaining = 900; // Default 15 minutes = 900 seconds
  }

  let remainingSeconds = initialRemaining;
  let timerInterval = null;
  let lastPingTime = Date.now();
  const PING_THROTTLE_MS = 15000; // Throttle server ping to at most once every 15s

  function formatTime(totalSec) {
    const mins = Math.floor(totalSec / 60);
    const secs = totalSec % 60;
    return String(mins).padStart(2, "0") + ":" + String(secs).padStart(2, "0");
  }

  function updateClockDisplay() {
    const formatted = formatTime(Math.max(0, remainingSeconds));
    allClocks.forEach(el => {
      el.textContent = formatted;
      if (remainingSeconds <= 60) {
        el.style.color = "#ef4444"; // Urgent red: less than 1 min
        el.style.fontWeight = "bold";
      } else if (remainingSeconds <= 180) {
        el.style.color = "#f59e0b"; // Warning amber: less than 3 mins
        el.style.fontWeight = "bold";
      } else {
        el.style.color = "";
        el.style.fontWeight = "";
      }
    });
  }

  function handleSessionExpiry() {
    if (timerInterval) clearInterval(timerInterval);
    allClocks.forEach(el => { el.textContent = "00:00"; });
    alert("Your session has expired. Please log in again.");
    window.location.href = "login.html?msg=timeout";
  }

  // 2. Countdown loop: ticks down every second
  function tick() {
    remainingSeconds--;
    if (remainingSeconds <= 0) {
      handleSessionExpiry();
      return;
    }
    updateClockDisplay();
  }

  updateClockDisplay();
  timerInterval = setInterval(tick, 1000);

  // 3. User interaction listener to refresh server session deadline
  function recordUserActivity() {
    const now = Date.now();
    // Do not ping on every mouse pixel or keystroke; throttle to 15s
    if (now - lastPingTime < PING_THROTTLE_MS) return;
    lastPingTime = now;

    fetch("php/session_timer.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "action=reset"
    })
      .then(res => res.json())
      .then(data => {
        if (data.status === "active") {
          remainingSeconds = data.remaining;
          updateClockDisplay();
        } else if (data.status === "expired") {
          handleSessionExpiry();
        }
      })
      .catch(() => {
        // Network failure fallback: client countdown continues locally
      });
  }

  // Listen for genuine user input
  const activityEvents = ["click", "keydown", "mousedown", "touchstart"];
  activityEvents.forEach(evt => {
    window.addEventListener(evt, recordUserActivity, { passive: true });
  });
});
