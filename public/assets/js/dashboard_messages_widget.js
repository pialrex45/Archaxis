(function(){
  const $ = (s, c=document)=>c.querySelector(s);
  const $$ = (s, c=document)=>Array.from(c.querySelectorAll(s));
  function url(p){ const b = window.__APP_URL || ''; return b + p; }

  const msgList = $('#dashMsgList');
  const projList = $('#dashProjList');

  const api = {
    threads: () => fetch(url('/api/messages/fetch.php'), { credentials:'same-origin' }).then(r=>r.json()),
    projects: () => fetch(url('/api/projects/mine_min.php'), { credentials:'same-origin' }).then(r=>r.json())
  };

  function renderThreads(data){
    if (!msgList) return;
    msgList.innerHTML = '';
    const items = (data && data.success) ? (data.data||[]) : [];
    if (!items.length){ msgList.innerHTML = '<li class="list-group-item text-muted">No conversations</li>'; return; }
    items.slice(0,5).forEach(t => {
      const li = document.createElement('li'); li.className = 'list-group-item';
      const other = t.other_user_name || ('User #'+(t.other_user_id||''));
      const prev = (t.message_text||'').slice(0,60);
      li.innerHTML = `<span class="truncate"><strong>${escapeHtml(other)}</strong>: ${escapeHtml(prev)}</span>
        <a class="btn btn-sm btn-link" href="${url('/messages')}">Open</a>`;
      msgList.appendChild(li);
    });
  }

  function renderProjects(data){
    if (!projList) return;
    projList.innerHTML = '';
    const items = (data && data.success) ? (data.data||[]) : [];
    if (!items.length){ projList.innerHTML = '<li class="list-group-item text-muted">No projects</li>'; return; }
    items.slice(0,5).forEach(p => {
      const li = document.createElement('li'); li.className='list-group-item';
      li.innerHTML = `<span class="truncate">${escapeHtml(p.name||('Project #'+p.id))}</span>
        <a class="btn btn-sm btn-link" href="${url('/messages/project')}">Open</a>`;
      projList.appendChild(li);
    });
  }

  function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c])); }

  function init(){
    if (msgList) api.threads().then(renderThreads).catch(()=>{});
    if (projList) api.projects().then(renderProjects).catch(()=>{});
  }
  document.addEventListener('DOMContentLoaded', init);
})();
