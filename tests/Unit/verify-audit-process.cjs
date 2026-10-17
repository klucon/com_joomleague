const assert=require('node:assert/strict');
const fs=require('node:fs');
const os=require('node:os');
const path=require('node:path');
const {spawnSync}=require('node:child_process');
const {evaluateProcess}=require('../Integration/audit-process.cjs');
const directory=fs.mkdtempSync(path.join(os.tmpdir(),'jl-audit-report-'));
const script=path.resolve(__dirname,'../Integration/AuditReport.php');
const file=path.join(directory,'report.json');
const required=['one','two'];
const good={schemaVersion:1,finished:true,passed:true,cases:required.map(id=>({id,status:'PASS',evidenceCount:1}))};
const processOk={status:0,stdout:'PASS: complete',stderr:''};
assert(evaluateProcess(processOk,good,required).passed);
for(const [result,report]of[
    [{...processOk,status:1},good],[{...processOk,signal:'SIGTERM'},good],
    [{...processOk,stdout:'Fatal error: memory exhausted'},good],
    [{...processOk,stderr:'Allowed memory size exhausted'},good],
    [{...processOk,stdout:'SKIP: absent fixture'},good],[{...processOk,stdout:'FAIL: hidden failure'},good],
    [processOk,null],[processOk,{...good,finished:false}],
    [processOk,{...good,cases:[good.cases[0]]}],
    [processOk,{...good,cases:[good.cases[0],good.cases[0]]}],
    [processOk,{...good,cases:[null,null]}],
    [processOk,{...good,cases:good.cases.map(c=>({...c,status:'SKIP'}))}],
    [processOk,{...good,cases:good.cases.map(c=>({...c,evidenceCount:0}))}],
])assert.equal(evaluateProcess(result,report,required).passed,false);
const secret='sensitive-email@example.invalid SQL-password-secret';
try{
    for(const [name,body,expected]of[
        ['valid',"$r->run('one',fn()=>1);$r->run('two',fn()=>2);exit($r->finish()?0:1);",true],
        ['missing',"$r->run('one',fn()=>1);$r->finish();exit(0);",false],
        ['empty',"$r->run('one',fn()=>0);$r->run('two',fn()=>1);$r->finish();exit(0);",false],
        ['error',`$r->run('one',function(){throw new RuntimeException('${secret}');});$r->run('two',fn()=>1);$r->finish();exit(0);`,false],
        ['early-exit',"$r->run('one',function(){exit(0);});",false],
        ['memory',"ini_set('memory_limit','16M');$r->run('one',function(){return strlen(str_repeat('x',64*1024*1024));});",false],
    ]) {
        if(fs.existsSync(file))fs.unlinkSync(file);
        const prefix=`require ${JSON.stringify(script)};$r=new AuditReport(${JSON.stringify(file)},['one','two']);`;
        const result=spawnSync(process.env.PHP_BINARY||'php',['-d','display_errors=1','-r',prefix+body],{encoding:'utf8',timeout:15000});
        const report=fs.existsSync(file)?JSON.parse(fs.readFileSync(file,'utf8')):null;
        assert.equal(evaluateProcess(result,report,required).passed,expected,name);
        if(report)assert(!JSON.stringify(report).includes(secret),'Sensitive exception text persisted');
        if(name==='memory')assert.equal(evaluateProcess({...result,status:0},report,required).passed,false,'Zero exit masked memory exhaustion');
    }
}finally{for(const entry of fs.readdirSync(directory))fs.unlinkSync(path.join(directory,entry));fs.rmdirSync(directory);}
console.log('PASS: real PHP reports reject missing/empty cases, exceptions, early success exit and memory exhaustion without retaining exception payloads');
