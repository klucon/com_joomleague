import { readFileSync } from 'node:fs';
import { firefox } from '/mnt/disk-a/dev/uklid/2026-09-19/office-browser-tests/node_modules/playwright-core/index.mjs';
const credentials = readFileSync('/mnt/disk-a/pswd/2026-joomla-joomla62-ai-developer.md', 'utf8');
const value = (name) => credentials.match(new RegExp(`^- ${name}:\\s*(.+)$`, 'm'))[1].replace(/^`|`$/g, '');
const browser = await firefox.launch({headless:true});
try {
 const page = await browser.newPage();
 await page.goto('https://joomla62.klucon.cz/administrator/');
 await page.locator('#mod-login-username').fill(value('Username'));
 await page.locator('#mod-login-password').fill(value('Password'));
 await Promise.all([page.waitForNavigation(),page.locator('#form-login button[type=submit]').click()]);
 for (const view of (process.argv.slice(2).length ? process.argv.slice(2) : ['clubs','teams','persons','seasons','competitions','projects','venues','sporttypes','positions','events','statistics'])) {
  await page.goto(`https://joomla62.klucon.cz/administrator/index.php?option=com_joomleague&view=${view}`);
  if(await page.locator('[name=filter_order]').count())throw Error(`${view}: legacy ordering input`);
  const columns = await page.locator('thead [data-order]').evaluateAll(nodes=>nodes.map(n=>n.dataset.order));
  if(!columns.length)throw Error(`${view}: no sorting headers: ${(await page.locator('main').innerText()).slice(0,1200)}`);
  for(const column of columns) {
   for(let i=0;i<2;i++) {
    const header=page.locator(`thead [data-order="${column}"]`);
    const direction=await header.getAttribute('data-direction');
    const response = page.waitForResponse(r=>r.request().isNavigationRequest()&&r.request().method()==='POST');
    await header.click();
    await (await response).finished();
    await page.waitForFunction(({column,direction})=>{
     const header=document.querySelector('thead #sorted');
     return header?.dataset.order===column && header.dataset.direction.toLowerCase()!==direction.toLowerCase();
    },{column,direction});
    const expected=`${column} ${direction}`;
    if((await page.locator('#list_fullordering').inputValue()).toLowerCase()!==expected.toLowerCase())throw Error(`${view}: selection mismatch ${expected}`);
    const active=await page.locator('thead #sorted').getAttribute('data-order');
    if(active!==column)throw Error(`${view}: model ignored column ${column}`);
    if(column.endsWith('.id')) {
     const ids=await page.locator('tbody input[name="cid[]"]').evaluateAll(nodes=>nodes.map(n=>Number(n.value)));
     const descending=direction.toLowerCase()==='desc';
     if(ids.some((id,j)=>j>0&&(descending?id>ids[j-1]:id<ids[j-1])))throw Error(`${view}: actual rows not ordered ${expected}`);
    }
    if(['team_count','project_count','assignment_count','override_count'].includes(column)) {
     const values=await page.locator('tbody tr').evaluateAll((rows,column)=>{
      const header=document.querySelector(`thead [data-order="${column}"]`).closest('th');
      const index=Array.from(header.parentElement.children).indexOf(header);
      return rows.filter(r=>r.querySelector('input[name="cid[]"]')).map(r=>{
       const numbers=r.children[index].textContent.match(/\d+/g)||[];
       return numbers.reduce((sum,n)=>sum+Number(n),0);
      });
     },column);
     const descending=direction.toLowerCase()==='desc';
     if(values.some((v,j)=>j>0&&(descending?v>values[j-1]:v<values[j-1])))throw Error(`${view}: numeric rows not ordered ${expected}`);
    }
   }
  }
  console.log(`${view}: ${columns.length} columns, ASC/DESC and actual ID order OK`);
 }
} finally {await browser.close();}
