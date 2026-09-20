const https = require('https');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

async function main() {
    console.log('Fetching windows.php.net/downloads/releases/ ...');
    const res = await fetch('https://windows.php.net/downloads/releases/');
    const html = await res.text();
    
    // Look for php-8.2.xx-Win32-vs16-x64.zip or php-8.3.xx-Win32-vs16-x64.zip
    const matches = [...html.matchAll(/href="\/downloads\/releases\/(php-8\.[23]\.[0-9]+-Win32-vs16-x64\.zip)"/g)];
    if (!matches || matches.length === 0) {
        // Try without /downloads/releases/ prefix
        const m2 = [...html.matchAll(/href="(php-8\.[23]\.[0-9]+-Win32-vs16-x64\.zip)"/g)];
        if (m2.length > 0) {
            matches.push(...m2);
        }
    }
    
    if (matches.length === 0) {
        console.log('No matches found. HTML excerpt:', html.substring(0, 500));
        return;
    }
    
    const zipName = matches[matches.length - 1][1];
    const zipUrl = `https://windows.php.net/downloads/releases/${zipName}`;
    console.log(`Found PHP binary: ${zipName}`);
    console.log(`URL: ${zipUrl}`);

    const targetDir = 'C:\\Users\\nadine\\.php';
    if (!fs.existsSync(targetDir)) {
        fs.mkdirSync(targetDir, { recursive: true });
    }

    const zipDest = path.join(targetDir, zipName);
    console.log(`Downloading to ${zipDest} ...`);

    const fileStream = fs.createWriteStream(zipDest);
    const downloadRes = await fetch(zipUrl);
    const arrayBuffer = await downloadRes.arrayBuffer();
    fs.writeFileSync(zipDest, Buffer.from(arrayBuffer));
    console.log(`Download complete: ${fs.statSync(zipDest).size} bytes`);

    console.log(`Extracting to ${targetDir} ...`);
    execSync(`powershell -Command "Expand-Archive -Path '${zipDest}' -DestinationPath '${targetDir}' -Force"`, { stdio: 'inherit' });

    console.log('Enabling extensions in php.ini ...');
    const iniDevelopment = path.join(targetDir, 'php.ini-development');
    const phpIni = path.join(targetDir, 'php.ini');
    if (fs.existsSync(iniDevelopment) && !fs.existsSync(phpIni)) {
        let content = fs.readFileSync(iniDevelopment, 'utf8');
        // Enable pdo_sqlite, sqlite3, curl, mbstring, openssl, gd, fileinfo
        content = content.replace(';extension_dir = "ext"', 'extension_dir = "ext"');
        content = content.replace(';extension=curl', 'extension=curl');
        content = content.replace(';extension=fileinfo', 'extension=fileinfo');
        content = content.replace(';extension=gd', 'extension=gd');
        content = content.replace(';extension=mbstring', 'extension=mbstring');
        content = content.replace(';extension=openssl', 'extension=openssl');
        content = content.replace(';extension=pdo_sqlite', 'extension=pdo_sqlite');
        content = content.replace(';extension=sqlite3', 'extension=sqlite3');
        content = content.replace(';extension=pdo_mysql', 'extension=pdo_mysql');
        content = content.replace(';extension=mysqli', 'extension=mysqli');
        fs.writeFileSync(phpIni, content);
        console.log('php.ini created and configured successfully.');
    }

    const phpExe = path.join(targetDir, 'php.exe');
    console.log('Testing PHP:');
    const versionOutput = execSync(`"${phpExe}" -v`).toString();
    console.log(versionOutput);
}

main().catch(err => {
    console.error('Error:', err);
});
