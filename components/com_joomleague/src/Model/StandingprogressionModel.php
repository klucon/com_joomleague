<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\Model;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;use Joomla\CMS\MVC\Model\BaseDatabaseModel;use Joomla\Database\DatabaseInterface;use Joomleague\Component\Joomleague\Domain\Service\StandingProgressionReader;
final class StandingprogressionModel extends BaseDatabaseModel{protected function populateState($ordering=null,$direction=null):void{$i=Factory::getApplication()->getInput();$this->setState('project_id',$i->getInt('project_id',0));$s=$i->getInt('stage_id',0);$this->setState('stage_id',$s>0?$s:null);$this->setState('scope',$i->getCmd('scope','')?:null);}public function getProgression():array{$id=(int)$this->getState('project_id');if($id<1)return['error'=>'COM_JOOMLEAGUE_PROGRESSION_NO_PROJECT'];Factory::getApplication()->bootComponent('com_joomleague');try{return(new StandingProgressionReader(Factory::getContainer()->get(DatabaseInterface::class)))->forProject($id,$this->getState('stage_id'),$this->getState('scope'));}catch(\Throwable){return['error'=>'COM_JOOMLEAGUE_PROGRESSION_UNAVAILABLE'];}}}
