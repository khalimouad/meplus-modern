const fs = require('fs');
const path = require('path');
const https = require('https');
const http = require('http');

const targetDir = path.resolve('c:/Users/nadine/Documents/meplus/meplus-modern/assets/documents');
if (!fs.existsSync(targetDir)) {
  fs.mkdirSync(targetDir, { recursive: true });
}

const pdfUrls = [
  {
    name: 'BO-6700-Controles-reglementaires-arrete-1281-18.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2026/02/BO-6700-Arrete-1281-18-du-15-mars-2018-Controles-reglementaires-page-1565.pdf',
    title: 'Contrôles Réglementaires des Équipements (Arrêté 1281-18)'
  },
  {
    name: 'BO-7157-Installations-electriques-maroc.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2025/06/BO-7157-Mesures-speciales-pour-les-installations-electriques-en-Arabe-page-11.pdf',
    title: 'Installations Électriques & Mesures Spéciales'
  },
  {
    name: 'BO-7157-Securite-machines-arabe.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2025/06/BO-7157-Securite-machine-en-Arabe-Page-5.pdf',
    title: 'Sécurité des Machines & Dispositifs d’Arrêt'
  },
  {
    name: 'BO-2207-Appareils-pression-gaz.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/2-BO-2207-portant-reglement-sur-lemploi-des-appareil-a-pression-de-gaz-page-187-.pdf',
    title: 'Appareils sous Pression de Gaz'
  },
  {
    name: 'BO-2066-Appareils-levage-echafaudages.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/BO-2066-Appareils-de-levage-et-manutention-Travaus-sous-terrain-Echaffaudages-echelles-travaux-de-toiture.pdf',
    title: 'Appareils de Levage, Échafaudages & Travaux en Hauteur'
  },
  {
    name: 'BO-6306-Tableau-maladies-professionnelles.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/BO-6306-BO-4788-tableau-des-MP-2014.pdf',
    title: 'Tableau Officiel des Maladies Professionnelles'
  },
  {
    name: 'BO-6306-Securite-incendie-decret-2-14-499.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/BO-6306-du-6-11-2014-SECURITE-INCENDIE-page-4270.pdf',
    title: 'Sécurité Incendie & Désenfumage (Décret 2.14.499)'
  },
  {
    name: 'BO-6454-Valeurs-limites-exposition-chimique.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/BO-6454-Valeurs-limites-dexpositions-professionnelles-page-478.pdf',
    title: 'Valeurs Limites d’Exposition aux Produits Chimiques (VLEP)'
  },
  {
    name: 'BO-5680-Code-du-travail-mesures-hygiene-securite.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/BO-5680-mesures-application-art-281-eu-293-CT-page-1410.pdf',
    title: 'Code du Travail - Mesures d’Hygiène et de Sécurité'
  },
  {
    name: 'BO-5956bis-Transport-marchandises-dangereuses.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/BO-5956bis-transport-par-route-de-marchandises-dangereuses-page-1765.pdf',
    title: 'Transport Routier de Marchandises Dangereuses (TMD)'
  },
  {
    name: 'BO-6700-Presses-et-appareils-levage.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/BO-6700-Controles-reglementaires-Presses-et-appareils-de-levage.pdf',
    title: 'Presses d’Atelier & Organes de Protection'
  },
  {
    name: 'BO-6214-Securite-machines-france-maroc.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/BO-6214-MAR-securite-machines.pdf',
    title: 'Sécurité Machines & Protections'
  },
  {
    name: 'BO-6214-Risques-chimiques-et-biologiques.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/BO-6214-Risques-chimiques-et-biologiques.pdf',
    title: 'Prévention des Risques Chimiques et Biologiques'
  },
  {
    name: 'BO-6158-Loi-24-09-Securite-produits-services.pdf',
    url: 'https://meplus.ma/wp-content/uploads/2023/12/BO-6158_LOI-24-09-Securite-des-produits-et-service.pdf',
    title: 'Loi 24-09 relative à la Sécurité des Produits et Services'
  }
];

function download(item) {
  return new Promise((resolve) => {
    const filePath = path.join(targetDir, item.name);
    console.log(`Downloading: ${item.name} from ${item.url}`);
    
    const client = item.url.startsWith('https') ? https : http;
    const req = client.get(item.url, {
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
      },
      timeout: 15000
    }, (res) => {
      // Handle redirects
      if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
        console.log(`Redirecting to ${res.headers.location}`);
        const redirectClient = res.headers.location.startsWith('https') ? https : http;
        redirectClient.get(res.headers.location, (redRes) => {
          const fileStream = fs.createWriteStream(filePath);
          redRes.pipe(fileStream);
          fileStream.on('finish', () => {
            fileStream.close();
            console.log(`Saved: ${item.name} (${fs.statSync(filePath).size} bytes)`);
            resolve(true);
          });
        }).on('error', err => {
          console.error(`Error downloading ${item.name}:`, err.message);
          resolve(false);
        });
        return;
      }

      if (res.statusCode === 200) {
        const fileStream = fs.createWriteStream(filePath);
        res.pipe(fileStream);
        fileStream.on('finish', () => {
          fileStream.close();
          console.log(`Saved: ${item.name} (${fs.statSync(filePath).size} bytes)`);
          resolve(true);
        });
      } else {
        console.error(`Status ${res.statusCode} for ${item.url}`);
        resolve(false);
      }
    });

    req.on('error', (err) => {
      console.error(`Request error on ${item.name}:`, err.message);
      resolve(false);
    });

    req.on('timeout', () => {
      req.destroy();
      console.error(`Timeout on ${item.name}`);
      resolve(false);
    });
  });
}

async function run() {
  for (const item of pdfUrls) {
    await download(item);
  }
  console.log('All downloads completed!');
}

run();
