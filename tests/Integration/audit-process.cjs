const {runtimeFailurePattern}=require('../Browser/audit-result.cjs');
function evaluateProcess(result, report, required) {
    const reasons=[];
    if(result.error || result.signal || result.status!==0)reasons.push('process-failure');
    if(runtimeFailurePattern.test(String(result.stdout||'')+'\n'+String(result.stderr||'')))reasons.push('runtime-output');
    if(/(?:^|\n)\s*(?:SKIP|FAIL|NOT_RUN|RUNNING)\b/i.test(String(result.stdout||'')+'\n'+String(result.stderr||'')))reasons.push('explicit-failure-output');
    if(!Array.isArray(required)||required.length===0||new Set(required).size!==required.length)return {passed:false,reasons:[...reasons,'invalid-contract']};
    if(!report || report.schemaVersion!==1 || report.finished!==true || report.passed!==true || !Array.isArray(report.cases))reasons.push('invalid-report');
    const cases=Array.isArray(report?.cases)?report.cases:[];
    const ids=cases.map(c=>c?.id);
    if(new Set(ids).size!==ids.length || cases.length!==required.length || required.some(id=>!ids.includes(id)) || ids.some(id=>!required.includes(id)))reasons.push('missing-or-duplicate-coverage');
    if(cases.some(c=>c?.status!=='PASS'||!Number.isSafeInteger(c?.evidenceCount)||c.evidenceCount<1))reasons.push('failed-skipped-or-empty-case');
    return {passed:reasons.length===0,reasons};
}
module.exports={evaluateProcess};
