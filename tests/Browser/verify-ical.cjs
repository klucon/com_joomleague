const assert = require('node:assert/strict');
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const base = process.env.JL_R1_BASE;
assert.match(base, /^http:\/\/127\.0\.0\.1:\d+$/);
const fixture = JSON.parse(process.env.JL_R1_FIXTURE);
const cache = new Map();
let hits = 0;
// Deliberately URI-only shared cache. It must cache the positive control,
// but must not retain access-dependent responses from Joomla.
const proxy = http.createServer(async (req, res) => {
    if (cache.has(req.url)) { hits++; const item = cache.get(req.url); res.writeHead(item.status, item.headers); return res.end(item.body); }
    if (req.url === '/positive-control') {
        const item = {status:200, headers:{'cache-control':'public, max-age=60'}, body:Buffer.from('cacheable')};
        cache.set(req.url, item); res.writeHead(item.status, item.headers); return res.end(item.body);
    }
    try {
        const response = await fetch(base + req.url, {headers:{cookie:req.headers.cookie || ''}, redirect:'manual'});
        const headers = Object.fromEntries(response.headers);
        const body = Buffer.from(await response.arrayBuffer());
        delete headers['transfer-encoding']; delete headers['content-encoding'];
        headers['content-length'] = String(body.length);
        if (!/private|no-store|no-cache/i.test(headers['cache-control'] || '') && /max-age=[1-9]/i.test(headers['cache-control'] || '')) cache.set(req.url,{status:response.status,headers,body});
        res.writeHead(response.status,headers); res.end(body);
    } catch (error) { res.writeHead(502); res.end(String(error)); }
});
(async () => {
    await new Promise(resolve => proxy.listen(0,'127.0.0.1',resolve));
    const proxyBase = `http://127.0.0.1:${proxy.address().port}`;
    const browser = await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox',headless:true});
    const contexts = {};
    const evidence = [];
    try {
        await fetch(proxyBase+'/positive-control'); await fetch(proxyBase+'/positive-control'); assert.equal(hits,1);
        for (const identity of ['guest','a','b']) {
            const context = contexts[identity] = await browser.newContext();
            if (identity !== 'guest') {
                const page = await context.newPage();
                await page.goto(base+'/index.php?option=com_users&view=login');
                await page.locator('#username').fill('r1.editor.'+identity);
                await page.locator('#password').fill(process.env.JL_R1_PASSWORD);
                await Promise.all([page.waitForNavigation(),page.locator('#com-users-login__form button[type=submit]').click()]);
                assert.ok(!(await page.locator('#username').count()), 'Site login failed');
                await page.close();
            }
        }
        for (const [owner, project] of Object.entries(fixture.projects)) {
            for (const [scope, extra] of Object.entries({project:'',entry:'&entry_id='+project.entries[0],event:'&event_id='+project.records.Match,club:'&club_id='+(owner==='a'?9001:9002),missing:'&club_id=999999'})) {
                const url = `/index.php?option=com_joomleague&task=ical.download&format=raw&project_id=${project.id}&scope=${scope==='missing'?'club':scope}${extra}`;
                for (const identity of [owner,'guest',owner==='a'?'b':'a',owner]) {
                    const cookies = await contexts[identity].cookies(base);
                    const response = await fetch(proxyBase+url,{headers:{cookie:cookies.map(c=>c.name+'='+c.value).join('; ')}});
                    const body = await response.text();
                    assert.match(response.headers.get('cache-control')||'',/no-store/);
                    const allowed = identity===owner && scope!=='missing';
                    assert.equal(response.status,allowed?200:404, `${identity}/${scope}: ${body.slice(0,200)}`);
                    assert.equal(body.includes('PRIVATE-CALENDAR-'+owner.toUpperCase()),allowed);
                    if (allowed) {
                        assert.match(response.headers.get('content-type'),/text\/calendar/);
                        assert.equal(response.headers.get('x-content-type-options'),'nosniff');
                        assert.match(body,/BEGIN:VCALENDAR/);
                        assert.equal(Number(response.headers.get('content-length')),Buffer.byteLength(body));
                    }
                    evidence.push({owner,scope,identity,status:response.status,cacheControl:response.headers.get('cache-control')});
                }
            }
        }
        for (const [action, status] of [['public',200],['hide',404]]) {
            execFileSync('docker',['exec',process.env.JL_R1_APP,'php','/opt/r1-tests/ical-acceptance.php',action]);
            const url = `/index.php?option=com_joomleague&task=ical.download&format=raw&project_id=${fixture.projects.a.id}`;
            const response = await fetch(proxyBase+url, {headers:{'if-none-match':'"old-calendar"','if-modified-since':'Wed, 01 Jan 2031 00:00:00 GMT'}});
            const body = await response.text();
            assert.equal(response.status,status);
            assert.match(response.headers.get('cache-control')||'',/no-store/);
            assert.equal(body.includes('PRIVATE-CALENDAR-A'),status===200);
            evidence.push({action,status:response.status,cacheControl:response.headers.get('cache-control')});
        }
        assert.equal(hits,1,'Private response was served from shared cache');
        fs.writeFileSync(path.join(process.env.JL_R1_ARTIFACTS,process.env.JL_R1_DRIVER+'-http.json'),JSON.stringify({passed:true,positiveControlHits:hits,checks:evidence},null,2));
        console.log('PASS: site authentication, scoped HTTP exports, denial responses and URI-only shared cache isolation');
    } finally { await browser.close(); await new Promise(resolve=>proxy.close(resolve)); }
})().catch(error=>{console.error(error); process.exitCode=1; proxy.close();});
