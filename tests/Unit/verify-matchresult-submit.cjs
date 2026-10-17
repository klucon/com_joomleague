const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm');
const root=path.join(__dirname,'../..');let listener;
class Form{
    constructor(){this.id='adminForm';this.field={value:''};}
    querySelector(selector){assert.equal(selector,'input[type="hidden"][name="task"]');return this.field;}
}
vm.runInNewContext(fs.readFileSync(path.join(root,'media/com_joomleague/js/matchresult.js'),'utf8'),{
    HTMLFormElement:Form,document:{addEventListener:(event,handler)=>{assert.equal(event,'submit');listener=handler;}}
});
assert.equal(typeof listener,'function');const form=new Form();
for(const task of ['matchresult.addSegment','matchresult.removeSegment']){
    listener({target:form,submitter:{dataset:{resultTask:task}}});assert.equal(form.field.value,task);
}
for(const button of [undefined,{dataset:{}},{dataset:{resultTask:'unrelated.delete'}}]){
    form.field.value='matchresult.apply';listener({target:form,submitter:button});assert.equal(form.field.value,'matchresult.apply');
}
const other=new Form();other.id='otherForm';listener({target:other,submitter:{dataset:{resultTask:'matchresult.addSegment'}}});assert.equal(other.field.value,'');
listener({target:{id:'adminForm'},submitter:{dataset:{resultTask:'matchresult.addSegment'}}});
const template=fs.readFileSync(path.join(root,'administrator/components/com_joomleague/tmpl/matchresult/default.php'),'utf8');
assert(!/<button\b[^>]*\bname="task"/.test(template),'Mutation buttons shadow the Joomla task field');
assert(template.includes('useScript(\'com_joomleague.matchresult\')'));
console.log('PASS: result mutation submits do not shadow Joomla toolbar tasks');
