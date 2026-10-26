<?php
declare(strict_types=1);
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomleague\Module\Eventranking\Site\Helper\DisplayOptions;
defined('_JEXEC') or die;
$display = DisplayOptions::from($params);
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$empty = !isset($ranking['error']) && ($ranking['definitions'] === [] || $ranking['rows'] === []);
if ($empty && !$display['show_empty']) return;
$eventName = '';
foreach ($ranking['definitions'] ?? [] as $definition) {
    if ($definition->event_code === ($ranking['selected_code'] ?? null)) $eventName = Text::_($definition->name_key);
}
?>
<div class="mod-joomleague-eventranking<?php echo $escape($params->get('moduleclass_sfx', '')); ?>">
<?php if (isset($ranking['error'])): ?>
    <div class="alert alert-info mb-0"><?php echo $escape(Text::_($ranking['error'])); ?></div>
<?php elseif ($empty): ?>
    <div class="alert alert-info mb-0"><?php echo $escape(Text::_('MOD_JOOMLEAGUE_EVENTRANKING_EMPTY')); ?></div>
<?php else: ?>
    <?php if ($display['show_project_name']): ?><div class="fw-bold mb-2"><?php echo $escape($ranking['project']->name); ?></div><?php endif; ?>
    <?php if ($display['show_event_name']): ?><div class="jl-event-heading mb-2"><?php echo $escape($eventName); ?></div><?php endif; ?>
    <div class="list-group list-group-flush<?php echo $display['orientation'] === 'horizontal' ? ' list-group-horizontal flex-wrap' : ''; ?>">
    <?php foreach ($ranking['rows'] as $row):
        $url = match ((string) $row->target_kind) {
            'person' => 'index.php?option=com_joomleague&view=person&person_id=' . (int) $row->target_id,
            'team' => 'index.php?option=com_joomleague&view=team&team_id=' . (int) $row->target_id,
            default => 'index.php?option=com_joomleague&view=participant&project_id=' . (int) $ranking['project']->id . '&entry_id=' . (int) $row->target_id,
        };
        $tag = $display['link_target'] ? 'a' : 'div';
        $highlighted = $display['highlight_target'] === ((string) $row->target_kind . ':' . (int) $row->target_id);
        ?>
        <<?php echo $tag; ?> class="list-group-item d-flex align-items-center gap-2<?php echo $highlighted ? ' list-group-item-primary fw-bold' : ''; ?><?php echo $display['link_target'] ? ' list-group-item-action' : ''; ?><?php echo $display['density'] === 'compact' ? ' py-1' : ' py-2'; ?><?php echo $display['orientation'] === 'horizontal' ? ' flex-grow-1' : ' px-0'; ?>"<?php if ($display['link_target']): ?> href="<?php echo $escape(Route::_($url)); ?>"<?php endif; ?>>
            <?php if ($display['show_images'] && !empty($row->image)): ?><img class="jl-event-image object-fit-contain flex-shrink-0" src="<?php echo $escape($row->image); ?>" alt="" width="<?php echo $display['image_size']; ?>" height="<?php echo $display['image_size']; ?>" loading="lazy"><?php endif; ?>
            <?php if ($display['show_rank']): ?><span class="jl-event-rank badge text-bg-secondary rounded-pill"><?php echo (int) $row->rank; ?></span><?php endif; ?>
            <span class="flex-grow-1 text-break"><?php echo $escape($row->display_name); ?></span>
            <?php if ($display['show_value']): ?><strong class="jl-event-value"><?php echo (int) $row->total_value; ?></strong><?php endif; ?>
            <?php if ($display['show_value'] && $display['show_unit']): ?><span class="jl-event-unit small"><?php echo $escape(Text::_('MOD_JOOMLEAGUE_EVENTRANKING_OCCURRENCES')); ?></span><?php endif; ?>
            <?php if ($display['show_appearances']): ?><span class="jl-event-appearances small text-nowrap"><?php echo $escape(Text::sprintf('MOD_JOOMLEAGUE_EVENTRANKING_APPEARANCES', (int) $row->appearances)); ?></span><?php endif; ?>
        </<?php echo $tag; ?>>
    <?php endforeach; ?>
    </div>
    <?php if ($display['show_all_link']): ?><a class="btn btn-sm btn-outline-secondary mt-2" href="<?php echo $escape(Route::_('index.php?option=com_joomleague&view=eventranking&project_id=' . (int) $ranking['project']->id . '&event_code=' . rawurlencode((string) $ranking['selected_code']))); ?>"><?php echo $escape(Text::_('MOD_JOOMLEAGUE_EVENTRANKING_SHOW_ALL')); ?></a><?php endif; ?>
<?php endif; ?>
</div>
