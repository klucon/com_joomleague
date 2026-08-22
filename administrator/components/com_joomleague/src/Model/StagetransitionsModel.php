<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;

final class StagetransitionsModel extends ListModel
{
	public function __construct($config = []) { $config['filter_fields'] ??= ['id','a.id','name','a.name','code','a.code','selector_type','a.selector_type','published','a.published','ordering','a.ordering']; parent::__construct($config); }
	protected function populateState($ordering = 'a.id', $direction = 'desc'): void { $this->setState('project_id', $this->getUserStateFromRequest($this->context . '.project_id', 'project_id', 0, 'uint')); foreach (['search','published','selector_type'] as $filter) $this->setState('filter.' . $filter, $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, '')); parent::populateState($ordering, $direction); }
	public function getProject(): object { return (new ProjectContextRepository($this->getDatabase()))->get((int) $this->getState('project_id')); }
	protected function getListQuery() { $db = $this->getDatabase(); $projectId = (int) $this->getState('project_id'); $query = $db->getQuery(true)->select(['a.*','source.name AS source_name','target.name AS target_name'])->from($db->quoteName('#__joomleague_stage_transition','a'))->innerJoin($db->quoteName('#__joomleague_project_stage','source') . ' ON source.id = a.source_stage_id')->innerJoin($db->quoteName('#__joomleague_project_stage','target') . ' ON target.id = a.target_stage_id')->where('a.project_id = :project')->bind(':project',$projectId,ParameterType::INTEGER); $published=$this->getState('filter.published'); if($published!==''){ $published=(int)$published;$query->where('a.published = :published')->bind(':published',$published,ParameterType::INTEGER);} $type=trim((string)$this->getState('filter.selector_type')); if($type!=='')$query->where('a.selector_type = :type')->bind(':type',$type); $search=trim((string)$this->getState('filter.search')); if($search!==''){ $search='%'.$search.'%';$query->where('(a.name LIKE :name OR a.code LIKE :code)')->bind(':name',$search)->bind(':code',$search);} return $query->order($db->escape((string)$this->getState('list.ordering','a.id')).' '.($this->getState('list.direction')==='desc'?'DESC':'ASC')); }
}
