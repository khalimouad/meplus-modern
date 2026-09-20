// Static shared components: generated pages also work without JavaScript or a server.
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const navigation = fs.readFileSync(path.join(root, 'components/navigation.html'), 'utf8');
const routes = [...navigation.matchAll(/<a\s+href="([^"]+)"[^>]*>([\s\S]*?)<\/a>/g)].map(match=>[match[1],match[2]]);
const check = process.argv.includes('--check');
let stale = false;
for (const file of fs.readdirSync(root).filter(file => file.endsWith('.html'))) {
  const original = fs.readFileSync(path.join(root, file), 'utf8');
  const active = file === 'formation-detail.html' ? 'formations.html' : file;
  const nav = mobile => routes.filter(([href]) => mobile || href !== 'contact.html').map(([href, label]) =>
    `<a href="${href}"${mobile ? ' class="drawer-link"' : ''}${href === active ? ' aria-current="page"' : ''}>${label}</a>`).join('\n');
  const generated = original.replace(/<!-- component:([\w-]+) -->[\s\S]*?<!-- \/component:\1 -->/g, (_, name) => {
    let component = fs.readFileSync(path.join(root, 'components', `${name}.html`), 'utf8').trim();
    component = component.replace('{{navigation}}', nav(false)).replace('{{mobileNavigation}}', nav(true));
    if (name === 'header' && active === 'contact.html') component = component.replace('class="ui-button ui-button-primary header-cta"', 'class="ui-button ui-button-primary header-cta" aria-current="page"');
    if (name === 'mobile-dock') component = component.replace(/<a href="([^"]+)"/g, (tag, href) => tag + (href === active ? ' aria-current="page"' : ''));
    return `<!-- component:${name} -->\n${component}\n<!-- /component:${name} -->`;
  });
  if (generated !== original) {
    if (check) { console.error(`Outdated components: ${file}`); stale = true; }
    else fs.writeFileSync(path.join(root, file), generated);
  }
}
if (stale) process.exitCode = 1;
else console.log(check ? 'All public pages have current shared components.' : 'Shared components built for all public pages.');
