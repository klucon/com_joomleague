<?php
declare(strict_types=1);namespace Joomleague\Module\Club\Site\Dispatcher;defined('_JEXEC')or die;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;use Joomla\CMS\Helper\HelperFactoryAwareInterface;use Joomla\CMS\Helper\HelperFactoryAwareTrait;use Joomla\CMS\Language\Text;
final class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface{use HelperFactoryAwareTrait;protected function getLayoutData():array{if($this->module->title===$this->module->module)$this->module->title=Text::_('MOD_JOOMLEAGUE_CLUB');$d=parent::getLayoutData();$d['summary']=$this->getHelperFactory()->getHelper('ClubHelper')->getSummary($d['params']);return$d;}}
