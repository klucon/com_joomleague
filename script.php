<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

return new class implements InstallerScriptInterface {
	public function install(InstallerAdapter $adapter): bool
	{
		$this->removeObsoleteFiles();

		return $this->installImages($adapter);
	}

	public function update(InstallerAdapter $adapter): bool
	{
		$this->removeObsoleteFiles();
		$this->removeTreetosMenu();
		$this->removeCustomFieldsMenu();
		$this->removeLegacyFlagPngs();

		return $this->installImages($adapter);
	}

	public function uninstall(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function preflight(string $type, InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function postflight(string $type, InstallerAdapter $adapter): bool
	{
		if ($type === 'update') {
			$this->migrateCountryCodes();
		}

		return true;
	}

	private function installImages(InstallerAdapter $adapter): bool
	{
		$source = $adapter->getParent()->getPath('source') . '/images/com_joomleague';
		$destination = JPATH_ROOT . '/images/com_joomleague';

		if (!is_dir($source)) {
			throw new RuntimeException(Text::_('COM_JOOMLEAGUE_INSTALL_ERROR_IMAGE_SOURCE_MISSING'));
		}

		$this->copyDirectory($source, $destination);

		$uploadDirectory = $destination . '/playgrounds';

		if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
			throw new RuntimeException(Text::sprintf('COM_JOOMLEAGUE_INSTALL_ERROR_CREATE_DIRECTORY', $uploadDirectory));
		}

		foreach (['small', 'medium', 'large'] as $size) {
			$clubDirectory = $destination . '/clubs/' . $size;

			if (!is_dir($clubDirectory) && !mkdir($clubDirectory, 0755, true) && !is_dir($clubDirectory)) {
				throw new RuntimeException(Text::sprintf('COM_JOOMLEAGUE_INSTALL_ERROR_CREATE_DIRECTORY', $clubDirectory));
			}
		}

		foreach (['teams', 'persons', 'statistics'] as $directory) {
			$uploadDirectory = $destination . '/' . $directory;

			if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
				throw new RuntimeException(Text::sprintf('COM_JOOMLEAGUE_INSTALL_ERROR_CREATE_DIRECTORY', $uploadDirectory));
			}

			if (!chmod($uploadDirectory, 02775)) {
				throw new RuntimeException(Text::sprintf('COM_JOOMLEAGUE_INSTALL_ERROR_SET_PERMISSIONS', $uploadDirectory));
			}
		}

		foreach (['playgrounds', 'clubs', 'clubs/small', 'clubs/medium', 'clubs/large', 'database/events', 'database/projects'] as $directory) {
			$writableDirectory = $destination . '/' . $directory;

			if (!is_dir($writableDirectory) && !mkdir($writableDirectory, 02775, true) && !is_dir($writableDirectory)) {
				throw new RuntimeException(Text::sprintf('COM_JOOMLEAGUE_INSTALL_ERROR_CREATE_DIRECTORY', $writableDirectory));
			}

			if (!chmod($writableDirectory, 02775)) {
				throw new RuntimeException(Text::sprintf('COM_JOOMLEAGUE_INSTALL_ERROR_SET_PERMISSIONS', $writableDirectory));
			}
		}

		return true;
	}

	private function removeTreetosMenu(): void
	{
		// Položka admin menu „Turnajové stromy" (treetos) byla z komponenty odebrána.
		// Pojistka: smaže případný zbylý záznam admin menu i při updatu.
		try {
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->createQuery()
				->delete($db->quoteName('#__menu'))
				->where($db->quoteName('menutype') . ' = ' . $db->quote('main'))
				->where($db->quoteName('client_id') . ' = 1')
				->where($db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_joomleague%view=treetos%'));

			$db->setQuery($query)->execute();
		} catch (\Throwable $exception) {
			// Nepodstatné pro instalaci – ignorujeme.
		}
	}

	private function removeCustomFieldsMenu(): void
	{
		// Správa vlastních polí (com_fields) se dřív přidávala jako položky admin menu.
		// Nově je jen jako toolbar tlačítko v seznamech. Pojistka: smaže zbylé menu záznamy.
		try {
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->createQuery()
				->delete($db->quoteName('#__menu'))
				->where($db->quoteName('menutype') . ' = ' . $db->quote('main'))
				->where($db->quoteName('client_id') . ' = 1')
				->where($db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_fields%context=com_joomleague.%'));

			$db->setQuery($query)->execute();
		} catch (\Throwable $exception) {
			// Nepodstatné pro instalaci – ignorujeme.
		}
	}

	private function migrateCountryCodes(): void
	{
		// Převod starých 3písmenných kódů států (CZE, ENG, BEL…) na nové 2písmenné
		// kódy číselníku #__joomleague_country (= názvy vlajek: cz, gb-eng, be…).
		$map = ['ABW'=>'aw', 'AFG'=>'af', 'AGO'=>'ao', 'AIA'=>'ai', 'ALA'=>'ax', 'ALB'=>'al', 'AND'=>'ad', 'ARE'=>'ae', 'ARG'=>'ar', 'ARM'=>'am', 'ASM'=>'as', 'ATA'=>'aq', 'ATF'=>'tf', 'ATG'=>'ag', 'AUS'=>'au', 'AUT'=>'at', 'AZE'=>'az', 'BDI'=>'bi', 'BEL'=>'be', 'BEN'=>'bj', 'BES'=>'bq', 'BFA'=>'bf', 'BGD'=>'bd', 'BGR'=>'bg', 'BHR'=>'bh', 'BHS'=>'bs', 'BIH'=>'ba', 'BLM'=>'bl', 'BLR'=>'by', 'BLZ'=>'bz', 'BMU'=>'bm', 'BOL'=>'bo', 'BRA'=>'br', 'BRB'=>'bb', 'BRN'=>'bn', 'BTN'=>'bt', 'BVT'=>'bv', 'BWA'=>'bw', 'CAF'=>'cf', 'CAN'=>'ca', 'CCK'=>'cc', 'CHE'=>'ch', 'CHL'=>'cl', 'CHN'=>'cn', 'CIV'=>'ci', 'CMR'=>'cm', 'COD'=>'cd', 'COG'=>'cg', 'COK'=>'ck', 'COL'=>'co', 'COM'=>'km', 'CPV'=>'cv', 'CRI'=>'cr', 'CUB'=>'cu', 'CUW'=>'cw', 'CXR'=>'cx', 'CYM'=>'ky', 'CYP'=>'cy', 'CZE'=>'cz', 'DEU'=>'de', 'DJI'=>'dj', 'DMA'=>'dm', 'DNK'=>'dk', 'DOM'=>'do', 'DZA'=>'dz', 'ECU'=>'ec', 'EGY'=>'eg', 'ENG'=>'gb-eng', 'ERI'=>'er', 'ESH'=>'eh', 'ESP'=>'es', 'EST'=>'ee', 'ETH'=>'et', 'FIN'=>'fi', 'FJI'=>'fj', 'FLK'=>'fk', 'FRA'=>'fr', 'FRO'=>'fo', 'FSM'=>'fm', 'GAB'=>'ga', 'GBR'=>'gb', 'GEO'=>'ge', 'GGY'=>'gg', 'GHA'=>'gh', 'GIB'=>'gi', 'GIN'=>'gn', 'GLP'=>'gp', 'GMB'=>'gm', 'GNB'=>'gw', 'GNQ'=>'gq', 'GRC'=>'gr', 'GRD'=>'gd', 'GRL'=>'gl', 'GTM'=>'gt', 'GUF'=>'gf', 'GUM'=>'gu', 'GUY'=>'gy', 'HKG'=>'hk', 'HMD'=>'hm', 'HND'=>'hn', 'HRV'=>'hr', 'HTI'=>'ht', 'HUN'=>'hu', 'IDN'=>'id', 'IMN'=>'im', 'IND'=>'in', 'IOT'=>'io', 'IRL'=>'ie', 'IRN'=>'ir', 'IRQ'=>'iq', 'ISL'=>'is', 'ISR'=>'il', 'ITA'=>'it', 'JAM'=>'jm', 'JEY'=>'je', 'JOR'=>'jo', 'JPN'=>'jp', 'KAZ'=>'kz', 'KEN'=>'ke', 'KGZ'=>'kg', 'KHM'=>'kh', 'KIR'=>'ki', 'KNA'=>'kn', 'KOR'=>'kr', 'KWT'=>'kw', 'LAO'=>'la', 'LBN'=>'lb', 'LBR'=>'lr', 'LBY'=>'ly', 'LCA'=>'lc', 'LIE'=>'li', 'LKA'=>'lk', 'LSO'=>'ls', 'LTU'=>'lt', 'LUX'=>'lu', 'LVA'=>'lv', 'MAC'=>'mo', 'MAF'=>'mf', 'MAR'=>'ma', 'MCO'=>'mc', 'MDA'=>'md', 'MDG'=>'mg', 'MDV'=>'mv', 'MEX'=>'mx', 'MHL'=>'mh', 'MKD'=>'mk', 'MLI'=>'ml', 'MLT'=>'mt', 'MMR'=>'mm', 'MNE'=>'me', 'MNG'=>'mn', 'MNP'=>'mp', 'MOZ'=>'mz', 'MRT'=>'mr', 'MSR'=>'ms', 'MTQ'=>'mq', 'MUS'=>'mu', 'MWI'=>'mw', 'MYS'=>'my', 'MYT'=>'yt', 'NAM'=>'na', 'NCL'=>'nc', 'NER'=>'ne', 'NFK'=>'nf', 'NGA'=>'ng', 'NIC'=>'ni', 'NIR'=>'gb-nir', 'NIU'=>'nu', 'NLD'=>'nl', 'NOR'=>'no', 'NPL'=>'np', 'NRU'=>'nr', 'NZL'=>'nz', 'OMN'=>'om', 'PAK'=>'pk', 'PAN'=>'pa', 'PCN'=>'pn', 'PER'=>'pe', 'PHL'=>'ph', 'PLW'=>'pw', 'PNG'=>'pg', 'POL'=>'pl', 'PRI'=>'pr', 'PRK'=>'kp', 'PRT'=>'pt', 'PRY'=>'py', 'PSE'=>'ps', 'PYF'=>'pf', 'QAT'=>'qa', 'RCS'=>'cz', 'REU'=>'re', 'ROU'=>'ro', 'RUS'=>'ru', 'RWA'=>'rw', 'SAU'=>'sa', 'SCO'=>'gb-sct', 'SDN'=>'sd', 'SEN'=>'sn', 'SGP'=>'sg', 'SGS'=>'gs', 'SHN'=>'sh', 'SJM'=>'sj', 'SLB'=>'sb', 'SLE'=>'sl', 'SLV'=>'sv', 'SMR'=>'sm', 'SOM'=>'so', 'SPM'=>'pm', 'SRB'=>'rs', 'SSD'=>'ss', 'STP'=>'st', 'SUR'=>'sr', 'SVK'=>'sk', 'SVN'=>'si', 'SWE'=>'se', 'SWZ'=>'sz', 'SXM'=>'sx', 'SYC'=>'sc', 'SYR'=>'sy', 'TCA'=>'tc', 'TCD'=>'td', 'TCH'=>'cz', 'TGO'=>'tg', 'THA'=>'th', 'TJK'=>'tj', 'TKL'=>'tk', 'TKM'=>'tm', 'TLS'=>'tl', 'TON'=>'to', 'TTO'=>'tt', 'TUN'=>'tn', 'TUR'=>'tr', 'TUV'=>'tv', 'TWN'=>'tw', 'TZA'=>'tz', 'UGA'=>'ug', 'UKR'=>'ua', 'UMI'=>'um', 'URY'=>'uy', 'USA'=>'us', 'UZB'=>'uz', 'VAT'=>'va', 'VCT'=>'vc', 'VEN'=>'ve', 'VGB'=>'vg', 'VIR'=>'vi', 'VNM'=>'vn', 'VUT'=>'vu', 'WAL'=>'gb-wls', 'WLF'=>'wf', 'WSM'=>'ws', 'XKK'=>'xk', 'YEM'=>'ye', 'ZAF'=>'za', 'ZMB'=>'zm', 'ZWE'=>'zw'];

		try {
			$db = Factory::getContainer()->get(DatabaseInterface::class);
		} catch (\Throwable $exception) {
			return;
		}

		$columns = [
			['#__joomleague_club', 'country'],
			['#__joomleague_league', 'country'],
			['#__joomleague_playground', 'country'],
			['#__joomleague_person', 'country'],
			['#__joomleague_person', 'address_country'],
		];

		foreach ($columns as [$table, $column]) {
			$qcol = $db->quoteName($column);

			// Pojistka: rozšířit sloupec (gb-eng = 6 znaků) před migrací.
			try {
				$db->setQuery('ALTER TABLE ' . $db->quoteName($table) . ' MODIFY ' . $qcol . ' varchar(6) DEFAULT NULL')->execute();
			} catch (\Throwable $exception) {
				// Sloupec už může být širší – ignorujeme.
			}

			$cases = '';
			$in = [];

			foreach ($map as $iso3 => $code) {
				$cases .= ' WHEN ' . $db->quote($iso3) . ' THEN ' . $db->quote($code);
				$in[]   = $db->quote($iso3);
			}

			$query = 'UPDATE ' . $db->quoteName($table)
				. ' SET ' . $qcol . ' = CASE UPPER(' . $qcol . ')' . $cases . ' ELSE ' . $qcol . ' END'
				. ' WHERE UPPER(' . $qcol . ') IN (' . implode(',', $in) . ')';

			try {
				$db->setQuery($query)->execute();
			} catch (\Throwable $exception) {
				// Migrace jednotlivého sloupce není kritická.
			}
		}
	}

	private function removeLegacyFlagPngs(): void
	{
		// Stará 16×11 PNG sada vlajek byla nahrazena novou SVG sadou.
		// Pojistka: při updatu smaže zbylé .png vlajky, aby nekoexistovaly s .svg.
		$dir = JPATH_ROOT . '/images/com_joomleague/flags';

		if (!is_dir($dir)) {
			return;
		}

		foreach (glob($dir . '/*.png') ?: [] as $png) {
			@unlink($png);
		}
	}

	private function removeObsoleteFiles(): void
	{
		foreach ([
			'/administrator/components/com_joomleague/tmpl/club/edit.php',
			'/administrator/components/com_joomleague/tmpl/league/edit.php',
			'/administrator/components/com_joomleague/tmpl/playground/edit.php',
			'/administrator/components/com_joomleague/tmpl/season/edit.php',
			'/administrator/components/com_joomleague/tmpl/sportstype/edit.php',
		] as $file) {
			$path = JPATH_ROOT . $file;

			if (is_file($path) && !unlink($path)) {
				throw new RuntimeException(Text::sprintf('COM_JOOMLEAGUE_INSTALL_ERROR_DELETE_FILE', $path));
			}
		}
	}

	private function copyDirectory(string $source, string $destination): void
	{
		if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
			throw new RuntimeException(Text::sprintf('COM_JOOMLEAGUE_INSTALL_ERROR_CREATE_DIRECTORY', $destination));
		}

		$iterator = new FilesystemIterator($source, FilesystemIterator::SKIP_DOTS);

		foreach ($iterator as $item) {
			$target = $destination . '/' . $item->getFilename();

			if ($item->isDir()) {
				$this->copyDirectory($item->getPathname(), $target);
				continue;
			}

			if (!$item->isFile() || !copy($item->getPathname(), $target)) {
				throw new RuntimeException(Text::sprintf('COM_JOOMLEAGUE_INSTALL_ERROR_COPY_FILE', $item->getPathname()));
			}
		}
	}

};
