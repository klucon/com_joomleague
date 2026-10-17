<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

final class RouteAliasRegistry
{
	/** @var array<string,array{en:string,cs:string,cs_unicode:string}> */
	private const ROUTES = [
		'aboutjoomleague' => ['en' => 'about-joomleague', 'cs' => 'o-joomleague', 'cs_unicode' => 'o-joomleague'],
		'bracket' => ['en' => 'bracket', 'cs' => 'vyrazovaci-pavouk', 'cs_unicode' => 'vyřazovací-pavouk'],
		'club' => ['en' => 'club', 'cs' => 'klub', 'cs_unicode' => 'klub'],
		'clubpeople' => ['en' => 'club-people', 'cs' => 'osoby-klubu', 'cs_unicode' => 'osoby-klubu'],
		'clubplan' => ['en' => 'club-schedule', 'cs' => 'program-klubu', 'cs_unicode' => 'program-klubu'],
		'clubrivals' => ['en' => 'club-rivals', 'cs' => 'souperi-klubu', 'cs_unicode' => 'soupeři-klubu'],
		'clubs' => ['en' => 'clubs', 'cs' => 'kluby', 'cs_unicode' => 'kluby'],
		'clubstaffhistory' => ['en' => 'staff-history', 'cs' => 'historie-realizacniho-tymu', 'cs_unicode' => 'historie-realizačního-týmu'],
		'comparison' => ['en' => 'comparison', 'cs' => 'porovnani', 'cs_unicode' => 'porovnání'],
		'eventranking' => ['en' => 'event-ranking', 'cs' => 'zebricek-udalosti', 'cs_unicode' => 'žebříček-událostí'],
		'eventreport' => ['en' => 'event-report', 'cs' => 'report-udalosti', 'cs_unicode' => 'report-události'],
		'nextmatch' => ['en' => 'next-event', 'cs' => 'nejblizsi-udalost', 'cs_unicode' => 'nejbližší-událost'],
		'participant' => ['en' => 'participant', 'cs' => 'ucastnik', 'cs_unicode' => 'účastník'],
		'participants' => ['en' => 'participants', 'cs' => 'ucastnici', 'cs_unicode' => 'účastníci'],
		'participantstats' => ['en' => 'participant-statistics', 'cs' => 'statistiky-ucastnika', 'cs_unicode' => 'statistiky-účastníka'],
		'person' => ['en' => 'person', 'cs' => 'osoba', 'cs_unicode' => 'osoba'],
		'personnel' => ['en' => 'officials', 'cs' => 'funkcionari', 'cs_unicode' => 'funkcionáři'],
		'programitem' => ['en' => 'programme-item', 'cs' => 'polozka-programu', 'cs_unicode' => 'položka-programu'],
		'project' => ['en' => 'competition', 'cs' => 'soutez', 'cs_unicode' => 'soutěž'],
		'projects' => ['en' => 'competitions', 'cs' => 'souteze', 'cs_unicode' => 'soutěže'],
		'resultmatrix' => ['en' => 'result-matrix', 'cs' => 'matice-vysledku', 'cs_unicode' => 'matice-výsledků'],
		'results' => ['en' => 'results', 'cs' => 'vysledky', 'cs_unicode' => 'výsledky'],
		'standingprogression' => ['en' => 'standing-progression', 'cs' => 'vyvoj-poradi', 'cs_unicode' => 'vývoj-pořadí'],
		'standings' => ['en' => 'standings', 'cs' => 'poradi', 'cs_unicode' => 'pořadí'],
		'statisticsoverview' => ['en' => 'statistics-overview', 'cs' => 'prehled-statistik', 'cs_unicode' => 'přehled-statistik'],
		'statranking' => ['en' => 'statistic-ranking', 'cs' => 'zebricek-statistik', 'cs_unicode' => 'žebříček-statistik'],
		'team' => ['en' => 'team', 'cs' => 'tym', 'cs_unicode' => 'tým'],
		'teamplan' => ['en' => 'team-schedule', 'cs' => 'program-tymu', 'cs_unicode' => 'program-týmu'],
		'venue' => ['en' => 'venue', 'cs' => 'misto-konani', 'cs_unicode' => 'místo-konání'],
		'venues' => ['en' => 'venues', 'cs' => 'sportoviste', 'cs_unicode' => 'sportoviště'],
	];

	public function __construct(private readonly string $languageTag, private readonly bool $unicodeSlugs)
	{
	}

	public function aliasFor(string $view): string
	{
		$route = self::ROUTES[$view] ?? null;
		if ($route === null) return $view;
		if (!str_starts_with(strtolower($this->languageTag), 'cs')) return $route['en'];
		return $this->unicodeSlugs ? $route['cs_unicode'] : $route['cs'];
	}

	public function viewFor(string $alias): ?string
	{
		$alias = strtolower(rawurldecode(trim($alias)));
		foreach (self::ROUTES as $view => $route) {
			if ($alias === $view || in_array($alias, $route, true)) return $view;
		}
		return null;
	}
}
