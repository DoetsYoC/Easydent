/**
 * DentalChart — reusable clickable FDI dental chart component
 * Tablet-optimised, single- and multi-select, no treatment-specific logic.
 *
 * Usage:
 *   DentalChart.init('container-id', {
 *     mode:     'multiple',          // 'single' | 'multiple'
 *     lang:     'de',               // 'de' | 'nl' | 'en'
 *     selected: [],                 // [{toothNumber:'14'}, ...]
 *     labels:   { ... },            // UI strings (see _defaultLabels)
 *     onChange: fn(teeth) {},       // called on every selection change
 *   });
 *
 *   DentalChart.getSelected()  → [{toothNumber:'14', name:'1. Prämolar'}, ...]
 *   DentalChart.setSelected([{toothNumber:'14'}])
 */
const DentalChart = (() => {

  // ── Tooth data ────────────────────────────────────────────────────────────
  // q = FDI quadrant (1=upper-right, 2=upper-left, 3=lower-left, 4=lower-right)
  const DATA = {
    '11': { de:'Mittl. Schneidezahn', nl:'Centrale snijtand',  en:'Central incisor',   q:1 },
    '12': { de:'Seitl. Schneidezahn', nl:'Laterale snijtand',  en:'Lateral incisor',   q:1 },
    '13': { de:'Eckzahn',             nl:'Hoektand',           en:'Canine',             q:1 },
    '14': { de:'1. Prämolar',         nl:'1e premolaar',       en:'First premolar',     q:1 },
    '15': { de:'2. Prämolar',         nl:'2e premolaar',       en:'Second premolar',    q:1 },
    '16': { de:'1. Molar',            nl:'1e molaar',          en:'First molar',        q:1 },
    '17': { de:'2. Molar',            nl:'2e molaar',          en:'Second molar',       q:1 },
    '18': { de:'Weisheitszahn',       nl:'Verstandskies',      en:'Wisdom tooth',       q:1 },

    '21': { de:'Mittl. Schneidezahn', nl:'Centrale snijtand',  en:'Central incisor',   q:2 },
    '22': { de:'Seitl. Schneidezahn', nl:'Laterale snijtand',  en:'Lateral incisor',   q:2 },
    '23': { de:'Eckzahn',             nl:'Hoektand',           en:'Canine',             q:2 },
    '24': { de:'1. Prämolar',         nl:'1e premolaar',       en:'First premolar',     q:2 },
    '25': { de:'2. Prämolar',         nl:'2e premolaar',       en:'Second premolar',    q:2 },
    '26': { de:'1. Molar',            nl:'1e molaar',          en:'First molar',        q:2 },
    '27': { de:'2. Molar',            nl:'2e molaar',          en:'Second molar',       q:2 },
    '28': { de:'Weisheitszahn',       nl:'Verstandskies',      en:'Wisdom tooth',       q:2 },

    '31': { de:'Mittl. Schneidezahn', nl:'Centrale snijtand',  en:'Central incisor',   q:3 },
    '32': { de:'Seitl. Schneidezahn', nl:'Laterale snijtand',  en:'Lateral incisor',   q:3 },
    '33': { de:'Eckzahn',             nl:'Hoektand',           en:'Canine',             q:3 },
    '34': { de:'1. Prämolar',         nl:'1e premolaar',       en:'First premolar',     q:3 },
    '35': { de:'2. Prämolar',         nl:'2e premolaar',       en:'Second premolar',    q:3 },
    '36': { de:'1. Molar',            nl:'1e molaar',          en:'First molar',        q:3 },
    '37': { de:'2. Molar',            nl:'2e molaar',          en:'Second molar',       q:3 },
    '38': { de:'Weisheitszahn',       nl:'Verstandskies',      en:'Wisdom tooth',       q:3 },

    '41': { de:'Mittl. Schneidezahn', nl:'Centrale snijtand',  en:'Central incisor',   q:4 },
    '42': { de:'Seitl. Schneidezahn', nl:'Laterale snijtand',  en:'Lateral incisor',   q:4 },
    '43': { de:'Eckzahn',             nl:'Hoektand',           en:'Canine',             q:4 },
    '44': { de:'1. Prämolar',         nl:'1e premolaar',       en:'First premolar',     q:4 },
    '45': { de:'2. Prämolar',         nl:'2e premolaar',       en:'Second premolar',    q:4 },
    '46': { de:'1. Molar',            nl:'1e molaar',          en:'First molar',        q:4 },
    '47': { de:'2. Molar',            nl:'2e molaar',          en:'Second molar',       q:4 },
    '48': { de:'Weisheitszahn',       nl:'Verstandskies',      en:'Wisdom tooth',       q:4 },
  };

  // Display order: left-to-right on screen = patient's right then patient's left
  // Upper jaw row (screen left → right):  Q1 teeth (18→11) | Q2 teeth (21→28)
  // Lower jaw row (screen left → right):  Q4 teeth (48→41) | Q3 teeth (31→38)
  const ROW_UPPER_L = ['18','17','16','15','14','13','12','11']; // Q1
  const ROW_UPPER_R = ['21','22','23','24','25','26','27','28']; // Q2
  const ROW_LOWER_L = ['48','47','46','45','44','43','42','41']; // Q4
  const ROW_LOWER_R = ['31','32','33','34','35','36','37','38']; // Q3

  // ── State ─────────────────────────────────────────────────────────────────
  let _el   = null;
  let _mode = 'multiple';
  let _lang = 'de';
  let _sel  = new Set();
  let _cb   = null;
  let _L    = {};

  // ── Public API ────────────────────────────────────────────────────────────

  function init(containerId, opts) {
    opts  = opts || {};
    _el   = document.getElementById(containerId);
    if (!_el) return;
    _mode = opts.mode     || 'multiple';
    _lang = opts.lang     || 'de';
    _cb   = opts.onChange || null;
    _L    = opts.labels   || _defaultLabels(_lang);

    _sel = new Set();
    if (Array.isArray(opts.selected)) {
      opts.selected.forEach(function(t) {
        var n = String(t.toothNumber !== undefined ? t.toothNumber : t);
        if (DATA[n]) _sel.add(n);
      });
    }
    _render();
  }

  function getSelected() {
    return Array.from(_sel).sort(_fdiSort).map(function(n) {
      return { toothNumber: n, name: _toothName(n) };
    });
  }

  function setSelected(teeth) {
    _sel = new Set();
    if (Array.isArray(teeth)) {
      teeth.forEach(function(t) {
        var n = String(t.toothNumber !== undefined ? t.toothNumber : t);
        if (DATA[n]) _sel.add(n);
      });
    }
    _updateClasses();
    _renderChips();
  }

  // ── Rendering ─────────────────────────────────────────────────────────────

  function _render() {
    var multi = _mode === 'multiple';
    var quickBtns = multi ? (
      '<button class="dc-qbtn" onclick="DentalChart._q(\'all\')">'   + _esc(_L.selectAll)  + '</button>' +
      '<button class="dc-qbtn" onclick="DentalChart._q(\'upper\')">' + _esc(_L.upperJaw)   + '</button>' +
      '<button class="dc-qbtn" onclick="DentalChart._q(\'lower\')">' + _esc(_L.lowerJaw)   + '</button>' +
      '<button class="dc-qbtn" onclick="DentalChart._q(\'q1\')">Q1</button>' +
      '<button class="dc-qbtn" onclick="DentalChart._q(\'q2\')">Q2</button>' +
      '<button class="dc-qbtn" onclick="DentalChart._q(\'q3\')">Q3</button>' +
      '<button class="dc-qbtn" onclick="DentalChart._q(\'q4\')">Q4</button>'
    ) : '';

    _el.innerHTML =
      '<div class="dc-wrap">' +
        '<div class="dc-chart-area">' +
          '<div class="dc-jaw-label">' + _esc(_L.upperJaw) + '</div>' +
          '<div class="dc-rows">' +
            '<div class="dc-row">' +
              '<div class="dc-half">' + ROW_UPPER_L.map(function(n){ return _btn(n,'upper'); }).join('') + '</div>' +
              '<div class="dc-midline"></div>' +
              '<div class="dc-half">' + ROW_UPPER_R.map(function(n){ return _btn(n,'upper'); }).join('') + '</div>' +
            '</div>' +
            '<div class="dc-gumline">' +
              '<span class="dc-dir">' + _esc(_L.right) + '</span>' +
              '<span class="dc-dir">' + _esc(_L.left)  + '</span>' +
            '</div>' +
            '<div class="dc-row">' +
              '<div class="dc-half">' + ROW_LOWER_L.map(function(n){ return _btn(n,'lower'); }).join('') + '</div>' +
              '<div class="dc-midline"></div>' +
              '<div class="dc-half">' + ROW_LOWER_R.map(function(n){ return _btn(n,'lower'); }).join('') + '</div>' +
            '</div>' +
          '</div>' +
          '<div class="dc-jaw-label">' + _esc(_L.lowerJaw) + '</div>' +
        '</div>' +

        '<div class="dc-sidebar">' +
          '<div class="dc-sel-label">' + _esc(_L.selected) + '</div>' +
          '<div class="dc-chips" id="dc-chips"></div>' +
          '<div class="dc-actions">' +
            quickBtns +
            '<button class="dc-qbtn dc-qbtn-clear" onclick="DentalChart._q(\'clear\')">' + _esc(_L.clear) + '</button>' +
          '</div>' +
          '<p class="dc-hint">' + _esc(_L.hint) + '</p>' +
        '</div>' +
      '</div>';

    _updateClasses();
    _renderChips();
  }

  function _btn(n, jaw) {
    var sel = _sel.has(n) ? ' dc-sel' : '';
    return '<button' +
      ' class="dc-tooth dc-tooth-' + jaw + sel + '"' +
      ' id="dct-' + n + '"' +
      ' data-n="' + n + '"' +
      ' title="' + _esc(_toothName(n)) + '"' +
      ' onclick="DentalChart._t(\'' + n + '\')">' +
      n +
      '</button>';
  }

  function _updateClasses() {
    Object.keys(DATA).forEach(function(n) {
      var btn = document.getElementById('dct-' + n);
      if (btn) btn.classList.toggle('dc-sel', _sel.has(n));
    });
  }

  function _renderChips() {
    var el = document.getElementById('dc-chips');
    if (!el) return;
    if (_sel.size === 0) {
      el.innerHTML = '<span class="dc-chips-empty">' + _esc(_L.none) + '</span>';
      return;
    }
    el.innerHTML = Array.from(_sel).sort(_fdiSort).map(function(n) {
      return '<span class="dc-chip">' +
        '<span class="dc-chip-num">' + n + '</span>' +
        '<span class="dc-chip-name">' + _esc(_toothName(n)) + '</span>' +
        '<button class="dc-chip-x" onclick="DentalChart._t(\'' + n + '\')" title="verwijder">&#x2715;</button>' +
      '</span>';
    }).join('');
  }

  // ── Interaction ───────────────────────────────────────────────────────────

  function _t(n) {  // toggle tooth
    if (_mode === 'single') {
      var was = _sel.has(n);
      _sel.clear();
      if (!was) _sel.add(n);
    } else {
      if (_sel.has(n)) _sel.delete(n);
      else _sel.add(n);
    }
    _updateClasses();
    _renderChips();
    if (_cb) _cb(getSelected());
  }

  function _q(action) {  // quick-action
    switch (action) {
      case 'all':
        Object.keys(DATA).forEach(function(n) { _sel.add(n); });
        break;
      case 'clear':
        _sel.clear();
        break;
      case 'upper':
        ['1','2'].forEach(function(q) {
          Object.keys(DATA).filter(function(n){ return n[0]===q; }).forEach(function(n){ _sel.add(n); });
        });
        break;
      case 'lower':
        ['3','4'].forEach(function(q) {
          Object.keys(DATA).filter(function(n){ return n[0]===q; }).forEach(function(n){ _sel.add(n); });
        });
        break;
      case 'q1': Object.keys(DATA).filter(function(n){ return n[0]==='1'; }).forEach(function(n){ _sel.add(n); }); break;
      case 'q2': Object.keys(DATA).filter(function(n){ return n[0]==='2'; }).forEach(function(n){ _sel.add(n); }); break;
      case 'q3': Object.keys(DATA).filter(function(n){ return n[0]==='3'; }).forEach(function(n){ _sel.add(n); }); break;
      case 'q4': Object.keys(DATA).filter(function(n){ return n[0]==='4'; }).forEach(function(n){ _sel.add(n); }); break;
    }
    _updateClasses();
    _renderChips();
    if (_cb) _cb(getSelected());
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  function _toothName(n) { return (DATA[n] && DATA[n][_lang]) || n; }

  function _fdiSort(a, b) { return parseInt(a, 10) - parseInt(b, 10); }

  function _esc(s) {
    return String(s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function _defaultLabels(lang) {
    var L = {
      de: {
        upperJaw:  'Bovenkaak',
        lowerJaw:  'Onderkaak',
        right:     'Rechts',
        left:      'Links',
        selected:  'Geselecteerde tanden',
        none:      'Geen tanden geselecteerd',
        selectAll: 'Alles',
        clear:     'Wis selectie',
        hint:      'Tik op een tand om te selecteren of deselecteren.',
      },
    };
    L.nl = L.de;
    L.en = {
      upperJaw:  'Upper jaw',
      lowerJaw:  'Lower jaw',
      right:     'Right',
      left:      'Left',
      selected:  'Selected teeth',
      none:      'No teeth selected',
      selectAll: 'All',
      clear:     'Clear',
      hint:      'Tap a tooth to select or deselect.',
    };
    return L[lang] || L.de;
  }

  return { init: init, getSelected: getSelected, setSelected: setSelected, _t: _t, _q: _q };

})();
