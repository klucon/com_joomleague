<?php
declare(strict_types=1);
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$data = $displayData['data'];
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<?php foreach ($data['breakdowns'] as $dimension => $rows) : if ($rows === []) continue; ?>
<section class="my-4" data-rival-breakdown="<?php echo $escape($dimension); ?>">
	<h3 class="h5"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_BREAKDOWN_' . strtoupper($dimension)); ?></h3>
	<div class="table-responsive"><table class="table table-striped align-middle">
		<thead><tr><th><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_BREAKDOWN_' . strtoupper($dimension)); ?></th><?php foreach (['played', 'wins', 'draws', 'losses', 'score'] as $column) : ?><th class="text-center"><?php echo Text::_('COM_JOOMLEAGUE_CLUBRIVALS_' . strtoupper($column)); ?></th><?php endforeach; ?></tr></thead>
		<tbody><?php foreach ($rows as $row) :
			$label = match ($dimension) {
				'venue' => Text::_('COM_JOOMLEAGUE_CLUBRIVALS_VENUE_' . strtoupper((string) $row['key'])),
				'project_type' => ($displayData['project_type_label'])((string) $row['key']),
				default => $row['name'],
			};
		?><tr><th scope="row"><?php echo $escape($label); ?></th><?php foreach (['played', 'wins', 'draws', 'losses'] as $column) : ?><td class="text-center"><?php echo (int) $row[$column]; ?></td><?php endforeach; ?><td class="text-center"><?php echo $escape($row['scored'] . ':' . $row['conceded']); ?></td></tr><?php endforeach; ?></tbody>
	</table></div>
</section>
<?php endforeach; ?>
