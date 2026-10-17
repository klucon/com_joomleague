<?php
declare(strict_types=1);

$root = getenv('JOOMLEAGUE_SOURCE_ROOT') ?: dirname(__DIR__, 2);
$manifest = simplexml_load_file($root . '/administrator/components/com_joomleague/joomleague.xml');
if (!$manifest || !in_array('brand', array_map('strval', iterator_to_array($manifest->media->folder)), true)) {
	throw new RuntimeException('Brand assets must be registered for installation.');
}
$asset = $root . '/media/com_joomleague/brand/joomleague-logo.png';
$size = getimagesize($asset);
if (!$size || $size[0] !== 1600 || $size[1] !== 500 || $size[2] !== IMAGETYPE_PNG) {
	throw new RuntimeException('Unexpected wordmark dimensions or format.');
}
foreach (['administrator/components/com_joomleague/tmpl/dashboard/default.php', 'components/com_joomleague/tmpl/aboutjoomleague/default.php'] as $template) {
	$source = file_get_contents($root . '/' . $template);
	if (!str_contains($source, "HTMLHelper::_('image', Uri::root(true) . '/media/com_joomleague/brand/joomleague-logo.png', 'JoomLeague'")
		|| !str_contains($source, 'false, -1)')
		|| !str_contains($source, "'class' => 'img-fluid'")) {
		throw new RuntimeException('Missing responsive local brand image: ' . $template);
	}
}
echo "Brand media manifest and responsive template contract OK\n";
