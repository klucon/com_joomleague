const runtimeFailurePattern = /Fatal error|Parse error|Allowed memory size|Maximum execution time|Uncaught\s+(?:\w*Error|\w*Exception)|Call Stack|SQLSTATE|Warning:|Notice:|Deprecated:/i;

function summarize(observations, required = []) {
    const skipped = observations.filter(item => item.skipped);
    const issues = observations.filter(item => !item.skipped && (
        !Number.isInteger(item.status) || item.status !== (item.expectedStatus ?? 200) ||
        item.fatal || item.keys?.length || item.errors?.length || item.overflow
        || (item.outcome !== undefined && item.outcome !== 'PASS')
    ));
    const ids=observations.map(item=>item.id);
    const missing=required.filter(id=>!ids.includes(id));
    const duplicate=required.length>0 && new Set(ids).size!==ids.length;
    const invalid=required.length>0 && (observations.some(item=>!required.includes(item.id)||!Number.isSafeInteger(item.evidenceCount)||item.evidenceCount<1)||new Set(required).size!==required.length);
    return {visited: observations.length, issues, skipped, missing, passed: observations.length > 0 && issues.length === 0 && skipped.length === 0 && missing.length===0 && !duplicate && !invalid};
}

module.exports = {runtimeFailurePattern, summarize};
