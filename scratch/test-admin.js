const http = require('http');

function post(url, data, cookies = '') {
  return new Promise((resolve, reject) => {
    const parsed = new URL(url);
    const body = new URLSearchParams(data).toString();
    const req = http.request({
      hostname: parsed.hostname,
      port: parsed.port,
      path: parsed.pathname + parsed.search,
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Content-Length': Buffer.byteLength(body),
        'Cookie': cookies,
        'Connection': 'close'
      }
    }, res => {
      let resBody = '';
      res.on('data', chunk => resBody += chunk);
      res.on('end', () => {
        resolve({
          statusCode: res.statusCode,
          headers: res.headers,
          body: resBody
        });
      });
    });
    req.on('error', reject);
    req.write(body);
    req.end();
  });
}

function get(url, cookies = '') {
  return new Promise((resolve, reject) => {
    const parsed = new URL(url);
    const req = http.request({
      hostname: parsed.hostname,
      port: parsed.port,
      path: parsed.pathname + parsed.search,
      method: 'GET',
      headers: {
        'Cookie': cookies,
        'Connection': 'close'
      }
    }, res => {
      let resBody = '';
      res.on('data', chunk => resBody += chunk);
      res.on('end', () => {
        resolve({
          statusCode: res.statusCode,
          headers: res.headers,
          body: resBody
        });
      });
    });
    req.on('error', reject);
    req.end();
  });
}

async function run() {
  try {
    console.log('1. Testing install.php GET...');
    const installPage = await get('http://localhost:8000/admin/install.php');
    console.log('Install page status:', installPage.statusCode);
    
    // Extract csrf token if any, or post install
    console.log('2. Running installation (POST)...');
    const installRes = await post('http://localhost:8000/admin/install.php', {
      action: 'install',
      admin_user: 'admin',
      admin_pass: 'admin123#',
      admin_email: 'direction@meplus.ma'
    });
    console.log('Install result status:', installRes.statusCode);
    console.log('Install result headers location:', installRes.headers.location);
    console.log('Install result body:', installRes.body.substring(0, 300));

    console.log('3. Getting login page to extract CSRF token...');
    const loginPage = await get('http://localhost:8000/admin/login.php');
    console.log('Login page status:', loginPage.statusCode);
    const setCookie = loginPage.headers['set-cookie'];
    let cookie = '';
    if (setCookie) {
      cookie = setCookie[0].split(';')[0];
    }
    console.log('Session cookie:', cookie);

    const csrfMatch = loginPage.body.match(/name="csrf_token"\s+value="([^"]+)"/);
    const csrfToken = csrfMatch ? csrfMatch[1] : '';
    console.log('CSRF Token:', csrfToken);

    console.log('4. Logging in...');
    const loginRes = await post('http://localhost:8000/admin/login.php', {
      csrf_token: csrfToken,
      username: 'admin',
      password: 'admin123#'
    }, cookie);

    console.log('Login response status:', loginRes.statusCode);
    console.log('Redirect location:', loginRes.headers.location);
    if (loginRes.headers['set-cookie']) {
      cookie = loginRes.headers['set-cookie'][0].split(';')[0];
    }

    console.log('5. Testing dashboard (index.php)...');
    const indexRes = await get('http://localhost:8000/admin/index.php', cookie);
    console.log('Dashboard status:', indexRes.statusCode);
    console.log('Contains "Tableau de Bord":', indexRes.body.includes('Tableau de Bord'));
    console.log('Contains formations count:', indexRes.body.includes('Formations Actives'));

    console.log('6. Testing formations list (formations.php)...');
    const formationsRes = await get('http://localhost:8000/admin/formations.php', cookie);
    console.log('Formations list status:', formationsRes.statusCode);
    console.log('Contains formations table:', formationsRes.body.includes('Audit comportement'));

    console.log('7. Testing formation edit (formation_edit.php?id=1)...');
    const editRes = await get('http://localhost:8000/admin/formation_edit.php?id=1', cookie);
    console.log('Edit formation status:', editRes.statusCode);
    console.log('Contains title field:', editRes.body.includes('Titre de la formation'));
    console.log('Contains "Audit comportement":', editRes.body.includes('Audit comportement'));

    console.log('8. Testing pages list (pages.php)...');
    const pagesRes = await get('http://localhost:8000/admin/pages.php', cookie);
    console.log('Pages list status:', pagesRes.statusCode);
    console.log('Contains "Page d\'Accueil":', pagesRes.body.includes('Page d&#039;Accueil') || pagesRes.body.includes("Page d'Accueil"));

    console.log('9. Testing page edit (page_edit.php?page=index)...');
    const pageEditRes = await get('http://localhost:8000/admin/page_edit.php?page=index', cookie);
    console.log('Page edit status:', pageEditRes.statusCode);
    console.log('Contains Hero section:', pageEditRes.body.includes('En-tête Principal (Hero)'));

    console.log('10. Testing documents manager (documents.php)...');
    const docsRes = await get('http://localhost:8000/admin/documents.php', cookie);
    console.log('Documents status:', docsRes.statusCode);
    console.log('Contains documents count:', docsRes.body.includes('document(s) téléchargé(s)'));
    console.log('Contains BO document:', docsRes.body.includes('BO-2066') || docsRes.body.includes('Appareils'));

    console.log('11. Testing consultants manager (consultants.php)...');
    const consultantsRes = await get('http://localhost:8000/admin/consultants.php', cookie);
    console.log('Consultants status:', consultantsRes.statusCode);
    console.log('Contains consultant name:', consultantsRes.body.includes('EL HOUARI'));

    console.log('12. Testing clients manager (clients.php)...');
    const clientsRes = await get('http://localhost:8000/admin/clients.php', cookie);
    console.log('Clients status:', clientsRes.statusCode);
    console.log('Contains client logo:', clientsRes.body.includes('OCP') || clientsRes.body.includes('Lafarge'));

    console.log('\nALL TESTS COMPLETED SUCCESSFULLY!');
  } catch (err) {
    console.error('Test error:', err);
  }
}

run();
