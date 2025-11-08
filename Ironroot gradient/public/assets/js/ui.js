// Minimal additive UI helpers
(function(){
  // Toast container
  const id = 'ui-toast-container';
  function ensureContainer(){
    let c = document.getElementById(id);
    if (!c) {
      c = document.createElement('div');
      c.id = id;
      c.style.position = 'fixed';
      c.style.top = '12px';
      c.style.right = '12px';
      c.style.zIndex = '9999';
      document.body.appendChild(c);
    }
    return c;
  }

  // Show toast message
  window.toast = function(message, type = 'info', timeout = 2500){
    const c = ensureContainer();
    const el = document.createElement('div');
    el.textContent = message || '';
    el.className = 'alert';
    el.style.marginBottom = '8px';
    el.style.padding = '.6rem .9rem';
    el.style.borderRadius = '.6rem';
    el.style.boxShadow = 'var(--shadow-1, 0 6px 16px rgba(16,24,40,0.08))';
    el.style.background = '#fff';
    el.style.border = '1px solid #e9edf5';
    // simple color accents
    if (type === 'success') { el.style.borderColor = '#ccf1df'; el.style.background = '#e9fbf2'; }
    if (type === 'warning') { el.style.borderColor = '#ffecb8'; el.style.background = '#fff8e6'; }
    if (type === 'danger')  { el.style.borderColor = '#ffd2d9'; el.style.background = '#ffeef0'; }
    if (type === 'info')    { el.style.borderColor = '#c7f2ff'; el.style.background = '#ebfaff'; }
    c.appendChild(el);
    setTimeout(()=>{
      el.style.transition = 'opacity .3s ease, transform .3s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-6px)';
      setTimeout(()=> el.remove(), 320);
    }, timeout);
  };

  // Fetch wrapper returning JSON or {success:false}
  window.fetchJSON = async function(url, options){
    try {
      const res = await fetch(url, options);
      let j = null;
      try { j = await res.json(); } catch(e) {}
      return j || { success: false, message: 'Invalid response' };
    } catch (e) {
      return { success: false, message: e && e.message ? e.message : 'Network error' };
    }
  };
})();
