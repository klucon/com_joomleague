<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomleague\Component\Joomleague\Site\Service\StructuredDataHelper;

$item = $this->item;
$jlFlagPath = JPATH_SITE . '/components/com_joomleague/layouts';

if ($item) {
	StructuredDataHelper::add($this->getDocument(), [
		'@context' => 'https://schema.org',
		'@type' => 'SportsActivityLocation',
		'@id' => StructuredDataHelper::currentUrl() . '#venue',
		'name' => (string) $item->name,
		'url' => StructuredDataHelper::absoluteUrl($item->website ?? null),
		'maximumAttendeeCapacity' => !empty($item->max_visitors) ? (int) $item->max_visitors : null,
		'address' => [
			'@type' => 'PostalAddress',
			'streetAddress' => $item->address ?? null,
			'postalCode' => $item->zipcode ?? null,
			'addressLocality' => $item->city ?? null,
			'addressCountry' => $item->country ?? null,
		],
	]);
}
?>
<div class="com-joomleague-site">
	<?php if (!$item) : ?><div class="alert alert-warning"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND_NOT_FOUND'); ?></div><?php return; endif; ?>
	<section class="jl-site-hero mb-4"><div class="jl-site-eyebrow"><?php echo Text::_('COM_JOOMLEAGUE_SITE_PLAYGROUND'); ?></div><h1 class="jl-site-title"><?php echo $this->escape($item->name); ?></h1><p class="jl-site-muted mb-0"><?php echo $this->escape(trim(($item->address ?? '') . ', ' . ($item->zipcode ?? '') . ' ' . ($item->city ?? ''), ' ,')); ?></p></section>
	<div class="jl-site-grid"><div class="jl-site-card"><strong><?php echo $this->escape($item->club_name ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CLUB'); ?></span></div><div class="jl-site-card"><strong><?php echo (int) ($item->max_visitors ?? 0); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_CAPACITY'); ?></span></div><div class="jl-site-card"><strong><?php echo $this->escape($item->website ?: Text::_('COM_JOOMLEAGUE_SITE_NOT_SET')); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_WEBSITE'); ?></span></div><?php if (!empty($item->country)) : ?><div class="jl-site-card"><strong><?php echo LayoutHelper::render('joomleague.flag', ['code' => $item->country], $jlFlagPath); ?></strong><span class="jl-site-muted"><?php echo Text::_('COM_JOOMLEAGUE_SITE_COUNTRY'); ?></span></div><?php endif; ?></div>
</div>
