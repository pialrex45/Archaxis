(function(){
  const $ = (sel, ctx=document) => ctx.querySelector(sel);
  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));
  const metaCsrf = document.querySelector('meta[name="csrf-token"]');
  const CSRF = metaCsrf ? metaCsrf.getAttribute('content') : '';

  const threadListEl = $('#threadList');
  const messageListEl = $('#messageList');
  const convTitleEl = $('#convTitle');
  const replyForm = $('#replyForm');
  const replyBody = $('#replyBody');
  const threadUserIdInput = $('#threadUserId');
  const btnNew = $('#btnNew');
  const btnRefresh = $('#btnRefresh');
  const composeDialog = $('#composeDialog');
  const composeForm = $('#composeForm');
  const composeToUser = $('#composeToUserId');
  const composeBody = $('#composeBody');
  const peersCache = { list: null, loaded: false };

  const api = {
    fetchThreads: () => fetch(url('/api/messages/fetch.php'), { credentials:'same-origin'}).then(r=>r.json()),
    fetchConversation: (otherUserId) => fetch(url('/api/messages/fetch.php?thread_id='+encodeURIComponent(otherUserId)), { credentials:'same-origin'}).then(r=>r.json()),
    fetchPeers: () => fetch(url('/api/messages/peers.php'), { credentials:'same-origin'}).then(r=>r.json()),
    send: (receiver_id, message_text, attachment) => {
      const fd = new FormData();
      fd.append('receiver_id', receiver_id);
      fd.append('message_text', message_text);
      if (CSRF) fd.append('csrf_token', CSRF);
      if (attachment) fd.append('attachment', attachment);
      return fetch(url('/api/messages/send.php'), { method:'POST', body: fd, credentials:'same-origin' }).then(r=>r.json());
    }
  };

  function url(path){
    // Support absolute and relative
    if (/^https?:/i.test(path)) return path;
    // APP_URL may be embedded in global window.__APP_URL if layout sets it; fallback to relative
    const base = window.__APP_URL || '';
    return base + path;
  }

  function renderThreads(threads){
    threadListEl.innerHTML = '';
    if (!threads || !threads.length){
      threadListEl.innerHTML = '<div class="thread-item text-muted">No conversations</div>';
      return;
    }
    threads.forEach(t => {
      const item = document.createElement('div');
      item.className = 'thread-item';
      item.dataset.otherUserId = t.other_user_id || t.receiver_id || t.sender_id;
      item.innerHTML = `
        <div class="name">${escapeHtml(t.other_user_name || ('User #'+(t.other_user_id||'')))}</div>
        <div class="preview">${escapeHtml((t.message_text||'').slice(0, 80))}</div>
        <div class="time" style="font-size:12px;color:#666">${formatTime(t.created_at)}</div>
      `;
      item.addEventListener('click', () => {
        $$('.thread-item', threadListEl).forEach(el=>el.classList.remove('active'));
        item.classList.add('active');
        loadConversation(item.dataset.otherUserId, t.other_user_name);
      });
      threadListEl.appendChild(item);
    });
  }

  function renderMessages(msgs){
    messageListEl.innerHTML = '';
    if (!msgs || !msgs.length){
      messageListEl.innerHTML = '<div class="text-muted">No messages in this conversation yet.</div>';
      return;
    }
    const myId = window.__CURRENT_USER_ID || null; // optional if layout provides
    msgs.forEach(m => {
      const div = document.createElement('div');
      const me = (myId && String(m.sender_id) === String(myId)) ? 'me' : 'them';
      div.className = 'msg ' + me;
      div.innerHTML = `
        <div class="body">${escapeHtml(m.message_text || '')}</div>
        ${m.file_path ? `<div class="att"><a href="${escapeAttr(m.file_path)}" target="_blank">Attachment</a></div>`: ''}
        <div class="meta" style="font-size:12px;color:#666">${formatTime(m.created_at)}</div>
      `;
      messageListEl.appendChild(div);
    });
    // scroll to bottom
    messageListEl.scrollTop = messageListEl.scrollHeight;
  }

  function loadThreads(){
    api.fetchThreads().then(json => {
      if (!json || !json.success) throw new Error(json && json.message || 'Failed to load threads');
      renderThreads(json.data || []);
    }).catch(err => {
      console.error(err);
      threadListEl.innerHTML = '<div class="thread-item text-danger">Failed to load conversations</div>';
    });
  }

  function loadConversation(otherUserId, name){
    if (!otherUserId) return;
    convTitleEl.textContent = name ? `Conversation with ${name}` : `Conversation`;
    threadUserIdInput.value = otherUserId;
    api.fetchConversation(otherUserId).then(json => {
      if (!json || !json.success) throw new Error(json && json.message || 'Failed to load conversation');
      renderMessages(json.data || []);
    }).catch(err => {
      console.error(err);
      messageListEl.innerHTML = '<div class="text-danger">Failed to load conversation</div>';
    });
  }

  function onReplySubmit(e){
    e.preventDefault();
    const to = threadUserIdInput.value;
    const text = (replyBody.value || '').trim();
    if (!to) { alert('Select a conversation'); return; }
    if (!text) { return; }
    api.send(to, text).then(json => {
      if (json && json.success){
        replyBody.value = '';
        loadConversation(to);
      } else {
        alert((json && json.message) || 'Failed to send');
      }
    }).catch(err => {
      console.error(err); alert('Failed to send');
    });
  }

  function onCompose(){
    if (typeof composeDialog.showModal === 'function') composeDialog.showModal();
    else composeDialog.setAttribute('open','open');
  }

  function onComposeSubmit(e){
    e.preventDefault();
    const to = composeToUser.value;
    const text = (composeBody.value || '').trim();
    if (!to || !text) return;
    api.send(to, text).then(json => {
      if (json && json.success){
        composeBody.value = '';
        composeToUser.value = '';
        if (typeof composeDialog.close === 'function') composeDialog.close();
        loadThreads();
        loadConversation(to);
      } else {
        alert((json && json.message) || 'Failed to send');
      }
    }).catch(err => {
      console.error(err); alert('Failed to send');
    });
  }

  function formatTime(iso){
    if (!iso) return '';
    try { return new Date(iso).toLocaleString(); } catch { return iso; }
  }
  function escapeHtml(s){
    return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));
  }
  function escapeAttr(s){ return escapeHtml(s); }

  function bind(){
    if (replyForm) replyForm.addEventListener('submit', onReplySubmit);
    if (btnNew) btnNew.addEventListener('click', onCompose);
    if (btnRefresh) btnRefresh.addEventListener('click', () => { loadThreads(); if (threadUserIdInput.value) loadConversation(threadUserIdInput.value); });
    if (composeForm) composeForm.addEventListener('submit', onComposeSubmit);
    // Load allowed recipients for compose dropdown
    loadPeers();
  }

  function loadPeers(){
    if (!composeToUser) return;
    // If already loaded, don't refetch immediately
    if (peersCache.loaded && Array.isArray(peersCache.list)) {
      renderPeers(peersCache.list);
      return;
    }
    api.fetchPeers().then(j => {
      const list = (j && j.success && Array.isArray(j.data)) ? j.data : [];
      peersCache.list = list; peersCache.loaded = true;
      renderPeers(list);
    }).catch(() => {
      renderPeers([]);
    });
  }

  function renderPeers(list){
    if (!composeToUser) return;
    composeToUser.innerHTML = '';
    if (!list.length) {
      composeToUser.innerHTML = '<option value="" disabled>No allowed recipients</option>';
      return;
    }
    const opts = ['<option value="" disabled selected>Select a recipient…</option>']
      .concat(list.map(u => `<option value="${Number(u.id)}">${escapeHtml(u.name || ('User #'+u.id))}${u.role?` — ${escapeHtml(u.role)}`:''}</option>`));
    composeToUser.innerHTML = opts.join('');
  }

  document.addEventListener('DOMContentLoaded', function(){
    bind();
    loadThreads();
  });
})();
