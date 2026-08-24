document.addEventListener('submit', (event) => {
  const form = event.target;
  if (!(form instanceof HTMLFormElement) || form.dataset.confirm !== 'delete') return;
  if (!window.confirm('Delete this item? This action cannot be undone.')) event.preventDefault();
});

document.addEventListener('click', (event) => {
  const trigger = event.target.closest('[data-edit-product]');
  const modal = document.querySelector('[data-product-edit-modal]');
  if (!(trigger instanceof HTMLButtonElement) || !(modal instanceof HTMLDialogElement)) return;
  const setValue = (field, value) => {
    const input = modal.querySelector(`[data-modal-field="${field}"]`);
    if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement || input instanceof HTMLSelectElement) input.value = value || '';
  };
  setValue('id', trigger.dataset.productId);
  setValue('name', trigger.dataset.productName);
  setValue('category', trigger.dataset.productCategory);
  setValue('price', trigger.dataset.productPrice);
  setValue('quantity', trigger.dataset.productQuantity);
  setValue('reorder', trigger.dataset.productReorder);
  setValue('description', trigger.dataset.productDescription);
  setValue('status', trigger.dataset.productStatus);
  const label = modal.querySelector('[data-modal-product-label]');
  if (label) label.textContent = `Product ID #${trigger.dataset.productId || ''} · ${trigger.dataset.productName || ''}`;
  modal.showModal();
  const name = modal.querySelector('[data-modal-field="name"]');
  if (name instanceof HTMLInputElement) name.focus();
});

document.addEventListener('click', (event) => {
  const close = event.target.closest('[data-close-product-modal]');
  const modal = document.querySelector('[data-product-edit-modal]');
  if (close && modal instanceof HTMLDialogElement) modal.close();
});
