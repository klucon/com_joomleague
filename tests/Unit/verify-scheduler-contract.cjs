const assert = require('node:assert/strict');
const {required} = require('../Integration/scheduler-contract.cjs');
const {summarize} = require('../Browser/audit-result.cjs');
assert.equal(required.length, 10);
assert.equal(new Set(required).size, 10);
const passed = required.map(id => ({id, status: 200, outcome: 'PASS', evidenceCount: 1}));
assert(summarize(passed, required).passed);
for (let i = 0; i < required.length; i++) {
    assert(!summarize(passed.filter((_, index) => index !== i), required).passed);
    for (const change of [{skipped: true}, {outcome: 'FAIL'}, {status: 500}, {evidenceCount: 0}]) {
        assert(!summarize(passed.map((row, index) => index === i ? {...row, ...change} : row), required).passed);
    }
}
assert(!summarize([...passed, passed[0]], required).passed);
console.log('PASS: all ten Scheduler lifecycle observations required; incomplete and failed evidence rejected.');
