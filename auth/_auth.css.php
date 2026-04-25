@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
  -webkit-font-smoothing: antialiased;
  background: #f2f5f8;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  color: #1a2e4a;
}
.auth-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 4px 28px rgba(26,46,74,.12);
  padding: 2.5rem 2.5rem 2rem;
  width: 100%;
  max-width: 420px;
}
.auth-logo {
  display: flex;
  align-items: center;
  gap: .85rem;
  margin-bottom: 2rem;
}
.logo-mark {
  width: 44px; height: 44px;
  background: #3aafa9;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 800; font-size: 1rem;
  letter-spacing: .02em;
  flex-shrink: 0;
}
.auth-logo h1 { font-size: 1.45rem; color: #1a2e4a; font-weight: 700; line-height: 1.2; }
.auth-logo p  { font-size: .8rem;  color: #64748b; }

.alert {
  border-radius: 7px;
  padding: .7rem 1rem;
  font-size: .875rem;
  margin-bottom: 1.25rem;
}
.alert-error   { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; }
.alert-warning { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
.alert-success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }

.form-group { margin-bottom: 1.1rem; }
label {
  display: block;
  font-size: .83rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: .35rem;
}
input[type=text],
input[type=password],
input[type=number],
select {
  width: 100%;
  padding: .65rem .95rem;
  border: 1.5px solid #d1d5db;
  border-radius: 8px;
  font-size: .95rem;
  color: #1a2e4a;
  background: #fff;
  transition: border-color .15s, box-shadow .15s;
}
input:focus, select:focus {
  outline: none;
  border-color: #3aafa9;
  box-shadow: 0 0 0 3px rgba(0,180,160,.15);
}
.btn {
  display: block;
  width: 100%;
  padding: .78rem 1rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity .15s, transform .08s;
  text-align: center;
  text-decoration: none;
}
.btn:active { transform: scale(.98); }
.btn:hover  { opacity: .88; }
.btn-primary  { background: #3aafa9; color: #fff; margin-top: .25rem; }
.btn-secondary { background: #1a2e4a; color: #fff; margin-top: .5rem; }
.btn-outline  {
  background: transparent;
  border: 1.5px solid #d1d5db;
  color: #374151;
  margin-top: .5rem;
}
.btn-outline:hover { border-color: #3aafa9; color: #3aafa9; opacity: 1; }

.auth-footer {
  margin-top: 1.5rem;
  text-align: center;
  font-size: .85rem;
  color: #64748b;
}
.auth-footer a {
  color: #3aafa9;
  text-decoration: none;
  font-weight: 600;
}
.auth-footer a:hover { text-decoration: underline; }

/* Practitioner list */
.practitioner-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .75rem;
  margin-bottom: 1.25rem;
}
.practitioner-btn {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: .9rem .75rem;
  text-align: center;
  cursor: pointer;
  font-size: .9rem;
  font-weight: 600;
  color: #1a2e4a;
  transition: border-color .15s, background .15s;
}
.practitioner-btn:hover {
  border-color: #3aafa9;
  background: #e8f5f4;
  color: #3aafa9;
}
.practitioner-btn .initials {
  display: block;
  width: 40px; height: 40px;
  background: #1a2e4a;
  border-radius: 50%;
  color: #fff;
  font-size: .85rem;
  font-weight: 700;
  line-height: 40px;
  margin: 0 auto .5rem;
}

/* PIN pad */
.pin-dots {
  display: flex;
  gap: .6rem;
  justify-content: center;
  margin-bottom: 1.5rem;
}
.pin-dot {
  width: 14px; height: 14px;
  border-radius: 50%;
  border: 2px solid #d1d5db;
  background: #fff;
  transition: background .15s, border-color .15s;
}
.pin-dot.filled { background: #3aafa9; border-color: #3aafa9; }

.pin-pad {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .6rem;
  max-width: 260px;
  margin: 0 auto 1rem;
}
.pin-key {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 1rem;
  font-size: 1.2rem;
  font-weight: 600;
  color: #1a2e4a;
  cursor: pointer;
  text-align: center;
  transition: background .1s, border-color .1s;
  user-select: none;
}
.pin-key:hover  { background: #e8f5f4; border-color: #3aafa9; }
.pin-key:active { background: #ccfbf1; }
.pin-key.del    { font-size: .85rem; color: #ef4444; }

/* Taalkiezer */
.lang-switcher { display: flex; gap: .25rem; justify-content: center; margin-bottom: 1.25rem; }
.lang-btn {
  padding: .25rem .6rem; border-radius: 5px; font-size: .78rem; font-weight: 600;
  text-decoration: none; color: #64748b; border: 1.5px solid #e2e8f0;
  transition: all .15s;
}
.lang-btn:hover { border-color: #3aafa9; color: #3aafa9; }
.lang-btn.lang-active { border-color: #3aafa9; color: #3aafa9; background: #e8f5f4; }
