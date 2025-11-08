<?php /* Multi-Dashboard mit Discord-Webhook aus dem Browser */ ?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Steam Market – Multi Watch + Discord</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { color-scheme: light dark; }
    body { font: 16px system-ui, sans-serif; margin: 2rem; }
    h1 { margin: 0 0 .25rem 0; font-size: 1.25rem; }
    .muted { color:#666; }
    #bar { display:flex; align-items:center; gap:.75rem; margin-bottom:.75rem; flex-wrap:wrap; }
    .pill { padding:.2rem .6rem; border:1px solid #bbb; border-radius:999px; font-size:.85rem; }
    #spinner { display:inline-block; width:1rem; text-align:center; }
    table { border-collapse: collapse; width: 100%; max-width: 1200px; }
    th, td { padding:.6rem .5rem; border-bottom:1px solid #e0e0e0; text-align:left; }
    th { font-weight:600; }
    tr:hover { background: rgba(0,0,0,.04); }
    .good { color:#1a7f37; font-weight:600; }
    .warn { color:#9a6700; font-weight:600; }
    .bad  { color:#d1242f; font-weight:600; }
    code { background:#f5f5f5; padding:.1rem .3rem; border-radius:.25rem; }
    a.btn, button.btn { text-decoration:none; border:1px solid #888; padding:.3rem .5rem; border-radius:.35rem; background:none; cursor:pointer; }

    .row { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    input[type="text"]{ padding:.35rem .5rem; min-width: 380px; }
    label { font-size:.9rem; }
  </style>
</head>
<body>
  <div id="bar">
    <h1>Steam Market Watch – Messer</h1>
    <span class="pill" id="status">lädt…</span>
    <span id="spinner">⠋</span>
    <span class="muted">Letzte Aktualisierung: <span id="gen">–</span></span>
  </div>

  <div class="row" style="margin-bottom:1rem">
    <label for="wh">Discord Webhook URL</label>
    <input type="text" id="wh" placeholder="https://discord.com/api/webhooks/...">
    <label><input type="checkbox" id="enableAlerts"> Alerts aktivieren</label>
    <button class="btn" id="save">Speichern</button>
    <button class="btn" id="test">Test senden</button>
    <span class="muted" id="saveStatus"></span>
  </div>

  <table id="tbl">
    <thead>
      <tr>
        <th>Item</th>
        <th>Lowest</th>
        <th>Median</th>
        <th>Volume</th>
        <th>Ziel</th>
        <th>Status</th>
        <th>Zuletzt</th>
        <th>Link</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>

  <p class="muted" style="margin-top:1rem">
    Preise via <code>priceoverview</code> (undokumentierter Market‑Endpoint; moderat pollen & Cache nutzen, um Rate‑Limits zu vermeiden). Alerts werden clientseitig per Discord‑Webhook gesendet.
  </p>

<script>
  const FETCH_URL  = 'fetch_multi.php';
  const REFRESH_MS = 80000;
  const el  = {
    status: document.getElementById('status'),
    spinner:document.getElementById('spinner'),
    gen:    document.getElementById('gen'),
    tbody:  document.querySelector('#tbl tbody'),
    wh:     document.getElementById('wh'),
    enable: document.getElementById('enableAlerts'),
    save:   document.getElementById('save'),
    test:   document.getElementById('test'),
    saveStatus: document.getElementById('saveStatus')
  };

  // Spinner
  const frames=['⠋','⠙','⠹','⠸','⠼','⠴','⠦','⠧','⠇','⠏']; let i=0; setInterval(()=>{ el.spinner.textContent = frames[i++%frames.length]; }, 100);

  // Lokaler Speicher für Webhook + Toggle
  const store = {
    get url(){ return localStorage.getItem('discordWebhook') || ''; },
    set url(v){ localStorage.setItem('discordWebhook', v||''); },
    get enabled(){ return localStorage.getItem('alertsEnabled') === '1'; },
    set enabled(v){ localStorage.setItem('alertsEnabled', v? '1':'0'); }
  };

  // Dedupe innerhalb einer Browsersession
  const alerted = new Set(); // key: item@price

  function loadSettings(){ el.wh.value = store.url; el.enable.checked = store.enabled; }
  function saveSettings(){ store.url = el.wh.value.trim(); store.enabled = el.enable.checked; el.saveStatus.textContent = 'gespeichert'; setTimeout(()=> el.saveStatus.textContent = '', 1500); }

  el.save.onclick = saveSettings;
  el.test.onclick = async ()=>{
    try{ await sendWebhook('Test‑Nachricht', `<@1308134572168446064> Dies ist ein Test von ${location.href}`, null, null); el.saveStatus.textContent='Test gesendet'; setTimeout(()=> el.saveStatus.textContent='',1500);}catch(e){ el.saveStatus.textContent='Fehler beim Test'; console.error(e);} };

  async function sendWebhook(title, message, price, link){
    const url = store.url.trim();
    if (!url) throw new Error('Kein Webhook konfiguriert');
    const content = link ? `🔔 **${title}**\n${message}\n${link}` : `🔔 **${title}**\n${message}`;
    const res = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ content })});
    if (!res.ok) throw new Error('Discord HTTP '+res.status);
  }

  function td(text, cls=''){ const e=document.createElement('td'); e.textContent=text; if (cls) e.className=cls; return e; }
  function tdLink(href, text='öffnen'){ const e=document.createElement('td'); const a=document.createElement('a'); a.href=href; a.target='_blank'; a.rel='noopener'; a.textContent=text; a.className='btn'; e.appendChild(a); return e; }
  function statusLabel(it){ if(it.success===false) return {l:'Fehler',c:'bad'}; if(it.warning) return {l:'Warnung (Cache)',c:'warn'}; if(it.under_target) return {l:'Unter Zielpreis',c:'good'}; return {l:'OK',c:''}; }

  async function load(){
    try{
      el.status.textContent = 'lädt…';
      const r = await fetch(FETCH_URL, {cache:'no-store'});
      if (!r.ok) throw new Error('HTTP '+r.status);
      const d = await r.json();
      el.gen.textContent = d.generated_at || '–';
      el.tbody.innerHTML = '';

      (d.items||[]).forEach(async (it)=>{
        const tr = document.createElement('tr');
        tr.appendChild(td(it.item || '–'));
        tr.appendChild(td(it.lowest_price ?? (it.lowest_float!=null ? it.lowest_float.toFixed(2) : '–')));
        tr.appendChild(td(it.median_price ?? '–'));
        tr.appendChild(td(it.volume ?? '–'));
        tr.appendChild(td(it.target!=null ? it.target.toFixed(2) : '–'));
        const st = statusLabel(it);
        tr.appendChild(td(st.l, st.c));
        tr.appendChild(td(it.fetched_at ?? '–'));
        tr.appendChild(tdLink(it.market_url || '#'));
        el.tbody.appendChild(tr);

        // Clientseitiger Alert, falls aktiviert
        if (store.enabled && it.under_target && it.lowest_float!=null && it.target!=null) {
          const key = it.item + '@' + it.lowest_float;
          if (!alerted.has(key) && store.url) {
            try{
              const msg = `${it.item} ist bei ${it.lowest_price ?? it.lowest_float} (≤ ${it.target})`;
              await sendWebhook(it.item, msg, it.lowest_float, it.market_url);
              alerted.add(key);
            }catch(e){ console.error('Webhook-Fehler', e); }
          }
        }
      });

      el.status.textContent = 'aktualisiert';
    } catch(e){
      console.error(e); el.status.textContent = 'Fehler';
    }
  }

  loadSettings();
  load();
  setInterval(load, REFRESH_MS);
</script>
</body>
</html>
