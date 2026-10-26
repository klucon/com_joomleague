const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {inspectPackageLanguages} = require('../Architecture/verify-package-languages.cjs');
const archive = process.argv[2];
assert(path.isAbsolute(archive) && fs.statSync(archive).isFile());
assert.deepEqual(inspectPackageLanguages(archive), {archives: 25, files: 59, tags: ['en-GB']});
const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'jl-language-mutations-'));
const command = (name, args, options = {}) => execFileSync(name, args, {timeout: 30000, maxBuffer: 32 * 1024 * 1024, stdio: ['ignore', 'pipe', 'pipe'], ...options});
const add = (root, zip, member, data) => {
    const file = path.join(root, member);
    fs.mkdirSync(path.dirname(file), {recursive: true});
    fs.writeFileSync(file, data);
    command('zip', ['-q', zip, member], {cwd: root});
};
try {
    for (const [index, mutation] of ['outer-language', 'inner-language', 'missing-file', 'undeclared-file', 'bad-tag', 'unsafe-path'].entries()) {
        const root = path.join(temp, String(index));
        fs.mkdirSync(root);
        const target = path.join(root, 'package.zip');
        fs.copyFileSync(archive, target);
        if (mutation === 'outer-language') {
            add(root, target, 'language/cs-CZ/pkg_joomleague.ini', 'PKG_JOOMLEAGUE="Wrongly bundled"\n');
        } else {
            const name = command('unzip', ['-Z1', target], {encoding: 'utf8'}).trim().split('\n').find(member => /^com_joomleague-[^/]+\.zip$/.test(member));
            assert(name);
            const child = path.join(root, name);
            fs.writeFileSync(child, command('unzip', ['-p', target, name]));
            if (mutation === 'inner-language') add(root, child, 'admin/language/cs-CZ/com_joomleague.ini', 'COM_JOOMLEAGUE="Separate translation"\n');
            if (mutation === 'missing-file') command('zip', ['-dq', child, 'admin/language/en-GB/com_joomleague.sys.ini']);
            if (mutation === 'undeclared-file') add(root, child, 'admin/language/en-GB/not_declared.ini', 'COM_JOOMLEAGUE="Undeclared file"\n');
            if (mutation === 'bad-tag' || mutation === 'unsafe-path') {
                const xml = command('unzip', ['-p', child, 'joomleague.xml'], {encoding: 'utf8'});
                const changed = mutation === 'bad-tag'
                    ? xml.replace('tag="en-GB"', 'tag="fr-FR"')
                    : xml.replace('language/en-GB/com_joomleague.ini', '../language/en-GB/com_joomleague.ini');
                assert.notEqual(changed, xml);
                add(root, child, 'joomleague.xml', changed);
            }
            command('zip', ['-q', target, name], {cwd: root});
        }
        assert.throws(() => inspectPackageLanguages(target), undefined, mutation + ' accepted');
    }
    console.log('PASS: real package inventory; six malformed outer/nested language payloads rejected.');
} finally {
    fs.rmSync(temp, {recursive: true, force: true});
}
