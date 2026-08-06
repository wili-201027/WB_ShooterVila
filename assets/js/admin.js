/* ============================================================
   VR GAME WEB — admin.js
   Admin panel logic: jugadors, puntuacions, panell general
   ============================================================ */

/* ── Secció activa ──────────────────────────────────────── */
function showSection(id) {
  document.querySelectorAll('.admin-section').forEach(function (s) {
    s.style.display = 'none';
  });
  document.querySelectorAll('.admin-nav a').forEach(function (a) {
    a.classList.toggle('active', a.dataset.section === id);
  });
  var el = document.getElementById('section-' + id);
  if (el) el.style.display = 'block';
}

/* ── Carregar jugadors ──────────────────────────────────── */
function adminLoadPlayers() {
  var tbody = document.getElementById('admin-players-body');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px"><div class="loader" style="margin:0 auto"></div></td></tr>';

  fetch('php/get_leaderboard.php?limit=100')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success || !data.players.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-dim)">Sense jugadors.</td></tr>';
        return;
      }
      var html = '';
      data.players.forEach(function (p, i) {
        html +=
          '<tr>' +
          '<td style="color:var(--text-dim);font-size:.8rem">' + (i + 1) + '</td>' +
          '<td><span class="player-name">' + escHtml(p.player_name) + '</span></td>' +
          '<td><span class="score-value">' + formatScore(p.score) + '</span></td>' +
          '<td style="color:var(--text-dim);font-size:.8rem;font-family:var(--font-mono)">' + (p.games_played || 1) + '</td>' +
          '<td style="display:flex;gap:6px;flex-wrap:wrap">' +
            '<button class="btn btn--outline" style="font-size:.55rem;padding:5px 10px" onclick="adminEditPlayer(\'' + escHtml(p.player_name) + '\',' + p.score + ')">✏ Editar</button>' +
            '<button class="btn btn--danger" style="font-size:.55rem;padding:5px 10px" onclick="adminDeletePlayer(\'' + escHtml(p.player_name) + '\')">✕ Eliminar</button>' +
          '</td>' +
          '</tr>';
      });
      tbody.innerHTML = html;
    })
    .catch(function () {
      tbody.innerHTML = '<tr><td colspan="5" style="color:var(--red);padding:20px">Error de connexió.</td></tr>';
    });
}

/* ── Editar puntuació ───────────────────────────────────── */
function adminEditPlayer(name, currentScore) {
  document.getElementById('edit-player-name').value = name;
  document.getElementById('edit-player-name-display').textContent = name;
  document.getElementById('edit-score-value').value = currentScore;
  document.getElementById('edit-modal').style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('edit-modal').style.display = 'none';
}

function adminSaveEdit() {
  var name  = document.getElementById('edit-player-name').value;
  var score = parseInt(document.getElementById('edit-score-value').value, 10);
  if (isNaN(score) || score < 0) { showToast('Puntuació no vàlida.', 'error'); return; }

  fetch('php/set_points.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': (window.CSRF_TOKEN || '') },
    body: JSON.stringify({ player: name, score: score })
  })
  .then(function (r) { return r.json(); })
  .then(function (data) {
    if (data.success) {
      showToast('Puntuació actualitzada! ✓', 'success');
      closeEditModal();
      adminLoadPlayers();
    } else {
      showToast('Error: ' + (data.error || '?'), 'error');
    }
  })
  .catch(function () { showToast('Error de connexió.', 'error'); });
}

/* ── Eliminar jugador ───────────────────────────────────── */
function adminDeletePlayer(name) {
  if (!confirm('Eliminar totes les puntuacions de "' + name + '"?')) return;
  fetch('php/remove_score.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': (window.CSRF_TOKEN || '') },
    body: JSON.stringify({ player: name })
  })
  .then(function (r) { return r.json(); })
  .then(function (data) {
    if (data.success) { showToast('Jugador eliminat.', 'success'); adminLoadPlayers(); }
    else showToast('Error: ' + (data.error || '?'), 'error');
  })
  .catch(function () { showToast('Error de connexió.', 'error'); });
}

/* ── Reset total ────────────────────────────────────────── */
function adminResetAll() {
  var pw = document.getElementById('reset-password').value;
  if (!pw) { showToast('Introdueix la contrasenya d\'admin.', 'error'); return; }
  if (!confirm('⚠️ Esborrar TOTES les puntuacions? Acció irreversible!')) return;

  fetch('php/reset_scores.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': (window.CSRF_TOKEN || '') },
    body: JSON.stringify({ admin_password: pw })
  })
  .then(function (r) { return r.json(); })
  .then(function (data) {
    if (data.success) {
      showToast('Totes les puntuacions esborrades.', 'success');
      adminLoadPlayers();
    } else {
      showToast('Error: ' + (data.error || 'Contrasenya incorrecta?'), 'error');
    }
  })
  .catch(function () { showToast('Error de connexió.', 'error'); });
}

/* ── Afegir punts manualment ────────────────────────────── */
function adminAddPoints() {
  var name   = document.getElementById('manual-player').value.trim();
  var points = parseInt(document.getElementById('manual-points').value, 10);
  if (!name || isNaN(points)) { showToast('Omple tots els camps.', 'error'); return; }

  fetch('php/add_points.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': (window.CSRF_TOKEN || '') },
    body: JSON.stringify({ player: name, points: points })
  })
  .then(function (r) { return r.json(); })
  .then(function (data) {
    if (data.success) {
      showToast('+' + points + ' pts a ' + name + ' ✓', 'success');
      adminLoadPlayers();
    } else showToast('Error: ' + (data.error || '?'), 'error');
  })
  .catch(function () { showToast('Error de connexió.', 'error'); });
}

/* ── Treure punts manualment ────────────────────────────── */
function adminRemovePoints() {
  var name   = document.getElementById('manual-player').value.trim();
  var points = parseInt(document.getElementById('manual-points').value, 10);
  if (!name || isNaN(points)) { showToast('Omple tots els camps.', 'error'); return; }

  fetch('php/remove_points.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': (window.CSRF_TOKEN || '') },
    body: JSON.stringify({ player: name, points: points })
  })
  .then(function (r) { return r.json(); })
  .then(function (data) {
    if (data.success) {
      showToast('-' + points + ' pts de ' + name + ' ✓', 'success');
      adminLoadPlayers();
    } else showToast('Error: ' + (data.error || '?'), 'error');
  })
  .catch(function () { showToast('Error de connexió.', 'error'); });
}

/* ── Reset jugador individual ───────────────────────────── */
function adminResetPlayer() {
  var name = document.getElementById('reset-player-name').value.trim();
  if (!name) { showToast('Introdueix un nom.', 'error'); return; }
  if (!confirm('Posar a 0 les puntuacions de "' + name + '"?')) return;

  fetch('php/reset_player.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': (window.CSRF_TOKEN || '') },
    body: JSON.stringify({ player: name })
  })
  .then(function (r) { return r.json(); })
  .then(function (data) {
    if (data.success) { showToast('Jugador resetejat.', 'success'); adminLoadPlayers(); }
    else showToast('Error: ' + (data.error || '?'), 'error');
  })
  .catch(function () { showToast('Error de connexió.', 'error'); });
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}