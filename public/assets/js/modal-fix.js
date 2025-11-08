// Global Modal Relocation & Safety Utility
(function(){
  function relocate(modal){
    if (!modal || modal.parentNode === document.body) return;
    document.body.appendChild(modal);
  }
  function scan(){ document.querySelectorAll('.modal').forEach(relocate); }
  document.addEventListener('DOMContentLoaded', function(){
    scan();
    // Observe future additions
    const obs = new MutationObserver(function(muts){
      muts.forEach(m => { m.addedNodes && m.addedNodes.forEach(n => { if (n.nodeType===1) { if (n.classList && n.classList.contains('modal')) relocate(n); n.querySelectorAll && n.querySelectorAll('.modal').forEach(relocate); } }); });
    });
    obs.observe(document.documentElement, { childList:true, subtree:true });
  });
})();
