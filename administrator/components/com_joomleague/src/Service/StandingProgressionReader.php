<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Replays the canonical standings calculation cumulatively after each completed round. */
final class StandingProgressionReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @return array<string,mixed> */
	public function forProject(int $projectId, ?int $stageId, ?string $scope): array
	{
		$reader=new StandingsReader($this->database);$context=$reader->context($projectId,$stageId,$scope);$scope=(string)($scope?:$context['default_scope']);
		$input=(new StandingsRecalculator($this->database,$reader))->inputForCalculation($context,$scope);$rounds=[];
		foreach($input['matches']as$match){$sequence=(int)($match['round_sequence']??0);if($sequence<1)continue;$rounds[$sequence]['id']=(int)$match['round_id'];$rounds[$sequence]['name']=(string)$match['round_name'];$rounds[$sequence]['matches'][]=$match;}
		ksort($rounds,SORT_NUMERIC);$calculator=new StandingsCalculator();$cumulative=[];$points=[];$previous=[];
		foreach($rounds as$sequence=>$round){$cumulative=array_merge($cumulative,$round['matches']);$dates=array_values(array_filter(array_column($cumulative,'scheduled_start')));$cutoff=$dates===[]?null:substr((string)max($dates),0,10);$adjustments=array_values(array_filter($input['adjustments'],static fn(array$a):bool=>$a['effective_date']===null||($cutoff!==null&&$a['effective_date']<=$cutoff)));$rows=$calculator->calculate($context['contract'],$input['entries'],$cumulative,$scope,$adjustments);$rankByEntry=[];foreach($rows as$row)$rankByEntry[(int)$row['id']]=(int)$row['rank'];$changes=[];foreach($rankByEntry as$id=>$rank)$changes[$id]=isset($previous[$id])?$previous[$id]-$rank:0;$points[]=['round_id'=>$round['id'],'round_name'=>$round['name'],'round_sequence'=>$sequence,'ranks'=>$rankByEntry,'changes'=>$changes];$previous=$rankByEntry;}
		unset($context['project']->payload_json);return['project'=>$context['project'],'stage'=>$context['stage'],'entries'=>$input['entries'],'scope'=>$scope,'available_scopes'=>$context['available_scopes'],'points'=>$points];
	}
}
