const assert = require('node:assert/strict');
const {required} = require('../Integration/reset-browser-contract.cjs');
const {summarize} = require('../Browser/audit-result.cjs');
assert.equal(required.length, 18);
assert.equal(new Set(required).size, 18);
const passed = required.map(id => ({id, status: 200, expectedStatus: 200, outcome: 'PASS', evidenceCount: 2}));
assert(summarize(passed, required).passed);
for (let i = 0; i < required.length; i++) {
    assert(!summarize(passed.filter((_, index) => index !== i), required).passed);
    for (const change of [{skipped: true}, {outcome: 'FAIL'}, {status: 403}, {evidenceCount: 0}, {fatal: true}, {keys: ['COM_JOOMLEAGUE_EXAMPLE']}, {errors: ['TypeError']}]) {
        assert(!summarize(passed.map((row, index) => index === i ? {...row, ...change} : row), required).passed);
    }
}
assert(!summarize([...passed, passed[0]], required).passed);
console.log('PASS: all eighteen browser reset cases mandatory; failed, missing, duplicate, skipped and empty evidence rejected.');
