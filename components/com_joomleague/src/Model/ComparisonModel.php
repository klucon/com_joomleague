<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\Model;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;use Joomla\CMS\MVC\Model\BaseDatabaseModel;use Joomla\Database\DatabaseInterface;use Joomleague\Component\Joomleague\Domain\Service\ParticipantComparisonReader;
final class ComparisonModel extends BaseDatabaseModel{
	protected function populateState($ordering=null,$direction=null):void{$i=Factory::getApplication()->getInput();$this->setState('project_id',$i->getInt('project_id',0));foreach(['first_id','second_id']as$key){$id=$i->getInt($key,0);$this->setState($key,$id>0?$id:null);}}
	public function getComparison():array{$project=(int)$this->getState('project_id');if($project<1)return['error'=>'COM_JOOMLEAGUE_COMPARISON_NO_PROJECT'];Factory::getApplication()->bootComponent('com_joomleague');try{return(new ParticipantComparisonReader(Factory::getContainer()->get(DatabaseInterface::class)))->forProject($project,$this->getState('first_id'),$this->getState('second_id'),Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());}catch(\Throwable){return['error'=>'COM_JOOMLEAGUE_COMPARISON_UNAVAILABLE'];}}
}
