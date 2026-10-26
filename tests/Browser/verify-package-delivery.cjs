const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {createHash} = require('node:crypto');
const {execFileSync} = require('node:child_process');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const {required} = require('../Integration/package-delivery-contract.cjs');
const {summarize, runtimeFailurePattern} = require('./audit-result.cjs');

(async () => {
    const base = process.env.JL_R1_BASE;
    const app = process.env.JL_R1_APP;
    const directory = process.env.JL_R1_ARTIFACTS;
    const driver = process.env.JL_R1_DRIVER;
    const feedRoot = process.env.JL_R1_FEED_ROOT;
    const feedApp = process.env.JL_R1_FEED_APP;
    const feedUrl = process.env.JL_R1_FEED_URL;
    assert.match(base, /^http:\/\/127\.0\.0\.1:\d+$/);
    assert.match(app, /^jl-r1-[a-z0-9]+-(mysqli|pgsql)-app$/);
    assert.match(feedApp, /^jl-r1-[a-z0-9]+-feed$/);
    assert(app.startsWith(feedApp.slice(0, -5) + '-'));
    assert.equal(feedUrl, 'http://' + feedApp + ':8080/update.xml');
    assert.equal(path.basename(feedRoot), 'feed');
    assert(path.basename(path.dirname(feedRoot)).startsWith('jl-r1-'));
    assert.equal(fs.realpathSync(feedRoot), path.resolve(feedRoot));
    assert(['mysqli', 'pgsql'].includes(driver));
    const targetZip = process.env.JL_PACKAGE_ZIP;
    const legacyZip = process.env.JL_LEGACY_PACKAGE_ZIP;
    for (const zip of [targetZip, legacyZip]) assert(path.isAbsolute(zip) && fs.statSync(zip).isFile());
    const native = (action, ...args) => {
        try {
            return JSON.parse(execFileSync('docker', ['exec', '-u', 'www-data', app, 'php', '/opt/r1-tests/package-delivery-state.php', action, ...args], {encoding: 'utf8', timeout: 60000, maxBuffer: 16 * 1024 * 1024}));
        } catch (error) {
            const detail = String(error.stdout || '') + String(error.stderr || '');
            fs.writeFileSync(path.join(directory, driver + '-delivery-native-error.txt'), detail);
            throw new Error('Native ' + action + ': ' + detail.slice(0, 1000));
        }
    };
    const configure = () => native('configure', feedUrl);
    const configuration = configure();
    assert.match(configuration.target, /^6\.2\.0-rc[0-9]+$/);
    const target = configuration.target;
    const bytes = fs.readFileSync(targetZip);
    const digest = createHash('sha256').update(bytes).digest('hex');
    fs.writeFileSync(path.join(feedRoot, 'package.zip'), bytes);
    const writeFeed = hash => {
        assert.match(hash, /^[a-f0-9]{64}$/);
        const xml = `<?xml version="1.0" encoding="utf-8"?><updates><update>
<name>JoomLeague delivery acceptance</name><description>Isolated package delivery</description>
<element>pkg_joomleague</element><type>package</type><client>site</client><version>${target}</version>
<downloads><downloadurl type="full" format="zip">http://${feedApp}:8080/package.zip</downloadurl></downloads>
<tags><tag>rc</tag></tags><targetplatform name="joomla" version="6\\.[12]"/>
<php_minimum>8.3</php_minimum><sha256>${hash}</sha256></update></updates>`;
        fs.writeFileSync(path.join(feedRoot, 'update.xml.tmp'), xml);
        fs.renameSync(path.join(feedRoot, 'update.xml.tmp'), path.join(feedRoot, 'update.xml'));
    };
    writeFeed('0'.repeat(64));
    const observations = [];
    const errors = [];
    const browser = await firefox.launch({executablePath: '/opt/playwright/firefox-1532/firefox/firefox', headless: true});
    try {
        const context = await browser.newContext({viewport: {width: 1440, height: 1000}});
        const page = await context.newPage();
        page.setDefaultTimeout(30000);
        page.on('pageerror', error => errors.push(error.name));
        const text = async () => page.locator('main').innerText();
        const record = async (id, response) => {
            const body = await text();
            observations.push({id, status: response.status(), evidenceCount: 2, fatal: runtimeFailurePattern.test(body), errors: [...errors], keys: body.match(/\b(?:COM_JOOMLEAGUE|PKG_JOOMLEAGUE)_[A-Z0-9_]+\b/g) || []});
            await page.screenshot({path: path.join(directory, driver + '-' + id + '.png'), fullPage: true});
        };
        await page.goto(base + '/administrator/', {waitUntil: 'networkidle'});
        await page.locator('#mod-login-username').fill('admin');
        await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.locator('#form-login button[type=submit]').click()]);
        assert.equal(await page.locator('#mod-login-username').count(), 0);
        const seeded = native('seed');
        assert.equal(seeded.seeded, true);
        const upload = async zip => {
            await page.goto(base + '/administrator/index.php?option=com_installer&view=install', {waitUntil: 'networkidle'});
            assert.equal(await page.locator('#install_package').count(), 1);
            const [response] = await Promise.all([
                page.waitForNavigation({waitUntil: 'networkidle', timeout: 120000}),
                page.locator('#install_package').setInputFiles(zip)
            ]);
            assert.equal(response.status(), 200);
            assert.match(await text(), /Installing package was successful|Installation of the package was successful/i);
            return response;
        };
        const beforeUpload = native('state');
        const legacyResponse = await upload(legacyZip);
        native('preserved');
        assert.deepEqual(native('state'), beforeUpload, 'Legacy browser reinstall altered stored data/code');
        await record('delivery-legacy-upload', legacyResponse);
        configure();
        const updateUrl = base + '/administrator/index.php?option=com_installer&view=update';
        const find = async () => {
            await page.goto(updateUrl, {waitUntil: 'networkidle'});
            const [response] = await Promise.all([page.waitForNavigation({waitUntil: 'networkidle', timeout: 60000}), page.locator('#toolbar-search button').click()]);
            assert.equal(response.status(), 200);
            return response;
        };
        const row = () => page.locator('#installer-update table tbody tr').filter({hasText: 'JoomLeague delivery acceptance'});
        const stable = await find();
        assert.equal(await row().count(), 0);
        // PHP's built-in server writes its request log to stderr.
        const {spawnSync} = require('node:child_process');
        const feedLogs = () => {
            const result = spawnSync('docker', ['logs', feedApp], {encoding: 'utf8', timeout: 10000});
            assert.equal(result.status, 0);
            return (result.stdout || '') + (result.stderr || '');
        };
        assert.match(feedLogs(), /200.*GET \/update\.xml/);
        await record('delivery-stable-hidden', stable);
        await page.goto(base + '/administrator/index.php?option=com_config&view=component&component=com_installer', {waitUntil: 'networkidle'});
        await page.locator('#jform_minimum_stability').selectOption('3');
        const [configured] = await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.locator('#toolbar-apply button').click()]);
        assert.equal(await page.locator('#jform_minimum_stability').inputValue(), '3');
        await record('delivery-rc-option', configured);
        const discovered = await find();
        assert.equal(await row().count(), 1, 'RC discovery missing: ' + await text());
        assert((await row().innerText()).includes(seeded.versions['pkg_joomleague:']));
        assert((await row().innerText()).includes(target));
        await record('delivery-rc-discovery', discovered);
        const update = async () => {
            await row().locator('[name="cid[]"]').check();
            const [response] = await Promise.all([page.waitForNavigation({waitUntil: 'networkidle', timeout: 120000}), page.locator('#toolbar-upload button').click()]);
            assert.equal(response.status(), 200);
            return response;
        };
        const badBefore = native('state');
        const rejected = await update();
        assert.match(await text(), /checksum.*(?:incorrect|wrong|failed)|(?:incorrect|wrong).*checksum/i);
        assert.deepEqual(native('state'), badBefore, 'Bad checksum installed or changed data/code');
        assert.match(feedLogs(), /200.*GET \/package\.zip/);
        await record('delivery-bad-checksum', rejected);
        writeFeed(digest);
        await find();
        assert.equal(await row().count(), 1);
        const successful = await update();
        assert.match(await text(), /Updating package was successful|Update of the package was successful/i);
        await record('delivery-update-success', successful);
        const result = native('verify');
        assert.equal(result.preserved, true);
        assert.deepEqual(result.versions, result.expected_versions);
        assert.equal(result.versions['pkg_joomleague:'], target);
        await record('delivery-version', successful);
        await record('delivery-preserved', successful);
        native('disconnect-feed');
        execFileSync('docker', ['exec', '-u', 'www-data', app, 'php', '/opt/r1-tests/package-acceptance.php', 'seed'], {encoding: 'utf8', timeout: 30000});
        const currentResponse = await upload(targetZip);
        try {
            execFileSync('docker', ['exec', '-u', 'www-data', app, 'php', '/opt/r1-tests/package-acceptance.php', 'verify'], {encoding: 'utf8', timeout: 30000});
        } catch (error) {
            const detail = String(error.stdout || '') + String(error.stderr || '');
            fs.writeFileSync(path.join(directory, driver + '-delivery-reinstall-error.txt'), detail);
            throw new Error('Current package preservation: ' + detail.slice(0, 2000));
        }
        await record('delivery-current-upload', currentResponse);
        assert(summarize(observations, required).passed);
        console.log('PASS: browser package uploads, actual legacy upgrade over HTTP, stable/RC discovery, checksum rejection and preserved data.');
    } finally {
        await browser.close();
        for (const id of required) if (!observations.some(item => item.id === id)) observations.push({id, status: 0, outcome: 'FAIL', evidenceCount: 0});
        fs.writeFileSync(path.join(directory, driver + '-delivery-http.json'), JSON.stringify({observations, ...summarize(observations, required)}, null, 2));
    }
})().catch(error => {console.error('FAIL: package delivery ' + error.name + ' ' + String(error.message).slice(0, 1200)); process.exitCode = 1;});
