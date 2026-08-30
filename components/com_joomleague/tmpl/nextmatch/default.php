<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$event = $this->event;
?>
<div class="com-joomleague-nextmatch">
	<?php if (isset($event['error'])) : ?>
		<div class="alert alert-info"><?php echo Text::_($event['error']); ?></div>
	<?php else :
		$item = $event['item'];
		$formatter = new IntlDateFormatter(str_replace('-', '_', Factory::getLanguage()->getTag()), IntlDateFormatter::LONG, IntlDateFormatter::SHORT);
		?>
		<a class="card text-bg-primary bg-gradient shadow-sm text-decoration-none" href="<?php echo Route::_('index.php?option=com_joomleague&view=eventreport&event_id=' . (int) $item->id); ?>">
			<div class="card-body">
				<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
					<span class="badge text-bg-light"><?php echo Text::_('COM_JOOMLEAGUE_NEXTMATCH_BADGE'); ?></span>
					<span class="badge rounded-pill text-bg-light"><?php echo htmlspecialchars((string) $item->round_name, ENT_QUOTES, 'UTF-8'); ?></span>
				</div>
				<h1 class="h4 mb-3"><?php echo htmlspecialchars((string) $item->project_name, ENT_QUOTES, 'UTF-8'); ?></h1>
				<div class="d-flex flex-wrap gap-2 mb-3">
					<?php foreach ($event['participants'] as $participant) : ?>
						<span class="badge text-bg-light fs-6"><?php echo htmlspecialchars((string) $participant->display_name, ENT_QUOTES, 'UTF-8'); ?></span>
					<?php endforeach; ?>
				</div>
				<div class="d-flex flex-wrap gap-3">
					<span><span class="icon-calendar" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $formatter->format(strtotime($item->scheduled_start)), ENT_QUOTES, 'UTF-8'); ?></span>
					<?php if ($item->venue_name) : ?><span><span class="icon-location" aria-hidden="true"></span> <?php echo htmlspecialchars((string) $item->venue_name, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
				</div>
			</div>
		</a>
	<?php endif; ?>
</div>
