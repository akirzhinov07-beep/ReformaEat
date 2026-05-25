<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Панель управления — Магистраль Р-217</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-body">
<header class="admin-header">
  <div class="admin-header-logo">Магистраль <span>Р-217</span></div>
  <span class="admin-header-badge">Панель управления</span>
</header>
<main class="admin-main">
  <h1 class="admin-title">Ассортимент</h1>
  <p class="admin-subtitle">Добавляйте, редактируйте и удаляйте товары каталога</p>
  <div class="admin-card">
    <div class="admin-card-title">Добавить товар</div>
    <form id="add-form">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Название *</label><input class="form-input" type="text" id="f-name" required placeholder="Дренажная труба ПП DN200"></div>
        <div class="form-group"><label class="form-label">Категория</label><input class="form-input" type="text" id="f-category" placeholder="Дренаж и водоотвод" list="categories-list"><datalist id="categories-list"></datalist></div>
        <div class="form-group"><label class="form-label">Цена</label><input class="form-input" type="text" id="f-price" placeholder="Уточняйте у менеджера"></div>
        <div class="form-group"><label class="form-label">Характеристики</label><textarea class="form-textarea" id="f-specs" rows="3" placeholder="DN200, длина 6 м, SN8..."></textarea></div>
        <div class="form-group form-full">
          <label class="form-label">Фотография товара</label>
          <div class="form-file-area" id="drop-zone">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <p class="form-file-hint"><strong>Нажмите для выбора</strong> или перетащите файл сюда</p>
            <p class="form-file-hint" style="font-size:13px;margin-top:4px;">JPG, PNG, WebP — до 8 МБ</p>
            <input type="file" id="file-input" accept="image/*" style="display:none">
            <img class="img-preview" id="img-preview" alt="">
          </div>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-admin btn-admin-primary">Добавить товар</button>
        <button type="button" class="btn-admin btn-admin-secondary" id="clear-form-btn">Очистить</button>
      </div>
    </form>
  </div>
  <div class="admin-card">
    <div class="admin-card-title" style="display:flex;justify-content:space-between;align-items:center;"><span>Список товаров</span><span id="products-count" style="font-size:14px;font-weight:400;color:var(--text-muted)"></span></div>
    <div id="products-container"><div class="admin-empty">Загрузка...</div></div>
  </div>
</main>
<div class="modal-overlay" id="edit-modal">
  <div class="modal-box">
    <div class="modal-header"><span class="modal-title">Редактировать товар</span><button class="modal-close" id="modal-close-btn">✕</button></div>
    <form id="edit-form">
      <input type="hidden" id="edit-id">
      <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="form-group"><label class="form-label">Название *</label><input class="form-input" type="text" id="edit-name" required></div>
        <div class="form-group"><label class="form-label">Категория</label><input class="form-input" type="text" id="edit-category"></div>
        <div class="form-group"><label class="form-label">Цена</label><input class="form-input" type="text" id="edit-price"></div>
        <div class="form-group"><label class="form-label">Характеристики</label><textarea class="form-textarea" id="edit-specs" rows="3"></textarea></div>
        <div class="form-group">
          <label class="form-label">Новая фотография (необязательно)</label>
          <div class="form-file-area" id="edit-drop-zone">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:28px;height:28px;margin:0 auto 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <p class="form-file-hint" style="font-size:13px;"><strong>Выберите новое фото</strong> или оставьте пустым</p>
            <input type="file" id="edit-file-input" accept="image/*" style="display:none">
            <img class="img-preview" id="edit-img-preview" alt="">
          </div>
        </div>
      </div>
      <div class="form-actions" style="margin-top:20px;">
        <button type="submit" class="btn-admin btn-admin-primary">Сохранить</button>
        <button type="button" class="btn-admin btn-admin-secondary" id="modal-cancel-btn">Отмена</button>
      </div>
    </form>
  </div>
