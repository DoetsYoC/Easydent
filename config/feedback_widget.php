<?php
// Inclusief na <body> op elke pagina waar feedback gewenst is.
// Verwacht: $csrf (csrfToken()) beschikbaar in de includerende pagina.
?>
<style>
.fb-btn{position:fixed;bottom:1.5rem;right:1.5rem;z-index:900;background:#1a2e4a;color:#fff;border:none;border-radius:50px;padding:.6rem 1.1rem;font-size:.82rem;font-weight:700;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.2);display:flex;align-items:center;gap:.4rem;transition:background .15s,transform .1s;font-family:inherit}
.fb-btn:hover{background:#3aafa9;transform:translateY(-1px)}
.fb-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:910;align-items:center;justify-content:center;padding:1rem}
.fb-backdrop.open{display:flex}
.fb-modal{background:#fff;border-radius:14px;box-shadow:0 8px 40px rgba(0,0,0,.18);padding:2rem;width:100%;max-width:480px;font-family:inherit}
.fb-modal h3{font-size:1.1rem;font-weight:700;color:#1a2e4a;margin-bottom:1.25rem}
.fb-group{margin-bottom:1rem}
.fb-group label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:.3rem}
.fb-group input,.fb-group textarea{width:100%;padding:.6rem .85rem;border:1.5px solid #d1d5db;border-radius:8px;font-size:.9rem;font-family:inherit;color:#1a2e4a;resize:vertical}
.fb-group input:focus,.fb-group textarea:focus{outline:none;border-color:#3aafa9;box-shadow:0 0 0 3px rgba(58,175,169,.15)}
.fb-actions{display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.25rem}
.fb-btn-cancel{background:transparent;border:1.5px solid #d1d5db;color:#374151;border-radius:8px;padding:.55rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;font-family:inherit}
.fb-btn-cancel:hover{border-color:#9ca3af}
.fb-btn-submit{background:#3aafa9;color:#fff;border:none;border-radius:8px;padding:.55rem 1.25rem;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .15s}
.fb-btn-submit:hover{opacity:.88}
.fb-btn-submit:disabled{opacity:.5;cursor:default}
.fb-msg{font-size:.83rem;padding:.55rem .85rem;border-radius:7px;margin-bottom:.75rem;display:none}
.fb-msg.ok{background:#f0fdf4;border:1px solid #86efac;color:#166534}
.fb-msg.err{background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c}
</style>

<button class="fb-btn" onclick="fbOpen()">💬 Feedback</button>

<div class="fb-backdrop" id="fbBackdrop" onclick="if(event.target===this)fbClose()">
  <div class="fb-modal">
    <h3>💬 Feedback of probleem melden</h3>
    <div class="fb-msg" id="fbMsg"></div>
    <div class="fb-group">
      <label>Onderwerp *</label>
      <input type="text" id="fbTitle" placeholder="Korte omschrijving van het probleem of idee" maxlength="150">
    </div>
    <div class="fb-group">
      <label>Toelichting</label>
      <textarea id="fbBody" rows="4" placeholder="Beschrijf wat er mis gaat, of welk idee je hebt..."></textarea>
    </div>
    <div class="fb-actions">
      <button class="fb-btn-cancel" onclick="fbClose()">Annuleren</button>
      <button class="fb-btn-submit" id="fbSubmit" onclick="fbSubmit()">Versturen</button>
    </div>
  </div>
</div>

<script>
(function() {
  const csrf = '<?= addslashes($csrf) ?>';
  const page = window.location.pathname;

  window.fbOpen  = () => document.getElementById('fbBackdrop').classList.add('open');
  window.fbClose = () => {
    document.getElementById('fbBackdrop').classList.remove('open');
    document.getElementById('fbMsg').style.display = 'none';
    document.getElementById('fbTitle').value = '';
    document.getElementById('fbBody').value  = '';
    document.getElementById('fbSubmit').disabled = false;
  };

  window.fbSubmit = async () => {
    const title = document.getElementById('fbTitle').value.trim();
    const body  = document.getElementById('fbBody').value.trim();
    const msg   = document.getElementById('fbMsg');
    const btn   = document.getElementById('fbSubmit');

    if (!title) {
      msg.textContent = 'Vul een onderwerp in.';
      msg.className = 'fb-msg err';
      msg.style.display = 'block';
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Versturen...';
    msg.style.display = 'none';

    const fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('title', title);
    fd.append('body',  body);
    fd.append('page',  page);

    try {
      const res  = await fetch('/easydent/feedback_submit.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        msg.textContent = '✓ Bedankt! Je feedback is aangemaakt als GitHub issue.';
        msg.className = 'fb-msg ok';
        msg.style.display = 'block';
        document.getElementById('fbTitle').value = '';
        document.getElementById('fbBody').value  = '';
        setTimeout(fbClose, 3000);
      } else {
        msg.textContent = data.error || 'Er ging iets mis.';
        msg.className = 'fb-msg err';
        msg.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Versturen';
      }
    } catch {
      msg.textContent = 'Verbindingsfout. Probeer het opnieuw.';
      msg.className = 'fb-msg err';
      msg.style.display = 'block';
      btn.disabled = false;
      btn.textContent = 'Versturen';
    }
  };
})();
</script>
