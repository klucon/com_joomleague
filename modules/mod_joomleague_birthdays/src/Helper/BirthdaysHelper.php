<?php
declare(strict_types=1);
namespace Joomleague\Module\Birthdays\Site\Helper;
defined('_JEXEC') or die;
use DateTimeImmutable;use DateTimeZone;use Joomla\CMS\Factory;use Joomla\Database\DatabaseInterface;use Joomla\Registry\Registry;use Joomleague\Component\Joomleague\Domain\Service\BirthdayReader;use Joomleague\Module\Birthdays\Site\Configuration\BirthdaysConfiguration;
final class BirthdaysHelper
{
	/** @return array<string,mixed> */
	public function getBirthdays(Registry $params):array
	{
		$config=BirthdaysConfiguration::fromRegistry($params);
		try{$app=Factory::getApplication();$app->bootComponent('com_joomleague');$today=new DateTimeImmutable('today',new DateTimeZone((string)$app->get('offset','UTC')));$levels=$app->getIdentity()?->getAuthorisedViewLevels()??[1];$items=(new BirthdayReader(Factory::getContainer()->get(DatabaseInterface::class)))->upcoming($today,$config['days'],$config['limit'],$levels,$config['club_id'],$config['project_id'],$config['team_id'],$config['include_deceased'],$config['person_types'],$config['role_codes'],$config['period_mode'],$config['selected_month'],$config['start_offset'],$config['order'],$config['direction']);foreach($items as$item)$item->picture=\Joomleague\Component\Joomleague\Site\Service\MediaResolver::image('person',$item->picture??null);return$items===[]?['error'=>'MOD_JOOMLEAGUE_BIRTHDAYS_EMPTY','config'=>$config]:['items'=>$items,'config'=>$config];}catch (\Throwable $moduleFailure) { \Joomla\CMS\Log\Log::add('mod_joomleague_birthdays failure: ' . get_debug_type($moduleFailure), \Joomla\CMS\Log\Log::WARNING, 'com_joomleague.modules');return['error'=>'MOD_JOOMLEAGUE_BIRTHDAYS_UNAVAILABLE','config'=>$config];}
	}
}
