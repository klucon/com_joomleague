<?php
declare(strict_types=1);
$root = dirname(__DIR__);
if (!class_exists(ZipArchive::class)) throw new RuntimeException('PHP ZIP extension required.');
$destination = $argv[1] ?? ($root . '/dist/com_kluconcountries-0.1.0-dev.zip');
if (!is_dir(dirname($destination))) mkdir(dirname($destination), 0755, true);
$zip = new ZipArchive();
if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Cannot create archive.');
foreach (['admin', 'media'] as $directory) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if(str_contains($file->getPathname(),'/language/cs-CZ/'))continue;
        if ($file->isLink()) throw new RuntimeException('Symlinks are not permitted.');
        if ($file->isFile()) $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen($root) + 1));
    }
}
foreach (['cz.json', 'world.json', 'continents.json', 'continent-facts.json', 'flags-manifest.json', 'countries.json', 'iso-codes-copyright.txt', 'iso-codes-LGPL-2.1.txt'] as $name) $zip->addFile($root . '/data/' . $name, 'admin/data/' . $name);
foreach (['kluconcountries.xml', 'script.php', 'THIRD-PARTY-NOTICES.md'] as $name) $zip->addFile($root . '/' . $name, $name);
$zip->close();
file_put_contents($destination.'.sha256',hash_file('sha256',$destination).'  '.basename($destination)."\n");
echo $destination . PHP_EOL;
