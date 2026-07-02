<?php

/**
 * Sdílený, přepisovatelný layout vlajky státu.
 * Používá frontend (detail klubu, osoby, stadionu) i admin (přes stejný obrázek).
 * Override v šabloně:
 *   templates/<sablona>/html/layouts/joomleague/flag.php
 *
 * @var array $displayData ['code' => string, 'showName' => bool, 'size' => int]
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$code = strtolower(trim((string) ($displayData['code'] ?? '')));

if ($code === '') {
	return;
}

$showName = $displayData['showName'] ?? true;
$width    = (int) ($displayData['size'] ?? 20);
$height   = (int) round($width * 2 / 3); // poměr vlajek 3:2

$name = Text::_('COM_JOOMLEAGUE_COUNTRY_' . strtoupper(str_replace('-', '_', $code)));
$src  = Uri::root(true) . '/images/com_joomleague/flags/' . $code . '.svg';
?>
<span class="jl-flag" style="display:inline-flex;align-items:center;gap:.4rem">
	<img class="jl-flag-img" src="<?php echo htmlspecialchars($src, ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" loading="lazy" style="border:1px solid rgba(0,0,0,.15);border-radius:2px;vertical-align:middle;object-fit:cover">
	<?php if ($showName) : ?><span class="jl-flag-name"><?php echo htmlspecialchars($name, ENT_QUOTES); ?></span><?php endif; ?>
</span>
