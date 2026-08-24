document.addEventListener('click', (event) => {
  const button = event.target.closest('[data-password-toggle]');
  if (!(button instanceof HTMLButtonElement)) return;
  const control = button.closest('.password-control');
  const input = control?.querySelector('input');
  if (!(input instanceof HTMLInputElement)) return;
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  button.textContent = isHidden ? 'Hide' : 'Show';
  button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
  button.setAttribute('aria-label', `${isHidden ? 'Hide' : 'Show'} password`);
});

document.addEventListener('change', (event) => {
  const field = event.target.closest('[data-auto-submit]');
  if (!(field instanceof HTMLSelectElement) || !(field.form instanceof HTMLFormElement)) return;
  field.form.requestSubmit();
});
