const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const {runtimeFailurePattern, summarize} = require('./audit-result.cjs');
const root = path.resolve(__dirname, '../..');
const base = 'http://joomla62.local';
const data = JSON.parse(execFileSync('docker', ['exec','local-joomla62-app','php','/tmp/jl-review-context.php'], {encoding:'utf8'}));
const params = new URLSearchParams({option:'com_joomleague',project_id:data.project,stage_id:data.stage,entry_id:data.entry,event_id:data.event,club_id:data.club,team_id:data.team,person_id:data.person,venue_id:data.venue,first_id:data.entry,event_code:'assist',statistic_code:'corners'});
const observations=[];
async function inspect(page, client, view, viewport) {
    const query=new URLSearchParams(params); query.set('view', view);
    if(client==='admin') {
        query.set('match_id',data.event); query.set('round_id',data.round);
        query.set('transition_id',data.formIds.stagetransition);
        if(fs.existsSync(root+'/administrator/components/com_joomleague/tmpl/'+view+'/edit.php')) {
            query.set('layout','edit');
            query.set('id',data.formIds[view]??({match:data.event,projectentry:data.entry}[view]??data[view]??0));
        }
    }
    const errors=[]; const listener=e=>errors.push(e.message); page.on('pageerror', listener);
    const response=await page.goto(base+(client==='admin'?'/administrator/index.php?':'/index.php?')+query, {waitUntil:'domcontentloaded'});
    await page.waitForTimeout(100);
    const main=page.locator('main');
    const text=await (await main.count()?main.first():page.locator('body')).innerText();
    const keys=[...new Set(text.match(/\b(?:COM_JOOMLEAGUE|MOD_JOOMLEAGUE)_[A-Z0-9_]+\b/g)||[])];
    const fatal=runtimeFailurePattern.test(text);
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2);
    observations.push({client,view,viewport,status:response?.status(),keys,fatal,overflow,errors});
    page.off('pageerror',listener);
}
(async()=>{
    const browser=await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox'});
    try {
        const context=await browser.newContext();
        const page=await context.newPage();
        for (const width of [1440,390]) {
            await page.setViewportSize({width,height:1000});
            for(const dir of fs.readdirSync(root+'/components/com_joomleague/src/View',{withFileTypes:true}).filter(d=>d.isDirectory())) await inspect(page,'site',dir.name.toLowerCase(),width);
        }
        await page.setViewportSize({width:1440,height:1000});
        await page.goto(base+'/administrator/');
        await page.locator('#mod-login-username').fill('admin');
        await page.locator('#mod-login-password').fill(fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md','utf8').match(/^Password: (.+)$/m)[1]);
        await page.locator('button[type=submit]').click(); await page.waitForURL('**/administrator/index.php');
        for(const dir of fs.readdirSync(root+'/administrator/components/com_joomleague/src/View',{withFileTypes:true}).filter(d=>d.isDirectory()&&fs.existsSync(root+'/administrator/components/com_joomleague/src/View/'+d.name+'/HtmlView.php'))) {
            if(dir.name==='Stageprogression'&&!data.formIds.stagetransition) {
                observations.push({client:'admin',view:'stageprogression',skipped:'No existing transition fixture; preview requires an actual transition.'}); continue;
            }
            await inspect(page,'admin',dir.name.toLowerCase(),1440);
        }
        const out=root+'/tests/artifacts/rc-code-review-20261010'; fs.mkdirSync(out,{recursive:true});
        fs.writeFileSync(out+'/browser-observations.json',JSON.stringify(observations,null,2));
        const result = summarize(observations);
        console.log(JSON.stringify(result,null,2));
        if (!result.passed) throw new Error('RC browser audit failed: detected issues or missing mandatory coverage.');
    } finally { await browser.close(); }
})().catch(e=>{console.error(e);process.exitCode=1});
