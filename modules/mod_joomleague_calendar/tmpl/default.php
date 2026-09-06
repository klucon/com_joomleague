<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$moduleclassSfx = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$locale = str_replace('-', '_', Factory::getLanguage()->getTag());
$dateFormatter = new IntlDateFormatter($locale, IntlDateFormatter::FULL, IntlDateFormatter::NONE);
$timeFormatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
?>
<div class="mod-joomleague-calendar<?php echo $moduleclassSfx; ?>">
	<?php if (isset($calendar['error'])) : ?>
		<div class="alert alert-info mb-0"><?php echo Text::_($calendar['error']); ?></div>
	<?php else : ?>
		<?php foreach ($calendar['groups'] as $group) : ?>
			<section class="mb-3">
				<h3 class="h6 border-bottom pb-2 mb-0"><?php echo htmlspecialchars((string) $dateFormatter->format(strtotime($group['date'] . ' 12:00:00 UTC')), ENT_QUOTES, 'UTF-8'); ?></h3>
				<div class="list-group list-group-flush">
					<?php foreach ($group['items'] as $item) : ?>
						<?php $tag = $item['show_detail'] ? 'a' : 'div'; ?>
						<<?php echo $tag; ?> class="list-group-item<?php echo $item['show_detail'] ? ' list-group-item-action' : ''; ?> px-0"<?php echo $item['show_detail'] ? ' href="' . htmlspecialchars(Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item['id']), ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
							<div class="d-flex justify-content-between gap-3">
								<div>
									<?php if ((int) $params->get('show_project', 1) === 1) : ?><div class="small text-body-secondary"><?php echo htmlspecialchars((string) $item['project_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
									<div class="fw-semibold">
										<?php foreach ($item['participants'] as $index => $participant) : ?>
											<?php if ($index > 0) : ?><span class="text-body-secondary mx-1">·</span><?php endif; ?>
											<?php echo htmlspecialchars((string) $participant['name'], ENT_QUOTES, 'UTF-8'); ?>
											<?php if ((int) $params->get('show_result', 1) === 1 && $participant['score'] !== null) : ?><span class="badge text-bg-secondary"><?php echo htmlspecialchars((string) $participant['score'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
										<?php endforeach; ?>
									</div>
									<?php if ((int) $params->get('show_venue', 1) === 1 && $item['venue_name']) : ?><div class="small text-body-secondary"><span class="icon-location" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $item['venue_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
								</div>
								<time class="text-nowrap" datetime="<?php echo htmlspecialchars((string) $item['scheduled_start'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $timeFormatter->format((int) $item['timestamp']), ENT_QUOTES, 'UTF-8'); ?></time>
							</div>
						</<?php echo $tag; ?>>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