</div>
<div class="admin-notification" id="notification"></div>
<script>
  const API = '../api/products.php';
  const UPLOAD = '../api/upload.php';
  function notify(msg, type='success'){const el=document.getElementById('notification');el.textContent=msg;el.className='admin-notification show '+type;setTimeout(()=>el.classList.remove('show'),3500);}
  function setupDropZone(zone,fileInput,preview){zone.addEventListener('click',()=>fileInput.click());zone.addEventListener('dragover',e=>{e.preventDefault();zone.classList.add('drag-over');});zone.addEventListener('dragleave',()=>zone.classList.remove('drag-over'));zone.addEventListener('drop',e=>{e.preventDefault();zone.classList.remove('drag-over');if(e.dataTransfer.files[0])showPreview(e.dataTransfer.files[0],preview,fileInput);});fileInput.addEventListener('change',()=>{if(fileInput.files[0])showPreview(fileInput.files[0],preview,fileInput);});}
  function showPreview(file,previewEl,fileInput){const reader=new FileReader();reader.onload=e=>{previewEl.src=e.target.result;previewEl.style.display='block';};reader.readAsDataURL(file);const dt=new DataTransfer();dt.items.add(file);fileInput.files=dt.files;}
  setupDropZone(document.getElementById('drop-zone'),document.getElementById('file-input'),document.getElementById('img-preview'));
  setupDropZone(document.getElementById('edit-drop-zone'),document.getElementById('edit-file-input'),document.getElementById('edit-img-preview'));
  async function uploadImage(fileInput){if(!fileInput.files[0])return null;const fd=new FormData();fd.append('image',fileInput.files[0]);const res=await fetch(UPLOAD,{method:'POST',body:fd});if(!res.ok)throw new Error('Ошибка загрузки изображения');const data=await res.json();return data.url;}
  let products=[];
  async function loadProducts(){const res=await fetch(API);products=await res.json();renderProducts();updateCategoryList();}
  function updateCategoryList(){const dl=document.getElementById('categories-list');const cats=[...new Set(products.map(p=>p.category).filter(Boolean))];dl.innerHTML=cats.map(c=>`<option value="${esc(c)}">`).join('');}
  function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
  function plural(n,one,few,many){if(n%10===1&&n%100!==11)return one;if([2,3,4].includes(n%10)&&![12,13,14].includes(n%100))return few;return many;}
  function renderProducts(){const container=document.getElementById('products-container');const count=document.getElementById('products-count');count.textContent=products.length+' '+plural(products.length,'товар','товара','товаров');if(!products.length){container.innerHTML='<div class="admin-empty">Товаров пока нет. Добавьте первый товар выше.</div>';return;}container.innerHTML=`<table class="admin-product-table"><thead><tr><th style="width:60px"></th><th>Название</th><th>Категория</th><th>Цена</th><th style="width:100px">Действия</th></tr></thead><tbody>${products.map(p=>`<tr><td>${p.image?`<img class="admin-product-thumb" src="../${esc(p.image)}" alt="">`:`<div class="admin-product-thumb-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>`}</td><td><strong>${esc(p.name)}</strong></td><td>${esc(p.category)||'<span style="color:var(--border)">—</span>'}</td><td>${esc(p.price)||'<span style="color:var(--text-muted)">Уточняйте у менеджера</span>'}</td><td><div class="table-actions"><button class="btn-icon btn-icon-edit" onclick="openEditModal('${esc(p.id)}')" title="Редактировать"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button><button class="btn-icon btn-icon-delete" onclick="deleteProduct('${esc(p.id)}')" title="Удалить"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button></div></td></tr>`).join('')}</tbody></table>`;}
  document.getElementById('add-form').addEventListener('submit',async e=>{e.preventDefault();const btn=e.target.querySelector('[type=submit]');btn.disabled=true;btn.textContent='Добавление...';try{const imageUrl=await uploadImage(document.getElementById('file-input'));const body={name:document.getElementById('f-name').value,category:document.getElementById('f-category').value,price:document.getElementById('f-price').value,specs:document.getElementById('f-specs').value,image:imageUrl||''};const res=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});if(!res.ok)throw new Error('Ошибка сохранения');await loadProducts();clearAddForm();notify('Товар добавлен');}catch(err){notify(err.message,'error');}finally{btn.disabled=false;btn.textContent='Добавить товар';}});
  document.getElementById('clear-form-btn').addEventListener('click',clearAddForm);
  function clearAddForm(){document.getElementById('add-form').reset();const preview=document.getElementById('img-preview');preview.src='';preview.style.display='none';}
  async function deleteProduct(id){if(!confirm('Удалить этот товар?'))return;try{const res=await fetch(API+'?id='+id,{method:'DELETE'});if(!res.ok)throw new Error('Ошибка удаления');await loadProducts();notify('Товар удалён');}catch(err){notify(err.message,'error');}}
  const modal=document.getElementById('edit-modal');
  function openEditModal(id){const p=products.find(x=>x.id===id);if(!p)return;document.getElementById('edit-id').value=p.id;document.getElementById('edit-name').value=p.name;document.getElementById('edit-category').value=p.category||'';document.getElementById('edit-price').value=p.price||'';document.getElementById('edit-specs').value=p.specs||'';const prev=document.getElementById('edit-img-preview');prev.src=p.image?'../'+p.image:'';prev.style.display=p.image?'block':'none';document.getElementById('edit-file-input').value='';modal.classList.add('open');}
  document.getElementById('modal-close-btn').addEventListener('click',()=>modal.classList.remove('open'));
  document.getElementById('modal-cancel-btn').addEventListener('click',()=>modal.classList.remove('open'));
  modal.addEventListener('click',e=>{if(e.target===modal)modal.classList.remove('open');});
  document.getElementById('edit-form').addEventListener('submit',async e=>{e.preventDefault();const btn=e.target.querySelector('[type=submit]');btn.disabled=true;btn.textContent='Сохранение...';const id=document.getElementById('edit-id').value;try{const newImage=await uploadImage(document.getElementById('edit-file-input'));const body={name:document.getElementById('edit-name').value,category:document.getElementById('edit-category').value,price:document.getElementById('edit-price').value,specs:document.getElementById('edit-specs').value};if(newImage)body.image=newImage;const res=await fetch(API+'?id='+id,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});if(!res.ok)throw new Error('Ошибка обновления');await loadProducts();modal.classList.remove('open');notify('Товар обновлён');}catch(err){notify(err.message,'error');}finally{btn.disabled=false;btn.textContent='Сохранить';}});
  loadProducts();
</script>
</body>
</html>
