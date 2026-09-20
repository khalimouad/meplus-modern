const fs = require('fs');
const path = require('path');

const phpDir = 'C:\\Users\\nadine\\.php';
const iniDev = path.join(phpDir, 'php.ini-development');
const iniDest = path.join(phpDir, 'php.ini');

let content = fs.readFileSync(iniDev, 'utf8');

// Enable extension_dir
content = content.replace(';extension_dir = "ext"', 'extension_dir = "ext"');

// Enable extensions
const exts = ['curl', 'fileinfo', 'gd', 'mbstring', 'openssl', 'pdo_sqlite', 'sqlite3', 'pdo_mysql', 'mysqli'];
exts.forEach(ext => {
    content = content.replace(';extension=' + ext, 'extension=' + ext);
});

// Also ensure upload max filesize
content = content.replace('upload_max_filesize = 2M', 'upload_max_filesize = 64M');
content = content.replace('post_max_size = 8M', 'post_max_size = 64M');

fs.writeFileSync(iniDest, content);
console.log('php.ini created with all extensions enabled!');
