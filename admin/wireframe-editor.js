function wireframeBlocks(parsed,groups){
 const controls=[...groups.Textes.children],byNode=new Map(controls.map(c=>[c.sourceText,c]));
 const roots=[...parsed.body.querySelectorAll('section,header,footer,main,article')].filter(n=>!n.parentElement.closest('section,header,footer,article')&&(n.tagName!=='MAIN'||!n.querySelector('section,article')));
 if(!roots.length)roots.push(parsed.body);const used=new Set(),blocks=[];
 function build(node){
  if(node.nodeType===3){const control=byNode.get(node);if(!control)return null;used.add(control);const original=control.querySelector('input,textarea');const edit=el('textarea',{class:'wire-text','aria-label':control.textContent.trim(),rows:'2'});edit.value=original.value;edit.oninput=()=>{original.value=edit.value;original.dispatchEvent(new Event('input',{bubbles:true}));edit.style.height='auto';edit.style.height=edit.scrollHeight+'px';};original.addEventListener('input',()=>edit.value=original.value);return edit;}
  if(node.nodeType!==1||node.matches('script,style,svg,noscript'))return null;
  if(node.tagName==='IMG'){const wrap=el('div',{class:'wire-image'});wrap.append(el('img',{src:new URL(node.getAttribute('src')||'',location.origin+'/').href,alt:node.getAttribute('alt')||'Image'}));return wrap;}
  const children=[...node.childNodes].map(build).filter(Boolean);if(!children.length)return null;
  const box=el('div',{class:'wire-box'});const classes=node.getAttribute('class')||'';
  const cols=classes.match(/(?:grid-cols-|grid-template-columns.*repeat\()(\d)/);if(cols){box.classList.add('wire-columns');box.style.setProperty('--wire-cols',Math.min(Number(cols[1]),4));}
  else if(/\bflex\b/.test(classes)&&!/flex-col/.test(classes))box.classList.add('wire-row');
  if(/^H[1-6]$/.test(node.tagName))box.classList.add('wire-heading');if(node.matches('a,button'))box.classList.add('wire-button');if(node.matches('section,article'))box.classList.add('wire-section');
  box.append(...children);return box;
 }
 for(const [index,root] of roots.entries()){
  if(!controls.some(c=>c.sourceText&&root.contains(c.sourceText)))continue;
  const title=root.querySelector('h1,h2,h3')?.textContent.trim().slice(0,70)||'Section '+(index+1),body=build(root);if(!body)continue;
  const panel=el('section',{class:'block-card',role:'tabpanel'});panel.append(el('h3',{class:'block-card-title'},title),body);
  const preview=parsed.cloneNode(true);preview.body.replaceChildren(root.cloneNode(true));preview.querySelectorAll('script,iframe,object,embed,base').forEach(n=>n.remove());preview.querySelectorAll('*').forEach(n=>{[...n.attributes].filter(a=>a.name.startsWith('on')).forEach(a=>n.removeAttribute(a.name));});const base=preview.createElement('base');base.href=location.origin+'/';preview.head.prepend(base);if(!preview.querySelector('link[rel=stylesheet]'))for(const href of ['/style.css','/design-system.css']){const link=preview.createElement('link');link.rel='stylesheet';link.href=href;preview.head.append(link);}
  blocks.push({name:title,title,panel,body,seo:false,preview:preview.documentElement.outerHTML});
 }
 controls.forEach(c=>{if(used.has(c))c.remove();});return blocks;
}
