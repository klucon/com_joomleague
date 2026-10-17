<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

final class AboutjoomleagueModel extends BaseDatabaseModel
{
	/** @return array{author:string,component_version:string,profile_count:int} */
	public function getInstallation(): array
	{
		$manifest = simplexml_load_file(JPATH_ADMINISTRATOR . '/components/com_joomleague/joomleague.xml');
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$profileCount = (int) $db->setQuery(
			$db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName('#__joomleague_sport_profile'))
				->where($db->quoteName('published') . ' = 1')
		)->loadResult();

		return [
			'author' => $manifest === false ? '' : trim((string) $manifest->author),
			'component_version' => $manifest === false ? '' : trim((string) $manifest->version),
			'profile_count' => $profileCount,
		];
	}
}
