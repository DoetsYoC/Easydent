<?php
// Inclusief na <body> op elke pagina waar feedback gewenst is.
// Verwacht: $csrf (csrfToken()) beschikbaar in de includerende pagina.
?>
<style>
.fb-btn{position:fixed;top:.65rem;right:1.25rem;z-index:950;background:#3aafa9;color:#fff;border:none;border-radius:50px;padding:.35rem .85rem;font-size:.78rem;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.2);display:flex;align-items:center;gap:.35rem;transition:opacity .15s;font-family:inherit}
.fb-btn:hover{opacity:.85}
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

<button class="fb-btn" onclick="fbOpen()">💬 <?= __('fb_btn') ?></button>

<div class="fb-backdrop" id="fbBackdrop" onclick="if(event.target===this)fbClose()">
  <div class="fb-modal">
    <h3>💬 <?= __('fb_title') ?></h3>
    <div class="fb-msg" id="fbMsg"></div>
    <div class="fb-group">
      <label><?= __('fb_subject') ?></label>
      <input type="text" id="fbTitle" placeholder="<?= __('fb_subject_ph') ?>" maxlength="150">
    </div>
    <div class="fb-group">
      <label><?= __('fb_body') ?></label>
      <textarea id="fbBody" rows="4" placeholder="<?= __('fb_body_ph') ?>"></textarea>
    </div>
    <div class="fb-actions">
      <button class="fb-btn-cancel" onclick="fbClose()"><?= __('fb_cancel') ?></button>
      <button class="fb-btn-submit" id="fbSubmit" onclick="fbSubmit()"><?= __('fb_submit') ?></button>
    </div>
  </div>
</div>

<script>
(function() {
  const csrf = '<?= addslashes($csrf) ?>';
  const page = window.location.pathname;
  const T = {
    required:   '<?= addslashes(__('fb_required')) ?>',
    submitting: '<?= addslashes(__('fb_submitting')) ?>',
    submit:     '<?= addslashes(__('fb_submit')) ?>',
    success:    '<?= addslashes(__('fb_success')) ?>',
    error:      '<?= addslashes(__('fb_error')) ?>',
    connError:  '<?= addslashes(__('fb_conn_error')) ?>',
  };

  window.fbOpen  = () => document.getElementById('fbBackdrop').classList.add('open');
  window.fbClose = () => {
    document.getElementById('fbBackdrop').classList.remove('open');
    document.getElementById('fbMsg').style.display = 'none';
    document.getElementById('fbTitle').value = '';
    document.getElementById('fbBody').value  = '';
    document.getElementById('fbSubmit').disabled = false;
    document.getElementById('fbSubmit').textContent = T.submit;
  };

  window.fbSubmit = async () => {
    const title = document.getElementById('fbTitle').value.trim();
    const body  = document.getElementById('fbBody').value.trim();
    const msg   = document.getElementById('fbMsg');
    const btn   = document.getElementById('fbSubmit');

    if (!title) {
      msg.textContent = T.required;
      msg.className = 'fb-msg err';
      msg.style.display = 'block';
      return;
    }

    btn.disabled = true;
    btn.textContent = T.submitting;
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
        msg.textContent = T.success;
        msg.className = 'fb-msg ok';
        msg.style.display = 'block';
        document.getElementById('fbTitle').value = '';
        document.getElementById('fbBody').value  = '';
        setTimeout(fbClose, 3000);
      } else {
        msg.textContent = data.error || T.error;
        msg.className = 'fb-msg err';
        msg.style.display = 'block';
        btn.disabled = false;
        btn.textContent = T.submit;
      }
    } catch {
      msg.textContent = T.connError;
      msg.className = 'fb-msg err';
      msg.style.display = 'block';
      btn.disabled = false;
      btn.textContent = T.submit;
    }
  };
})();
</script>
