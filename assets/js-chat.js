(function () {
  const root = document.querySelector('[data-grok-chat]');
  if (!root) return;
  const panel = root.querySelector('.grok-panel');
  const toggle = root.querySelector('.grok-toggle');
  const close = root.querySelector('.grok-close');
  const messagesEl = root.querySelector('.grok-messages');
  const form = root.querySelector('.grok-form');
  const input = root.querySelector('.grok-input');
  const send = root.querySelector('.grok-send');
  const quickReplies = Array.from(root.querySelectorAll('.grok-quick-replies [data-prompt]'));
  const endpoint = root.dataset.endpoint;
  const mode = root.dataset.mode || 'customer';
  const assistantName = mode === 'admin' ? 'MobiSaathi Admin' : 'MobiSaathi';
  const history = [];
  let selectedProduct = null;

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (char) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[char]);
  }

  function containsUrduScript(value) {
    return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]/.test(String(value));
  }

  function applyTextDirection(element, value) {
    const isRtl = containsUrduScript(value);
    element.dir = isRtl ? 'rtl' : 'ltr';
    element.classList.toggle('rtl', isRtl);
  }

  function pageProducts() {
    return Array.from(document.querySelectorAll('[data-product-card]'))
      .map((card) => ({
        id: Number(card.dataset.productId || 0),
        name: String(card.dataset.productName || '').trim()
      }))
      .filter((product) => product.id > 0 && product.name)
      .slice(0, 24);
  }

  function productFromMessage(text) {
    const normalized = String(text).toLocaleLowerCase();
    return pageProducts().find((product) => normalized.includes(product.name.toLocaleLowerCase())) || null;
  }

  function currentProductContext(text) {
    const mentioned = productFromMessage(text);
    if (mentioned) selectedProduct = mentioned;
    return {
      page: window.location.pathname,
      selected_product_id: selectedProduct ? selectedProduct.id : null,
      selected_product_name: selectedProduct ? selectedProduct.name : null,
      products: pageProducts()
    };
  }

  function linkifyShopContact(text) {
    const whatsappLinkToken = '__MOBISAATHI_WHATSAPP_LINK__';
    const withToken = text.replace(/https:\/\/wa\.me\/923096707786\b/g, whatsappLinkToken);
    const withNumberLink = withToken.replace(
      /(^|[\s(])(?:\+92\s?309\s?6707786|0092\s?309\s?6707786|0309\s?6707786)\b/g,
      '$1<a href="https://wa.me/923096707786" target="_blank" rel="noopener noreferrer">03096707786</a>'
    );
    return withNumberLink.replace(
      new RegExp(whatsappLinkToken, 'g'),
      '<a href="https://wa.me/923096707786" target="_blank" rel="noopener noreferrer">WhatsApp 03096707786</a>'
    );
  }

  // Small, safe Markdown-style renderer: no raw HTML is ever accepted from the AI.
  function formatReply(value) {
    const escaped = escapeHtml(String(value).trim());
    const lines = escaped.split(/\r?\n/);
    const out = [];
    let list = [];

    const inline = (text) => linkifyShopContact(text
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/__(.+?)__/g, '<strong>$1</strong>')
      .replace(/`([^`]+)`/g, '<code>$1</code>'));

    function flushList() {
      if (list.length) {
        out.push('<ul>' + list.map(item => '<li>' + inline(item) + '</li>').join('') + '</ul>');
        list = [];
      }
    }

    let paragraph = [];
    function flushParagraph() {
      if (paragraph.length) {
        out.push('<p>' + inline(paragraph.join('<br>')) + '</p>');
        paragraph = [];
      }
    }

    lines.forEach((line) => {
      const trimmed = line.trim();
      if (/^[-*]\s+/.test(trimmed)) {
        flushParagraph();
        list.push(trimmed.replace(/^[-*]\s+/, ''));
      } else if (/^\d+[.)]\s+/.test(trimmed)) {
        flushParagraph();
        list.push(trimmed.replace(/^\d+[.)]\s+/, ''));
      } else if (!trimmed) {
        flushList();
        flushParagraph();
      } else {
        flushList();
        paragraph.push(trimmed);
      }
    });
    flushList();
    flushParagraph();
    return out.join('') || '<p></p>';
  }

  function addMessage(role, text, extraClass) {
    const el = document.createElement('div');
    el.className = 'grok-msg ' + (role === 'user' ? 'user' : 'bot') + (extraClass ? ' ' + extraClass : '');
    el.textContent = text;
    applyTextDirection(el, text);
    messagesEl.appendChild(el);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    return el;
  }

  function cleanForTyping(value) {
    return String(value)
      .replace(/\*\*(.+?)\*\*/g, '$1')
      .replace(/__(.+?)__/g, '$1')
      .replace(/`([^`]+)`/g, '$1')
      .replace(/^\s*[-*]\s+/gm, '• ')
      .replace(/^\s*\d+[.)]\s+/gm, '');
  }

  async function revealReply(el, text) {
    applyTextDirection(el, text);
    const cleanText = cleanForTyping(text);
    const words = cleanText.split(/(\s+)/);
    let current = '';
    el.textContent = '';
    for (const token of words) {
      current += token;
      el.textContent = current;
      messagesEl.scrollTop = messagesEl.scrollHeight;
      await new Promise(resolve => setTimeout(resolve, token.trim() ? 18 : 5));
    }
    el.innerHTML = formatReply(text);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function openChat() {
    panel.classList.add('open');
    toggle.setAttribute('aria-expanded', 'true');
    setTimeout(() => input.focus(), 50);
  }
  function closeChat() {
    panel.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
  }

  toggle.addEventListener('click', () => panel.classList.contains('open') ? closeChat() : openChat());
  close.addEventListener('click', closeChat);
  input.addEventListener('input', () => applyTextDirection(input, input.value));

  function setBusy(isBusy) {
    send.disabled = isBusy;
    input.disabled = isBusy;
    quickReplies.forEach((button) => { button.disabled = isBusy; });
  }

  async function sendMessage(text) {
    if (!text || send.disabled) return;

    addMessage('user', text);
    history.push({ role: 'user', content: text });
    input.value = '';
    applyTextDirection(input, '');
    setBusy(true);
    const typing = addMessage('assistant', assistantName + ' is typing…', 'grok-typing');

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: JSON.stringify({
          messages: history.slice(-10),
          page_context: currentProductContext(text)
        })
      });
      const raw = await response.text();
      let data;
      try { data = JSON.parse(raw); } catch (_) { throw new Error('MobiSaathi returned an invalid response. Please try again.'); }
      typing.remove();
      if (!response.ok || !data.ok) throw new Error(data.message || 'Request failed');
      const reply = String(data.message || '').trim();
      const messageEl = addMessage('assistant', '');
      await revealReply(messageEl, reply);
      history.push({ role: 'assistant', content: reply });
    } catch (error) {
      typing.remove();
      addMessage('assistant', error.message || 'I could not answer right now. Please try again.', 'grok-error');
      history.pop();
    } finally {
      setBusy(false);
      input.focus();
    }
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    sendMessage(input.value.trim());
  });

  quickReplies.forEach((button) => button.addEventListener('click', function () {
    openChat();
    sendMessage(this.dataset.prompt || '');
  }));

  document.querySelectorAll('[data-product-select]').forEach((button) => button.addEventListener('click', function () {
    const card = this.closest('[data-product-card]');
    if (!card) return;
    selectedProduct = {
      id: Number(card.dataset.productId || 0),
      name: String(card.dataset.productName || '').trim()
    };
    openChat();
    sendMessage('Is product ki puri specification, price aur stock batao.');
  }));
})();
