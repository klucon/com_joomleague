<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Site\Model;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\ClubSummaryReader;
final class ClubModel extends BaseDatabaseModel
{
	protected function populateState($ordering=null,$direction=null):void{$this->setState('club_id',Factory::getApplication()->getInput()->getInt('club_id',0));}
	/** @return array<string,mixed> */
	public function getClub():array{$app=Factory::getApplication();$app->bootComponent('com_joomleague');$data=(new ClubSummaryReader(Factory::getContainer()->get(DatabaseInterface::class)))->read((int)$this->getState('club_id'),$app->getIdentity()->getAuthorisedViewLevels());if(isset($data['error']))$data['error']=$data['error']==='club_required'?'COM_JOOMLEAGUE_CLUB_NOT_CONFIGURED':'COM_JOOMLEAGUE_CLUB_UNAVAILABLE';return$data;}
}
