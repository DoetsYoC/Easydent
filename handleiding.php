<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Celereon — DEIN DENTAL Handleiding</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --navy:   #1a2e4a;
  --teal:   #3aafa9;
  --teal-l: #e8f5f4;
  --gray-1: #f8fafc;
  --gray-2: #f1f5f9;
  --gray-3: #e2e8f0;
  --gray-5: #64748b;
  --gray-7: #374151;
}
body {
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  background: var(--gray-2);
  color: var(--navy);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}

/* ── Header ── */
header {
  background: var(--navy);
  padding: .9rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 12px rgba(0,0,0,.18);
}
.header-brand {
  display: flex;
  align-items: center;
  gap: .65rem;
  text-decoration: none;
}
.logo-mark {
  width: 38px; height: 38px;
  background: var(--teal);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: .9rem; color: #fff;
  letter-spacing: .02em;
  flex-shrink: 0;
}
.header-brand span {
  font-size: 1.15rem;
  font-weight: 700;
  color: #fff;
}
.header-brand small {
  display: block;
  font-size: .72rem;
  color: rgba(255,255,255,.55);
  font-weight: 400;
}

/* ── Language switcher ── */
.lang-switcher {
  display: flex;
  gap: .35rem;
}
.lang-btn {
  padding: .3rem .65rem;
  border-radius: 6px;
  font-size: .78rem;
  font-weight: 600;
  text-decoration: none;
  color: rgba(255,255,255,.55);
  border: 1.5px solid rgba(255,255,255,.18);
  background: transparent;
  cursor: pointer;
  transition: all .15s;
}
.lang-btn:hover { border-color: var(--teal); color: #fff; }
.lang-btn.active { border-color: var(--teal); color: #fff; background: rgba(58,175,169,.2); }

/* ── Layout ── */
.page { max-width: 820px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

/* ── Hero ── */
.hero {
  background: linear-gradient(135deg, var(--navy) 0%, #1e3a5f 100%);
  border-radius: 14px;
  padding: 2.5rem 2rem;
  margin-bottom: 2rem;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}
.hero-icon {
  width: 64px; height: 64px;
  background: var(--teal);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem;
  flex-shrink: 0;
}
.hero h1 { font-size: 1.6rem; font-weight: 800; margin-bottom: .3rem; }
.hero p  { color: rgba(255,255,255,.65); font-size: .95rem; }

/* ── Section ── */
.section {
  background: #fff;
  border-radius: 12px;
  border: 1px solid var(--gray-3);
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
  margin-bottom: 1.5rem;
  overflow: hidden;
}
.section-header {
  background: var(--navy);
  padding: 1rem 1.5rem;
  display: flex;
  align-items: center;
  gap: .75rem;
}
.section-header .icon {
  width: 34px; height: 34px;
  background: var(--teal);
  border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}
.section-header h2 {
  font-size: 1rem;
  font-weight: 700;
  color: #fff;
}
.section-header p {
  font-size: .78rem;
  color: rgba(255,255,255,.55);
  margin-top: .1rem;
}
.section-body { padding: 1.5rem; }

/* ── Steps ── */
.steps { list-style: none; }
.steps li {
  display: flex;
  gap: .85rem;
  padding: .6rem 0;
  border-bottom: 1px solid var(--gray-3);
  font-size: .92rem;
  align-items: flex-start;
}
.steps li:last-child { border-bottom: none; }
.step-num {
  width: 24px; height: 24px;
  background: var(--teal-l);
  border: 1.5px solid var(--teal);
  color: var(--teal);
  border-radius: 50%;
  font-size: .75rem;
  font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  margin-top: .1rem;
}

/* ── Cards grid ── */
.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  margin-top: .5rem;
}
.feature-card {
  background: var(--gray-1);
  border: 1.5px solid var(--gray-3);
  border-radius: 10px;
  padding: 1.1rem;
  font-size: .88rem;
}
.feature-card .fc-icon { font-size: 1.4rem; margin-bottom: .5rem; }
.feature-card strong { display: block; font-size: .9rem; margin-bottom: .25rem; color: var(--navy); }
.feature-card span { color: var(--gray-5); font-size: .83rem; }

/* ── Note / tip ── */
.note {
  background: var(--teal-l);
  border-left: 3px solid var(--teal);
  border-radius: 0 8px 8px 0;
  padding: .75rem 1rem;
  font-size: .87rem;
  color: var(--navy);
  margin-top: 1rem;
}
.note strong { color: var(--teal); }

.warning {
  background: #fffbeb;
  border-left: 3px solid #f59e0b;
  border-radius: 0 8px 8px 0;
  padding: .75rem 1rem;
  font-size: .87rem;
  color: #78350f;
  margin-top: 1rem;
}

/* ── Divider label ── */
.role-label {
  text-align: center;
  margin: 2rem 0 1rem;
  position: relative;
}
.role-label::before {
  content: '';
  position: absolute;
  top: 50%; left: 0; right: 0;
  height: 1px;
  background: var(--gray-3);
}
.role-label span {
  position: relative;
  background: var(--gray-2);
  padding: 0 1rem;
  font-size: .8rem;
  font-weight: 700;
  color: var(--gray-5);
  letter-spacing: .06em;
  text-transform: uppercase;
}

/* ── Footer ── */
footer {
  text-align: center;
  padding: 1.5rem;
  font-size: .8rem;
  color: var(--gray-5);
}

/* ── Language visibility ── */
[data-lang] { display: none; }
[data-lang].visible { display: block; }

@media (max-width: 560px) {
  .hero { flex-direction: column; text-align: center; }
  .hero-icon { margin: 0 auto; }
  .page { padding: 1.5rem 1rem 3rem; }
}
</style>
</head>
<body>

<header>
  <a class="header-brand" href="#">
    <div class="logo-mark">C</div>
    <div>
      <span>Celereon <span style="opacity:.45;font-weight:400;font-size:.85rem;">× DEIN DENTAL</span></span>
      <small data-label="subtitle"></small>
    </div>
  </a>
  <div class="lang-switcher">
    <button class="lang-btn active" onclick="setLang('de')">DE</button>
    <button class="lang-btn"        onclick="setLang('nl')">NL</button>
    <button class="lang-btn"        onclick="setLang('en')">EN</button>
  </div>
</header>

<div class="page">

  <!-- ═══════════════════════════════════════════════════════ HERO -->
  <div class="hero">
    <div class="hero-icon">🦷</div>
    <div>
      <div style="font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--teal);margin-bottom:.4rem;" data-t="hero_network"></div>
      <h1 data-t="hero_title"></h1>
      <p  data-t="hero_sub"></p>
      <p style="margin-top:.75rem;font-size:.82rem;color:rgba(255,255,255,.45);font-style:italic;" data-t="hero_slogan"></p>
    </div>
  </div>

  <!-- ═══════════════════════════════════════ TAALBLOK (altijd zichtbaar) -->
  <div class="section" style="margin-bottom:1.5rem;">
    <div class="section-header">
      <div class="icon">🌐</div>
      <div>
        <h2>DE / NL / EN</h2>
        <p>Sprachauswahl · Taalkeuze · Language</p>
      </div>
    </div>
    <div class="section-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
      <div style="font-size:.88rem;">
        <strong style="display:block;margin-bottom:.3rem;color:var(--navy);"><svg width="20" height="13" viewBox="0 0 5 3" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;border-radius:1px;margin-right:4px"><rect width="5" height="1"/><rect y="1" width="5" height="1" fill="#D00"/><rect y="2" width="5" height="1" fill="#FFCE00"/></svg>Deutsch</strong>
        Wählen Sie Ihre bevorzugte Sprache mit den Schaltflächen <strong>DE · NL · EN</strong> oben rechts auf dieser Seite.
      </div>
      <div style="font-size:.88rem;">
        <strong style="display:block;margin-bottom:.3rem;color:var(--navy);"><svg width="20" height="13" viewBox="0 0 3 3" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;border-radius:1px;margin-right:4px"><rect width="3" height="1" fill="#AE1C28"/><rect y="1" width="3" height="1" fill="#fff"/><rect y="2" width="3" height="1" fill="#21468B"/></svg>Nederlands</strong>
        Kies uw voorkeurstaal via de knoppen <strong>DE · NL · EN</strong> rechtsboven op deze pagina.
      </div>
      <div style="font-size:.88rem;">
        <strong style="display:block;margin-bottom:.3rem;color:var(--navy);"><svg width="20" height="13" viewBox="0 0 60 30" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;border-radius:1px;margin-right:4px"><rect width="60" height="30" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#C8102E" stroke-width="4"/><path d="M30,0 V30 M0,15 H60" stroke="#fff" stroke-width="10"/><path d="M30,0 V30 M0,15 H60" stroke="#C8102E" stroke-width="6"/></svg>English</strong>
        Select your preferred language using the <strong>DE · NL · EN</strong> buttons at the top right of this page.
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════ ROLE LABEL: MEDEWERKER -->
  <div class="role-label"><span data-t="role_practitioner"></span></div>

  <!-- ─── Inloggen behandelaar ─── -->
  <div class="section">
    <div class="section-header">
      <div class="icon">🔐</div>
      <div>
        <h2 data-t="login_title"></h2>
        <p  data-t="login_sub"></p>
      </div>
    </div>
    <div class="section-body">
      <ol class="steps">
        <li><span class="step-num">1</span><span data-t="login_p1"></span></li>
        <li><span class="step-num">2</span><span data-t="login_p2"></span></li>
        <li><span class="step-num">3</span><span data-t="login_p3"></span></li>
        <li><span class="step-num">4</span><span data-t="login_p4"></span></li>
      </ol>
      <div class="warning" data-t="login_lockout"></div>
    </div>
  </div>

  <!-- ─── Agenda ─── -->
  <div class="section">
    <div class="section-header">
      <div class="icon">📅</div>
      <div>
        <h2 data-t="agenda_title"></h2>
        <p  data-t="agenda_sub"></p>
      </div>
    </div>
    <div class="section-body">
      <ol class="steps">
        <li><span class="step-num">1</span><span data-t="agenda_p1"></span></li>
        <li><span class="step-num">2</span><span data-t="agenda_p2"></span></li>
        <li><span class="step-num">3</span><span data-t="agenda_p3"></span></li>
        <li><span class="step-num">4</span><span data-t="agenda_p4"></span></li>
      </ol>
    </div>
  </div>

  <!-- ─── Behandeling ─── -->
  <div class="section">
    <div class="section-header">
      <div class="icon">🩺</div>
      <div>
        <h2 data-t="treatment_title"></h2>
        <p  data-t="treatment_sub"></p>
      </div>
    </div>
    <div class="section-body">
      <ol class="steps">
        <li><span class="step-num">1</span><span data-t="treat_p1"></span></li>
        <li><span class="step-num">2</span><span data-t="treat_p2"></span></li>
        <li><span class="step-num">3</span><span data-t="treat_p3"></span></li>
        <li><span class="step-num">4</span><span data-t="treat_p4"></span></li>
        <li><span class="step-num">5</span><span data-t="treat_p5"></span></li>
        <li><span class="step-num">6</span><span data-t="treat_p6"></span></li>
      </ol>
      <div class="note" data-t="treat_note"></div>
    </div>
  </div>

  <!-- ─── Testen tips medewerker ─── -->
  <div class="section">
    <div class="section-header">
      <div class="icon">✅</div>
      <div>
        <h2 data-t="test_title"></h2>
        <p  data-t="test_sub"></p>
      </div>
    </div>
    <div class="section-body">
      <div class="card-grid">
        <div class="feature-card">
          <div class="fc-icon">🔢</div>
          <strong data-t="tc1_title"></strong>
          <span   data-t="tc1_body"></span>
        </div>
        <div class="feature-card">
          <div class="fc-icon">🧾</div>
          <strong data-t="tc2_title"></strong>
          <span   data-t="tc2_body"></span>
        </div>
        <div class="feature-card">
          <div class="fc-icon">✍️</div>
          <strong data-t="tc3_title"></strong>
          <span   data-t="tc3_body"></span>
        </div>
        <div class="feature-card">
          <div class="fc-icon">📱</div>
          <strong data-t="tc4_title"></strong>
          <span   data-t="tc4_body"></span>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── Feedback ─── -->
  <div class="section">
    <div class="section-header">
      <div class="icon">💬</div>
      <div>
        <h2 data-t="feedback_title"></h2>
        <p  data-t="feedback_sub"></p>
      </div>
    </div>
    <div class="section-body">
      <p style="font-size:.92rem;" data-t="feedback_body"></p>
    </div>
  </div>

  <!-- ═══════════════════════════════════════ ROLE LABEL: BEHEERDER -->
  <div class="role-label"><span data-t="role_admin"></span></div>

  <!-- ─── Inloggen beheerder ─── -->
  <div class="section">
    <div class="section-header">
      <div class="icon">🔑</div>
      <div>
        <h2 data-t="admin_login_title"></h2>
        <p  data-t="admin_login_sub"></p>
      </div>
    </div>
    <div class="section-body">
      <ol class="steps">
        <li><span class="step-num">1</span><span data-t="admin_l1"></span></li>
        <li><span class="step-num">2</span><span data-t="admin_l2"></span></li>
        <li><span class="step-num">3</span><span data-t="admin_l3"></span></li>
      </ol>
    </div>
  </div>

  <!-- ─── Beheermogelijkheden ─── -->
  <div class="section">
    <div class="section-header">
      <div class="icon">⚙️</div>
      <div>
        <h2 data-t="admin_cap_title"></h2>
        <p  data-t="admin_cap_sub"></p>
      </div>
    </div>
    <div class="section-body">
      <div class="card-grid">
        <div class="feature-card">
          <div class="fc-icon">🏥</div>
          <strong data-t="ac1_title"></strong>
          <span   data-t="ac1_body"></span>
        </div>
        <div class="feature-card">
          <div class="fc-icon">👤</div>
          <strong data-t="ac2_title"></strong>
          <span   data-t="ac2_body"></span>
        </div>
        <div class="feature-card">
          <div class="fc-icon">🗂️</div>
          <strong data-t="ac3_title"></strong>
          <span   data-t="ac3_body"></span>
        </div>
        <div class="feature-card">
          <div class="fc-icon">📋</div>
          <strong data-t="ac4_title"></strong>
          <span   data-t="ac4_body"></span>
        </div>
        <div class="feature-card">
          <div class="fc-icon">🔓</div>
          <strong data-t="ac5_title"></strong>
          <span   data-t="ac5_body"></span>
        </div>
        <div class="feature-card">
          <div class="fc-icon">🦷</div>
          <strong data-t="ac6_title"></strong>
          <span   data-t="ac6_body"></span>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── Testen tips beheerder ─── -->
  <div class="section">
    <div class="section-header">
      <div class="icon">✅</div>
      <div>
        <h2 data-t="admin_test_title"></h2>
        <p  data-t="admin_test_sub"></p>
      </div>
    </div>
    <div class="section-body">
      <ol class="steps">
        <li><span class="step-num">1</span><span data-t="at1"></span></li>
        <li><span class="step-num">2</span><span data-t="at2"></span></li>
        <li><span class="step-num">3</span><span data-t="at3"></span></li>
        <li><span class="step-num">4</span><span data-t="at4"></span></li>
      </ol>
    </div>
  </div>

</div><!-- /page -->

<footer>
  <span data-t="footer"></span>
</footer>

<script>
const T = {
  nl: {
    subtitle:          'Handleiding voor DEIN DENTAL',
    hero_network:      'DEIN DENTAL Netwerk',
    hero_title:        'Welkom bij Celereon',
    hero_sub:          'Celereon helpt u bij het gestructureerd vastleggen van behandelingen, verzorgt automatisch de billing en legt de toestemming van de patiënt digitaal vast. Deze handleiding helpt u als medewerker of beheerder snel op weg.',
    hero_slogan:       '„Weil dein Lächeln die Welt verändert."',
    role_practitioner: 'Voor medewerkers (behandelaars)',
    role_admin:        'Voor beheerders',

    login_title: 'Inloggen als behandelaar',
    login_sub:   'Snel en veilig via pincode',
    login_p1:    'Ga naar <a href="https://www.europeandentalgroup.eu/easydent/" target="_blank" style="color:var(--teal);font-weight:600;">www.europeandentalgroup.eu/easydent/</a> en klik op <strong>Inloggen als behandelaar</strong>.',
    login_p2:    'Kies een praktijk.',
    login_p3:    'Kies een behandelaar.',
    login_p4:    'Voer uw <strong>4-cijferige pincode</strong> in via het numeriek toetsenbord. <em>Tijdens de testfase is de pincode voor iedereen <strong>1234</strong>.</em>',
    login_lockout: '⚠️ Na 5 foute pincodes wordt het account tijdelijk vergrendeld. Neem contact op met de beheerder om het te ontgrendelen.',

    agenda_title: 'Agenda',
    agenda_sub:   'Uw afspraken op één plek',
    agenda_p1:    'Na het inloggen ziet u de agenda met afspraken voor de geselecteerde dag.',
    agenda_p2:    'Navigeer naar andere dagen met de pijlknoppen (<strong>‹ ›</strong>) of via de maandkalender.',
    agenda_p3:    'Dagen met afspraken zijn gemarkeerd in de kalender.',
    agenda_p4:    'Klik op een afspraak om de behandeling te openen.',

    treatment_title: 'Behandeling registreren',
    treatment_sub:   'GOZ-codes, toestemming en afronden',
    treat_p1:  'Open een afspraak vanuit de agenda.',
    treat_p2:  'Geef per <strong>GOZ-code</strong> aan of u deze wel of niet gebruikt bij deze behandeling.',
    treat_p3:  'Pas indien nodig de <strong>factor</strong> aan (standaard 2,3 — bereik 1,0 t/m 3,5). Het tarief wordt automatisch berekend.',
    treat_p4:  'Vul de <strong>anamnese</strong> en <strong>signaalvragen</strong> in.',
    treat_p5:  'Leg de <strong>digitale toestemming</strong> van de patiënt vast (handtekening).',
    treat_p6:  'Klik op <strong>Afronden</strong> om de behandeling te sluiten.',
    treat_note: '💡 <strong>Let op:</strong> een afgesloten behandeling kan niet zelf heropend worden. Vraag de beheerder dit te doen.',

    test_title: 'Wat te testen',
    test_sub:   'Suggesties voor een grondige test',
    tc1_title:  'Pincode blokkade',
    tc1_body:   'Voer 5x een fout pincode in en controleer of het account vergrendelt.',
    tc2_title:  'GOZ-codes & tarief',
    tc2_body:   'Voeg meerdere codes toe, wijzig de factor en controleer of het tarief correct wordt berekend.',
    tc3_title:  'Toestemming & handtekening',
    tc3_body:   'Leg een digitale toestemming vast en rond de behandeling af.',
    tc4_title:  'Mobiel gebruik',
    tc4_body:   'Test de volledige flow op een smartphone of tablet.',

    feedback_title: 'Feedback geven',
    feedback_sub:   'Uw bevindingen zijn waardevol',
    feedback_body:  'Gebruik de <strong>feedbackknop</strong> rechtsonder in het scherm om een opmerking of foutmelding direct vanuit de applicatie door te sturen. U kunt ook een schermafbeelding bijvoegen.<br><br>Voel u vrij om <strong>buiten de testscenario\'s</strong> te klikken en de applicatie op eigen wijze te verkennen — alle bevindingen zijn welkom. Heeft u tijdens het testen <strong>nieuwe wensen of ideeën</strong>? Geef die dan ook gerust mee via de feedbackknop.',

    admin_login_title: 'Inloggen als beheerder',
    admin_login_sub:   'Via gebruikersnaam en wachtwoord',
    admin_l1: 'Ga naar de inlogpagina.',
    admin_l2: 'Voer uw <strong>gebruikersnaam</strong> en <strong>wachtwoord</strong> in.',
    admin_l3: 'Klik op <strong>Inloggen</strong>.',

    admin_cap_title: 'Beheermogelijkheden',
    admin_cap_sub:   'Alles wat u als beheerder kunt instellen',
    ac1_title: 'Praktijken',
    ac1_body:  'Aanmaken, activeren of deactiveren van praktijken.',
    ac2_title: 'Gebruikers',
    ac2_body:  'Behandelaars en managers aanmaken, pincode instellen, accounts ontgrendelen.',
    ac3_title: 'Patiënten',
    ac3_body:  'Patiëntgegevens beheren en inzien.',
    ac4_title: 'Afspraken',
    ac4_body:  'Afspraken inplannen en overzichten bekijken.',
    ac5_title: 'Behandelingen heropenen',
    ac5_body:  'Een afgesloten behandeling heropenen voor correctie.',
    ac6_title: 'Behandeltypen',
    ac6_body:  'Nieuwe behandeltypen aanmaken en bestaande aanpassen.',

    admin_test_title: 'Wat te testen als beheerder',
    admin_test_sub:   'Suggesties voor een grondige test',
    at1: 'Maak een nieuwe behandelaar aan en stel een pincode in.',
    at2: 'Vergrendel een account (via 5x fout inloggen) en ontgrendel het daarna via het beheer.',
    at3: 'Open een afgesloten behandeling opnieuw en controleer of de wijziging wordt opgeslagen.',
    at4: 'Controleer of een beheerder géén toegang heeft tot de behandelaarsagenda (en vice versa).',

    footer: 'Celereon × DEIN DENTAL — Handleiding voor testers · April 2026',
  },

  en: {
    subtitle:          'Guide for DEIN DENTAL',
    hero_network:      'DEIN DENTAL Network',
    hero_title:        'Welcome to Celereon',
    hero_sub:          'Celereon helps you record treatments in a structured way, handles billing automatically, and captures patient consent digitally. This guide helps you get started quickly as a practitioner or administrator.',
    hero_slogan:       '„Weil dein Lächeln die Welt verändert."',
    role_practitioner: 'For practitioners (staff)',
    role_admin:        'For administrators',

    login_title: 'Logging in as a practitioner',
    login_sub:   'Quick and secure via PIN',
    login_p1:    'Go to <a href="https://www.europeandentalgroup.eu/easydent/" target="_blank" style="color:var(--teal);font-weight:600;">www.europeandentalgroup.eu/easydent/</a> and click <strong>Log in as practitioner</strong>.',
    login_p2:    'Select a practice.',
    login_p3:    'Select a practitioner.',
    login_p4:    'Enter your <strong>4-digit PIN</strong> using the numeric keypad. <em>During the test phase, everyone\'s PIN is <strong>1234</strong>.</em>',
    login_lockout: '⚠️ After 5 incorrect PIN attempts the account is temporarily locked. Contact your administrator to unlock it.',

    agenda_title: 'Agenda',
    agenda_sub:   'Your appointments in one place',
    agenda_p1:    'After logging in you will see the agenda with appointments for the selected day.',
    agenda_p2:    'Navigate to other days using the arrow buttons (<strong>‹ ›</strong>) or via the monthly calendar.',
    agenda_p3:    'Days with appointments are highlighted in the calendar.',
    agenda_p4:    'Click an appointment to open the treatment.',

    treatment_title: 'Registering a treatment',
    treatment_sub:   'GOZ codes, consent and completion',
    treat_p1:  'Open an appointment from the agenda.',
    treat_p2:  'For each <strong>GOZ code</strong>, indicate whether you are using it or not in this treatment.',
    treat_p3:  'Adjust the <strong>factor</strong> if needed (default 2.3 — range 1.0 to 3.5). The fee is calculated automatically.',
    treat_p4:  'Fill in the <strong>intake</strong> and <strong>signal questions</strong>.',
    treat_p5:  'Record the patient\'s <strong>digital consent</strong> (signature).',
    treat_p6:  'Click <strong>Complete</strong> to close the treatment.',
    treat_note: '💡 <strong>Note:</strong> a completed treatment cannot be reopened by the practitioner. Ask the administrator to reopen it.',

    test_title: 'What to test',
    test_sub:   'Suggestions for a thorough test',
    tc1_title:  'PIN lockout',
    tc1_body:   'Enter a wrong PIN 5 times and verify the account gets locked.',
    tc2_title:  'GOZ codes & fee',
    tc2_body:   'Add multiple codes, change the factor, and verify the fee is calculated correctly.',
    tc3_title:  'Consent & signature',
    tc3_body:   'Record a digital consent and complete the treatment.',
    tc4_title:  'Mobile use',
    tc4_body:   'Test the full flow on a smartphone or tablet.',

    feedback_title: 'Giving feedback',
    feedback_sub:   'Your findings are valuable',
    feedback_body:  'Use the <strong>feedback button</strong> at the bottom right of the screen to send a comment or bug report directly from within the application. You can also attach a screenshot.<br><br>Feel free to <strong>explore beyond the test scenarios</strong> and click around at your own pace — all findings are welcome. Do you have <strong>new wishes or ideas</strong> while testing? Please share those via the feedback button as well.',

    admin_login_title: 'Logging in as an administrator',
    admin_login_sub:   'Via username and password',
    admin_l1: 'Go to the login page.',
    admin_l2: 'Enter your <strong>username</strong> and <strong>password</strong>.',
    admin_l3: 'Click <strong>Log in</strong>.',

    admin_cap_title: 'Administrator capabilities',
    admin_cap_sub:   'Everything you can manage as an administrator',
    ac1_title: 'Practices',
    ac1_body:  'Create, activate or deactivate practices.',
    ac2_title: 'Users',
    ac2_body:  'Create practitioners and managers, set PINs, unlock accounts.',
    ac3_title: 'Patients',
    ac3_body:  'View and manage patient data.',
    ac4_title: 'Appointments',
    ac4_body:  'Schedule appointments and view overviews.',
    ac5_title: 'Reopen treatments',
    ac5_body:  'Reopen a completed treatment for corrections.',
    ac6_title: 'Treatment types',
    ac6_body:  'Create new treatment types and edit existing ones.',

    admin_test_title: 'What to test as an administrator',
    admin_test_sub:   'Suggestions for a thorough test',
    at1: 'Create a new practitioner and set a PIN.',
    at2: 'Lock an account (by logging in incorrectly 5 times) and unlock it via the admin panel.',
    at3: 'Reopen a completed treatment and verify the change is saved.',
    at4: 'Verify that an administrator cannot access the practitioner agenda (and vice versa).',

    footer: 'Celereon × DEIN DENTAL — Tester guide · April 2026',
  },

  de: {
    subtitle:          'Handbuch für DEIN DENTAL',
    hero_network:      'DEIN DENTAL Netzwerk',
    hero_title:        'Willkommen bei Celereon',
    hero_sub:          'Celereon unterstützt Sie bei der strukturierten Erfassung von Behandlungen, übernimmt automatisch die Abrechnung und dokumentiert die Einwilligung des Patienten digital. Dieses Handbuch hilft Ihnen als Behandler oder Administrator schnell durchzustarten.',
    hero_slogan:       '„Weil dein Lächeln die Welt verändert."',
    role_practitioner: 'Für Mitarbeiter (Behandler)',
    role_admin:        'Für Administratoren',

    login_title: 'Anmelden als Behandler',
    login_sub:   'Schnell und sicher per PIN',
    login_p1:    'Öffnen Sie <a href="https://www.europeandentalgroup.eu/easydent/" target="_blank" style="color:var(--teal);font-weight:600;">www.europeandentalgroup.eu/easydent/</a> und klicken Sie auf <strong>Als Behandler anmelden</strong>.',
    login_p2:    'Wählen Sie eine Praxis.',
    login_p3:    'Wählen Sie einen Behandler.',
    login_p4:    'Geben Sie Ihre <strong>4-stellige PIN</strong> über das Ziffernfeld ein. <em>In der Testphase lautet die PIN für alle <strong>1234</strong>.</em>',
    login_lockout: '⚠️ Nach 5 falschen PIN-Eingaben wird das Konto vorübergehend gesperrt. Wenden Sie sich an den Administrator, um es freizuschalten.',

    agenda_title: 'Terminübersicht',
    agenda_sub:   'Ihre Termine auf einen Blick',
    agenda_p1:    'Nach der Anmeldung sehen Sie die Terminübersicht für den ausgewählten Tag.',
    agenda_p2:    'Navigieren Sie mit den Pfeiltasten (<strong>‹ ›</strong>) oder über den Monatskalender zu anderen Tagen.',
    agenda_p3:    'Tage mit Terminen sind im Kalender hervorgehoben.',
    agenda_p4:    'Klicken Sie auf einen Termin, um die Behandlung zu öffnen.',

    treatment_title: 'Behandlung erfassen',
    treatment_sub:   'GOZ-Codes, Einwilligung und Abschluss',
    treat_p1:  'Öffnen Sie einen Termin aus der Terminübersicht.',
    treat_p2:  'Geben Sie für jeden <strong>GOZ-Code</strong> an, ob Sie ihn bei dieser Behandlung verwenden oder nicht.',
    treat_p3:  'Passen Sie bei Bedarf den <strong>Faktor</strong> an (Standard 2,3 — Bereich 1,0 bis 3,5). Das Honorar wird automatisch berechnet.',
    treat_p4:  'Füllen Sie die <strong>Anamnese</strong> und <strong>Signalfragen</strong> aus.',
    treat_p5:  'Erfassen Sie die <strong>digitale Einwilligung</strong> des Patienten (Unterschrift).',
    treat_p6:  'Klicken Sie auf <strong>Abschließen</strong>, um die Behandlung zu beenden.',
    treat_note: '💡 <strong>Hinweis:</strong> Eine abgeschlossene Behandlung kann vom Behandler nicht erneut geöffnet werden. Bitten Sie den Administrator, dies zu tun.',

    test_title: 'Was zu testen ist',
    test_sub:   'Empfehlungen für einen gründlichen Test',
    tc1_title:  'PIN-Sperrung',
    tc1_body:   'Geben Sie 5-mal eine falsche PIN ein und prüfen Sie, ob das Konto gesperrt wird.',
    tc2_title:  'GOZ-Codes & Honorar',
    tc2_body:   'Mehrere Codes hinzufügen, Faktor anpassen und prüfen, ob das Honorar korrekt berechnet wird.',
    tc3_title:  'Einwilligung & Unterschrift',
    tc3_body:   'Digitale Einwilligung erfassen und die Behandlung abschließen.',
    tc4_title:  'Mobile Nutzung',
    tc4_body:   'Den gesamten Ablauf auf einem Smartphone oder Tablet testen.',

    feedback_title: 'Feedback geben',
    feedback_sub:   'Ihre Rückmeldungen sind wertvoll',
    feedback_body:  'Verwenden Sie die <strong>Feedback-Schaltfläche</strong> unten rechts auf dem Bildschirm, um einen Kommentar oder eine Fehlermeldung direkt aus der Anwendung zu senden. Sie können auch einen Screenshot anhängen.<br><br>Sie sind herzlich eingeladen, <strong>über die Testszenarien hinaus</strong> zu klicken und die Anwendung auf eigene Weise zu erkunden — alle Rückmeldungen sind willkommen. Haben Sie während des Testens <strong>neue Wünsche oder Ideen</strong>? Teilen Sie diese gerne ebenfalls über die Feedback-Schaltfläche mit.',

    admin_login_title: 'Anmelden als Administrator',
    admin_login_sub:   'Mit Benutzername und Passwort',
    admin_l1: 'Öffnen Sie die Anmeldeseite.',
    admin_l2: 'Geben Sie Ihren <strong>Benutzernamen</strong> und Ihr <strong>Passwort</strong> ein.',
    admin_l3: 'Klicken Sie auf <strong>Anmelden</strong>.',

    admin_cap_title: 'Administratorfunktionen',
    admin_cap_sub:   'Alles, was Sie als Administrator verwalten können',
    ac1_title: 'Praxen',
    ac1_body:  'Praxen anlegen, aktivieren oder deaktivieren.',
    ac2_title: 'Benutzer',
    ac2_body:  'Behandler und Manager anlegen, PINs vergeben, Konten entsperren.',
    ac3_title: 'Patienten',
    ac3_body:  'Patientendaten einsehen und verwalten.',
    ac4_title: 'Termine',
    ac4_body:  'Termine planen und Übersichten einsehen.',
    ac5_title: 'Behandlungen wieder öffnen',
    ac5_body:  'Eine abgeschlossene Behandlung zur Korrektur wieder öffnen.',
    ac6_title: 'Behandlungstypen',
    ac6_body:  'Neue Behandlungstypen anlegen und bestehende anpassen.',

    admin_test_title: 'Was als Administrator zu testen ist',
    admin_test_sub:   'Empfehlungen für einen gründlichen Test',
    at1: 'Einen neuen Behandler anlegen und eine PIN vergeben.',
    at2: 'Ein Konto sperren (5-mal falsch anmelden) und es anschließend über die Verwaltung entsperren.',
    at3: 'Eine abgeschlossene Behandlung erneut öffnen und prüfen, ob die Änderung gespeichert wird.',
    at4: 'Prüfen, ob ein Administrator keinen Zugriff auf die Behandleransicht hat (und umgekehrt).',

    footer: 'Celereon × DEIN DENTAL — Testerhandbuch · April 2026',
  }
};

let currentLang = 'nl';

function setLang(lang) {
  currentLang = lang;
  const dict = T[lang];

  // Subtitle in header
  document.querySelectorAll('[data-label="subtitle"]').forEach(el => {
    el.textContent = dict.subtitle;
  });

  // All translatable elements
  document.querySelectorAll('[data-t]').forEach(el => {
    const key = el.getAttribute('data-t');
    if (dict[key] !== undefined) el.innerHTML = dict[key];
  });

  // Active lang button
  document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.classList.toggle('active', btn.textContent === lang.toUpperCase());
  });

  document.documentElement.lang = lang;
}

setLang('de');
</script>
</body>
</html>
