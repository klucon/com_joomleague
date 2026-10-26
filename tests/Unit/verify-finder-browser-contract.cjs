const assert=require('node:assert/strict');
const {browserRequired:required}=require('../Integration/execution-plugin-contract.cjs');
const {summarize}=require('../Browser/audit-result.cjs');
assert.equal(required.length,8);assert.equal(new Set(required).size,8);
const passed=required.map(id=>({id,status:200,outcome:'PASS',evidenceCount:2}));
assert(summarize(passed,required).passed);
for(let i=0;i<required.length;i++){
 assert(!summarize(passed.filter((_,index)=>index!==i),required).passed);
 for(const change of [{skipped:true},{status:500},{outcome:'FAIL'},{evidenceCount:0},{errors:['Error']},{keys:['PLG_FINDER_JOOMLEAGUE_TEST']}]){
  const bad=passed.map((row,index)=>index===i?{...row,...change}:row);assert(!summarize(bad,required).passed);
 }
}
assert(!summarize([...passed,passed[0]],required).passed);
console.log('PASS: all eight Finder browser observations mandatory; missing/duplicate/skipped/failed/empty/error/untranslated cases rejected.');
