<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Model;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Klucon\Component\Kluconcountries\Administrator\Service\Registry;

final class TerritoriesModel extends ListModel
{
    protected function populateState($ordering='a.ordering',$direction='ASC'): void {
        $this->context='com_kluconcountries.territories';$this->filter_fields=['a.name_en','a.kind','a.state','a.ordering','a.valid_from','a.valid_to','a.version'];
        $previous=Factory::getApplication()->getUserState($this->context.'.list');parent::populateState($ordering,$direction);
        $input=Factory::getApplication()->input;
        if($input->getMethod()==='GET'&&!$input->get('filter',[],'array')&&!$input->get('list',[],'array')&&!$input->getInt('limitstart')){
            foreach(['parent','branch','search','kind','state','continent','validity'] as $key)$this->setState('filter.'.$key,'');
            $this->setState('filter.mode','tree');
        }
        $params=ComponentHelper::getParams('com_kluconcountries');$mode=(string)$this->state->get('filter.mode',$params->get('default_view','tree'));
        if(Registry::uuid((string)$this->state->get('filter.parent',''))||trim((string)$this->state->get('filter.search',''))!==''||(string)$this->state->get('filter.kind','')!=='')$mode='list';
        $this->setState('filter.mode',in_array($mode,['tree','list'],true)?$mode:'tree');
        $this->setState('filter.depth',1);
        if(!$previous&&!Factory::getApplication()->input->get('list',[],'array'))$this->setState('list.limit',max(5,min(200,(int)$params->get('list_limit',25))));
        $this->setState('list.limit',max(0,min(200,(int)$this->state->get('list.limit',25))));
        if($this->state->get('filter.mode')==='tree'){$this->setState('list.limit',0);$this->setState('list.start',0);}
    }
    protected function getListQuery(){
        if(!Factory::getApplication()->getIdentity()->authorise('core.manage','com_kluconcountries'))throw new \RuntimeException('JERROR_ALERTNOAUTHOR',403);
        $db=$this->getDatabase();$q=$db->createQuery()->select('a.*')->from('#__kluconcountries_units a');
        $search=$this->getState('filter.search','');if(!is_string($search))$search='';$search=trim($search);
        if($search!==''){$search='%'.mb_strtolower($search,'UTF-8').'%';$q->where('(LOWER(a.name_en) LIKE :search OR a.uuid IN (SELECT i.unit_uuid FROM #__kluconcountries_identifiers i WHERE LOWER(i.code) LIKE :code))')->bind(':search',$search)->bind(':code',$search);}
        $kind=(string)$this->getState('filter.kind','');if(in_array($kind,Registry::KINDS,true))$q->where('a.kind=:kind')->bind(':kind',$kind);
        $state=(string)$this->getState('filter.state','');if(in_array($state,['-2','0','1'],true))$q->where('a.state='.(int)$state);elseif($state!=='all')$q->where('a.state<>-2');
        $continent=(string)$this->getState('filter.continent','');
        if(Registry::uuid($continent)){
            $all=array_column((new Registry($db))->all(),null,'uuid');$ids=[$continent];
            foreach($all as $row){$cursor=$row;$seen=[];while($cursor&&!isset($seen[$cursor->uuid])){$seen[$cursor->uuid]=true;if(in_array($continent,$cursor->continents,true)){$ids[]=$row->uuid;break;}$cursor=$cursor->parent_uuid?($all[$cursor->parent_uuid]??null):null;}}
            $q->where('a.uuid IN ('.implode(',',array_map([$db,'quote'],array_unique($ids))).')');
        }
        $parent=(string)$this->getState('filter.parent','');if(Registry::uuid($parent)){
            $unit=(new Registry($db))->find($parent);
            if($unit&&$unit->kind==='continent')$q->where('a.uuid IN (SELECT unit_uuid FROM #__kluconcountries_continents WHERE continent_uuid=:parent)')->bind(':parent',$parent);
            else $q->where('a.parent_uuid=:parent')->bind(':parent',$parent);
        }
        $valid=(string)$this->getState('filter.validity','');$today=gmdate('Y-m-d');
        if($valid==='current')$q->where('(a.valid_from IS NULL OR a.valid_from<=:from)')->where('(a.valid_to IS NULL OR a.valid_to>:until)')->bind(':from',$today)->bind(':until',$today);
        elseif($valid==='expired')$q->where('a.valid_to IS NOT NULL AND a.valid_to<=:until')->bind(':until',$today);
        $order=(string)$this->getState('list.ordering','a.ordering');if(!in_array($order,$this->filter_fields,true))$order='a.ordering';
        return $q->order($db->quoteName($order).' '.(strtoupper((string)$this->getState('list.direction'))==='DESC'?'DESC':'ASC'))->order('a.uuid ASC');
    }
    public function getFilterForm($data=[],$loadData=true){
        $xml='<form><fields name="filter"><field name="search" type="text" label="JSEARCH_FILTER" hint="COM_KLUCONCOUNTRIES_SEARCH"/>'
            .'<field name="mode" type="list" label="COM_KLUCONCOUNTRIES_CONFIG_VIEW" onchange="this.form.submit()"><option value="tree">COM_KLUCONCOUNTRIES_VIEW_TREE</option><option value="list">COM_KLUCONCOUNTRIES_VIEW_LIST</option></field>'
            .'<field name="state" type="list" label="JSTATUS" onchange="this.form.submit()"><option value="">JOPTION_SELECT_PUBLISHED</option><option value="1">JPUBLISHED</option><option value="0">JUNPUBLISHED</option><option value="-2">JTRASHED</option><option value="all">JALL</option></field>'
            .'<field name="kind" type="list" label="COM_KLUCONCOUNTRIES_TYPE" onchange="this.form.submit()"><option value="">COM_KLUCONCOUNTRIES_ALL_TYPES</option>';
        foreach(Registry::KINDS as $kind)$xml.='<option value="'.$kind.'">COM_KLUCONCOUNTRIES_TYPE_'.strtoupper($kind).'</option>';
        $xml.='</field><field name="continent" type="sql" label="COM_KLUCONCOUNTRIES_CONTINENTS" query="SELECT uuid AS value,name_en AS text FROM #__kluconcountries_units WHERE kind=\'continent\' AND state != -2 ORDER BY ordering,uuid" key_field="value" value_field="text" onchange="this.form.submit()"><option value="">COM_KLUCONCOUNTRIES_ALL_CONTINENTS</option></field>'
            .'<field name="validity" type="list" label="COM_KLUCONCOUNTRIES_VALIDITY" onchange="this.form.submit()"><option value="">COM_KLUCONCOUNTRIES_ALL_DATES</option><option value="current">COM_KLUCONCOUNTRIES_CURRENT</option><option value="expired">COM_KLUCONCOUNTRIES_EXPIRED</option></field><field name="parent" type="hidden"/><field name="branch" type="hidden"/></fields>'
            .'<fields name="list"><field name="fullordering" type="list" label="JGLOBAL_SORT_BY" onchange="this.form.submit()">';
        foreach(['a.name_en'=>'COM_KLUCONCOUNTRIES_NAME','a.kind'=>'COM_KLUCONCOUNTRIES_TYPE','a.ordering'=>'JFIELD_ORDERING_LABEL','a.state'=>'JSTATUS','a.valid_to'=>'COM_KLUCONCOUNTRIES_VALID_TO'] as $column=>$label)foreach(['ASC','DESC'] as $dir)$xml.='<option value="'.$column.' '.$dir.'">'.htmlspecialchars(\Joomla\CMS\Language\Text::_($label).' - '.\Joomla\CMS\Language\Text::_($dir==='ASC'?'JGLOBAL_ORDER_ASCENDING':'JGLOBAL_ORDER_DESCENDING'),ENT_XML1|ENT_QUOTES,'UTF-8').'</option>';
        $xml.='</field><field name="limit" type="limitbox" label="JGLOBAL_LIST_LIMIT" onchange="this.form.submit()"/></fields></form>';
        $form=Form::getInstance('com_kluconcountries.filters',$xml,['control'=>'']);$filter=[];foreach(['search','mode','depth','state','kind','continent','validity','parent','branch'] as $key)$filter[$key]=$this->getState('filter.'.$key,'');
        $form->bind(['filter'=>$filter,'list'=>['fullordering'=>$this->getState('list.ordering').' '.$this->getState('list.direction'),'limit'=>$this->getState('list.limit')]]);return $form;
    }
    public function getActiveFilters(){ $filters=[];foreach(['search','state','kind','continent','validity'] as $key){$value=$this->getState('filter.'.$key);if($value!==null&&$value!=='')$filters[$key]=$value;}return $filters; }
}
