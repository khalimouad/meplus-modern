// Preview-only controls. All changes flow through the existing form inputs.
function visualTextEditor(host,fields,source){
 const inputs=[...fields.querySelectorAll('input[type=text],textarea')].filter(input=>!input.closest('.field-image,.document-field')&&!input.closest('label')?.textContent.includes('Identifiant'));
 if(!inputs.length)return;
 const panel=el('section',{class:'visual-builder'}),toolbar=el('div',{class:'visual-toolbar'}),canvas=el('div',{class:'visual-canvas'}),inspector=el('div',{class:'visual-inspector'});
 toolbar.append(el('strong',{},'Édition visuelle du texte'),el('span',{},'Cliquez sur un texte, puis modifiez-le. La mise en page reste fixe.'));
 const label=el('h3',{},'Sélectionnez un texte'),edit=el('textarea',{'aria-label':'Texte sélectionné',disabled:''}),hint=el('p',{},'Les modifications restent en brouillon jusqu’à l’enregistrement.');inspector.append(label,edit,hint);
 const layout=el('div',{class:'visual-layout'});layout.append(canvas,inspector);panel.append(toolbar,layout);host.insertBefore(panel,fields.parentElement?.tagName==='DETAILS'?fields.parentElement:fields);
 let active=null;const previews=new Map();
 function select(input){active=input;label.textContent=input.closest('label')?.querySelector('span')?.textContent||'Texte';edit.disabled=false;edit.value=input.value;previews.forEach((node,key)=>node.classList.toggle('visual-selected',key===input));edit.focus({preventScroll:true});}
 edit.addEventListener('input',()=>{if(!active)return;active.value=edit.value;active.dispatchEvent(new Event('input',{bubbles:true}));});
 function bind(node,input){previews.set(input,node);node.tabIndex=0;node.setAttribute('role','button');node.setAttribute('aria-label','Modifier : '+input.value.slice(0,80));node.classList.add('visual-text');node.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();select(input);});node.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();select(input);}});input.addEventListener('input',()=>{node.textContent=input.value;if(active===input)edit.value=input.value;});}
 if(source){
  const frame=el('iframe',{title:'Aperçu visuel du contenu',sandbox:'allow-same-origin'});canvas.append(frame);
  const doc=new DOMParser().parseFromString(source,'text/html');doc.querySelectorAll('script,iframe,object,embed,base,meta[http-equiv]').forEach(n=>n.remove());
  const base=doc.createElement('base');base.href=location.origin+'/';doc.head.prepend(base);if(!doc.querySelector('link[rel=stylesheet]')){for(const href of ['/style.css','/design-system.css']){const link=doc.createElement('link');link.rel='stylesheet';link.href=href;doc.head.append(link);}}
  doc.querySelectorAll('*').forEach(n=>{[...n.attributes].filter(a=>a.name.startsWith('on')).forEach(a=>n.removeAttribute(a.name));});
  const walker=doc.createTreeWalker(doc.body,NodeFilter.SHOW_TEXT),texts=[];let node;while(node=walker.nextNode()){if(node.textContent.trim()&&!node.parentElement.closest('style,noscript,svg')&&!node.textContent.includes('{{'))texts.push(node);}
  const used=new Set();for(const input of inputs){const text=texts.find(n=>!used.has(n)&&n.textContent.trim()===input.value.trim());if(!text)continue;used.add(text);const span=doc.createElement('span');span.dataset.visualIndex=String(inputs.indexOf(input));span.textContent=text.textContent;text.replaceWith(span);}
  const style=doc.createElement('style');style.textContent='.visual-text{cursor:text;outline:1px dashed #527df299;outline-offset:3px}.visual-text:hover,.visual-selected{outline:2px solid #2563eb!important;background:#2563eb15}html,body{overflow-x:hidden}*{animation:none!important;transition:none!important}';doc.head.append(style);
  frame.addEventListener('load',()=>{const preview=frame.contentDocument;if(!preview)return;preview.querySelectorAll('[data-visual-index]').forEach(n=>bind(n,inputs[Number(n.dataset.visualIndex)]));preview.addEventListener('click',e=>e.preventDefault());preview.addEventListener('submit',e=>e.preventDefault());});frame.srcdoc=doc.documentElement.outerHTML;
 }else{
  canvas.classList.add('visual-record');for(const input of inputs){const block=el('div',{class:'visual-record-block'});block.append(el('small',{},input.closest('label')?.querySelector('span')?.textContent||'Texte'));const text=el('div',{},input.value||'Texte vide');bind(text,input);block.append(text);canvas.append(block);}
 }
}
