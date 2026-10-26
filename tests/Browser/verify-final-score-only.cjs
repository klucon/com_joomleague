const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
(async () => {
    const base = process.env.JL_R1_BASE, app = process.env.JL_R1_APP;
    assert.match(base, /^http:\/\/127\.0\.0\.1:\d+$/);
    assert.match(app, /^jl-r1-[a-z0-9]+-(mysqli|pgsql)-app$/);
    const fixture = JSON.parse(process.env.JL_R1_FIXTURE), match = fixture.projects.a.records.Match;
    const native = (action = 'state') => JSON.parse(execFileSync('docker', ['exec', app, 'php', '/opt/r1-tests/football-result-state.php', action], {encoding: 'utf8'}));
    const result = () => {
        const state = native();
        const segments = state.match_score_segment.filter(row => Number(row.match_id) === match);
        const root = segments.find(row => row.level_code === 'result');
        const values = state.match_score_value.filter(row => Number(row.segment_id) === Number(root?.id)).sort((a, b) => Number(a.participant_id) - Number(b.participant_id));
        return {segments, scores: values.map(row => Number(row.numeric_value)), stored: state.match_result.find(row => Number(row.match_id) === match)};
    };
    const browser = await firefox.launch({executablePath: '/opt/playwright/firefox-1532/firefox/firefox', headless: true});
    const cases = [];
    try {
        const context = await browser.newContext({viewport: {width: 1440, height: 1000}});
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const admin = base + '/administrator/index.php';
        await page.goto(base + '/administrator/');
        await page.locator('#mod-login-username').fill('r1.editor.a');
        await page.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.locator('#form-login button[type=submit]').click()]);
        const open = async () => {
            assert.equal((await page.goto(admin + '?option=com_joomleague&view=matchresult&match_id=' + match, {waitUntil: 'networkidle'})).status(), 200);
            await page.locator('#jform_status_code').selectOption('final');
            await page.locator('#jform_outcome_code').selectOption('completed');
        };
        const inputs = () => page.locator('input[name^="jform[segments][0][values]"][name$="[numeric_value]"]');
        const save = async () => {
            await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.locator('#toolbar-apply button').click()]);
            assert.doesNotMatch(await page.locator('#system-message-container').innerText(), /danger|Error|Invalid|required/i);
        };
        const list = admin + '?option=com_joomleague&view=matches&round_id=' + fixture.projects.a.round;
        await page.goto(list,{waitUntil:'networkidle'});
        const row = page.locator('[data-match-id="'+match+'"]');
        const quick = row.locator('[data-quick-result]');
        assert.equal(await quick.count(),1,'Quick score missing for football: '+(await page.locator('body').innerText()).slice(-4000));
        const participantIds = await quick.locator('[data-quick-participant]').evaluateAll(els=>els.map(el=>el.dataset.quickParticipant));
        const initialRevision = await quick.getAttribute('data-revision');
        const quickPost = async (changes={},request=context.request) => {
            const token=await page.locator('#matchList').getAttribute('data-csrf-token');
            const data={option:'com_joomleague',task:'matchresult.quickSave',format:'json',match_id:String(match),round_id:String(fixture.projects.a.round),revision:await quick.getAttribute('data-revision'),[token]:'1',[`scores[${participantIds[0]}]`]:'3',[`scores[${participantIds[1]}]`]:'1',...changes};
            if(data.noCsrf){delete data[token];delete data.noCsrf;}
            const response=await request.post(admin,{form:data,maxRedirects:0});
            try{const result=await response.json();return {...result,success:result.success===true};}catch{return {success:false};}
        };
        for (const changes of [{noCsrf:true},{match_id:String(fixture.projects.b.records.Match)},{round_id:String(fixture.projects.b.round)},{[`scores[${participantIds[0]}]`]:'-1'},{[`scores[${participantIds[0]}]`]:'1.5'},{'scores[999999]':'2'}]) {
            const before=native();
            assert.equal((await quickPost(changes)).success,false,'Accepted invalid quick score: '+JSON.stringify(changes));
            assert.deepEqual(native(),before,'Rejected quick score altered stored result');
        }
        const anonymous=await browser.newContext();
        const beforeAnonymous=native();
        assert.equal((await quickPost({},anonymous.request)).success,false);
        assert.deepEqual(native(),beforeAnonymous);
        await anonymous.close();
        cases.push('quick-security-rejections');
        native('quick-checkout');
        const lockedBefore=native();
        assert.equal((await quickPost()).success,false);
        assert.deepEqual(native(),lockedBefore);
        native('quick-checkin');
        cases.push('quick-checkout-rejection');
        for(const values of [['3','1'],['0','2']]) {
            await quick.locator('input').nth(0).fill(values[0]);
            await quick.locator('input').nth(1).fill(values[1]);
            await quick.locator('[data-quick-save]').click();
            await quick.locator('[data-quick-message].text-success').waitFor();
            assert.deepEqual(result().scores,values.map(Number));
            assert.equal(result().segments.length,1);
            assert.equal(await row.locator('[data-schedule-field="participant_slot_1"]').isDisabled(),true);
        }
        cases.push('quick-create-and-correct-zero');
        const beforeStale=native();
        assert.equal((await quickPost({revision:initialRevision})).success,false);
        assert.deepEqual(native(),beforeStale);
        cases.push('quick-stale-rejection');
        for(const width of [1440,390]){
            await page.setViewportSize({width,height:1000});
            assert(await quick.locator('input').first().evaluate(el=>el.getBoundingClientRect().width<=100),'Quick score control is not compact');
            await page.screenshot({path:path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-quick-score-'+width+'.png'),fullPage:true});
        }
        await page.setViewportSize({width:1440,height:1000});
        const concurrent=await browser.newContext();
        const otherPage=await concurrent.newPage();
        await otherPage.goto(base+'/administrator/');
        await otherPage.locator('#mod-login-username').fill('admin');
        await otherPage.locator('#mod-login-password').fill(process.env.JL_R1_PASSWORD);
        await Promise.all([otherPage.waitForNavigation({waitUntil:'networkidle'}),otherPage.locator('#form-login button[type=submit]').click()]);
        await otherPage.goto(list,{waitUntil:'networkidle'});
        const concurrentToken=await otherPage.locator('#matchList').getAttribute('data-csrf-token');
        const concurrentRevision=await quick.getAttribute('data-revision');
        const secondRequest=concurrent.request.post(admin,{form:{option:'com_joomleague',task:'matchresult.quickSave',format:'json',match_id:String(match),round_id:String(fixture.projects.a.round),revision:concurrentRevision,[concurrentToken]:'1',[`scores[${participantIds[0]}]`]:'4',[`scores[${participantIds[1]}]`]:'1'}}).then(response=>response.json());
        const race=await Promise.all([quickPost(),secondRequest]);
        assert.equal(race.filter(response=>response.success===true).length,1,'Exactly one concurrent revision may save');
        assert([3,4].includes(result().scores[0]));assert.equal(result().scores[1],1);
        assert.equal(result().segments.length,1);
        await concurrent.close();
        cases.push('quick-two-session-concurrency');
        native('timeline-fixture');
        await page.goto(admin+'?option=com_joomleague&view=matchevents&match_id='+match,{waitUntil:'networkidle'});
        const timeline=page.locator('#adminForm');
        assert.match(await timeline.innerText(),/51 minute/);
        assert.doesNotMatch(await timeline.innerText(),/\.000000000/);
        assert.match(await timeline.innerText(),/<b>S101 actor<\/b>/);
        assert.equal(await timeline.locator('b').count(),0,'Timeline actor markup not escaped');
        cases.push('authenticated-timeline-formatting');
        await open();
        await inputs().nth(0).fill('2');
        await inputs().nth(1).fill('1');
        await save();
        assert.deepEqual(result().scores, [2, 1]);
        assert.equal(result().segments.length, 1);
        assert.equal(JSON.parse(result().stored.metadata_json).aggregate_only, true);
        cases.push('save-root-only');
        await open();
        assert.deepEqual(await inputs().evaluateAll(nodes => nodes.map(node => node.value)), ['2', '1']);
        assert.equal(await page.locator('input[name*="[children]"][name$="[numeric_value]"]').evaluateAll(nodes => nodes.filter(node => node.value !== '').length), 0);
        cases.push('reopen-root-only');
        await inputs().nth(0).fill('0');
        await inputs().nth(1).fill('1');
        await save();
        assert.deepEqual(result().scores, [0, 1]);
        cases.push('correct-root-only');
        const standings = native('standings');
        const ownStanding = standings.rows.find(row => Number(row.project_entry_id) === fixture.projects.a.entries[0]);
        assert(ownStanding);
        assert.equal(Number(ownStanding.metrics.losses), 1);
        cases.push('standings-root-only');
        const rootGuest = await browser.newContext({viewport: {width: 390, height: 1000}});
        const rootPage = await rootGuest.newPage();
        assert.equal((await rootPage.goto(base + '/index.php?option=com_joomleague&view=eventreport&event_id=' + match, {waitUntil: 'networkidle'})).status(), 200);
        assert.deepEqual(await rootPage.locator('main .fs-3.fw-bold').allTextContents(), ['0', '1']);
        await rootGuest.close();
        cases.push('public-root-only');
        for (const kind of ['partial', 'csrf', 'foreign']) {
            await open();
            const data = await page.locator('#adminForm').evaluate(form => Object.fromEntries(new FormData(form)));
            data.option = 'com_joomleague'; data.task = 'matchresult.apply';
            if (kind === 'partial') {
                const key = Object.keys(data).find(key => key.includes('[children][0][values]') && key.endsWith('[numeric_value]'));
                assert(key); data[key] = '1';
            } else if (kind === 'csrf') {
                const key = Object.keys(data).find(key => /^[a-f0-9]{32}$/.test(key));
                assert(key); delete data[key];
            } else data.match_id = String(fixture.projects.b.records.Match);
            const before = native();
            const response = await context.request.post(admin, {form: data, maxRedirects: 0});
            assert.equal(response.status(), kind === 'partial' ? 303 : 403);
            assert.deepEqual(native(), before);
            cases.push('reject-' + kind);
        }
        await open();
        const children = page.locator('input[name*="[children]"][name$="[numeric_value]"]');
        const values = ['0', '0', '0', '1'];
        for (let i = 0; i < 4; ++i) await children.nth(i).fill(values[i]);
        await save();
        assert.equal(result().segments.length, 3);
        assert.equal(JSON.parse(result().stored.metadata_json || '{}').aggregate_only, undefined);
        cases.push('upgrade-to-detailed');
        const detailedBefore=native();
        await page.goto(list,{waitUntil:'networkidle'});
        assert.equal(await row.locator('[data-quick-result]').count(),0,'Detailed result exposed as quick score');
        const token=await page.locator('#matchList').getAttribute('data-csrf-token');
        const denied=await context.request.post(admin,{form:{option:'com_joomleague',task:'matchresult.quickSave',format:'json',match_id:String(match),round_id:String(fixture.projects.a.round),revision:initialRevision,[token]:'1',[`scores[${participantIds[0]}]`]:'9',[`scores[${participantIds[1]}]`]:'0'}});
        assert.equal((await denied.json()).success,false);
        assert.deepEqual(native(),detailedBefore,'Quick endpoint overwrote detailed score');
        cases.push('quick-detailed-preservation');
        const guest = await browser.newContext();
        const publicPage = await guest.newPage();
        for (const width of [1440, 390]) {
            await publicPage.setViewportSize({width, height: 1000});
            assert.equal((await publicPage.goto(base + '/index.php?option=com_joomleague&view=eventreport&event_id=' + match, {waitUntil: 'networkidle'})).status(), 200);
            assert.deepEqual(await publicPage.locator('main .fs-3.fw-bold').allTextContents(), ['0', '1']);
            assert.doesNotMatch(await publicPage.locator('main').innerText(), /COM_JOOMLEAGUE_|Fatal error|Warning:/);
            await publicPage.screenshot({path: path.join(process.env.JL_R1_ARTIFACTS, process.env.JL_R1_DRIVER + '-final-score-' + width + '.png'), fullPage: true});
            cases.push('public-' + width);
        }
        await guest.close();
        assert.deepEqual(errors, []);
        assert.equal(new Set(cases).size, 18);
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS, process.env.JL_R1_DRIVER + '-final-score.json'), JSON.stringify({passed: true, cases}, null, 2));
        console.log('PASS: eighteen quick/full final-score browser, timeline, standings, preservation and security cases.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
