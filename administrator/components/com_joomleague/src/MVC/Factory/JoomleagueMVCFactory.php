<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\MVC\Factory;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomleague\Component\Joomleague\Administrator\Model\ClubModel;
use Joomleague\Component\Joomleague\Administrator\Service\ClubProvisioningService;
use Joomleague\Component\Joomleague\Administrator\Model\ProjectpanelModel;
use Joomleague\Component\Joomleague\Administrator\Model\DivisionModel;
use Joomleague\Component\Joomleague\Administrator\Model\DivisionsModel;
use Joomleague\Component\Joomleague\Administrator\Model\RoundModel;
use Joomleague\Component\Joomleague\Administrator\Model\RoundsModel;
use Joomleague\Component\Joomleague\Administrator\Model\MatchModel;
use Joomleague\Component\Joomleague\Administrator\Model\MatchesModel;
use Joomleague\Component\Joomleague\Administrator\Model\ScheduleModel;
use Joomleague\Component\Joomleague\Administrator\Model\TeamplayerModel;
use Joomleague\Component\Joomleague\Administrator\Model\TeamplayersModel;
use Joomleague\Component\Joomleague\Administrator\Model\TeamstaffModel;
use Joomleague\Component\Joomleague\Administrator\Model\TeamstaffsModel;
use Joomleague\Component\Joomleague\Administrator\Model\TemplateModel;
use Joomleague\Component\Joomleague\Administrator\Model\TemplatesModel;
use Joomleague\Component\Joomleague\Administrator\Model\TreetonodeModel;
use Joomleague\Component\Joomleague\Administrator\Model\TreetonodesModel;
use Joomleague\Component\Joomleague\Administrator\Controller\ScheduleController;
use Joomleague\Component\Joomleague\Administrator\Controller\SportsbootstrapController;
use Joomleague\Component\Joomleague\Administrator\Service\ScheduleGeneratorService;
use Joomleague\Component\Joomleague\Administrator\Service\ScheduleTemplateService;
use Joomleague\Component\Joomleague\Administrator\Service\SportsBootstrapService;

final class JoomleagueMVCFactory extends MVCFactory
{
	public function __construct(
		string $namespace,
		private readonly ClubProvisioningService $clubProvisioningService,
		private readonly CMSApplicationInterface $application,
		private readonly ScheduleGeneratorService $scheduleGeneratorService,
		private readonly ScheduleTemplateService $scheduleTemplateService,
		private readonly SportsBootstrapService $sportsBootstrapService
	) {
		parent::__construct($namespace);
	}
	public function createController($name, $prefix, array $config, CMSApplicationInterface $app, Input $input)
	{
		$controller = parent::createController($name, $prefix, $config, $app, $input);
		if ($controller instanceof ScheduleController) { $controller->setScheduleGeneratorService($this->scheduleGeneratorService); }
		if ($controller instanceof SportsbootstrapController) { $controller->setSportsBootstrapService($this->sportsBootstrapService); }
		return $controller;
	}

	public function createModel($name, $prefix = '', array $config = [])
	{
		$model = parent::createModel($name, $prefix, $config);

		if ($model instanceof ClubModel) {
			$model->setProvisioningService($this->clubProvisioningService);
		}

		if ($model instanceof ScheduleModel) {
			$model->setScheduleTemplateService($this->scheduleTemplateService);
		}

		if ($this->application instanceof AdministratorApplication && ($model instanceof ProjectpanelModel || $model instanceof DivisionModel || $model instanceof DivisionsModel || $model instanceof RoundModel || $model instanceof RoundsModel || $model instanceof MatchModel || $model instanceof MatchesModel || $model instanceof TeamplayerModel || $model instanceof TeamplayersModel || $model instanceof TeamstaffModel || $model instanceof TeamstaffsModel || $model instanceof TemplateModel || $model instanceof TemplatesModel || $model instanceof TreetonodeModel || $model instanceof TreetonodesModel)) {
			$model->setApplication($this->application);
		}

		return $model;
	}
}
