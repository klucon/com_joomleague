<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Languages\HtmlView $this */

$style = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.14.0';
$translateUrl = 'https://translate.klucon.cz';
$token = Session::getFormToken();

$formatBytes = static function (int $size): string {
	if ($size <= 0) {
		return '-';
	}

	$units = ['B', 'KB', 'MB', 'GB'];
	$value = (float) $size;

	foreach ($units as $unit) {
		if ($value < 1024 || $unit === 'GB') {
			return $unit === 'B' ? (int) $value . ' ' . $unit : number_format($value, 1, '.', ' ') . ' ' . $unit;
		}

		$value /= 1024;
	}

	return number_format($size, 0, ',', ' ') . ' B';
};

$formatDate = static function (string $value): string {
	if ($value === '') {
		return '-';
	}

	try {
		return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Europe/Prague'))->format('Y-m-d H:i T');
	} catch (Exception) {
		return $value;
	}
};
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
<div class="com-joomleague-dashboard com-joomleague-workflow">
	<div class="jl-section-panel jl-language-hero mb-4">
		<span class="jl-section-panel__icon icon-comments" aria-hidden="true"></span>
		<div class="jl-section-panel__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_EYEBROW'); ?></p>
			<h1 class="h3 mb-2"><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_TITLE'); ?></h1>
			<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_DESC'); ?></p>
		</div>
		<div class="jl-language-hero__actions">
			<a class="btn btn-primary" href="<?php echo htmlspecialchars($translateUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_HELP_TRANSLATE'); ?>
			</a>
		</div>
	</div>

	<div class="jl-language-summary mb-4">
		<div class="jl-language-summary__item">
			<span><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_SOURCE'); ?></span>
			<strong><?php echo $this->escape((string) ($this->summary['source'] ?? 'en-GB')); ?></strong>
		</div>
		<div class="jl-language-summary__item">
			<span><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_SOURCE_STRINGS'); ?></span>
			<strong><?php echo number_format((int) ($this->summary['source_total'] ?? 0), 0, ',', ' '); ?></strong>
		</div>
		<div class="jl-language-summary__item">
			<span><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_AVAILABLE'); ?></span>
			<strong><?php echo number_format((int) ($this->summary['available'] ?? 0), 0, ',', ' '); ?></strong>
		</div>
		<div class="jl-language-summary__item">
			<span><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_INSTALLED'); ?></span>
			<strong><?php echo number_format((int) ($this->summary['installed'] ?? 0), 0, ',', ' '); ?></strong>
		</div>
	</div>

	<div class="table-responsive">
		<table class="table table-striped align-middle jl-language-table">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_LANGUAGE'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_CODE'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_STATUS'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_PACKAGE_VERSION'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_PACKAGE_UPDATED'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_PACKAGE_SIZE'); ?></th>
					<th><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_PACKAGE_SHA256'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_TRANSLATED'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_MISSING'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_OBSOLETE'); ?></th>
					<th class="text-end"><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_ACTIONS'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->languages as $language) : ?>
					<?php
					$installed = (bool) $language['installed'];
					$source = (bool) $language['source'];
					$percent = (int) $language['percent'];
					$showStats = $installed || $source;
					?>
					<tr>
						<th scope="row">
							<?php echo $this->escape((string) $language['name']); ?>
							<?php if ($source) : ?>
								<span class="badge text-bg-primary ms-2"><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_SOURCE_BADGE'); ?></span>
							<?php endif; ?>
						</th>
						<td><code><?php echo $this->escape((string) $language['tag']); ?></code></td>
						<td>
							<?php if ($showStats) : ?>
								<div class="jl-language-progress" aria-label="<?php echo Text::sprintf('COM_JOOMLEAGUE_LANGUAGES_PROGRESS_LABEL', $percent); ?>">
									<span style="width: <?php echo max(0, min(100, $percent)); ?>%"></span>
								</div>
							<?php endif; ?>
							<span class="jl-language-status-text">
								<?php echo $installed ? Text::_('COM_JOOMLEAGUE_LANGUAGES_STATUS_INSTALLED') : Text::_('COM_JOOMLEAGUE_LANGUAGES_STATUS_NOT_INSTALLED'); ?>
							</span>
						</td>
						<td>
							<?php echo $language['package_available'] ? $this->escape((string) $language['package_version']) : '<span class="text-muted">-</span>'; ?>
						</td>
						<td>
							<?php echo $language['package_available'] ? $this->escape($formatDate((string) $language['package_updated'])) : '<span class="text-muted">-</span>'; ?>
						</td>
						<td class="text-end">
							<?php echo $language['package_available'] ? $this->escape($formatBytes((int) $language['package_size'])) : '<span class="text-muted">-</span>'; ?>
						</td>
						<td>
							<?php if ($language['package_available'] && (string) $language['package_sha256'] !== '') : ?>
								<code title="<?php echo $this->escape((string) $language['package_sha256']); ?>"><?php echo $this->escape(substr((string) $language['package_sha256'], 0, 12)); ?></code>
							<?php else : ?>
								<span class="text-muted">-</span>
							<?php endif; ?>
						</td>
						<?php if ($showStats) : ?>
							<td class="text-end"><?php echo number_format((int) $language['translated'], 0, ',', ' '); ?> / <?php echo number_format((int) $language['source_total'], 0, ',', ' '); ?> (<?php echo $percent; ?>%)</td>
							<td class="text-end"><?php echo number_format((int) $language['missing'], 0, ',', ' '); ?></td>
							<td class="text-end"><?php echo number_format((int) $language['obsolete'], 0, ',', ' '); ?></td>
						<?php else : ?>
							<td class="text-end text-muted"><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_NOT_INSTALLED_STATS'); ?></td>
							<td class="text-end text-muted">-</td>
							<td class="text-end text-muted">-</td>
						<?php endif; ?>
						<td class="text-end">
							<?php if ($source) : ?>
								<button type="button" class="btn btn-sm btn-outline-secondary" disabled><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_ACTION_LOCKED'); ?></button>
							<?php elseif ($installed) : ?>
								<a
									class="btn btn-sm btn-outline-primary"
									href="<?php echo Route::_('index.php?option=com_joomleague&task=languages.download&tag=' . rawurlencode((string) $language['tag']) . '&' . $token . '=1'); ?>"
								><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_ACTION_UPDATE'); ?></a>
								<a
									class="btn btn-sm btn-outline-danger"
									href="<?php echo Route::_('index.php?option=com_joomleague&task=languages.remove&tag=' . rawurlencode((string) $language['tag']) . '&' . $token . '=1'); ?>"
									onclick="return confirm('<?php echo $this->escape(Text::sprintf('COM_JOOMLEAGUE_LANGUAGES_REMOVE_CONFIRM', (string) $language['tag'])); ?>');"
								><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_ACTION_REMOVE'); ?></a>
							<?php else : ?>
								<a
									class="btn btn-sm btn-outline-primary"
									href="<?php echo Route::_('index.php?option=com_joomleague&task=languages.download&tag=' . rawurlencode((string) $language['tag']) . '&' . $token . '=1'); ?>"
								><?php echo Text::_('COM_JOOMLEAGUE_LANGUAGES_ACTION_DOWNLOAD'); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
