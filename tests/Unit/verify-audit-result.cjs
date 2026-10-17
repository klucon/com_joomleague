const assert = require('node:assert/strict');
const {runtimeFailurePattern, summarize} = require('../Browser/audit-result.cjs');
const good = {status:200, keys:[], errors:[], overflow:false, fatal:false};
assert.equal(summarize([good]).passed, true);
assert.equal(summarize([]).passed, false);
for (const item of [{...good,status:500}, {...good,status:undefined}, {...good,fatal:true}, {...good,keys:['COM_JOOMLEAGUE_TEST']}, {...good,errors:['runtime']}, {...good,overflow:true}, {skipped:'Missing fixture'}]) {
    assert.equal(summarize([good,item]).passed, false);
}
for (const text of ['Allowed memory size of 536870912 bytes exhausted', 'Fatal error: failed', 'Maximum execution time of 30 seconds exceeded', 'Uncaught Error: missing', 'SQLSTATE[42S02]', 'Warning: missing', 'Notice: undefined']) assert.equal(runtimeFailurePattern.test(text), true);
assert.equal(runtimeFailurePattern.test('Historie klubu'), false);
console.log('PASS: audit rejects fatal output, bad HTTP, runtime errors, skipped mandatory cases and empty coverage.');
const named={...good,id:'required',evidenceCount:1};
assert.equal(summarize([named],['required']).passed,true);
for(const items of [[named,named],[{...named,evidenceCount:0}],[{...named,id:'unexpected'}],[{...named,status:303}]])assert.equal(summarize(items,['required']).passed,false);
assert.equal(summarize([named],['required','missing']).passed,false);
assert.equal(summarize([{...named,status:404,expectedStatus:404}],['required']).passed,true);
