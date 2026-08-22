<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$formatValues = static function (array $values): string {
	if ($values === []) {
		return '<span class="text-body-secondary">' . Text::_('COM_JOOMLEAGUE_TEMPLATES_NO_OVERRIDES') . '</span>';
	}

	$output = [];

	foreach ($values as $key => $value) {
		$display = is_bool($value) ? Text::_($value ? 'JYES' : 'JNO') : (string) $value;
		$output[] = '<span class="badge text-bg-light border me-1 mb-1">'
			. htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . ': '
			. htmlspecialchars($display, ENT_QUOTES, 'UTF-8') . '</span>';
	}

	return implode('', $output);
};
?>
<div class="container-fluid">
	<div class="alert alert-info" role="alert">
		<h2 class="h5 alert-heading"><?php echo Text::_('COM_JOOMLEAGUE_TEMPLATES_SCOPE_TITLE'); ?></h2>
		<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_TEMPLATES_SCOPE_DESC'); ?></p>
	</div>

	<div class="row row-cols-2 row-cols-lg-4 g-3 mb-4">
		<?php foreach ([
			['profiles', 'COM_JOOMLEAGUE_TEMPLATES_METRIC_PROFILES'],
			['definitions', 'COM_JOOMLEAGUE_TEMPLATES_METRIC_DEFINITIONS'],
			['templates', 'COM_JOOMLEAGUE_TEMPLATES_METRIC_ASSIGNMENTS'],
			['overrides', 'COM_JOOMLEAGUE_TEMPLATES_METRIC_OVERRIDES'],
		] as [$key, $label]) : ?>
			<div class="col"><div class="card h-100"><div class="card-body">
				<div class="fs-3 fw-semibold"><?php echo (int) ($this->summary[$key] ?? 0); ?></div>
				<div class="text-body-secondary"><?php echo Text::_($label); ?></div>
			</div></div></div>
		<?php endforeach; ?>
	</div>

	<div class="accordion d-grid gap-3" id="templateProfiles">
		<?php foreach ($this->items as $index => $profile) : ?>
			<div class="accordion-item">
				<h2 class="accordion-header" id="profile-heading-<?php echo (int) $index; ?>">
					<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#profile-<?php echo (int) $index; ?>" aria-expanded="false" aria-controls="profile-<?php echo (int) $index; ?>">
						<span class="row g-2 align-items-center flex-grow-1 text-start me-3">
							<strong class="col-12 col-md-6"><?php echo Text::_($profile->name_key); ?></strong>
							<span class="col-7 col-md-4 font-monospace text-body-secondary"><?php echo htmlspecialchars($profile->profile_code, ENT_QUOTES, 'UTF-8'); ?></span>
							<span class="col-5 col-md-2 text-md-end text-body-secondary"><?php echo htmlspecialchars($profile->profile_version, ENT_QUOTES, 'UTF-8'); ?></span>
						</span>
					</button>
				</h2>
				<div id="profile-<?php echo (int) $index; ?>" class="accordion-collapse collapse" aria-labelledby="profile-heading-<?php echo (int) $index; ?>" data-bs-parent="#templateProfiles">
					<div class="accordion-body p-0">
						<?php foreach ($profile->templates as $templateIndex => $template) : ?>
							<section class="p-3<?php echo $templateIndex > 0 ? ' border-top' : ''; ?>">
								<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
									<div>
										<h3 class="h6 mb-1"><?php echo Text::_($template->name_key); ?></h3>
										<p class="small text-body-secondary mb-0"><?php echo Text::_($template->description_key); ?></p>
									</div>
									<span class="badge text-bg-<?php echo $template->overrides === [] ? 'info' : 'warning'; ?>"><?php echo Text::_($template->overrides === [] ? 'COM_JOOMLEAGUE_TEMPLATES_INHERITED' : 'COM_JOOMLEAGUE_TEMPLATES_OVERRIDDEN'); ?></span>
								</div>
								<div class="row row-cols-1 row-cols-lg-3 g-3">
									<div class="col">
										<h4 class="h6 text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_TEMPLATES_COLUMN_BUNDLED'); ?></h4>
										<div><?php echo $formatValues($template->bundled); ?></div>
									</div>
									<div class="col">
										<h4 class="h6 text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_TEMPLATES_COLUMN_OVERRIDE'); ?></h4>
										<div><?php echo $formatValues($template->overrides); ?></div>
									</div>
									<div class="col">
										<h4 class="h6 text-body-secondary"><?php echo Text::_('COM_JOOMLEAGUE_TEMPLATES_COLUMN_EFFECTIVE'); ?></h4>
										<div><?php echo $formatValues($template->effective); ?></div>
									</div>
								</div>
							</section>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
