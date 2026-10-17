<?php

declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Controller;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\SportVariantService;
use Joomleague\Component\Joomleague\Administrator\Service\SportVariantRules;

final class SportvariantsController extends BaseController
{
	public function create(): void
	{
		$this->guard('core.create');
		$data = $this->input->post->get('jform', [], 'array');
		$id = 0;
		try {
			if (!is_string($data['code'] ?? null) || !is_string($data['name'] ?? null)) throw new \InvalidArgumentException('Invalid identity.');
			$id = $this->service()->create($this->id($data['profile_id'] ?? null), $data['code'], $data['name'], (int) $this->app->getIdentity()->id);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_SPORTVARIANT_CREATED'));
		} catch (\Throwable $error) {
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_SPORTVARIANT_FAILED'), 'error');
			$this->app->setUserState('com_joomleague.sportvariant.create', $data);
		}
		$this->redirectTo($id);
	}

	public function revision(): void
	{
		$this->guard('core.edit');
		$data = $this->input->post->get('jform', [], 'array');
		$id = 0;
		try {
			$id = $this->id($data['variant_id'] ?? null);
			$baseId = $this->id($data['base_id'] ?? null);
			if (!is_string($data['revision'] ?? null) || !is_array($data['rules'] ?? null)) throw new \InvalidArgumentException('Invalid revision.');
			$service = $this->service();
			$overrides = (new SportVariantRules())->parse($service->base($id, $baseId), $data['rules']);
			$service->addRevision($id, $baseId, $data['revision'], $overrides, (int) $this->app->getIdentity()->id);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_SPORTVARIANT_REVISION_SAVED'));
		} catch (\Throwable $error) {
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_SPORTVARIANT_FAILED'), 'error');
			$this->app->setUserState('com_joomleague.sportvariant.revision', $data);
		}
		$this->redirectTo($id);
	}

	public function publish(): void
	{
		$this->guard('core.edit.state');
		$id = $this->input->post->getInt('variant_id');
		try {
			$this->service()->setPublished($id, $this->input->post->getInt('published', -1));
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_SPORTVARIANT_STATE_SAVED'));
		} catch (\Throwable $error) {
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_SPORTVARIANT_FAILED'), 'error');
		}
		$this->redirectTo($id);
	}

	private function guard(string $action): void
	{
		if (!Session::checkToken('post')) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		if (!$this->app->getIdentity()->authorise($action, 'com_joomleague')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}

	private function service(): SportVariantService
	{
		return new SportVariantService(Factory::getContainer()->get(DatabaseInterface::class));
	}

	private function id(mixed $value): int
	{
		if ((!is_string($value) && !is_int($value)) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) throw new \InvalidArgumentException('Invalid identity.');
		return (int) $value;
	}

	private function redirectTo(int $id): void
	{
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=sportvariants' . ($id > 0 ? '&id=' . $id : ''), false));
	}
}
