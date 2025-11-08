(function(){
  const $ = (s, c=document)=>c.querySelector(s);
  const $$ = (s, c=document)=>Array.from(c.querySelectorAll(s));
  const listEl = $('#pmProjectList');
  const tabsEl = $('#pmChannelTabs');
  const msgEl = $('#pmMessageList');
  const titleEl = $('#pmProjectTitle');
  const detailsEl = $('#pmProjectDetails');
  const form = $('#pmSendForm');
  const projectIdInput = $('#pmProjectId');
  const channelKeyInput = $('#pmChannelKey');
  const bodyInput = $('#pmBody');
  const fileInput = $('#pmFile');

  function url(path){ const base = window.__APP_URL || ''; return base + path; }

  const api = {
    projects: () => fetch(url('/api/projects/mine_min.php'), { credentials:'same-origin' }).then(r=>r.json()),
    channels: (pid) => fetch(url('/api/projects/messages/channels.php?project_id='+encodeURIComponent(pid)), { credentials:'same-origin' }).then(r=>r.json()),
    list: (pid, ch, before, limit=50) => {
      const u = new URL(url('/api/projects/messages/list.php'), window.location.origin);
      u.searchParams.set('project_id', pid);
      if (ch) u.searchParams.set('channel', ch);
      if (before) u.searchParams.set('before', before);
      u.searchParams.set('limit', String(limit));
      return fetch(u.toString(), { credentials:'same-origin' }).then(r=>r.json());
    },
    send: (pid, ch, body, files) => {
      const fd = new FormData();
      fd.append('project_id', pid);
      if (ch) fd.append('channel', ch);
      fd.append('body', body);
      if (files && files.length){
        // support multiple attachments
        Array.from(files).forEach((f, idx) => fd.append('attachments[]', f));
      }
      return fetch(url('/api/projects/messages/send.php'), { method:'POST', body: fd, credentials:'same-origin' }).then(r=>r.json());
    },
    projectDetails: (pid) => fetch(url('/api/projects/show_min.php?project_id='+encodeURIComponent(pid)), { credentials:'same-origin' }).then(r=>r.json())
  };

  function renderProjects(list){
    listEl.innerHTML = '';
    if (!list || !list.length){ listEl.innerHTML = '<div class="pm-item text-muted">No projects</div>'; return; }
    list.forEach(p => {
      const d = document.createElement('div');
      d.className = 'pm-item'; d.dataset.pid = p.id; d.textContent = p.name;
      d.addEventListener('click', () => selectProject(p.id, p.name));
      listEl.appendChild(d);
    });
  }

  function setActiveProject(pid){
    $$('.pm-item', listEl).forEach(el => el.classList.toggle('active', String(el.dataset.pid)===String(pid)));
  }

  function renderChannels(channels){
    tabsEl.innerHTML = '';
    const items = (channels && channels.length) ? channels : [{ key_slug:'general', title:'General' }];
    const current = channelKeyInput.value || 'general';
    items.forEach(c => {
      const k = c.key_slug || 'general';
      const b = document.createElement('button');
      b.type = 'button'; b.className = 'pm-tab' + (k===current?' active':'');
      b.textContent = c.title || k;
      b.addEventListener('click', () => { channelKeyInput.value = k; renderChannels(items.map(x=>({...x}))); loadMessages(projectIdInput.value, k); });
      tabsEl.appendChild(b);
    });
  }

  function renderMessages(msgs){
    msgEl.innerHTML='';
    if (!msgs || !msgs.length){ msgEl.innerHTML = '<div class="text-muted">No messages yet.</div>'; return; }
    const myId = window.__CURRENT_USER_ID || null;
    msgs.forEach(m => {
      const d = document.createElement('div');
      const me = (myId && String(m.sender_id)===String(myId)) ? 'me':'them';
      d.className = 'pm-msg '+me;
      let attachmentsHtml = '';
      if (m.metadata){
        try {
          const meta = typeof m.metadata === 'string' ? JSON.parse(m.metadata) : m.metadata;
          const files = meta && meta.attachments ? meta.attachments : [];
          if (files && files.length){
            attachmentsHtml = '<div class="mt-1">' + files.map(file => {
              const name = escapeHtml(file.name || 'file');
              const token = encodeURIComponent(file.token || '');
              const urlPath = `/api/projects/messages/download.php?token=${token}`;
              return `<a href="${url(urlPath)}" target="_blank" rel="noopener">${name}</a>`;
            }).join('<br>') + '</div>';
          }
        } catch (e) { /* ignore */ }
      }
      d.innerHTML = `<div class="body">${escapeHtml(m.body||'')}${attachmentsHtml}</div><div class="meta" style="font-size:12px;color:#666">${fmt(m.created_at)}</div>`;
      msgEl.appendChild(d);
    });
    msgEl.scrollTop = msgEl.scrollHeight;
  }

  function selectProject(pid, name){
    projectIdInput.value = pid; setActiveProject(pid);
    titleEl.textContent = name || ('Project #'+pid);
    api.channels(pid).then(json => renderChannels((json && json.success) ? json.data : null));
    loadMessages(pid, channelKeyInput.value || 'general');
    api.projectDetails(pid).then(json => {
      if (json && json.success && json.data){
        const d = json.data;
        detailsEl.innerHTML = `
          <div><strong>${escapeHtml(d.name||'')}</strong></div>
          <div>Status: ${escapeHtml(d.status||'')}</div>
          <div>Owner: ${escapeHtml(d.owner_name||String(d.owner_id||''))}</div>
          <div>Start: ${escapeHtml(d.start_date||'')}</div>
          <div>End: ${escapeHtml(d.end_date||'')}</div>
        `;
      } else {
        detailsEl.textContent = 'Details unavailable';
      }
    });
  }

  function loadMessages(pid, ch){
    if (!pid) return; msgEl.innerHTML = '<div>Loading…</div>';
    api.list(pid, ch).then(json => { if (json && json.success) renderMessages(json.data); else msgEl.innerHTML = '<div class="text-danger">Failed to load</div>'; })
    .catch(()=> msgEl.innerHTML = '<div class="text-danger">Failed to load</div>');
  }

  function onSend(e){
    e.preventDefault();
    const pid = projectIdInput.value; if (!pid) { alert('Select a project'); return; }
    const ch = channelKeyInput.value || 'general';
    const body = (bodyInput.value||'').trim();
    const files = fileInput && fileInput.files ? fileInput.files : null;
    if (!body && (!files || !files.length)) return;
    api.send(pid, ch, body, files).then(json => {
      if (json && json.success){ bodyInput.value=''; if (fileInput) fileInput.value=''; loadMessages(pid, ch); }
      else alert((json && json.message) || 'Failed to send');
    }).catch(()=> alert('Failed to send'));
  }

  function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c])); }
  function fmt(iso){ try { return new Date(iso).toLocaleString(); } catch { return iso; } }

  function init(){
    if (form) form.addEventListener('submit', onSend);
    api.projects().then(json => renderProjects((json && json.success) ? json.data : [])).
      catch(()=>{ listEl.innerHTML = '<div class="pm-item text-danger">Failed to load projects</div>'; });
  }
  document.addEventListener('DOMContentLoaded', init);
})();
