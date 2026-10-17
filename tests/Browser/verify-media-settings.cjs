const fs = require('node:fs');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const {randomBytes} = require('node:crypto');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const container = 'local-joomla62-app';
const key = process.env.MEDIA_FIXTURE_KEY || randomBytes(16).toString('hex');
const fixture = '/tmp/jl-media-fixture.php';
const run = action => execFileSync('docker', ['exec', '-u', 'www-data', container, 'php', fixture, action, key], {encoding:'utf8'});
execFileSync('docker', ['cp', __dirname + '/../Integration/media-fixture.php', container + ':' + fixture]);
const data = process.env.MEDIA_FIXTURE_KEY
    ? JSON.parse(execFileSync('docker', ['exec', container, 'cat', '/tmp/jl-media-' + key + '.json'], {encoding:'utf8'}))
    : JSON.parse(run('setup'));
const url = 'http://joomla62.local';
const configUrl = url + '/administrator/index.php?option=com_config&view=component&component=com_joomleague';
const fields = ['images_base_path', 'placeholder_club_logo', 'placeholder_person_picture', 'placeholder_team_picture', 'placeholder_venue_picture'];
(async () => {
    let browser, admin, original;
    const errors = [];
    async function values() {
        const result = {};
        for (const field of fields) result[field] = await admin.locator('#jform_' + field).inputValue();
        return result;
    }
    async function save(settings) {
        await admin.goto(configUrl);
        for (const [field, value] of Object.entries(settings)) {
            await admin.locator('#jform_' + field).evaluate((input, next) => {
                input.value = next;
                input.dispatchEvent(new Event('change', {bubbles:true}));
            }, value);
        }
        await Promise.all([admin.waitForNavigation(), admin.evaluate(() => Joomla.submitbutton('component.apply'))]);
        assert.equal(await admin.locator('#system-message-container [type="danger"], #system-message-container .alert-danger').count(), 0, 'Configuration validation failed.');
        await admin.goto(configUrl);
        const saved = await values();
        for (const [field, value] of Object.entries(settings)) assert.equal(saved[field].split('#joomlaImage://')[0], value.split('#joomlaImage://')[0], 'Not saved: ' + field);
    }
    try {
        browser = await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox'});
        admin = await browser.newPage({viewport:{width:1440,height:1000}});
        admin.on('pageerror', e => errors.push(e.message));
        await admin.goto(url + '/administrator/');
        await admin.locator('#mod-login-username').fill('admin');
        await admin.locator('#mod-login-password').fill(fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md','utf8').match(/^Password: (.+)$/m)[1]);
        await admin.locator('button[type=submit]').click();
        await admin.waitForURL('**/administrator/index.php');
        await admin.goto(configUrl);
        original = await values();
        const next = {images_base_path:data.base};
        for (const [kind, suffix] of Object.entries({club:'logo', person:'picture', team:'picture', venue:'picture'})) next['placeholder_' + kind + '_' + suffix] = data.placeholders[kind];
        await save(next);
        for (const directory of ['clubs','teams/logos','teams/photos','persons','venues','placeholders','projects']) {
            execFileSync('docker', ['exec', container, 'test', '-d', '/var/www/html/' + data.base + '/' + directory]);
        }
        for (const [view, field, suffix] of [['club','logo','clubs'],['team','logo','teams/logos'],['person','picture','persons'],['venue','picture','venues'],['project','picture','projects']]) {
            await admin.goto(url + '/administrator/index.php?option=com_joomleague&view=' + view + '&layout=edit&id=' + data[view]);
            const media = admin.locator('#jform_' + field).locator('xpath=ancestor::joomla-field-media');
            const html = await media.evaluate(node => node.outerHTML);
            assert.ok(decodeURIComponent(html).includes('local-images:/' + data.base.slice(7) + '/' + suffix), 'Wrong media picker directory: ' + view + ' ' + html);
        }
        console.log(run('modules').trim());
        for (const viewport of [{width:1440,height:1000}, {width:390,height:844}]) {
            const page = await browser.newPage({viewport});
            page.on('pageerror', e => errors.push(e.message));
            for (const kind of ['club','team','person','venue']) {
                await page.goto(url + '/index.php?option=com_joomleague&view=' + kind + '&' + kind + '_id=' + data[kind]);
                const image = page.locator('.com-joomleague-' + kind + ' img').first();
                assert.equal(await image.getAttribute('src'), '/' + data.placeholders[kind], 'Wrong public fallback: ' + kind);
                await image.scrollIntoViewIfNeeded();
                await page.waitForFunction(src => Array.from(document.images).some(image => image.getAttribute('src') === src && image.complete && image.naturalWidth > 0), '/' + data.placeholders[kind]);
            }
            await page.goto(url + '/index.php?option=com_joomleague&view=participant&project_id=' + data.project + '&entry_id=' + data.entry);
            assert.equal(await page.locator('.com-joomleague-participant img').first().getAttribute('src'), '/' + data.placeholders.team);
            assert.ok(await page.locator('.com-joomleague-participant img[src="/' + data.placeholders.person + '"]').count() > 0);
            await page.goto(url + '/index.php?option=com_joomleague&view=participants&project_id=' + data.project);
            assert.equal(await page.locator('.com-joomleague-participants img[src="/' + data.placeholders.team + '"]').count(), 2);
            await page.close();
        }
        assert.deepEqual(errors, []);
        console.log('PASS: native configuration save, custom media folders, admin pickers, all public placeholders and participants on desktop/mobile.');
    } finally {
        try { if (original) await save(original); } finally {
            if (browser) await browser.close();
            run('cleanup');
        }
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
