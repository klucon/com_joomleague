<?php
declare(strict_types=1);
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$kindLabels=['team'=>'COM_JOOMLEAGUE_PARTICIPANTS_KIND_TEAM','person'=>'COM_JOOMLEAGUE_PARTICIPANTS_KIND_PERSON','group'=>'COM_JOOMLEAGUE_PARTICIPANTS_KIND_GROUP'];
?>
<?php if (isset($summary['error'])) : ?><div class="alert alert-info mb-0"><?php echo Text::_($summary['error']); ?></div>
<?php else : $participant=$summary['participant']; $media=$participant->team_logo ?: ($participant->person_picture ?: $participant->team_picture); ?>
<article class="card"><div class="card-body">
	<div class="d-flex gap-3 align-items-start">
		<?php if ((bool)$params->get('show_image',1) && $media) : ?><img src="<?php echo htmlspecialchars((string)$media,ENT_QUOTES,'UTF-8'); ?>" alt="" class="img-fluid" width="72" height="72" loading="lazy"><?php endif; ?>
		<div class="flex-grow-1"><h3 class="h5 mb-1"><a class="stretched-link text-decoration-none" href="<?php echo Route::_('index.php?option=com_joomleague&view=participant&project_id='.(int)$participant->project_id.'&entry_id='.(int)$participant->id); ?>"><?php echo htmlspecialchars((string)$participant->display_name,ENT_QUOTES,'UTF-8'); ?></a></h3>
		<?php if ((bool)$params->get('show_project_name',1)) : ?><div class="text-body-secondary small"><?php echo htmlspecialchars((string)$participant->project_name,ENT_QUOTES,'UTF-8'); ?></div><?php endif; ?>
		<div class="d-flex flex-wrap gap-2 mt-2"><span class="badge text-bg-secondary"><?php echo Text::_($kindLabels[(string)$participant->entry_kind]??$kindLabels['group']); ?></span><?php if ((bool)$params->get('show_member_count',1) && $summary['members']!==[]) : ?><span class="badge text-bg-light border"><?php echo Text::sprintf('MOD_JOOMLEAGUE_PARTICIPANT_MEMBER_COUNT',count($summary['members'])); ?></span><?php endif; ?></div></div>
	</div>
	<?php if ((bool)$params->get('show_club',1) && $participant->club_name) : ?><div class="mt-3 position-relative z-2"><a href="<?php echo Route::_('index.php?option=com_joomleague&view=club&club_id='.(int)$participant->club_id); ?>"><?php echo htmlspecialchars((string)$participant->club_name,ENT_QUOTES,'UTF-8'); ?></a></div><?php endif; ?>
	<?php if ((bool)$params->get('show_actions',1)) : ?><div class="d-flex flex-wrap gap-2 mt-3 position-relative z-2"><a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomleague&view=teamplan&project_id='.(int)$participant->project_id.'&entry_id='.(int)$participant->id); ?>"><span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANT_OPEN_PROGRAM'); ?></a><?php if ((int)$summary['statistic_count']>0):?><a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_joomleague&view=participantstats&project_id='.(int)$participant->project_id.'&entry_id='.(int)$participant->id); ?>"><span class="icon-chart" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLEAGUE_PARTICIPANT_OPEN_STATISTICS'); ?></a><?php endif;?></div><?php endif; ?>
</div></article><?php endif; ?>
