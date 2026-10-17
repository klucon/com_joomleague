const fs = require('node:fs');
const {firefox} = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
(async () => {
    const browser = await firefox.launch({executablePath:'/opt/playwright/firefox-1532/firefox/firefox'});
    const observations = [];
    const output = require('node:path').resolve(__dirname,'../artifacts/admin-widths-20261011');
    fs.mkdirSync(output,{recursive:true});
    try {
        const page = await browser.newPage();
        await page.goto('http://joomla62.local/administrator/');
        await page.locator('#mod-login-username').fill('admin');
        await page.locator('#mod-login-password').fill(fs.readFileSync('/mnt/disk-a/pswd/joomla62-local.md','utf8').match(/^Password: (.+)$/m)[1]);
        await Promise.all([page.waitForURL('**/administrator/index.php'),page.locator('button[type=submit]').click()]);
        for (const width of [1440,390]) {
            await page.setViewportSize({width,height:1000});
            for (const view of ['matches','positions','projects','sportprofiles']) {
                const response = await page.goto(`http://joomla62.local/administrator/index.php?option=com_joomleague&view=${view}&round_id=3607`);
                await page.waitForTimeout(350);
                const scrolling = await page.locator('.table-responsive').first().evaluate(node => {
                    const overflowing = node.scrollWidth > node.clientWidth + 2;
                    node.scrollLeft = 100;
                    const moved = node.scrollLeft > 0;
                    node.scrollLeft = 0;
                    return {overflowing,moved};
                });
                if (scrolling.overflowing && !scrolling.moved) throw Error(`${view}: table cannot scroll.`);
                await page.screenshot({path:`${output}/${view}-${width}.png`,fullPage:true});
                const dimensions = await page.evaluate(() => {
                    const nodes = [...document.querySelectorAll('main, main form, .table-responsive')];
                    let parent = document.querySelector('main')?.parentElement;
                    while (parent) { nodes.push(parent); parent = parent.parentElement; }
                    const offenders = [...document.querySelectorAll('body *')].filter(node => !node.closest('.table-responsive') && node.getBoundingClientRect().right > innerWidth + 2).slice(0,12).map(node=>({tag:node.tagName,class:node.className,width:Math.round(node.getBoundingClientRect().width)}));
                    return {overflow:document.documentElement.scrollWidth-innerWidth, offenders, nodes:nodes.map(node=>({tag:node.tagName, id:node.id, class:node.className, width:Math.round(node.getBoundingClientRect().width), scroll:node.scrollWidth, min:getComputedStyle(node).minWidth, display:getComputedStyle(node).display}))};
                });
                observations.push({view,width,status:response.status(),...dimensions});
            }
        }
        console.log(JSON.stringify(process.env.AUDIT_DIAGNOSTIC === '1' ? observations : observations.map(({view,width,status,overflow,offenders})=>({view,width,status,overflow,offenders:overflow>2?offenders:[]})),null,2));
        fs.writeFileSync(`${output}/observations.json`,JSON.stringify(observations,null,2));
        if (process.env.AUDIT_DIAGNOSTIC !== '1' && observations.some(row=>row.status!==200||row.overflow>2)) throw Error('Admin table width regression.');
    } finally {await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
