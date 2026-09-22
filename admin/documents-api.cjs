const fs=require('node:fs');
const path=require('node:path');
const crypto=require('node:crypto');
const hash=b=>crypto.createHash('sha256').update(b).digest('hex');
module.exports=function(root,route,method,payload){
  if(!['documents','document-upload','document-delete'].includes(route))return null;
  const dir=path.join(root,'assets/documents');
  const sources=['data.json',...fs.readdirSync(root).filter(x=>x.endsWith('.html')),...fs.readdirSync(path.join(root,'components')).filter(x=>x.endsWith('.html')).map(x=>'components/'+x)];
  function usages(url){const found=[];const data=JSON.parse(fs.readFileSync(path.join(root,'data.json'),'utf8'));for(const [section,items] of Object.entries(data)){if(Array.isArray(items))items.forEach(item=>{if(JSON.stringify(item).includes(url))found.push({section,title:item.title||item.name||item.id});});}for(const source of sources.filter(x=>x!=='data.json'))if(fs.readFileSync(path.join(root,source),'utf8').includes(url))found.push({section:'pages',title:source});return found;}
  function file(url){if(typeof url!=='string'||!/^\/assets\/documents\/[a-zA-Z0-9_.-]+\.pdf$/.test(url))throw new Error('Document invalide.');return path.join(dir,path.basename(url));}
  if(route==='documents'&&method==='GET')return {documents:fs.existsSync(dir)?fs.readdirSync(dir).filter(x=>x.endsWith('.pdf')).map(name=>{const b=fs.readFileSync(path.join(dir,name));const url='/assets/documents/'+name;return {name,url,size:b.length,revision:hash(b),usages:usages(url)};}):[]};
  if(method!=='POST')throw new Error('Méthode non autorisée.');
  if(route==='document-upload'){
    if(typeof payload.base64!=='string'||!/^[A-Za-z0-9+/]*={0,2}$/.test(payload.base64))throw new Error('PDF invalide.');
    const buffer=Buffer.from(payload.base64,'base64');
    if(buffer.length>10*1024*1024||buffer.subarray(0,5).toString()!=='%PDF-')throw new Error('Choisissez un PDF de 10 Mo maximum.');
    fs.mkdirSync(dir,{recursive:true});
    let url;
    if(payload.url){const target=file(payload.url);if(hash(fs.readFileSync(target))!==payload.revision)throw new Error('Le document a changé. Rechargez la bibliothèque.');url=payload.url;}
    else {const base=String(payload.name||'document').replace(/\.pdf$/i,'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-zA-Z0-9_-]/g,'-').slice(0,90);url='/assets/documents/'+base+'-'+crypto.randomUUID().slice(0,8)+'.pdf';}
    fs.writeFileSync(file(url),buffer);return {url};
  }
  if(route==='document-delete'){
    const target=file(payload.url);if(hash(fs.readFileSync(target))!==payload.revision)throw new Error('Le document a changé. Rechargez la bibliothèque.');
    if(usages(payload.url).length)throw new Error('Ce document est encore utilisé. Retirez ses liens dans les sections indiquées avant de le supprimer.');
    fs.unlinkSync(target);return {deleted:payload.url};
  }
  throw new Error('Fonction inconnue.');
};
