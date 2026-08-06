VR_ShooterVila — Instrucciones de integración
============================================

Resumen
-------
Este repo contiene la web acompañante de un juego exportado (UE5 → HTML5/WebGL). Incluye un backend PHP ligero para gestionar la clasificación (scores) y un frontend estático.

Estructura relevante
- [php](php): endpoints API para puntuaciones y administración.
- [assets/js](assets/js): lógica front (main, leaderboard, admin).
- [game](game): carpeta donde debe colocarse el build exportado del juego (placeholder incluido).
- [db/schema.sql](db/schema.sql): script para crear la base de datos y la tabla `scores`.

Instalación rápida (servidor LAMP/LEMP)
------------------------------------
1. Copia el proyecto al webroot del servidor.
2. Crea la base de dades i importa l'schema:

```sql
-- executar a MySQL / phpMyAdmin
SOURCE db/schema.sql;
```

3. Ajusta las constantes de conexión en [php/config.php](php/config.php).

Endpoints principales (resumen)
-----------------------------
Todos los endpoints devuelven JSON. El contenido y ejemplo de uso:

- `php/add_score.php` (POST)
	- Body JSON: `{ "player": "Name", "score": 1500 }`
	- Guarda/actualiza la puntuación máxima y aumenta `games_played`.
	- Respuesta: `{ "success": true, "best_score": 1500 }`

- `php/get_leaderboard.php` (GET)
	- Parámetro opcional `?limit=50`
	- Respuesta: `{ "success": true, "players": [ { player_name, score, games_played, updated_at }, ... ] }`

- `php/get_player.php` (GET)
	- `?player=Name`
	- Respuesta: `{ "success": true, "player": { ... } }`

- `php/register_player.php` (POST)
	- Body: `{ "player": "Name" }` — crea el registro si no existe.

- `php/add_points.php`, `php/remove_points.php` (POST)
	- Body: `{ "player": "Name", "points": 100 }` — suman/restan puntos (límites comprobados).

- `php/set_points.php` (POST)
	- Body: `{ "player": "Name", "score": 2000 }` — establece la puntuación exacta (admin).

- `php/remove_score.php` (POST)
	- Body: `{ "player": "Name" }` — elimina el registro del jugador (admin).

- `php/reset_player.php` (POST)
	- Body: `{ "player": "Name" }` — pone a 0 la puntuación del jugador.

- `php/reset_scores.php` (POST)
	- Body: `{ "admin_password": "..." }` — borra todas las puntuaciones (requiere ADMIN_PASSWORD en config).

- `php/import_scores.php` (POST)
	- Body JSON: `{ "players": [ { "player": "Name", "score": 1200, "games_played": 1 }, ... ] }`
	- Importa en batch: crea nous jugadors o actualitza la puntuació només si la nova és millor. Resposta resum.

Ejemplo de importación en batch
-------------------------------
Ejemplo de payload (`scores.json`):

```json
{
  "players": [
    { "player": "Alice", "score": 1250, "games_played": 1 },
    { "player": "Bob", "score": 980, "games_played": 2 }
  ]
}
```

Enviar con `curl`:

```bash
curl -X POST -H "Content-Type: application/json" --data-binary @scores.json https://tu-servidor/path/php/import_scores.php
```

Notas:
- Si un jugador ja existeix, la puntuació s'actualitza només si la nova és millor; `games_played` s'incrementa amb el valor proporcionat.
- També puedes pegar JSON en el panel Admin → Importar JSON.

Integración con el juego (UE5 export)
-----------------------------------
1. Exporta tu build HTML5/JS desde Unreal y coloca los archivos dentro de la carpeta `/game` (reemplazando el placeholder `game/index.html`).
2. El `index.html` principal de la web carga el juego dentro de un `<iframe src="game/index.html">` en `game.html`.
3. Comunicación desde el juego al contenedor (web):
	 - Cuando el juego quiera actualizar la HUD: `parent.postMessage({ type: 'score_update', score: <n> }, '*')`.
	 - Para indicar fin de partida y enviar la puntuación final: `parent.postMessage({ type: 'game_over', score: <n> }, '*')`.
	 - La página `game.html` ya escucha `message` y hará `fetch('php/add_score.php', ...)` para guardar la puntuación.

Seguridad y buenas prácticas
---------------------------
- Cambia las claves por defecto en [php/config.php](php/config.php) antes del deployment.
- Usa HTTPS en producción para evitar manipulaciones de tráfico.
- Si necesitas más seguridad: añade un token firmado que el juego incluya al enviar puntuaciones.
- Configura permisos de fichero para que `php/config.php` no sea descargable desde la web (idealmente colócalo fuera del webroot).

Pruebas locales
---------------
- Abre `index.html` en navegador a través del host local o servidor web.
- Para probar la comunicación entre juego y web, deja el placeholder de [game/index.html](game/index.html) y usa los botones "Enviar +100" y "Enviar game_over".
 - Para probar la comunicación entre juego y web, deja el placeholder de [game/index.html](game/index.html) y usa los botones "Enviar player_info", "Enviar +100" y "Enviar game_over" (el botón `player_info` envia el nom del jugador a la pàgina mare).

Crear administrador
------------------
Puedes crear un usuario administrador (con contraseña hasheada) mediante `php/create_admin_user.php` enviando JSON protegido por la constante `ADMIN_PASSWORD` definida en `php/config.php`:

```bash
curl -X POST -H "Content-Type: application/json" \
	-d '{"admin_password":"<ADMIN_PASSWORD>","username":"admin","password":"secret123"}' \
	https://tu-servidor/path/php/create_admin_user.php
```

El script escribirá `php/admin_users.json` con la contraseña hasheada. Por seguridad elimina o protege `create_admin_user.php` después de usarlo.

Contacto
-------
Si quieres que adapte la API para tokens firmados, integración con OAuth o registre eventos adicionales (kills, tiempo, etc.), dime qué campos quieres y lo implemento.
