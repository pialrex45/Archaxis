(function(){
  const projectSelect = document.getElementById('projectSelect');
  const supplierSelect = document.getElementById('supplierSelect');
  const productSelect = document.getElementById('productSelect');
  const categorySelect = document.getElementById('categorySelect');
  const descriptionInput = document.getElementById('descriptionInput');
  const unitPriceInput = document.getElementById('unitPriceInput');
  const quantityInput = document.getElementById('quantityInput');
  const addItemBtn = document.getElementById('addItemBtn');
  const importBtn = document.getElementById('importBtn');
  const planBtn = document.getElementById('planBtn');
  const exportBtn = document.getElementById('exportBtn');
  const itemsBody = document.getElementById('itemsBody');
  const materialsTotalEl = document.getElementById('materialsTotal');
  const laborTotalEl = document.getElementById('laborTotal');
  const equipmentTotalEl = document.getElementById('equipmentTotal');
  const subTotalEl = document.getElementById('subTotal');
  const taxInput = document.getElementById('taxInput');
  const taxAmountEl = document.getElementById('taxAmount');
  const grandTotalEl = document.getElementById('grandTotal');
  // Chip mirrors
  const materialsTotalChip = document.getElementById('materialsTotalChip');
  const laborTotalChip = document.getElementById('laborTotalChip');
  const equipmentTotalChip = document.getElementById('equipmentTotalChip');
  const subTotalChip = document.getElementById('subTotalChip');
  const taxAmountChip = document.getElementById('taxAmountChip');
  const grandTotalChip = document.getElementById('grandTotalChip');
  const printBtn = document.getElementById('printBtn');
  const clearBtn = document.getElementById('clearBtn');
  const projectSelectEl = document.getElementById('projectSelect');
  const projectNameDisplay = document.getElementById('projectNameDisplay');
  const printDateDisplay = document.getElementById('printDateDisplay');

  const state = { items: [], suppliers: [], products: [], projects: [], editIndex: null, editProducts: [], editDraft: null };

  function api(path){
    // Try to resolve relatively under Ironroot root
    return APP_BASE.replace(/\\/g,'/') + '/../api' + path;
  }

  async function fetchJsonWithRetry(url, opts={}, retries=2, backoffMs=300){
    const defaultHeaders = { 'Pragma': 'no-cache', 'Cache-Control': 'no-store' };
    const options = Object.assign({ credentials: window.IS_GUEST ? 'omit' : 'include', cache: 'no-store', headers: defaultHeaders }, opts);
    options.headers = Object.assign({}, defaultHeaders, (opts && opts.headers) ? opts.headers : {});
    for (let attempt=0; attempt<=retries; attempt++){
      try{
        const res = await fetch(url, options);
        // Detect HTML login redirect
        const contentType = (res.headers.get('content-type')||'').toLowerCase();
        if (contentType.includes('application/json')){
          const json = await res.json();
          if (res.status === 401){
            if (window.IS_GUEST) return { ok: false, status: 401, data: json };
            throw Object.assign(new Error(json.error||'Unauthorized'), { code: 401 });
          }
          return { ok: res.ok, status: res.status, data: json };
        } else {
          const text = await res.text();
          if (res.status === 401 || /login/i.test(text)){
            if (window.IS_GUEST) return { ok: false, status: 401, data: null };
            throw Object.assign(new Error('Session expired. Please log in again.'), { code: 401 });
          }
          // Try parsing anyway
          try { const json = JSON.parse(text); return { ok: res.ok, status: res.status, data: json }; }
          catch(_e){ throw new Error('Unexpected response'); }
        }
      }catch(e){
        if (e && e.code === 401){
          if (window.IS_GUEST) return { ok: false, status: 401, data: null };
          alert('Your session may have expired. Please log in again.');
          try{ window.location.href = (window.APP_LOGIN_URL || '/login'); }catch(_e){}
          throw e;
        }
        if (attempt < retries){
          await new Promise(r=>setTimeout(r, backoffMs * (attempt+1)));
          continue;
        }
        throw e;
      }
    }
  }
  async function loadProjects(){
    if (window.IS_GUEST){
      // Hide project field in guest mode
      const pf = document.getElementById('projectField'); if (pf) pf.style.display = 'none';
      // Also hide toolbar actions that depend on project
      const importBtnEl = document.getElementById('importBtn'); if (importBtnEl) importBtnEl.style.display = 'none';
      const exportBtnEl = document.getElementById('exportBtn'); if (exportBtnEl) exportBtnEl.style.display = 'none';
      const planBtnEl = document.getElementById('planBtn'); if (planBtnEl) planBtnEl.style.display = 'none';
      projectNameDisplay && (projectNameDisplay.textContent = '—');
      return; // No project loading for guests
    }
    try{
      const { ok, data } = await fetchJsonWithRetry(api('/projects/list.php?mine=1'));
      if(!ok) throw new Error('Failed to load projects');
      state.projects = Array.isArray(data.data) ? data.data : [];
      renderProjects();
    }catch(e){
      projectSelect.innerHTML = '<option value="">Error loading projects</option>';
      console.error(e);
    }
  }

  function renderProjects(){
    projectSelect.innerHTML = '<option value="">Select project</option>' + state.projects.map(p=>`<option value="${p.id}">${p.name||('Project #'+p.id)}</option>`).join('');
  }


  function fmt(n){ return (Number(n)||0).toFixed(2); }

  function clampTax(){
    let v = Number(taxInput.value);
    if (isNaN(v)) v = 10;
    if (v < 10) v = 10;
    if (v > 15) v = 15;
    taxInput.value = v.toFixed(1);
  }

  async function loadSuppliers(){
    if (window.IS_GUEST){
      // Try to pull suppliers anonymously; if blocked, present fallback
    }
    try{
      const { ok, data } = await fetchJsonWithRetry(api('/suppliers/index.php'));
      if(!ok) throw new Error('Failed to load suppliers');
      state.suppliers = Array.isArray(data.data) ? data.data : [];
      renderSuppliers();
    }catch(e){
      supplierSelect.innerHTML = window.IS_GUEST ? '<option value="">Suppliers unavailable in demo</option>' : '<option value="">Error loading suppliers</option>';
      console.error(e);
    }
  }

  function renderSuppliers(){
    if (!state.suppliers.length){
      supplierSelect.innerHTML = '<option value="">No suppliers found</option>';
      return;
    }
    supplierSelect.innerHTML = '<option value="">Select supplier</option>' + state.suppliers.map(s=>`<option value="${s.id}">${s.name}</option>`).join('');
  }

  async function loadProductsBySupplier(supplierId){
    productSelect.innerHTML = '<option value="">Loading...</option>';
    try{
      const { ok, data } = await fetchJsonWithRetry(api('/products/index.php?supplier_id=')+encodeURIComponent(supplierId));
      if(!ok) throw new Error('Failed to load products');
      state.products = Array.isArray(data.data) ? data.data : [];
      renderProducts();
    }catch(e){
      productSelect.innerHTML = window.IS_GUEST ? '<option value="">Products unavailable in demo</option>' : '<option value="">Error loading products</option>';
      console.error(e);
    }
  }

  function renderProducts(){
    if(!state.products.length){
      productSelect.innerHTML = '<option value="">No products for supplier</option>';
      return;
    }
    productSelect.innerHTML = '<option value="">Select product</option>' + state.products.map(p=>{
      const unit = (p.unit||'').toString();
      const price = typeof p.unit_price !== 'undefined' ? p.unit_price : '';
      return `<option value="${p.id}" data-unit="${unit}" data-price="${price}">${p.name}</option>`;
    }).join('');
  }

  function serializeEstimate(){
    return {
      version: 1,
      items: state.items.map(i=>({
        category: i.category,
        description: i.description||'',
        supplierId: i.supplierId||null,
        supplierName: i.supplierName||'',
        productId: i.productId||null,
        productName: i.productName||'',
        unit: i.unit||'',
        unitPrice: Number(i.unitPrice)||0,
        qty: Number(i.qty)||0
      })),
      totals: {
        materials: Number(materialsTotalEl.textContent)||0,
        labor: Number(laborTotalEl.textContent)||0,
        equipment: Number(equipmentTotalEl.textContent)||0,
        subtotal: Number(subTotalEl.textContent)||0,
        taxRatePct: Number(taxInput.value)||10,
        taxAmount: Number(taxAmountEl.textContent)||0,
        grandTotal: Number(grandTotalEl.textContent)||0
      }
    };
  }

  function tryParseEstimateFromDescription(desc){
    if (!desc) return null;
    // We store JSON in description with a prefix marker to be safe
    const marker = 'ESTIMATE_JSON:';
    const idx = desc.indexOf(marker);
    let jsonStr = null;
    if (idx >= 0) {
      jsonStr = desc.slice(idx + marker.length).trim();
    } else {
      // fallback: whole description is JSON
      jsonStr = desc.trim();
    }
    try {
      const obj = JSON.parse(jsonStr);
      if (obj && Array.isArray(obj.items)) return obj;
    } catch(e){ /* ignore */ }
    return null;
  }

  function loadEstimateIntoState(est){
    if (!est || !Array.isArray(est.items)) return;
    state.items = est.items.map(x=>({
      category: x.category||'materials',
      description: x.description||'',
      supplierId: x.supplierId||null,
      supplierName: x.supplierName||'',
      productId: x.productId||null,
      productName: x.productName||'',
      unit: x.unit||'',
      unitPrice: Number(x.unitPrice)||0,
      qty: Number(x.qty)||0
    }));
    if (est.totals && typeof est.totals.taxRatePct !== 'undefined') {
      taxInput.value = String(est.totals.taxRatePct);
      clampTax();
    }
    renderItems();
  }

  function estimateFromDesigner(estimates){
    // Map designer estimates into calculator items
    // We’ll generate a few representative line items without requiring exact product mapping
    const items = [];
    const pushItem = (category, description, unit, unitPrice, qty) => items.push({ category, description, unit, unitPrice:Number(unitPrice)||0, qty:Number(qty)||1 });

    if (estimates && estimates.wall){
      const w = estimates.wall;
      // Materials
      if (w.bricks){ pushItem('materials', `Bricks (${Math.round(w.bricks)} pcs)`, 'pcs', 0, Math.round(w.bricks)); }
      if (w.cementBags){ pushItem('materials', `Cement (${Math.ceil(w.cementBags)} bags)`, 'bag', 0, Math.ceil(w.cementBags)); }
      if (w.sandM3){ pushItem('materials', `Sand (${w.sandM3.toFixed(2)} m3)`, 'm3', 0, Number(w.sandM3)); }
      // Labor approximation from totals, if available
      if (typeof w.laborCostBDT !== 'undefined'){
        pushItem('labor', `Wall labor (~${(Number(w.areaM2)||0).toFixed(1)} m²)`, 'm2', Number(w.laborCostBDT) / Math.max(1, Number(w.areaM2)||1), Number(w.areaM2)||1);
      }
    }

    if (estimates && estimates.rcc){
      const r = estimates.rcc;
      if (r.steelKg){ pushItem('materials', `Steel (${r.steelKg.toFixed(1)} kg)`, 'kg', 0, Number(r.steelKg)); }
      if (r.cementBags){ pushItem('materials', `Cement for RCC (${Math.ceil(r.cementBags)} bags)`, 'bag', 0, Math.ceil(r.cementBags)); }
      if (r.sandM3){ pushItem('materials', `Sand for RCC (${r.sandM3.toFixed(2)} m3)`, 'm3', 0, Number(r.sandM3)); }
      if (r.stoneM3){ pushItem('materials', `Aggregate (${r.stoneM3.toFixed(2)} m3)`, 'm3', 0, Number(r.stoneM3)); }
      if (typeof r.laborCostBDT !== 'undefined' && typeof r.volumeM3 !== 'undefined'){
        pushItem('labor', `RCC labor (~${(Number(r.volumeM3)||0).toFixed(2)} m³)`, 'm3', Number(r.laborCostBDT) / Math.max(1, Number(r.volumeM3)||1), Number(r.volumeM3)||1);
      }
      if (typeof r.shutteringCostBDT !== 'undefined' && typeof r.shutteringAreaM2 !== 'undefined'){
        pushItem('equipment', `Shuttering (${(Number(r.shutteringAreaM2)||0).toFixed(1)} m²)`, 'm2', Number(r.shutteringCostBDT) / Math.max(1, Number(r.shutteringAreaM2)||1), Number(r.shutteringAreaM2)||1);
      }
    }

    // Existing items remain; we append these new ones
    state.items = state.items.concat(items);
    renderItems();
  }

  function addItem(row){
    state.items.push(row);
    renderItems();
  }

  function removeItem(idx){
    state.items.splice(idx,1);
    renderItems();
  }

  function updateTotals(){
    let materials = 0, labor = 0, equipment = 0;
    state.items.forEach(i => {
      const sub = i.unitPrice * i.qty;
      if(i.category==='materials') materials += sub;
      else if(i.category==='labor') labor += sub;
      else if(i.category==='equipment') equipment += sub;
    });
    const subtotal = materials + labor + equipment;
    const taxRate = Math.min(15, Math.max(10, Number(taxInput.value)||10)) / 100;
    const tax = subtotal * taxRate;
    const grand = subtotal + tax;

    const m = fmt(materials), l = fmt(labor), e = fmt(equipment), s = fmt(subtotal), t = fmt(tax), g = fmt(grand);
    if (materialsTotalEl) materialsTotalEl.textContent = m;
    if (laborTotalEl) laborTotalEl.textContent = l;
    if (equipmentTotalEl) equipmentTotalEl.textContent = e;
    if (subTotalEl) subTotalEl.textContent = s;
    if (taxAmountEl) taxAmountEl.textContent = t;
    if (grandTotalEl) grandTotalEl.textContent = g;
    if (materialsTotalChip) materialsTotalChip.textContent = m;
    if (laborTotalChip) laborTotalChip.textContent = l;
    if (equipmentTotalChip) equipmentTotalChip.textContent = e;
    if (subTotalChip) subTotalChip.textContent = s;
    if (taxAmountChip) taxAmountChip.textContent = t;
    if (grandTotalChip) grandTotalChip.textContent = g;
  }

  function renderItems(){
    itemsBody.innerHTML = '';
    state.items.forEach((i, idx) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${idx+1}</td>
        <td>${i.category}</td>
        <td>${i.description||''}</td>
        <td>${i.supplierName||''}</td>
        <td>${i.productName||''}</td>
        <td>${i.unit||''}</td>
        <td class="right">${fmt(i.unitPrice)}</td>
        <td class="right">${fmt(i.qty)}</td>
        <td class="right">${fmt(i.unitPrice * i.qty)}</td>
        <td class="right">
          <button class="btn btn-edit" data-idx="${idx}">Edit</button>
          <button class="btn danger" data-idx="${idx}">Remove</button>
        </td>
      `;
      itemsBody.appendChild(tr);

      // Inline editor row
      if (state.editIndex === idx) {
        const editor = document.createElement('tr');
        const suppliersOptions = state.suppliers.map(s=>`<option value="${s.id}">${s.name}</option>`).join('');
        const draft = state.editDraft || {};
        const draftSupplierId = draft.supplierId || '';
        const draftProductId = draft.productId || '';
        const draftUnit = typeof draft.unit !== 'undefined' ? draft.unit : (i.unit||'');
        const draftPrice = typeof draft.unitPrice !== 'undefined' ? draft.unitPrice : (i.unitPrice||0);
        const productOptions = (state.editProducts||[]).map(p=>`<option value="${p.id}" data-unit="${p.unit||''}" data-price="${p.unit_price}">${p.name}</option>`).join('');
        editor.innerHTML = `
          <td colspan="10">
            <div class="row" style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;align-items:end;">
              <div class="field">
                <label>Supplier</label>
                <select id="editSupplier">
                  <option value="">Select supplier</option>
                  ${suppliersOptions}
                </select>
              </div>
              <div class="field">
                <label>Product</label>
                <select id="editProduct">
                  <option value="">${productOptions ? 'Select product' : 'Select supplier first'}</option>
                  ${productOptions}
                </select>
              </div>
              <div class="field">
                <label>Unit</label>
                <input id="editUnit" value="${draftUnit}" />
              </div>
              <div class="field">
                <label>Unit Price</label>
                <input id="editPrice" type="number" step="0.01" value="${fmt(draftPrice)}" />
              </div>
              <div class="field">
                <button id="saveEdit" class="primary" data-idx="${idx}">Save</button>
              </div>
              <div class="field">
                <button id="cancelEdit" data-idx="${idx}">Cancel</button>
              </div>
            </div>
          </td>`;
        itemsBody.appendChild(editor);

        // Initialize selected values after DOM added
        const selSup = editor.querySelector('#editSupplier');
        const selProd = editor.querySelector('#editProduct');
        if (selSup) selSup.value = String(draftSupplierId||'');
        if (selProd && draftProductId) selProd.value = String(draftProductId);

        // Handlers for editor controls
        if (selSup){
          selSup.addEventListener('change', async ()=>{
            const sid = selSup.value;
            const prodSel = editor.querySelector('#editProduct');
            prodSel.innerHTML = '<option value="">Loading...</option>';
            state.editProducts = [];
            state.editDraft = Object.assign({}, draft, { supplierId: sid||null, productId: null });
            if (sid){
              try{
                const { ok, data } = await fetchJsonWithRetry(api('/products/index.php?supplier_id=')+encodeURIComponent(sid));
                if (ok){
                  state.editProducts = Array.isArray(data.data) ? data.data : [];
                  prodSel.innerHTML = '<option value="">Select product</option>' + state.editProducts.map(p=>`<option value="${p.id}" data-unit="${p.unit||''}" data-price="${p.unit_price}">${p.name}</option>`).join('');
                } else {
                  prodSel.innerHTML = '<option value="">Failed to load</option>';
                }
              }catch(_e){ prodSel.innerHTML = '<option value="">Failed to load</option>'; }
            } else {
              prodSel.innerHTML = '<option value="">Select supplier first</option>';
            }
          });
        }

        if (selProd){
          selProd.addEventListener('change', ()=>{
            const opt = selProd.selectedOptions[0];
            const unitInput = editor.querySelector('#editUnit');
            const priceInput = editor.querySelector('#editPrice');
            if (opt){
              const unit = opt.getAttribute('data-unit')||'';
              const price = opt.getAttribute('data-price')||'';
              if (unitInput && unit) unitInput.value = unit;
              if (priceInput && price) priceInput.value = String(price);
              state.editDraft = Object.assign({}, (state.editDraft||{}), { productId: selProd.value||null });
            }
          });
        }

        const saveBtn = editor.querySelector('#saveEdit');
        const cancelBtn = editor.querySelector('#cancelEdit');
        if (saveBtn){
          saveBtn.addEventListener('click', ()=>{
            const selS = editor.querySelector('#editSupplier');
            const selP = editor.querySelector('#editProduct');
            const unitEl = editor.querySelector('#editUnit');
            const priceEl = editor.querySelector('#editPrice');
            const supplierId = selS && selS.value ? Number(selS.value) : null;
            const productId = selP && selP.value ? Number(selP.value) : null;
            const supplierName = supplierId ? (state.suppliers.find(s=>String(s.id)===String(supplierId))?.name || '') : '';
            const productObj = productId ? (state.editProducts.find(p=>String(p.id)===String(productId)) || null) : null;
            const productName = productObj ? productObj.name : '';
            const unit = unitEl ? unitEl.value : '';
            const unitPrice = priceEl ? Number(priceEl.value||0) : 0;
            state.items[idx] = Object.assign({}, state.items[idx], { supplierId, supplierName, productId, productName, unit, unitPrice });
            state.editIndex = null; state.editProducts = []; state.editDraft = null;
            renderItems();
          });
        }
        if (cancelBtn){
          cancelBtn.addEventListener('click', ()=>{ state.editIndex = null; state.editProducts = []; state.editDraft = null; renderItems(); });
        }
      }
    });
    itemsBody.querySelectorAll('button.danger').forEach(btn=>{
      btn.addEventListener('click', (e)=>{
        const idx = Number(e.currentTarget.getAttribute('data-idx'));
        removeItem(idx);
      });
    });
    itemsBody.querySelectorAll('button.btn-edit').forEach(btn=>{
      btn.addEventListener('click', (e)=>{
        const idx = Number(e.currentTarget.getAttribute('data-idx'));
        // Open editor for this row
        state.editIndex = idx;
        const row = state.items[idx];
        state.editDraft = { supplierId: row.supplierId||'', productId: row.productId||'', unit: row.unit||'', unitPrice: row.unitPrice||0 };
        // Preload products for existing supplier
        (async function(){
          try{
            if (row.supplierId){
              const { ok, data } = await fetchJsonWithRetry(api('/products/index.php?supplier_id=')+encodeURIComponent(row.supplierId));
              state.editProducts = ok && Array.isArray(data.data) ? data.data : [];
            } else { state.editProducts = []; }
          }catch(_e){ state.editProducts = []; }
          renderItems();
        })();
      });
    });
    updateTotals();
  }

  // Events
  projectSelect.addEventListener('change', ()=>{
    // no-op for now; can auto-import on change if desired
  });
  supplierSelect.addEventListener('change', ()=>{
    const id = supplierSelect.value;
    productSelect.innerHTML = '<option value="">Select supplier first</option>';
    unitPriceInput.value = '';
    if(id) loadProductsBySupplier(id);
  });

  productSelect.addEventListener('change', ()=>{
    const opt = productSelect.selectedOptions[0];
    if(opt){
      const price = opt.getAttribute('data-price');
      unitPriceInput.value = price || '';
    }
  });

  function updatePrintMeta(){
    try{
      const projId = projectSelectEl ? projectSelectEl.value : '';
      const name = projId ? (projectSelectEl.selectedOptions[0]?.textContent || '—') : '—';
      if (projectNameDisplay) projectNameDisplay.textContent = name;
    }catch(_e){}
    try{
      const d = new Date();
      const s = d.toLocaleDateString();
      if (printDateDisplay) printDateDisplay.textContent = s;
    }catch(_e){}
  }

  if (projectSelectEl){ projectSelectEl.addEventListener('change', updatePrintMeta); }

  taxInput.addEventListener('input', ()=>{
    clampTax();
    updateTotals();
  });

  addItemBtn.addEventListener('click', ()=>{
    const category = categorySelect.value;
    const description = descriptionInput.value.trim();
    const supplierId = supplierSelect.value || null;
    const supplierName = supplierId ? (supplierSelect.selectedOptions[0].textContent) : '';
    const productId = productSelect.value || null;
    const productName = productId ? (productSelect.selectedOptions[0].textContent) : '';
    const unit = productId ? (productSelect.selectedOptions[0].getAttribute('data-unit')||'') : '';
    const unitPrice = Number(unitPriceInput.value || 0);
    const qty = Number(quantityInput.value || 0);
    if(!category || qty <= 0 || unitPrice < 0){
      alert('Please provide category, positive quantity, and a valid unit price.');
      return;
    }
    addItem({ category, description, supplierId, supplierName, productId, productName, unit, unitPrice, qty });

    // reset quick inputs except supplier/product to allow more items
    descriptionInput.value='';
    unitPriceInput.value='';
    quantityInput.value='1';
  });

  printBtn.addEventListener('click', ()=>{
    updatePrintMeta();
    window.print(); // Simple: users can choose Save as PDF
  });

  importBtn.addEventListener('click', async ()=>{
    if (window.IS_GUEST){ alert('Please log in to import estimates.'); return; }
    const pid = projectSelect.value;
    if (!pid){ alert('Select a project first.'); return; }
    try{
      const r = await fetch(api('/finance/by_project.php?project_id=')+encodeURIComponent(pid), { credentials: 'include' });
      if (!r.ok) throw new Error('Failed to load finance');
      const j = await r.json();
      const rows = (j && j.success && Array.isArray(j.data)) ? j.data : [];
      // Find most recent finance record that contains our estimate marker
      const marker = 'ESTIMATE_JSON:';
      const found = rows.find(row => (row.description||'').includes(marker) || (function(desc){ try{ JSON.parse(desc); return true; }catch{return false;} })(row.description||''));
      if (!found){ alert('No saved estimate found for this project.'); return; }
      const est = tryParseEstimateFromDescription(found.description||'');
      if (!est){ alert('Saved data is not a valid estimate.'); return; }
      loadEstimateIntoState(est);
    }catch(e){ alert(e.message||'Import failed'); }
  });

  exportBtn.addEventListener('click', async ()=>{
    if (window.IS_GUEST){ alert('Please log in to export to Finance.'); return; }
    const pid = projectSelect.value;
    if (!pid){ alert('Select a project first.'); return; }
    if (!state.items.length){ if(!confirm('No items added. Save empty estimate?')) return; }
    const est = serializeEstimate();
    const marker = 'ESTIMATE_JSON:';
    const description = marker + JSON.stringify(est);
    const amount = Number(grandTotalEl.textContent)||0;
    const payload = { project_id: Number(pid), type: 'expense', amount: amount>0?amount:0.01, description };
    try{
      const r = await fetch(api('/finance/log.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(payload)
      });
      const j = await r.json().catch(()=>({success:false,message:'Invalid response'}));
      if (!r.ok || !j.success) throw new Error(j.message||('HTTP '+r.status));
      alert('Estimate exported to Finance as expense entry.');
    }catch(e){ alert(e.message||'Export failed'); }
  });

  planBtn.addEventListener('click', async ()=>{
    if (window.IS_GUEST){ alert('Please log in to import from plan.'); return; }
    const pid = projectSelect.value;
    if (!pid){ alert('Select a project first.'); return; }
    try{
      // Get latest designer summary/estimates for this project
      const u = api('/designs_summary.php?project_id=') + encodeURIComponent(pid);
      const { ok, data } = await fetchJsonWithRetry(u);
      if (!ok || !data || !Array.isArray(data.data) || !data.data.length){
        alert('No designer plan with estimates found for this project.');
        return;
      }
      // Use the first (latest) entry
      const entry = data.data[0];
      const est = entry && entry.estimates ? entry.estimates : null;
      if (!est){
        alert('Designer data has no estimates.');
        return;
      }
      estimateFromDesigner(est);
    }catch(e){
      alert(e.message||'Failed to import from plan');
    }
  });

  clearBtn.addEventListener('click', ()=>{
    if(confirm('Clear all items?')){
      state.items = [];
      renderItems();
    }
  });

  // Initialize
  clampTax();
  loadProjects();
  // Suppliers/products can be public or protected; attempt load but handle errors for guests
  loadSuppliers().catch(()=>{
    // In guest mode silently ignore; user can still type description/unit price manually
    if (window.IS_GUEST){
      supplierSelect.innerHTML = '<option value="">Suppliers unavailable in demo</option>';
      productSelect.innerHTML = '<option value="">Products unavailable in demo</option>';
    }
  });
  updatePrintMeta();
})();
