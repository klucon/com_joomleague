<?php
declare(strict_types=1);
namespace Joomleague\Module\Statranking\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;use Joomla\Database\DatabaseInterface;use Joomla\Registry\Registry;use Joomleague\Component\Joomleague\Domain\Service\StatisticRankingReader;
final class StatrankingHelper {/** @return array<string,mixed> */public function getRanking(Registry $params):array{$projectId=(int)$params->get('project_id',0);if($projectId<1)return['error'=>'MOD_JOOMLEAGUE_STATRANKING_NO_PROJECT'];Factory::getApplication()->bootComponent('com_joomleague');$language=Factory::getLanguage();$language->load('com_joomleague',JPATH_SITE)||$language->load('com_joomleague',JPATH_SITE.'/components/com_joomleague');try{$code=trim((string)$params->get('statistic_code',''))?:null;$limit=min(100,max(1,(int)$params->get('limit',5)));return(new StatisticRankingReader(Factory::getContainer()->get(DatabaseInterface::class)))->forProject($projectId,$code,$limit,Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());}catch(\Throwable){return['error'=>'MOD_JOOMLEAGUE_STATRANKING_UNAVAILABLE'];}}}
