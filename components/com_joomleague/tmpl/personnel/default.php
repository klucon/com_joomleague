<?php

declare(strict_types=1);

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$data = $this->personnel;
?>
<div class="com-joomleague-personnel">
	<?php if (isset($data['error'])) : ?>
		<div class="alert alert-warning"><?php echo Text::_($data['error']); ?></div>
	<?php else : ?>
		<header class="border-bottom pb-3 mb-4">
			<h1 class="mb-1"><?php echo Text::_('COM_JOOMLEAGUE_PERSONNEL_VIEW_TITLE'); ?></h1>
			<div class="text-body-secondary"><?php echo htmlspecialchars((string) $data['project']->name, ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">&middot;</span> <?php echo htmlspecialchars((string) $data['project']->sport_type_name, ENT_QUOTES, 'UTF-8'); ?></div>
		</header>
		<?php if ($data['groups']['staff'] === [] && $data['groups']['official'] === []) : ?>
			<div class="alert alert-info"><?php echo Text::_('COM_JOOMLEAGUE_PERSONNEL_EMPTY'); ?></div>
		<?php endif; ?>
		<?php foreach (['staff', 'official'] as $groupCode) : if ($data['groups'][$groupCode] === []) continue; ?>
			<section class="mb-5">
				<h2 class="h4 mb-3"><?php echo Text::_('COM_JOOMLEAGUE_PERSONNEL_GROUP_' . strtoupper($groupCode)); ?></h2>
				<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
					<?php foreach ($data['groups'][$groupCode] as $person) : ?>
						<div class="col"><article class="card h-100"><div class="card-body d-flex gap-3 align-items-start">
							<?php if ($person->picture) : ?><img src="<?php echo htmlspecialchars((string) $person->picture, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="64" height="64" class="img-fluid" loading="lazy"><?php else : ?><span class="icon-user fs-2 text-body-secondary" aria-hidden="true"></span><?php endif; ?>
							<div class="flex-grow-1"><h3 class="h5 mb-1"><a class="stretched-link text-decoration-none" href="<?php echo Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $person->person_id); ?>"><?php echo htmlspecialchars((string) $person->name, ENT_QUOTES, 'UTF-8'); ?></a></h3>
								<?php if ($person->nickname !== '') : ?><div class="text-body-secondary mb-1"><?php echo htmlspecialchars((string) $person->nickname, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
								<div class="small fw-semibold"><?php echo $person->role_label !== '' ? Text::_($person->role_label) : htmlspecialchars((string) $person->role_code, ENT_QUOTES, 'UTF-8'); ?></div>
								<?php if ($groupCode === 'staff' && $person->entry_count > 0) : ?><div class="small text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSONNEL_ENTRY_COUNT', (int) $person->entry_count); ?></div><?php endif; ?>
								<?php if ($groupCode === 'official') : ?><div class="small text-body-secondary"><?php echo Text::sprintf('COM_JOOMLEAGUE_PERSONNEL_ASSIGNMENT_COUNT', (int) $person->assignment_count); ?></div><?php endif; ?>
							</div>
						</div></article></div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
