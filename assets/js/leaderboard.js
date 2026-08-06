/* ============================================================
   VR GAME WEB — leaderboard.js
   Fetch & render leaderboard · Auto-refresh polling
   ============================================================ */

var _lbInterval = null;

/**
 * Carrega la classificació i la pinta al tbody indicat.
 * @param {string} tbodyId  ID del <tbody>
 * @param {number} limit    Màxim de files (0 = tot)
 */
function loadLeaderboard(tbodyId, limit) {
  limit = limit || 0;
  var tbody = document.getElementById(tbodyId);
  if (!tbody) return;

  var url = 'php/get_leaderboard.php' + (limit ? '?limit=' + limit : '');

  fetch(url)
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success || !data.players || !data.players.length) {
        tbody.innerHTML =
          '<tr><td colspan="4" style="text-align:center;padding:32px;color:var(--text-dim)">Encara no hi ha puntuacions.</td></tr>';
        return;
      }
      renderRows(tbody, data.players);
    })
    .catch(function () {
      tbody.innerHTML =
        '<tr><td colspan="4" style="text-align:center;padding:32px;color:var(--red)">Error connectant amb el servidor.</td></tr>';
    });
}

function renderRows(tbody, players) {
  var html = '';
  players.forEach(function (p, i) {
    var rank = i + 1;
    var rankClass = rank <= 3 ? ' rank--' + rank : '';
    var medal = rank === 1 ? '🥇' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : '';
    html +=
      '<tr>' +
      '<td><span class="rank' + rankClass + '">' + (medal || '#' + rank) + '</span></td>' +
      '<td><span class="player-name">' + escHtml(p.player_name) + '</span></td>' +
      '<td><span class="score-value">' + formatScore(p.score) + '</span></td>' +
      '<td style="color:var(--text-dim);font-size:.8rem;font-family:var(--font-mono)">' +
        (p.games_played || 1) +
      '</td>' +
      '</tr>';
  });
  tbody.innerHTML = html;
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * Activa el polling automàtic cada `seconds` segons.
 */
function startPolling(tbodyId, limit, seconds) {
  seconds = seconds || 15;
  loadLeaderboard(tbodyId, limit);
  _lbInterval = setInterval(function () {
    loadLeaderboard(tbodyId, limit);
  }, seconds * 1000);
}

function stopPolling() {
  if (_lbInterval) clearInterval(_lbInterval);
}

/**
 * Start live leaderboard using Server-Sent Events with polling fallback.
 */
function startLiveLeaderboard(tbodyId, limit) {
  var tbody = document.getElementById(tbodyId);
  if (!tbody) return;
  limit = limit || 0;

  if (!!window.EventSource) {
    try {
      var es = new EventSource('php/leaderboard_stream.php');
      es.onmessage = function (e) {
        try {
          var data = JSON.parse(e.data);
          if (data.players && data.players.length) renderRows(tbody, data.players);
        } catch (err) {
          // ignore parse errors
        }
      };
      es.onerror = function () {
        try { es.close(); } catch (e) {}
        // fallback to polling
        startPolling(tbodyId, limit, 15);
      };
    } catch (e) {
      startPolling(tbodyId, limit, 15);
    }
  } else {
    startPolling(tbodyId, limit, 15);
  }
}