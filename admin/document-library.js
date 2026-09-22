// Shared library used by page links and collection attachments.
function documentField(label,value,change){
 const wrap=el('div',{class:'document-field'}),input=field(label,value,change);
 const actions=el('div',{class:'actions'});
 actions.append(button('Choisir un document',()=>openDocuments(url=>{input.querySelector('input,textarea').value=url;change(url);changed();})),button('Retirer le lien',()=>{input.querySelector('input,textarea').value='';change('');changed();}));
 wrap.append(input,actions);return wrap;
}
async function openDocuments(pick){
 const dialog=el('dialog');document.body.append(dialog);const heading=el('div',{class:'dialog-head'});
 heading.append(el('h2',{},'Choisir un document'),button('Fermer',()=>dialog.close()));dialog.append(heading);
 dialog.addEventListener('close',()=>dialog.remove());dialog.showModal();
 await documentLibrary(dialog,url=>{pick(url);dialog.close();});
}
async function documentLibrary(host,pick){
 const shell=el('section',{class:'panel document-library'});host.append(shell);
 const notice=el('p',{role:'status'});
 const tools=el('div',{class:'toolbar'}),search=el('input',{type:'search',placeholder:'Rechercher un document…','aria-label':'Rechercher un document'});
 const filter=el('select',{'aria-label':'Section du document'}),sort=el('select',{'aria-label':'Trier les documents'});
 for(const [key,label] of [['','Toutes les sections'],['unused','Non utilisés'],...Object.entries(labels).filter(([k])=>!['dashboard','media','documents','settings','seo'].includes(k))])filter.append(el('option',{value:key},label));
 for(const [value,label] of [['name','Nom A–Z'],['size','Plus volumineux']])sort.append(el('option',{value},label));
 const upload=el('input',{type:'file',accept:'application/pdf,.pdf','aria-label':'Importer un PDF'});
 const counter=el('p',{'aria-live':'polite'}),list=el('div',{class:'document-list'});
 tools.append(search,filter,sort);shell.append(el('h2',{},'Documents PDF'),el('p',{},'Importez vos fichiers, consultez leurs utilisations ou remplacez un PDF sans changer ses liens. PDF · 10 Mo maximum.'),tools,upload,notice,counter,list);
 let docs=[];
 async function refresh(){const result=await api('documents');docs=result.documents||[];render();}
 async function send(file,record){
  if(!file)return;if(file.size>10*1024*1024)throw new Error('PDF limité à 10 Mo.');
  const base64=await new Promise((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(reader.result.split(',')[1]);reader.onerror=()=>reject(new Error('Lecture impossible.'));reader.readAsDataURL(file);});
  await api('document-upload',{name:file.name,base64,...(record?{url:record.url,revision:record.revision}:{})});
  notice.textContent=record?'Document remplacé. Tous ses liens restent valides.':'Document importé.';await refresh();
 }
 upload.onchange=async()=>{upload.disabled=true;try{await send(upload.files[0]);}catch(e){notice.textContent=e.message;}finally{upload.value='';upload.disabled=false;}};
 function render(){
  list.replaceChildren();const rows=docs.filter(d=>searchKey(d.name+' '+d.usages.map(u=>u.title).join(' ')).includes(searchKey(search.value))&&(!filter.value||(filter.value==='unused'?!d.usages.length:d.usages.some(u=>u.section===filter.value))));
  rows.sort((a,b)=>sort.value==='size'?b.size-a.size:a.name.localeCompare(b.name,'fr'));counter.textContent=rows.length+' document(s) sur '+docs.length;
  for(const record of rows){
   const card=el('article',{class:'document-card'});card.append(el('h3',{},record.name),el('p',{},Math.round(record.size/1024)+' Ko · '+record.usages.length+' utilisation(s)'));
   const usages=el('ul');record.usages.forEach(u=>usages.append(el('li',{},(labels[u.section]||u.section)+' — '+u.title)));card.append(usages);
   const actions=el('div',{class:'actions'});actions.append(el('a',{href:record.url,target:'_blank',rel:'noopener',class:'button'},'Ouvrir PDF'));
   if(pick)actions.append(button('Sélectionner',()=>pick(record.url),'primary'));
   const replace=el('label',{class:'upload-button'},'Remplacer');const input=el('input',{type:'file',accept:'application/pdf,.pdf','aria-label':'Remplacer '+record.name});replace.append(input);
   input.onchange=async()=>{if(!input.files[0])return;if(!confirm('Remplacer ce PDF dans toutes ses utilisations ?')){input.value='';return;}input.disabled=true;try{await send(input.files[0],record);}catch(e){notice.textContent=e.message;}finally{input.disabled=false;input.value='';}};
   const remove=button('Supprimer',async()=>{if(!confirm('Supprimer définitivement ce document inutilisé ?'))return;try{await api('document-delete',{url:record.url,revision:record.revision});notice.textContent='Document supprimé.';await refresh();}catch(e){notice.textContent=e.message;}},'danger');remove.disabled=record.usages.length>0;remove.title=record.usages.length?'Retirez les liens avant de supprimer ce document.':'Supprimer le document';
   actions.append(replace,remove);card.append(actions);list.append(card);
  }
  if(!rows.length)list.append(el('p',{},'Aucun document ne correspond aux filtres.'));
 }
 search.oninput=render;filter.onchange=render;sort.onchange=render;
 try{await refresh();}catch(e){notice.textContent=e.message;}
}
