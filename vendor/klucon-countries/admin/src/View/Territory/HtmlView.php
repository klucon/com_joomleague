<?php
namespace Klucon\Component\Kluconcountries\Administrator\View\Territory;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use Klucon\Component\Kluconcountries\Administrator\Service\{Registry,Editor};

final class HtmlView extends \Joomla\CMS\MVC\View\HtmlView
{
    public ?object $item=null;public Form $form;public Registry $registry;public string $returnUrl;public string $returnBranch='';
    public function display($tpl=null){
        $app=Factory::getApplication();$uuid=$app->input->getString('uuid');$user=$app->getIdentity();
        if(!$user->authorise('core.manage','com_kluconcountries')||!$user->authorise($uuid?'core.edit':'core.create','com_kluconcountries'))throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $this->registry=new Registry(Factory::getContainer()->get(DatabaseInterface::class));$this->item=$uuid?$this->registry->find($uuid):null;
        if($uuid&&!$this->item)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $branch=$app->input->getString('return_branch');
        $memberships=$this->item?$this->registry->continentsFor($this->item):[];
        $this->returnBranch=in_array($branch,$memberships,true)?$branch:($memberships[0]??'');
        $parent=$this->item?->parent_uuid;
        if(!$parent&&$this->item&&$this->item->kind!=='continent')$parent=$this->returnBranch;
        $this->returnUrl='index.php?'.http_build_query(['option'=>'com_kluconcountries','view'=>'territories','filter'=>['mode'=>$parent?'list':'tree','depth'=>1,'parent'=>$parent??'','branch'=>$this->returnBranch,'search'=>'','kind'=>'','state'=>'','continent'=>'','validity'=>''],'limitstart'=>0],'','&',PHP_QUERY_RFC3986);
        $this->form=Form::getInstance('com_kluconcountries.territory',JPATH_ADMINISTRATOR.'/components/com_kluconcountries/forms/territory.xml',['control'=>'jform']);
        if($this->item&&$this->item->kind==='continent'){
            $this->form->loadFile(JPATH_ADMINISTRATOR.'/components/com_kluconcountries/forms/continentfacts.xml',false);
            $app->getLanguage()->load('com_kluconcountries.facts',JPATH_ADMINISTRATOR);
        }
        foreach(Registry::KINDS as $kind)$this->form->getField('kind')->addOption(Text::_('COM_KLUCONCOUNTRIES_TYPE_'.strtoupper($kind)),['value'=>$kind]);
        foreach($this->registry->all() as $row){
            if($row->uuid===$uuid||(int)$row->state===-2)continue;
            if($row->kind==='continent')$this->form->getField('continents')->addOption(Registry::label($row,'en-GB'),['value'=>$row->uuid]);
            else $this->form->getField('parent_uuid')->addOption(Registry::label($row,'en-GB').' ['.$row->kind.']',['value'=>$row->uuid]);
        }
        foreach(Registry::flags() as $path)$this->form->getField('flag')->addOption(basename($path,'.svg'),['value'=>$path]);
        foreach($this->registry->db->setQuery('SELECT path FROM #__kluconcountries_assets ORDER BY path')->loadColumn() as $path)$this->form->getField('flag')->addOption($path,['value'=>$path]);
        $bind=Editor::bind($this->registry,$this->item);$pending=$app->getSession()->get('com_kluconcountries.form');
        if(is_array($pending)&&$pending['uuid']===$uuid&&$pending['version']===(int)($this->item?->version??0))$bind=array_replace($bind,$pending['fields']);$app->getSession()->set('com_kluconcountries.form',null);
        if(!$user->authorise('core.edit.state','com_kluconcountries'))$this->form->setFieldAttribute('state','disabled','true');
        if(!$user->authorise('countries.upload','com_kluconcountries')||!ComponentHelper::getParams('com_kluconcountries')->get('upload_enabled',1))$this->form->removeField('flag_file');
        $this->form->bind($bind);$app->getLanguage()->load('com_kluconcountries.units',JPATH_ADMINISTRATOR);
        ToolbarHelper::title(Text::_($uuid?'COM_KLUCONCOUNTRIES_EDIT':'COM_KLUCONCOUNTRIES_NEW'),'globe');ToolbarHelper::apply('territory.apply');ToolbarHelper::save('territory.save');
        if($uuid&&$user->authorise('countries.history','com_kluconcountries'))ToolbarHelper::link('index.php?option=com_kluconcountries&view=history&uuid='.rawurlencode($uuid),Text::_('COM_KLUCONCOUNTRIES_HISTORY'),'clock');
        ToolbarHelper::link($this->returnUrl,Text::_('JCANCEL'),'times');parent::display($tpl);
    }
}
