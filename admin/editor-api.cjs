const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {execFileSync} = require('node:child_process');
const pages = ['index','formations','formation-detail','services','consultants','reglementation','simulateur','contact'];
const components = ['header','navigation','footer','home-hero','mobile-dock'];
const documents = [...pages.map(p=>`${p}.html`),...components.map(p=>`components/${p}.html`)];
const revision = s => crypto.createHash('sha256').update(s).digest('hex');
function createEditorAPI(root) {
  const read = name => fs.readFileSync(path.join(root,name),'utf8');
  function json(res,status,data) { res.writeHead(status,{'Content-Type':'application/json; charset=utf-8','Cache-Control':'no-store'}); res.end(JSON.stringify(data)); }
  return async function(req,res,url) {
    if (!url.pathname.startsWith('/api/editor/')) return false;
    try {
      const hosted = process.env.VERCEL === '1' || process.env.VERCEL === 'true';
      const address = req.socket.remoteAddress;
      if (!hosted && !['127.0.0.1','::1','::ffff:127.0.0.1'].includes(address)) throw Object.assign(new Error('Administration locale uniquement.'),{status:403});
      if (!hosted && !/^(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/.test(req.headers.host || '')) throw Object.assign(new Error('Hôte non autorisé.'),{status:403});
      if (!hosted && req.headers.origin && req.headers.origin !== `http://${req.headers.host}`) throw Object.assign(new Error('Origine non autorisée.'),{status:403});
      if (hosted && req.method === 'POST') throw Object.assign(new Error('Le studio Vercel est en lecture seule : connectez un stockage persistant pour enregistrer les modifications.'),{status:501});
      let payload;
      if (req.method === 'POST') {
        if (!req.headers['content-type']?.startsWith('application/json')) throw Object.assign(new Error('JSON requis.'),{status:415});
        let body=''; for await (const chunk of req) { body+=chunk; if (Buffer.byteLength(body)>15*1024*1024) throw Object.assign(new Error('Fichier trop volumineux (10 Mo maximum).'),{status:413}); }
        payload=JSON.parse(body);
      }
      const route=url.pathname.slice('/api/editor/'.length);
      const documentResult=require('./documents-api.cjs')(root,route,req.method,payload);
      if(documentResult){json(res,200,documentResult);return true;}
      if (route === 'inventory' && req.method === 'GET') { json(res,200,{pages,components}); return true; }
      if (route === 'document') {
        const name=req.method === 'GET' ? url.searchParams.get('name') : payload.name;
        if (!documents.includes(name)) throw Object.assign(new Error('Document non autorisé.'),{status:400});
        const source=read(name);
        if (req.method === 'GET') { json(res,200,{name,source,revision:revision(source)}); return true; }
        if (req.method !== 'POST') throw new Error('Méthode non autorisée.');
        if (payload.revision !== revision(source)) throw Object.assign(new Error('Ce contenu a changé. Rechargez avant de sauvegarder.'),{status:409});
        if (typeof payload.source !== 'string' || !payload.source.trim() || payload.source.length>500000) throw new Error('Contenu invalide.');
        // Preserve generated blocks: shared content is edited in its own component.
        const blocks=source.match(/<!-- component:[\w-]+ -->[\s\S]*?<!-- \/component:[\w-]+ -->/g)||[];
        for(const block of blocks) if(!payload.source.includes(block)) throw new Error('Modifiez les composants partagés dans leur onglet dédié.');
        const previous = new Map(documents.map(file=>[file,read(file)]));
        try {
          fs.writeFileSync(path.join(root,name),payload.source);
          execFileSync(process.execPath,[path.join(root,'scripts/build-components.cjs')],{cwd:root,windowsHide:true});
        } catch(error) { for(const [file,content] of previous) fs.writeFileSync(path.join(root,file),content); throw error; }
        const saved=read(name); json(res,200,{name,source:saved,revision:revision(saved)}); return true;
      }
      if (route === 'data') {
        const source=read('data.json');
        if(req.method==='GET') { json(res,200,{data:JSON.parse(source),revision:revision(source)}); return true; }
        if(req.method!=='POST') throw new Error('Méthode non autorisée.');
        if(payload.revision!==revision(source)) throw Object.assign(new Error('Les données ont changé. Rechargez avant de sauvegarder.'),{status:409});
        for(const key of ['formations','consultants','regulations','clients']) if(!Array.isArray(payload.data?.[key])) throw new Error('Collection invalide : '+key);
        const content=JSON.stringify(payload.data,null,2)+'\n';
        const oldJS=read('data.js');
        try { fs.writeFileSync(path.join(root,'data.json'),content); fs.writeFileSync(path.join(root,'data.js'),'window.MEPLUS_DATA = '+JSON.stringify(payload.data).replace(/</g,'\\u003c')+';\n'); }
        catch(error) { fs.writeFileSync(path.join(root,'data.json'),source); fs.writeFileSync(path.join(root,'data.js'),oldJS); throw error; }
        json(res,200,{revision:revision(content)}); return true;
      }
      if(route==='media' && req.method==='GET') {
        const images=[];
        function walk(dir) { for(const entry of fs.readdirSync(dir,{withFileTypes:true})) { const full=path.join(dir,entry.name); if(entry.isDirectory()) walk(full); else if(/\.(png|jpe?g|webp|gif)$/i.test(entry.name)) images.push({url:'/'+path.relative(root,full).split(path.sep).join('/'),name:entry.name,size:fs.statSync(full).size}); } }
        walk(path.join(root,'assets')); json(res,200,{images}); return true;
      }
      if(route==='media-delete' && req.method==='POST') {
        if(typeof payload.url !== 'string' || !payload.url.startsWith('/assets/')) throw new Error('Image non autorisée.');
        const target=path.resolve(root, payload.url.slice(1));
        const assetsRoot=path.resolve(root,'assets');
        if(target!==assetsRoot && !target.startsWith(assetsRoot+path.sep)) throw new Error('Image non autorisée.');
        if(!fs.existsSync(target) || !fs.statSync(target).isFile()) throw new Error('Image introuvable.');
        fs.unlinkSync(target);
        json(res,200,{deleted:payload.url}); return true;
      }
      if(route==='data-delete' && req.method==='POST') {
        const source=read('data.json');
        if(payload.revision!==revision(source)) throw Object.assign(new Error('Les données ont changé. Rechargez avant de supprimer.'),{status:409});
        const key=payload.collection;
        if(!['formations','consultants','regulations','clients'].includes(key)) throw new Error('Collection invalide.');
        const current=JSON.parse(source);
        if(!Array.isArray(current[key])) throw new Error('Collection invalide.');
        const index=Number(payload.index);
        if(!Number.isInteger(index) || index<0 || index>=current[key].length) throw new Error('Élément introuvable.');
        current[key].splice(index,1);
        const content=JSON.stringify(current,null,2)+'\n';
        const oldJS=read('data.js');
        try { fs.writeFileSync(path.join(root,'data.json'),content); fs.writeFileSync(path.join(root,'data.js'),'window.MEPLUS_DATA = '+JSON.stringify(current).replace(/</g,'\\u003c')+';\n'); }
        catch(error) { fs.writeFileSync(path.join(root,'data.json'),source); fs.writeFileSync(path.join(root,'data.js'),oldJS); throw error; }
        json(res,200,{data:current,revision:revision(content)}); return true;
      }
      if(route==='upload' && req.method==='POST') {
        if(typeof payload.base64!=='string' || !/^[A-Za-z0-9+/]*={0,2}$/.test(payload.base64)) throw new Error('Image invalide.');
        const buffer=Buffer.from(payload.base64,'base64');
        if(!buffer.length || buffer.length>10*1024*1024) throw new Error('Image limitée à 10 Mo.');
        let ext;
        if(buffer.subarray(0,8).equals(Buffer.from([137,80,78,71,13,10,26,10]))) ext='png';
        else if(buffer[0]===255&&buffer[1]===216&&buffer[2]===255) ext='jpg';
        else if(buffer.toString('ascii',0,4)==='RIFF'&&buffer.toString('ascii',8,12)==='WEBP') ext='webp';
        else if(/^GIF8[79]a/.test(buffer.toString('ascii',0,6))) ext='gif';
        else throw new Error('Formats acceptés : PNG, JPEG, WebP et GIF.');
        const dir=path.join(root,'assets/uploads'); fs.mkdirSync(dir,{recursive:true});
        const name=crypto.randomUUID()+'.'+ext; fs.writeFileSync(path.join(dir,name),buffer);
        json(res,201,{url:'/assets/uploads/'+name}); return true;
      }
      json(res,404,{error:'Fonction inconnue.'});
    } catch(error) { json(res,error.status||400,{error:error.message}); }
    return true;
  };
}
module.exports={createEditorAPI};
