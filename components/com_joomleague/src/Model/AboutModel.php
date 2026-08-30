<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

final class AboutModel extends BaseDatabaseModel
{
	/** @return array{component_version:string,joomla_version:string,profile_count:int} */
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
			'component_version' => $manifest === false ? '' : trim((string) $manifest->version),
			'joomla_version' => JVERSION,
			'profile_count' => $profileCount,
		];
	}
}
