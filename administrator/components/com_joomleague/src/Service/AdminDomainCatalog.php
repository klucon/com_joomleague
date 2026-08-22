<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class AdminDomainCatalog
{
	/** @return array<string, array{title: string, description: string, phase: string, icon: string, status: string}> */
	public static function all(): array
	{
		return [
			'competitions' => self::item('COMPETITIONS', 'flag', 'foundation', 'available'),
			'seasons' => self::item('SEASONS', 'calendar', 'foundation', 'available'),
			'projects' => self::item('PROJECTS', 'folder-open', 'profile_runtime', 'available'),
			'clubs' => self::item('CLUBS', 'shield', 'foundation', 'available'),
			'teams' => self::item('TEAMS', 'users', 'foundation', 'available'),
			'persons' => self::item('PERSONS', 'user', 'foundation', 'available'),
			'venues' => self::item('VENUES', 'location', 'foundation', 'available'),
			'positions' => self::item('POSITIONS', 'address', 'profile_runtime', 'available'),
			'events' => self::item('EVENTS', 'bolt', 'profile_runtime', 'available'),
			'statistics' => self::item('STATISTICS', 'chart', 'profile_runtime', 'available'),
			'rounds' => self::item('ROUNDS', 'list', 'competition_runtime', 'project_available'),
			'matches' => self::item('MATCHES', 'play', 'competition_runtime', 'project_available'),
			'standings' => self::item('STANDINGS', 'ranking-star', 'competition_runtime', 'project_available'),
		];
	}

	/** @return array<string, array{title: string, description: string, icon: string, status: string}> */
	public static function dashboard(): array
	{
		$domains = self::all();

		return [
			'sportprofiles' => self::dashboardItem('COM_JOOMLEAGUE_SPORTPROFILES_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_AVAILABLE_PROFILES_DESC', 'puzzle-piece', 'available'),
			'sporttypes' => self::dashboardItem('COM_JOOMLEAGUE_SPORTTYPES_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_AVAILABLE_SPORTTYPES_DESC', 'options', 'available'),
			'competitions' => self::dashboardItem($domains['competitions']['title'], 'COM_JOOMLEAGUE_DASHBOARD_AVAILABLE_COMPETITIONS_DESC', $domains['competitions']['icon'], $domains['competitions']['status']),
			'seasons' => self::dashboardItem($domains['seasons']['title'], 'COM_JOOMLEAGUE_DASHBOARD_AVAILABLE_SEASONS_DESC', $domains['seasons']['icon'], $domains['seasons']['status']),
			'projects' => self::dashboardItem($domains['projects']['title'], 'COM_JOOMLEAGUE_DASHBOARD_AVAILABLE_PROJECTS_DESC', $domains['projects']['icon'], $domains['projects']['status']),
			'templates' => self::dashboardItem('COM_JOOMLEAGUE_TEMPLATES_TITLE', 'COM_JOOMLEAGUE_TEMPLATES_DESC', 'palette', 'available'),
			'tools' => self::dashboardItem('COM_JOOMLEAGUE_TOOLS_TITLE', 'COM_JOOMLEAGUE_TOOLS_DESC', 'wrench', 'available'),
			'migrations' => self::dashboardItem('COM_JOOMLEAGUE_MIGRATIONS_TITLE', 'COM_JOOMLEAGUE_DASHBOARD_AVAILABLE_MIGRATIONS_DESC', 'refresh', 'available'),
			...array_map(
				static fn (array $domain): array => self::dashboardItem(
					$domain['title'],
					match ($domain['status']) {
						'available' => $domain['description'],
						'project_available' => 'COM_JOOMLEAGUE_DASHBOARD_PROJECT_AVAILABLE_DESC',
						'schema_ready' => 'COM_JOOMLEAGUE_DASHBOARD_SCHEMA_READY_DESC',
						default => 'COM_JOOMLEAGUE_DASHBOARD_PLANNED_DESC',
					},
					$domain['icon'],
					$domain['status']
				),
				array_intersect_key($domains, array_flip(['clubs', 'teams', 'persons', 'venues', 'positions', 'events', 'statistics', 'rounds', 'matches', 'standings']))
			),
		];
	}

	/** @return array{title: string, description: string, phase: string, icon: string, status: string} */
	public static function get(string $code): array
	{
		$items = self::all();

		return $items[$code] ?? throw new \InvalidArgumentException('Unknown administration domain.');
	}

	/** @return array{title: string, description: string, phase: string, icon: string, status: string} */
	private static function item(string $key, string $icon, string $phase, string $status): array
	{
		return [
			'title' => 'COM_JOOMLEAGUE_' . $key . '_TITLE',
			'description' => 'COM_JOOMLEAGUE_' . $key . '_DESC',
			'phase' => 'COM_JOOMLEAGUE_PHASE_' . strtoupper($phase),
			'icon' => $icon,
			'status' => $status,
		];
	}

	/** @return array{title: string, description: string, icon: string, status: string} */
	private static function dashboardItem(string $title, string $description, string $icon, string $status): array
	{
		return compact('title', 'description', 'icon', 'status');
	}
}
