const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const root = path.resolve(__dirname, '../..');
const archive = path.resolve(process.argv[2] || '');
if (!process.argv[2] || !fs.existsSync(archive)) throw new Error('Existing package archive required.');
const scratch = fs.mkdtempSync(path.join(os.tmpdir(), 'jl-package-audit-'));
const issues = [];
let files = 0;
let extensions = 0;
const list = zip => execFileSync('unzip', ['-Z1', zip], {encoding: 'utf8'}).trim().split('\n').filter(name => !name.endsWith('/'));
const read = (zip, name) => execFileSync('unzip', ['-p', zip, name], {maxBuffer: 32 * 1024 * 1024});
const walk = directory => fs.readdirSync(directory, {withFileTypes: true}).flatMap(entry => entry.isDirectory() ? walk(path.join(directory, entry.name)) : [path.join(directory, entry.name)]);
function compare(zip, expected) {
    // Other locales are distributed as independent language packages.
    for (const name of expected.keys()) {
        const locale = name.match(/^(?:(?:admin|site)\/)?language\/([a-z]{2,3}-[A-Z]{2})\//);
        if (locale && !['en-GB', 'cs-CZ'].includes(locale[1])) expected.delete(name);
    }
    const actual = new Set(list(zip));
    for (const name of actual) {
        if (name.startsWith('/') || name.split('/').includes('..') || name.includes('\\')) issues.push('Unsafe archive path: ' + name);
        if (!expected.has(name)) issues.push('Unexpected package file: ' + path.basename(zip) + '/' + name);
    }
    for (const [name, source] of expected) {
        if (!actual.has(name)) issues.push('Missing package file: ' + path.basename(zip) + '/' + name);
        else if (!read(zip, name).equals(fs.readFileSync(source))) issues.push('Source differs: ' + path.basename(zip) + '/' + name);
        files++;
    }
}
try {
    for (const name of list(archive).filter(name => name.endsWith('.zip'))) {
        const zip = path.join(scratch, 'extension.zip');
        fs.writeFileSync(zip, read(archive, name));
        const expected = new Map();
        if (name.startsWith('com_joomleague-')) {
            for (const [prefix, directory] of [['admin', 'administrator/components/com_joomleague'], ['site', 'components/com_joomleague'], ['media', 'media/com_joomleague']]) {
                const base = path.join(root, directory);
                for (const file of walk(base)) expected.set(prefix + '/' + path.relative(base, file), file);
            }
            for (const file of ['joomleague.xml', 'script.php']) expected.set(file, path.join(root, 'administrator/components/com_joomleague', file));
        } else if (name === 'dependencies/com_kluconcountries.zip') {
            const base = path.resolve(root, '../com_kluconcountries');
            for (const directory of ['admin', 'media']) for (const file of walk(path.join(base, directory))) {
                const relative = path.relative(base, file);
                if (!relative.includes('/language/cs-CZ/')) expected.set(relative, file);
            }
            for (const file of ['cz.json', 'world.json', 'continents.json', 'continent-facts.json', 'flags-manifest.json', 'countries.json', 'iso-codes-copyright.txt', 'iso-codes-LGPL-2.1.txt']) expected.set('admin/data/' + file, path.join(base, 'data', file));
            for (const file of ['kluconcountries.xml', 'script.php', 'THIRD-PARTY-NOTICES.md']) expected.set(file, path.join(base, file));
        } else if (name.startsWith('plg_kluconcountries_joomleague-')) {
            const base = path.resolve(root, '../plg_kluconcountries_joomleague');
            expected.set('joomleague.xml', path.join(base, 'joomleague.xml'));
            for (const directory of ['services', 'src', 'language']) for (const file of walk(path.join(base, directory))) expected.set(path.relative(base, file), file);
        } else {
            const module = name.match(/^(mod_joomleague_[a-z_]+)-/);
            const plugin = name.match(/^plg_([a-z]+)_joomleague-/);
            const directory = module ? path.join(root, 'modules', module[1]) : plugin ? path.join(root, 'plugins', plugin[1], 'joomleague') : null;
            if (!directory) throw new Error('Unknown extension archive: ' + name);
            for (const file of walk(directory)) expected.set(path.relative(directory, file), file);
        }
        compare(zip, expected);
        extensions++;
        fs.unlinkSync(zip);
    }
    if (extensions !== 24) issues.push('Expected 23 owned extensions and the standalone Countries dependency; got ' + extensions);
    console.log(JSON.stringify({extensions, filesCompared: files, issues, scope: 'nested extension payloads vs source, including path safety; not fresh install acceptance'}, null, 2));
    if (issues.length) process.exitCode = 1;
} finally {
    const owned = path.join(scratch, 'extension.zip');
    if (fs.existsSync(owned)) fs.unlinkSync(owned);
    fs.rmdirSync(scratch);
}
