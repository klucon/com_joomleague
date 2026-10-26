const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');

function inspectPackageLanguages(archive) {
    assert(path.isAbsolute(archive) && fs.statSync(archive).isFile(), 'Existing absolute package ZIP required');
    const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'jl-language-inventory-'));
    const allowed = new Set(['en-GB']);
    const report = {archives: 0, files: 0, tags: [...allowed]};
    const command = (name, args, options = {}) => execFileSync(name, args, {maxBuffer: 32 * 1024 * 1024, timeout: 30000, ...options});
    const read = (zip, member) => command('unzip', ['-p', zip, member]);
    const members = zip => {
        command('unzip', ['-tq', zip]);
        const names = command('unzip', ['-Z1', zip], {encoding: 'utf8'}).trim().split('\n');
        assert.equal(new Set(names).size, names.length, 'Duplicate archive members');
        assert(names.every(name => /^[A-Za-z0-9_./-]+$/.test(name) && !name.startsWith('/') && !name.split('/').includes('..')), 'Unsafe archive path');
        return names;
    };
    const xpath = (xml, expression) => command('xmllint', ['--nonet', '--xpath', expression, '-'], {input: xml, encoding: 'utf8'}).trim();
    const check = (zip, expectedTags = [...allowed]) => {
        const names = members(zip);
        const manifests = names.filter(name => !name.includes('/') && name.endsWith('.xml'));
        assert.equal(manifests.length, 1, 'Exactly one root manifest required');
        const xml = read(zip, manifests[0]);
        assert(!/<!DOCTYPE|<!ENTITY/i.test(xml.toString()), 'Manifest declarations are not allowed');
        const nodes = '(/extension/languages/language | /extension/administration/languages/language)';
        const count = Number(xpath(xml, `count(${nodes})`));
        assert(Number.isSafeInteger(count) && count > 0, 'Language declarations required');
        const expected = [];
        const tags = new Set();
        for (let i = 1; i <= count; i++) {
            const node = `${nodes}[${i}]`;
            const tag = xpath(xml, `string(${node}/@tag)`);
            const folder = xpath(xml, `string(${node}/../@folder)`);
            const relative = xpath(xml, `string(${node})`);
            assert(allowed.has(tag), 'Unbundled manifest language: ' + tag);
            const member = [folder, relative].filter(Boolean).join('/');
            assert(/^[A-Za-z0-9_./-]+\.ini$/.test(member) && !member.split('/').includes('..') && !member.startsWith('/'), 'Unsafe language declaration');
            assert(member.includes('language/' + tag + '/'), 'Tag/path mismatch');
            assert(names.includes(member), 'Declared language missing: ' + member);
            assert(read(zip, member).length > 0, 'Empty language file: ' + member);
            expected.push(member);
            tags.add(tag);
        }
        assert.deepEqual([...tags].sort(), expectedTags.sort(), 'Bundled language set differs');
        assert.equal(new Set(expected).size, expected.length, 'Duplicate language declaration');
        for (const name of names) {
            const match = name.match(/(?:^|\/)language\/([^/]+)(?:\/|$)/);
            if (match) assert(allowed.has(match[1]), 'Separate language leaked into ZIP: ' + name);
        }
        const actual = names.filter(name => /(?:^|\/)language\/[^/]+\/.+\.ini$/.test(name));
        assert.deepEqual(actual.sort(), expected.sort(), 'Undeclared or missing bundled language payload');
        report.archives++;
        report.files += expected.length;
        return {xml, names};
    };
    try {
        const outer = check(archive);
        const count = Number(xpath(outer.xml, 'count(/extension/files/file)'));
        assert.equal(count, 23, 'Full package must own 23 child extensions');
        const children = [];
        for (let i = 1; i <= count; i++) {
            const name = xpath(outer.xml, `string(/extension/files/file[${i}])`);
            assert(/^[a-z][a-z0-9_]*-[0-9A-Za-z.-]+\.zip$/.test(name), 'Unsafe child archive path');
            assert(outer.names.includes(name), 'Child archive missing');
            children.push(name);
            const target = path.join(temp, name);
            fs.writeFileSync(target, read(archive, name));
            check(target);
        }
        const dependency = 'dependencies/com_kluconcountries.zip';
        assert(outer.names.includes(dependency), 'Countries dependency missing');
        const target = path.join(temp, 'com_kluconcountries.zip');
        fs.writeFileSync(target, read(archive, dependency));
        check(target, ['en-GB']);
        children.push(dependency);
        assert.equal(new Set(children).size, 24, 'Duplicate archive declaration');
        assert.deepEqual(outer.names.filter(name => name.endsWith('.zip')).sort(), children.sort(), 'Undeclared child archive');
        return report;
    } finally {
        fs.rmSync(temp, {recursive: true, force: true});
    }
}

module.exports = {inspectPackageLanguages};
if (require.main === module) {
    try {
        const report = inspectPackageLanguages(process.argv[2]);
        console.log(`PASS: ${report.archives} ZIP manifests and ${report.files} bundled INI files; separate translations excluded.`);
    } catch (error) {
        console.error('FAIL: package language inventory: ' + error.message);
        process.exitCode = 1;
    }
}
