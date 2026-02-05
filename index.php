<?php require_once __DIR__ . '/util.php'; start_session(); $u = current_user(); ?><!doctype html>
<html lang="ro"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?=h(APP_NAME)?> - Transfer</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b1020;color:#e8ecff;margin:0}
.wrap{max-width:900px;margin:0 auto;padding:28px}
.card{background:#111a33;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:block;font-size:12px;opacity:.85;margin-bottom:6px}
input,textarea,select{width:100%;box-sizing:border-box;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:#0e1630;color:#e8ecff;padding:12px}
textarea{min-height:90px;resize:vertical}
.btn{background:#6d5efc;border:none;color:white;padding:12px 14px;border-radius:12px;cursor:pointer;font-weight:600}
.btn:disabled{opacity:.5;cursor:not-allowed}
.muted{opacity:.8;font-size:13px}
.bar{height:10px;background:rgba(255,255,255,.12);border-radius:999px;overflow:hidden}
.bar>div{height:100%;width:0%;background:#39d98a}
.mono{font-family:ui-monospace,Menlo,Consolas,monospace}
a{color:#9bd1ff}

/* TOP */
.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;gap:12px;flex-wrap:wrap}
.badge{font-size:12px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.08)}
.top-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}

/* ===== File picker custom + drag&drop ===== */
.filebox{display:flex;gap:10px;align-items:center}
.filebtn{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.10);color:#e8ecff;padding:12px 14px;border-radius:12px;cursor:pointer;font-weight:700;white-space:nowrap}
.filelabel{flex:1;min-width:220px;background:#0e1630;border:1px solid rgba(255,255,255,.14);border-radius:12px;padding:12px;color:#e8ecff;opacity:.95;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dropzone{margin-top:10px;border:1px dashed rgba(255,255,255,.22);background:rgba(255,255,255,.04);border-radius:14px;padding:14px;text-align:center;transition:all .15s ease}
.dropzone strong{display:block;font-size:13px}
.dropzone span{display:block;margin-top:6px;font-size:12px;opacity:.8}
.dropzone.drag{border-color:rgba(57,217,138,.9);background:rgba(57,217,138,.08)}
.filesummary{margin-top:8px;font-size:12px;opacity:.85}

/* ===== MOBILE FRIENDLY ===== */
@media (max-width: 720px){
  .wrap{padding:16px}
  .card{padding:14px}
  .row{grid-template-columns:1fr} /* o coloana */
  h1{font-size:18px !important}

  .top-actions{width:100%;gap:8px}
  .badge{display:inline-flex;align-items:center}

  .filebox{flex-direction:column;align-items:stretch}
  .filebtn{width:100%}
  .filelabel{width:100%;min-width:0}

  .btn{width:100%}
  .dropzone{padding:12px}
}
</style></head>

<body><div class="wrap">
<div class="top">
  <h1 style="margin:0;font-size:22px"><?=h(APP_NAME)?> 🇷🇴</h1>

  <div class="top-actions">
    <div class="badge">Limită: 2GB / transfer</div>
    <?php if ($u): ?>
      <a class="badge" href="user.php" style="text-decoration:none; color:inherit; font-weight:700;">Cont: <?=h($u['email'])?></a>
    <?php else: ?>
      <a class="badge" href="account.php" style="text-decoration:none; color:inherit; font-weight:700;">Cont</a>
    <?php endif; ?>
    <a class="badge" href="admin.php" style="text-decoration:none; color:inherit; font-weight:700;">Administrare</a>
  </div>
</div>

<div class="card">
<div class="row">
  <div>
    <label>Fișiere</label>

    <!-- input real (ascuns) -->
    <input id="files" type="file" multiple style="display:none" />

    <!-- UI custom -->
    <div class="filebox">
      <button type="button" class="filebtn" id="pickBtn">Alege fișiere</button>
      <div class="filelabel" id="fileLabel">Niciun fișier selectat</div>
    </div>

    <!-- drag&drop -->
    <div class="dropzone" id="dropzone">
      <strong>Trage fișierele aici (Drag & Drop)</strong>
      <span>sau apasă „Alege fișiere”</span>
      <div class="filesummary" id="fileSummary"></div>
    </div>
  </div>

  <div>
    <label>Expiră în</label>
    <select id="expire">
      <option value="1">o zi</option>
      <option value="3">3 zile</option>
      <option value="7" selected>7 zile</option>
      <option value="14">14 zile</option>
    </select>
  </div>
</div>

<div class="row" style="margin-top:12px">
  <div><label>E-mail destinatar (opțional)</label><input id="to" type="email" placeholder="destinatar@exemplu.ro"/></div>
  <div><label>Subiect (opțional)</label><input id="title" type="text" placeholder="Ați primit fișiere"/></div>
</div>

<div class="row" style="margin-top:12px">
  <div><label>E-mail destinatar 2 (opțional)</label><input id="to2" type="email" placeholder="destinatar2@exemplu.ro"/></div>
  <div><label>E-mail destinatar 3 (opțional)</label><input id="to3" type="email" placeholder="destinatar3@exemplu.ro"/></div>
</div>

<div class="row" style="margin-top:12px">
  <div>
    <label>Parolă transfer (opțional)</label>
    <input id="pw" type="password" placeholder="Ex: 1234 (Se va solicita parola la accesarea transferului trimis)"/>
    <div class="muted" style="margin-top:8px">Setați o parolă pentru un plus de securitate.</div>
  </div>
  <div></div>
</div>

<div style="margin-top:12px">
  <label>Mesaj (opțional)</label>
  <textarea id="msg" placeholder="Specificați un mesaj scurt pentru destinatar..."></textarea>
</div>

<div style="margin-top:12px"><button id="btn" class="btn">Inițializare Transfer</button></div>
<div style="margin-top:14px" class="bar"><div id="bar"></div></div>
<div id="status" class="muted" style="margin-top:10px"></div>
<div id="result" class="mono" style="margin-top:10px"></div>
</div></div>

<script>
const CHUNK = <?= (int)CHUNK_BYTES ?>;

const inputFiles = document.getElementById('files');
const pickBtn = document.getElementById('pickBtn');
const fileLabel = document.getElementById('fileLabel');
const fileSummary = document.getElementById('fileSummary');
const dropzone = document.getElementById('dropzone');

pickBtn.addEventListener('click', () => inputFiles.click());

function bytesToHuman(b){
  const gb = 1024*1024*1024, mb = 1024*1024, kb = 1024;
  if (b >= gb) return (b/gb).toFixed(2) + ' GB';
  if (b >= mb) return (b/mb).toFixed(2) + ' MB';
  if (b >= kb) return (b/kb).toFixed(2) + ' KB';
  return b + ' B';
}

function updateFileUI(){
  const files = inputFiles.files ? [...inputFiles.files] : [];
  if (!files.length){
    fileLabel.textContent = 'Niciun fișier selectat';
    fileSummary.textContent = '';
    return;
  }
  if (files.length === 1){
    fileLabel.textContent = files[0].name;
  } else {
    fileLabel.textContent = files.length + ' fișiere selectate';
  }
  const total = files.reduce((a,f)=>a+f.size,0);
  fileSummary.textContent = 'Total: ' + bytesToHuman(total);
}

inputFiles.addEventListener('change', updateFileUI);
updateFileUI();

/* ===== Drag & Drop ===== */
function prevent(e){ e.preventDefault(); e.stopPropagation(); }

['dragenter','dragover','dragleave','drop'].forEach(ev=>{
  dropzone.addEventListener(ev, prevent);
});

dropzone.addEventListener('dragenter', ()=> dropzone.classList.add('drag'));
dropzone.addEventListener('dragover', ()=> dropzone.classList.add('drag'));
dropzone.addEventListener('dragleave', ()=> dropzone.classList.remove('drag'));
dropzone.addEventListener('drop', (e)=>{
  dropzone.classList.remove('drag');
  const dt = e.dataTransfer;
  if (!dt || !dt.files || !dt.files.length) return;

  const d = new DataTransfer();
  [...dt.files].forEach(f=>d.items.add(f));
  inputFiles.files = d.files;
  updateFileUI();
});

async function postJson(url, obj){
  const r = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(obj)});
  const t = await r.text();
  try { return {ok:r.ok, status:r.status, data: JSON.parse(t)}; } catch(e){ return {ok:r.ok, status:r.status, data:t}; }
}

async function upload(){
  const files = [...document.getElementById('files').files];
  if(!files.length) return alert('Selectați cel puțin un fișier.');
  let total = files.reduce((a,f)=>a+f.size,0);
  if(total > <?= (int)MAX_TRANSFER_BYTES ?>) return alert('Dimensiunea prea mare. Limita este de 2GB/transfer.');
  if(files.length > <?= (int)MAX_FILES_PER_TRANSFER ?>) return alert('Prea multe fișiere selectate. Limita este de 50 fișiere/transfer');

  const btn = document.getElementById('btn');
  const bar = document.getElementById('bar');
  const status = document.getElementById('status');
  const result = document.getElementById('result');
  btn.disabled = true; result.textContent=''; bar.style.width='0%';
  status.textContent = 'Inițializare transfer...';

  const payload = {
    title: document.getElementById('title').value.trim(),
    message: document.getElementById('msg').value.trim(),
    recipient_email: document.getElementById('to').value.trim(),
    recipient_email2: document.getElementById('to2') ? document.getElementById('to2').value.trim() : '',
    recipient_email3: document.getElementById('to3') ? document.getElementById('to3').value.trim() : '',
    password: document.getElementById('pw').value,
    expire_days: parseInt(document.getElementById('expire').value,10),
    files: files.map(f=>({name:f.name, size:f.size, type:f.type||'application/octet-stream'}))
  };

  const created = await postJson('api_create_transfer.php', payload);
  if(!created.ok){ btn.disabled=false; return alert('Eroare: ' + (created.data?.error || created.data)); }

  const {transfer_id, upload_files} = created.data;
  let uploadedBytes = 0;

  for(let i=0;i<files.length;i++){
    const f = files[i];
    const fileId = upload_files[i].file_id;
    status.textContent = `Încărcare ${f.name}...`;
    const totalChunks = Math.ceil(f.size / CHUNK);

    for(let c=0;c<totalChunks;c++){
      const start = c*CHUNK, end = Math.min(f.size, start+CHUNK);
      const chunk = f.slice(start,end);
      const fd = new FormData();
      fd.append('transfer_id', transfer_id);
      fd.append('file_id', fileId);
      fd.append('chunk_index', String(c));
      fd.append('total_chunks', String(totalChunks));
      fd.append('chunk', chunk);
      const r = await fetch('api_upload_chunk.php', {method:'POST', body: fd});
      if(!r.ok){ btn.disabled=false; return alert('Chunk upload failed: ' + await r.text()); }
      uploadedBytes += (end-start);
      bar.style.width = Math.min(100, Math.round(uploadedBytes/total*100)) + '%';
    }

    const completed = await postJson('api_complete_file.php', {transfer_id, file_id: fileId});
    if(!completed.ok){ btn.disabled=false; return alert('Complete file failed: ' + (completed.data?.error || completed.data)); }
  }

  status.textContent = 'Finalizare transfer...';
  const fin = await postJson('api_finalize_transfer.php', {transfer_id});
  if(!fin.ok){ btn.disabled=false; return alert('Eroare finalizare: ' + (fin.data?.error || fin.data)); }

  status.textContent = 'Transferul a fost efectuat. Link-ul a fost generat. Un e-mail va fi trimis către destinatarul specificat';
  const link = fin.data.link;
  result.innerHTML = `Link descărcare: <a href="${link}" target="_blank" rel="noopener">${link}</a>`;
  btn.disabled=false;
}
document.getElementById('btn').addEventListener('click', upload);
</script>
</body></html>