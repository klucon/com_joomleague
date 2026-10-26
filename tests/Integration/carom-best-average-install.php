<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$setup = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
chdir('/var/www/html');
if (is_file('configuration.php')) throw new RuntimeException('Refusing an existing installation.');
$options = ['site-name' => 'Isolated carom verification', 'admin-user' => 'Synthetic Administrator', 'admin-username' => 'admin', 'admin-password' => $setup['adminPassword'], 'admin-email' => 'audit@example.invalid', 'db-type' => $setup['driver'], 'db-host' => $setup['host'], 'db-user' => $setup['user'], 'db-pass' => $setup['password'], 'db-name' => 'encounter', 'db-prefix' => 'test_', 'db-encryption' => '0', 'public-folder' => ''];
$args = ['/var/www/html/installation/joomla.php', 'install', '--no-interaction'];
foreach ($options as $key => $value) $args[] = '--' . $key . '=' . $value;
$_SERVER['argv'] = $args; $_SERVER['argc'] = count($args);
require '/var/www/html/installation/joomla.php';
