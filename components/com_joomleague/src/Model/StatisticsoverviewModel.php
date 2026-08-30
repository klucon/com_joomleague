<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\Model;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;use Joomla\CMS\MVC\Model\BaseDatabaseModel;use Joomla\Database\DatabaseInterface;use Joomleague\Component\Joomleague\Domain\Service\ProjectStatisticsReader;
final class StatisticsoverviewModel extends BaseDatabaseModel{protected function populateState($ordering=null,$direction=null):void{$this->setState('project_id',Factory::getApplication()->getInput()->getInt('project_id',0));}public function getOverview():array{$id=(int)$this->getState('project_id');if($id<1)return['error'=>'COM_JOOMLEAGUE_STATSOVERVIEW_NO_PROJECT'];Factory::getApplication()->bootComponent('com_joomleague');try{return(new ProjectStatisticsReader(Factory::getContainer()->get(DatabaseInterface::class)))->forProject($id,Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());}catch(\Throwable){return['error'=>'COM_JOOMLEAGUE_STATSOVERVIEW_UNAVAILABLE'];}}}
