(() => {
  const buttons = document.querySelectorAll('[data-otp-resend]');

  function formatRemaining(totalSeconds) {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = String(totalSeconds % 60).padStart(2, '0');
    return `${minutes}:${seconds}`;
  }

  buttons.forEach((button) => {
    let seconds = Math.max(0, Number(button.dataset.resendSeconds || 0));

    const draw = () => {
      const label = button.querySelector('[data-otp-countdown]');
      if (label) label.textContent = formatRemaining(seconds);
      button.disabled = seconds > 0;
      button.classList.toggle('is-waiting', seconds > 0);
      button.classList.toggle('is-ready', seconds <= 0);
      button.setAttribute('aria-disabled', seconds > 0 ? 'true' : 'false');
      if (seconds <= 0) {
        button.textContent = 'Resend code';
        button.disabled = false;
      }
    };

    draw();
    if (!seconds) return;
    const timer = window.setInterval(() => {
      seconds -= 1;
      draw();
      if (seconds <= 0) window.clearInterval(timer);
    }, 1000);
  });
})();
