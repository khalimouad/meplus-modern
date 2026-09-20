const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = process.env.PORT || 3000;
const ROOT = __dirname;
const DATA_JSON_PATH = path.join(ROOT, 'data.json');
const DATA_JS_PATH = path.join(ROOT, 'data.js');
const LEADS_JSON_PATH = path.join(ROOT, 'admin', 'data', 'leads.json');
const editorAPI = require('./admin/editor-api.cjs').createEditorAPI(ROOT);

const MIME_TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.gif': 'image/gif',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon',
  '.pdf': 'application/pdf',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.ttf': 'font/ttf'
};

const server = http.createServer(async (req, res) => {
  const parsedUrl = new URL(req.url, `http://${req.headers.host}`);
  let pathname = decodeURIComponent(parsedUrl.pathname);
  if (await editorAPI(req, res, parsedUrl)) return;

  // Enable CORS for API
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  // ==========================================
  // REST API ENDPOINTS
  // ==========================================
  if (pathname === '/api/data' && req.method === 'GET') {
    fs.readFile(DATA_JSON_PATH, 'utf8', (err, data) => {
      if (err) {
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Failed to read data.json' }));
        return;
      }
      res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
      res.end(data);
    });
    return;
  }

  if (pathname === '/api/save' && req.method === 'POST') {
    let body = '';
    req.on('data', chunk => { body += chunk; });
    req.on('end', () => {
      try {
        const payload = JSON.parse(body);
        const jsonString = JSON.stringify(payload, null, 4);

        // 1. Write to data.json
        fs.writeFileSync(DATA_JSON_PATH, jsonString, 'utf8');

        // 2. Synchronize and generate data.js
        const jsContent = `// Données synchronisées MEPLUS Maroc - ${new Date().toISOString().replace('T', ' ').slice(0, 19)}\nwindow.MEPLUS_DATA = ${JSON.stringify(payload)};\n`;
        fs.writeFileSync(DATA_JS_PATH, jsContent, 'utf8');

        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ 
          success: true, 
          message: 'data.json and data.js synchronized successfully',
          formationsCount: (payload.formations || []).length
        }));
      } catch (err) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Invalid JSON payload: ' + err.message }));
      }
    });
    return;
  }

  if (pathname === '/api/leads') {
    if (req.method === 'GET') {
      if (fs.existsSync(LEADS_JSON_PATH)) {
        const leadsData = fs.readFileSync(LEADS_JSON_PATH, 'utf8');
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(leadsData);
      } else {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify([]));
      }
      return;
    }
    if (req.method === 'POST') {
      let body = '';
      req.on('data', chunk => { body += chunk; });
      req.on('end', () => {
        try {
          const newLead = JSON.parse(body);
          let leads = [];
          if (fs.existsSync(LEADS_JSON_PATH)) {
            leads = JSON.parse(fs.readFileSync(LEADS_JSON_PATH, 'utf8') || '[]');
          }
          leads.unshift(newLead);
          fs.writeFileSync(LEADS_JSON_PATH, JSON.stringify(leads, null, 2), 'utf8');
          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ success: true, lead: newLead }));
        } catch (err) {
          res.writeHead(400, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ error: err.message }));
        }
      });
      return;
    }
  }

  // ==========================================
  // ROUTING & STATIC FILES
  // ==========================================
  // Route /admin or /admin/ or /admin.html to admin/index.html
  if (['/admin','/admin/','/admin.html','/admin/index.html'].includes(pathname)) {
    return serveFile(path.join(ROOT, 'admin', 'studio.html'), res);
  }

  // Default route to index.html
  if (pathname === '/' || pathname === '') {
    pathname = '/index.html';
  }

  let filePath = path.join(ROOT, pathname);

  // Security check: prevent directory traversal
  if (!filePath.startsWith(ROOT)) {
    res.writeHead(403, { 'Content-Type': 'text/plain' });
    res.end('403 Forbidden');
    return;
  }

  // Check if direct file exists
  fs.stat(filePath, (err, stats) => {
    if (!err && stats.isFile()) {
      return serveFile(filePath, res);
    }

    // Try clean URLs (e.g. /formations -> /formations.html)
    const htmlPath = filePath + '.html';
    fs.stat(htmlPath, (htmlErr, htmlStats) => {
      if (!htmlErr && htmlStats.isFile()) {
        return serveFile(htmlPath, res);
      }

      // If it's a directory, look for index.html
      if (!err && stats.isDirectory()) {
        const dirIndex = path.join(filePath, 'index.html');
        fs.stat(dirIndex, (dirErr, dirStats) => {
          if (!dirErr && dirStats.isFile()) {
            return serveFile(dirIndex, res);
          }
          send404(res);
        });
        return;
      }

      send404(res);
    });
  });
});

function serveFile(filePath, res) {
  const ext = path.extname(filePath).toLowerCase();
  const contentType = MIME_TYPES[ext] || 'application/octet-stream';

  fs.readFile(filePath, (err, data) => {
    if (err) {
      res.writeHead(500, { 'Content-Type': 'text/plain' });
      res.end('500 Internal Server Error');
      return;
    }

    res.writeHead(200, {
      'Content-Type': contentType,
      'Cache-Control': 'no-cache'
    });
    res.end(data);
  });
}

function send404(res) {
  res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
  res.end(`<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Page Non Trouvée - 404</title>
  <style>
    body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #060c18; color: white; text-align: center; }
    h1 { font-size: 3rem; margin-bottom: 0.5rem; color: #00d2ff; }
    p { color: #94a3b8; margin-bottom: 1.5rem; }
    a { color: #00d2ff; text-decoration: none; border: 1px solid #00d2ff; padding: 10px 20px; border-radius: 9999px; }
    a:hover { background: #00d2ff; color: #060c18; }
  </style>
</head>
<body>
  <div>
    <h1>404</h1>
    <p>La page demandée n'existe pas.</p>
    <a href="/">Retour à l'accueil</a>
  </div>
</body>
</html>`);
}

server.listen(PORT, () => {
  console.log(`\n==================================================`);
  console.log(`🚀 ME PLUS Web Platform & Admin API is running!`);
  console.log(`👉 Public Site: http://localhost:${PORT}`);
  console.log(`👉 Admin Panel: http://localhost:${PORT}/admin`);
  console.log(`👉 Press Ctrl+C in terminal to stop`);
  console.log(`==================================================\n`);
});
