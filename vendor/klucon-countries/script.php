<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class Com_KluconcountriesInstallerScript
{
    public function preflight(string $type, $parent): bool
    {
        if ($type !== 'uninstall' && (version_compare(JVERSION, '6.0', '<') || version_compare(JVERSION, '7.0', '>='))) {
            throw new RuntimeException('Klucon Countries requires Joomla 6.');
        }
        return true;
    }

    public function postflight(string $type, $parent): void
    {
        if (!in_array($type, ['install', 'update'], true)) return;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->installData($db, JPATH_ADMINISTRATOR . '/components/com_kluconcountries/data/cz.json');
        // Remove only byte-identical files bundled by the original development build.
        // Independently maintained language packages or local overrides are never deleted.
        if(!(int)$db->setQuery('SELECT language_cleanup FROM #__kluconcountries_meta WHERE id=1')->loadResult()){
        foreach(['com_kluconcountries.ini'=>'7e610aa34c46dc395059f71197f72535ae0139d2380484910bb50d60658b7dd6','com_kluconcountries.sys.ini'=>'42c0db3435ddab0f9f81a64c9f5c07d0d616a1a9c0d6fa325d946fae2603c0bc'] as $file=>$hash){
            $path=JPATH_ADMINISTRATOR.'/language/cs-CZ/'.$file;
            if(is_file($path)&&hash_equals($hash,hash_file('sha256',$path))&&!\Joomla\Filesystem\File::delete($path))throw new RuntimeException('Cannot remove the legacy bundled language file.');
        }
        $db->setQuery('UPDATE #__kluconcountries_meta SET language_cleanup=1 WHERE id=1')->execute();
        }
    }

    public function installData(DatabaseInterface $db, string $seedPath): void
    {
        require_once (is_file(__DIR__.'/admin/src/Service/Schema.php')?__DIR__.'/admin/src/Service/Schema.php':JPATH_ADMINISTRATOR.'/components/com_kluconcountries/src/Service/Schema.php');
        \Klucon\Component\Kluconcountries\Administrator\Service\Schema::install($db,$seedPath);
    }
}
