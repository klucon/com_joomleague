const assert = require('node:assert/strict');
const {required} = require('../Integration/package-delivery-contract.cjs');
const {summarize} = require('../Browser/audit-result.cjs');
assert.equal(required.length, 9);
assert.equal(new Set(required).size, 9);
const passed = required.map(id => ({id, status: 200, evidenceCount: 2}));
assert(summarize(passed, required).passed);
for (let i = 0; i < required.length; i++) {
    assert(!summarize(passed.filter((_, index) => index !== i), required).passed);
    for (const change of [{skipped: true}, {outcome: 'FAIL'}, {status: 500}, {evidenceCount: 0}, {fatal: true}, {errors: ['TypeError']}, {keys: ['COM_JOOMLEAGUE_TEST']}]) {
        assert(!summarize(passed.map((row, index) => index === i ? {...row, ...change} : row), required).passed);
    }
}
assert(!summarize([...passed, passed[0]], required).passed);
console.log('PASS: all nine package delivery cases mandatory; missing, failed and empty evidence rejected.');
