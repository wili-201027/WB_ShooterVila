<?php
session_start();
require_once __DIR__ . '/php/config.php';
$isLogged = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
$adminUser = $isLogged ? (isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : 'admin') : null;
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin — VR GAME</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg" />
</head>
<body>

  <nav class="nav">
    <div class="nav__inner">
      <a class="nav__logo" href="index.html">VR<span>GAME</span></a>
      <ul class="nav__links">
        <li><a href="index.html">Inici</a></li>
        <li><a href="game.html">Joc</a></li>
        <li><a href="leaderboard.html">Classificació</a></li>
        <li><a href="admin.php" class="active">Admin</a></li>
      </ul>
      <div class="nav__toggle" onclick="toggleMenu()">
        <span></span><span></span><span></span>
      </div>
      <?php if ($isLogged): ?>
        <div style="margin-left:12px"><button class="btn btn--outline" onclick="doAdminLogout()">Tancar sessió (<?php echo htmlspecialchars($adminUser) ?>)</button></div>
      <?php endif; ?>
    </div>
  </nav>

  <main style="padding-top: 72px;">
    <div class="container">
      <h1 class="section__title">Panel d'Admin</h1>

      <?php if (! $isLogged): ?>
        <!-- Login form -->
        <div class="card" style="max-width:480px;margin:0 auto;text-align:left">
          <p style="margin:0 0 8px">Introdueix les credencials d'admin</p>
          <div style="margin-bottom:8px"><label>Usuari</label><input id="login-username" style="width:100%;padding:8px" /></div>
          <div style="margin-bottom:12px"><label>Contrasenya</label><input id="login-password" type="password" style="width:100%;padding:8px" /></div>
          <div style="display:flex;gap:8px;justify-content:flex-end">
            <button class="btn" onclick="doAdminLogin()">Iniciar sessió</button>
          </div>
          <p style="margin-top:12px;font-size:.85rem;color:var(--text-dim)">Si encara no has creat un usuari, executa el script `php/create_admin_user.php` (protegido por `ADMIN_PASSWORD`) o usa la constante `ADMIN_PASSWORD` en `php/config.php`.</p>
        </div>
      <?php else: ?>

        <div class="card">
          <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <input id="manual-player" placeholder="Nom del jugador" style="flex:1;padding:8px" />
            <input id="manual-points" placeholder="Punts" style="width:120px;padding:8px" />
            <button class="btn" onclick="adminAddPoints()">Afegir punts</button>
            <button class="btn" onclick="adminRemovePoints()">Treure punts</button>
          </div>
        </div>

        <div class="card" style="margin-top:16px">
          <p style="margin:0 0 8px">Reset total (requereix contrasenya)</p>
          <div style="display:flex;gap:8px;align-items:center">
            <input id="reset-password" type="password" placeholder="Contrasenya d'admin" style="padding:8px;flex:1" />
            <button class="btn btn--danger" onclick="adminResetAll()">Reset Tot</button>
          </div>
        </div>

        <div class="card" style="margin-top:16px">
          <p style="margin:0 0 8px">Importar JSON de puntuacions</p>
          <textarea id="import-json" placeholder='{"players":[{"player":"Nom","score":1200}]}' style="width:100%;height:120px;padding:8px"></textarea>
          <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
            <button class="btn" onclick="adminImportJSON()">Importar JSON</button>
            <button class="btn" onclick="document.getElementById('import-json').value = ''">Borrar</button>
          </div>
        </div>

        <div class="card" style="margin-top:16px">
          <p style="margin:0 0 8px">Jugadors</p>
          <table class="leaderboard__table" style="width:100%">
            <thead>
              <tr><th>#</th><th>Jugador</th><th>Punts</th><th>Partides</th><th>Accions</th></tr>
            </thead>
            <tbody id="admin-players-body">
              <tr><td colspan="5" style="text-align:center;padding:20px">Carregant...</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Edit modal -->
        <div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);align-items:center;justify-content:center">
          <div style="background:#0b0f12;padding:18px;border-radius:8px;min-width:320px;max-width:480px">
            <h3 style="margin:0 0 8px">Editar puntuació de <span id="edit-player-name-display"></span></h3>
            <input id="edit-player-name" type="hidden" />
            <div style="margin-bottom:8px"><input id="edit-score-value" type="number" style="width:100%;padding:8px" /></div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
              <button class="btn" onclick="closeEditModal()">Cancel·lar</button>
              <button class="btn btn--primary" onclick="adminSaveEdit()">Desar</button>
            </div>
          </div>
        </div>

      <?php endif; ?>

    </div>
  </main>

  <footer>
    <div class="container">
      <p>Admin · VRGame</p>
    </footer>

  <div class="toast-container" id="toast-container"></div>
  <script src="assets/js/main.js"></script>
  <?php if ($isLogged): ?>
    <script>window.CSRF_TOKEN = '<?php echo htmlspecialchars(getCsrfToken()); ?>';</script>
    <script src="assets/js/admin.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        showSection('players');
        adminLoadPlayers();
      });

      function doAdminLogout() {
        fetch('php/admin_logout.php', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': (window.CSRF_TOKEN || '') } })
            .then(function(r){ return r.json(); })
              .then(function(data){ window.location.reload(); })
              .catch(function(){ window.location.reload(); });
      }

      function adminImportJSON() {
        var txt = document.getElementById('import-json').value.trim();
        if (!txt) { showToast('Introdueix JSON per importar.','error'); return; }
        var payload;
        try { payload = JSON.parse(txt); } catch (e) { showToast('JSON invàlid.', 'error'); return; }

        fetch('php/import_scores.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': (window.CSRF_TOKEN || '') },
          body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success) {
            showToast('Import complet — inserits:' + data.inserted + ' millorats:' + data.updated_better + ' altres:' + data.updated_not_better, 'success');
            adminLoadPlayers();
          } else {
            showToast('Error: ' + (data.error || '?'), 'error');
          }
        })
        .catch(function() { showToast('Error de connexió.', 'error'); });
      }
    </script>
  <?php else: ?>
    <script>
      function doAdminLogin() {
        var username = document.getElementById('login-username').value.trim();
        var password = document.getElementById('login-password').value;
        if (!username || !password) { showToast('Omple tots els camps.', 'error'); return; }

        fetch('php/admin_login.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username: username, password: password })
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
          if (data.success) { showToast('Sessió iniciada.', 'success'); window.location.reload(); }
          else showToast('Error: ' + (data.error || '?'), 'error');
        })
        .catch(function(){ showToast('Error de connexió.', 'error'); });
      }
    </script>
  <?php endif; ?>
</body>
</html>
