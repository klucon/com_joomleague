<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_birthday
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                      $list    Birthday rows.
 * @var  \Joomla\Registry\Registry  $params  Module parameters.
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

$showPicture  = (int) $params->get('show_picture', 0) === 1;
$showFlag     = (int) $params->get('show_player_flag', 1) === 1;
$pictureWidth = trim((string) $params->get('picture_width', '120'));
$dayFormat    = (string) ($params->get('dayformat') ?: Text::_('MOD_JOOMLEAGUE_BIRTHDAY_DATE_FORMAT_DEFAULT'));
$birthFormat  = (string) ($params->get('birthdayformat') ?: Text::_('MOD_JOOMLEAGUE_BIRTHDAY_DATE_OF_BIRTH_FORMAT_DEFAULT'));
$futureText   = (string) ($params->get('futuremessage') ?: Text::_('MOD_JOOMLEAGUE_BIRTHDAY_FUTURE_MESSAGE_DEFAULT'));
$todayText    = (string) ($params->get('todaymessage') ?: Text::_('MOD_JOOMLEAGUE_BIRTHDAY_TODAY_MESSAGE_DEFAULT'));
$tomorrowText = (string) ($params->get('tomorrowmessage') ?: Text::_('MOD_JOOMLEAGUE_BIRTHDAY_TOMORROW_MESSAGE_DEFAULT'));
$birthText    = (string) ($params->get('birthdaytext') ?: Text::_('MOD_JOOMLEAGUE_BIRTHDAY_MESSAGE_FOR_BIRTHDAY_DEFAULT'));
$deathText    = (string) ($params->get('deathdaytext') ?: Text::_('MOD_JOOMLEAGUE_BIRTHDAY_MESSAGE_FOR_DEATHDAY_DEFAULT'));
$flagPath     = JPATH_ROOT . '/components/com_joomleague/layouts';

$whenText = static function (int $days) use ($futureText, $todayText, $tomorrowText): string {
    if ($days === 0) {
        return $todayText;
    }

    if ($days === 1) {
        return $tomorrowText;
    }

    return str_replace('%DAYS_TO%', (string) $days, $futureText);
};

$formatMessage = static function (object $row, string $template, string $dateFormat, string $sourceDateFormat, string $when) {
    $replacements = [
        '%NAME%'          => (string) $row->person_name,
        '%AGE%'           => (string) ($row->age ?? ''),
        '%YEARS%'         => (string) ($row->years ?? ''),
        '%WHEN%'          => $when,
        '%DATE%'          => HTMLHelper::_('date', $row->next_date, $dateFormat),
        '%DATE_OF_BIRTH%' => !empty($row->birthday_date) ? HTMLHelper::_('date', $row->birthday_date, $sourceDateFormat) : '',
        '%DATE_OF_DEATH%' => !empty($row->deathday_date) ? HTMLHelper::_('date', $row->deathday_date, $sourceDateFormat) : '',
    ];

    $message = strtr($template, $replacements);
    $parts   = array_map(
        static fn ($part): string => htmlspecialchars($part, ENT_QUOTES, 'UTF-8'),
        explode('%BR%', $message)
    );

    return implode('<br>', $parts);
};

$pictureAttributes = static function (string $width): string {
    if ($width === '') {
        return '';
    }

    if (ctype_digit($width)) {
        return ' width="' . (int) $width . '"';
    }

    if (preg_match('/^\d+%$/', $width) === 1) {
        return ' style="max-width:' . htmlspecialchars($width, ENT_QUOTES, 'UTF-8') . ';"';
    }

    return '';
};

if (empty($list)) {
    $empty = (string) ($params->get('not_found_text') ?: Text::_('MOD_JOOMLEAGUE_BIRTHDAY_NO_BIRTHDAY_MESSAGE_DEFAULT'));
    $empty = str_replace('%DAYS%', htmlspecialchars((string) $params->get('maxdays', ''), ENT_QUOTES, 'UTF-8'), $empty);
    echo '<div class="jl-module jl-birthday-empty text-muted small">' . htmlspecialchars($empty, ENT_QUOTES, 'UTF-8') . '</div>';

    return;
}
?>
<div class="jl-module jl-birthday">
	<ul class="list-unstyled mb-0">
		<?php foreach ($list as $row) :
            $isDeathday = ($row->anniversary_type ?? 'birthday') === 'deathday';
            $url = 'index.php?option=com_joomleague&view=person&id=' . (int) $row->person_id;
            $when = $whenText((int) $row->days_until);
            $message = $formatMessage($row, $isDeathday ? $deathText : $birthText, $dayFormat, $birthFormat, $when);
            $badgeClass = $isDeathday ? 'text-bg-secondary' : 'text-bg-primary';
            $badgeText = $isDeathday ? Text::_('MOD_JOOMLEAGUE_BIRTHDAY_ANNIVERSARY_TYPE_DEATHDAY') : Text::_('MOD_JOOMLEAGUE_BIRTHDAY_ANNIVERSARY_TYPE_BIRTHDAY');
        ?>
			<li class="jl-birthday-item d-flex gap-3 py-2 border-bottom">
				<?php if ($showPicture && !empty($row->person_picture)) : ?>
					<a class="jl-birthday-photo flex-shrink-0" href="<?php echo Route::_($url); ?>" aria-hidden="true" tabindex="-1">
						<img class="img-fluid rounded" src="<?php echo htmlspecialchars($row->person_picture, ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy"<?php echo $pictureAttributes($pictureWidth); ?>>
					</a>
				<?php endif; ?>
				<div class="jl-birthday-content min-w-0 flex-grow-1">
					<div class="d-flex flex-wrap align-items-center gap-2">
						<span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
						<span class="text-muted small"><?php echo HTMLHelper::_('date', $row->next_date, $dayFormat); ?></span>
						<?php if ($showFlag && !empty($row->person_country)) : ?>
							<?php echo LayoutHelper::render('joomleague.flag', ['code' => $row->person_country, 'showName' => false, 'size' => 18], $flagPath); ?>
						<?php endif; ?>
					</div>
					<div class="fw-semibold text-break">
						<a href="<?php echo Route::_($url); ?>">
							<?php echo htmlspecialchars($row->person_name, ENT_QUOTES, 'UTF-8'); ?>
						</a>
					</div>
					<div class="text-muted small"><?php echo $message; ?></div>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
