// Arrange existing controls into a focused block editor; their save handlers stay intact.
function blockEditor(host,groups,parsed){
 host.classList.add('block-editor');const header=host.querySelector('.editor-head'),save=host.querySelector('.savebar');
 const shell=el('div',{class:'block-shell'}),rail=el('nav',{class:'block-rail','aria-label':'Sections du contenu'}),center=el('div',{class:'block-center'}),aside=el('aside',{class:'block-aside'}),tabs=el('div',{class:'block-tabs',role:'tablist','aria-label':'Édition'});
 const blocks=parsed?wireframeBlocks(parsed,groups):[];for(const [name,container] of Object.entries(groups)){
  const children=[...container.children];if(!children.length)continue;
  const chunks=name==='Textes'?Array.from({length:Math.ceil(children.length/6)},(_,i)=>children.slice(i*6,i*6+6)):[children];
  chunks.forEach((nodes,i)=>{const title=chunks.length>1?name+' · '+(i+1):name;const panel=el('section',{class:'block-card',role:'tabpanel'});panel.append(el('h3',{class:'block-card-title'},title));const body=el('div',{class:'fields'});nodes.forEach(node=>body.append(node));panel.append(body);blocks.push({name,title,panel,body,seo:name==='Métadonnées'});});
 }
 host.querySelectorAll(':scope > input[type=search]').forEach(n=>n.remove());
 for(const container of Object.values(groups)){const parent=container.parentElement;if(parent?.tagName==='DETAILS')parent.remove();else container.remove();}
 const actions=host.querySelector('.editor-actions');
 aside.append(el('p',{class:'eyebrow'},'INFORMATIONS'),el('h3',{},'Votre contenu'),el('p',{},'Modifiez les champs du bloc sélectionné. La structure du site reste fixe.'),el('div',{class:'block-note'},'Les changements sont appliqués après enregistrement.'));
 if(actions)aside.append(actions);
 let currentMode=false;function choose(block){center.replaceChildren(block.panel);rail.querySelectorAll('button').forEach(b=>b.setAttribute('aria-current',String(b.dataset.block===block.title)));}
 function render(seo){currentMode=seo;rail.replaceChildren();const list=blocks.filter(b=>b.seo===seo);for(const block of list){const b=button('',()=>choose(block),'block-tile');b.dataset.block=block.title;const thumb=el('div',{class:'block-thumb','aria-hidden':'true'}),img=block.body.querySelector('img');if(block.preview){const frame=el('iframe',{title:'Aperçu : '+block.title,sandbox:'',tabindex:'-1',loading:'lazy'});frame.srcdoc=block.preview;thumb.classList.add('section-preview');thumb.append(frame);}else if(img){thumb.append(el('img',{src:img.getAttribute('src')||'',alt:''}));}else{const values=[...block.body.querySelectorAll('input,textarea,select')].slice(0,3);values.forEach((n,i)=>thumb.append(el(i===0?'strong':'span',{},n.value||'Champ à compléter')));}b.append(thumb,el('strong',{},block.title),el('small',{},block.body.querySelectorAll('input,textarea,select').length+' champs'));rail.append(b);}if(list.length)choose(list[0]);else center.replaceChildren(el('p',{},'Aucun champ dans cet onglet.'));tabs.querySelectorAll('button').forEach(b=>b.setAttribute('aria-selected',String((b.dataset.mode==='seo')===seo)));}
 for(const [name,mode] of [['Contenu','content'],['SEO & partage','seo']]){if(mode==='seo'&&!blocks.some(b=>b.seo))continue;const b=button(name,()=>render(mode==='seo'));b.dataset.mode=mode;b.setAttribute('role','tab');tabs.append(b);}
 const top=el('div',{class:'block-top'});top.append(tabs);if(save)top.append(save);shell.append(rail,center,aside);host.append(top,shell);if(header)host.prepend(header);render(currentMode);
}
