const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const base=path.resolve(__dirname,'../../media/com_joomleague');
const manifest=JSON.parse(fs.readFileSync(path.join(base,'joomla.asset.json'),'utf8'));
const names=new Set();
for(const asset of manifest.assets){
 assert(!names.has(asset.name),'Duplicate asset registration');names.add(asset.name);
 assert.match(asset.uri,/^com_joomleague\//);
 const folder=asset.type==='script'?'js':asset.type==='style'?'css':null;assert(folder);
 const relative=asset.uri.slice('com_joomleague/'.length);
 assert(!relative.split('/').includes('..'));
 assert(fs.statSync(path.join(base,folder,relative)).isFile(),'Unresolvable Joomla media URI: '+asset.uri);
}
console.log('PASS: '+names.size+' Joomla asset URIs resolve to packaged source files');
