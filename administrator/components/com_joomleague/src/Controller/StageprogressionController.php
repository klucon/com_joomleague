<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;
final class StageprogressionController extends BaseController
{
	public function apply(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$transitionId = $this->input->getInt('transition_id');
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromTransition($transitionId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('joomleague.project.run.transitions', $asset)) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		try {
			$result = $this->getModel('Stageprogression')->apply($transitionId, (int) $this->app->getIdentity()->id);
			$key = $result['reused'] ? 'COM_JOOMLEAGUE_STAGE_PROGRESSION_ALREADY_APPLIED' : 'COM_JOOMLEAGUE_STAGE_PROGRESSION_APPLIED';
			$this->setMessage(Text::sprintf($key, $result['resolved_count']));
		} catch (\Throwable $error) {
			Log::add($error->getMessage(), Log::ERROR, 'com_joomleague.stageprogression');
			$this->setMessage(Text::_('COM_JOOMLEAGUE_STAGE_PROGRESSION_APPLY_FAILED'), 'error');
		}
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=stageprogression&transition_id=' . $transitionId, false));
	}
}
