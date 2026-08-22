<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$report = $this->report;
$severityClasses = ['error' => 'danger', 'warning' => 'warning', 'info' => 'info', 'success' => 'success'];
?>
<div class="container-fluid">
	<div class="alert alert-<?php echo $report['ready'] ? 'success' : 'danger'; ?>" role="status">
		<h2 class="h4 mb-2"><?php echo Text::_($report['ready'] ? 'COM_JOOMLEAGUE_PREFLIGHT_READY' : 'COM_JOOMLEAGUE_PREFLIGHT_NOT_READY'); ?></h2>
		<p class="mb-0"><?php echo Text::sprintf('COM_JOOMLEAGUE_PREFLIGHT_SUMMARY', $report['summary']['error'], $report['summary']['warning']); ?></p>
	</div>
	<div class="row g-4">
	<?php foreach ($report['sections'] as $section) : ?>
		<div class="col-12 col-xl-6">
			<div class="card h-100">
				<div class="card-header d-flex align-items-center gap-2"><span class="badge bg-<?php echo $severityClasses[$section['status']]; ?>"><span class="icon-<?php echo $section['status'] === 'success' ? 'check' : ($section['status'] === 'error' ? 'times' : 'exclamation-triangle'); ?>" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('COM_JOOMLEAGUE_PREFLIGHT_SEVERITY_' . strtoupper($section['status'])); ?></span></span><h2 class="h5 mb-0"><?php echo Text::_($section['label']); ?></h2></div>
				<div class="card-body">
					<?php if ($section['checks'] === []) : ?><p class="text-success mb-0"><span class="icon-check me-2" aria-hidden="true"></span><?php echo Text::_('COM_JOOMLEAGUE_PREFLIGHT_SECTION_OK'); ?></p>
					<?php else : ?><ul class="list-group list-group-flush"><?php foreach ($section['checks'] as $check) : ?><li class="list-group-item px-0"><div class="d-flex gap-2"><span class="badge bg-<?php echo $severityClasses[$check['severity']]; ?> align-self-start"><?php echo Text::_('COM_JOOMLEAGUE_PREFLIGHT_SEVERITY_' . strtoupper($check['severity'])); ?></span><div class="flex-grow-1"><div><?php echo Text::sprintf($check['message'], ...$check['arguments']); ?></div><a href="<?php echo Route::_($check['url']); ?>"><?php echo Text::_('COM_JOOMLEAGUE_PREFLIGHT_RESOLVE'); ?></a></div></div></li><?php endforeach; ?></ul><?php endif; ?>
				</div>
				<div class="card-footer small text-body-secondary"><?php $parts=[];foreach($section['metrics'] as$key=>$value)$parts[]=Text::sprintf('COM_JOOMLEAGUE_PREFLIGHT_METRIC',Text::_('COM_JOOMLEAGUE_PREFLIGHT_METRIC_'.strtoupper($key)),$value);echo implode(' · ',$parts); ?></div>
			</div>
		</div>
	<?php endforeach; ?>
	</div>
	<p class="small text-body-secondary mt-4 mb-0"><?php echo Text::sprintf('COM_JOOMLEAGUE_PREFLIGHT_CHECKED_AT', $this->escape($report['checked_at'])); ?></p>
</div>
