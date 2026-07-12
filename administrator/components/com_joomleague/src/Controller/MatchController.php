<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;

final class MatchController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_MATCH';
	protected $view_list = 'matches';

	/**
	 * Inline AJAX uložení data a času zápasu ze seznamu (autosave). Vrací JSON.
	 */
	public function savedate(): void
	{
		$result = ['success' => false, 'value' => '', 'message' => ''];

		try {
			if (!Session::checkToken()) {
				throw new RuntimeException(Text::_('JINVALID_TOKEN'));
			}

			if (!$this->app->getIdentity()->authorise('core.edit', 'com_joomleague')) {
				throw new RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
			}

			$id = $this->input->getInt('id');
			$value = (string) $this->input->getString('match_date', '');

			$stored = $this->getModel('Match')->saveDate($id, $value);

			$result = [
				'success' => true,
				'value' => $stored,
				'message' => Text::_('COM_JOOMLEAGUE_MATCH_DATE_SAVED'),
			];
		} catch (Throwable $exception) {
			$message = $exception->getMessage();
			$result['message'] = str_starts_with($message, 'COM_') || str_starts_with($message, 'JLIB_') || str_starts_with($message, 'JINVALID')
				? Text::_($message)
				: $message;
		}

		$this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$this->app->sendHeaders();
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
		$this->app->close();
	}

	/**
	 * Vytvoří NEBO aktualizuje článek (com_content) ze zápasu a uloží vazbu
	 * article_id na zápas. Titulek z týmů + skóre, obsah ze zápisu.
	 */
	public function syncarticle(): void
	{
		try {
			if (!Session::checkToken('get')) {
				throw new RuntimeException(Text::_('JINVALID_TOKEN'));
			}

			$id = $this->input->getInt('id');

			if ($id < 1) {
				throw new RuntimeException(Text::_('COM_JOOMLEAGUE_MATCH_ARTICLE_INVALID'));
			}

			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$match = $db->setQuery(
				$db->createQuery()
					->select(['m.article_id', 'm.team1_result', 'm.team2_result', 'm.summary', 'th.name AS home', 'tg.name AS away'])
					->from($db->quoteName('#__joomleague_match', 'm'))
					->join('LEFT', $db->quoteName('#__joomleague_project_team', 'ph') . ' ON ph.id = m.projectteam1_id')
					->join('LEFT', $db->quoteName('#__joomleague_team', 'th') . ' ON th.id = ph.team_id')
					->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pg') . ' ON pg.id = m.projectteam2_id')
					->join('LEFT', $db->quoteName('#__joomleague_team', 'tg') . ' ON tg.id = pg.team_id')
					->where('m.id = :id')->bind(':id', $id, ParameterType::INTEGER)
			)->loadObject();

			if (!$match) {
				throw new RuntimeException(Text::_('COM_JOOMLEAGUE_MATCH_ARTICLE_INVALID'));
			}

			// Existuje propojený, nezahozený článek?
			$existingId = 0;

			if ((int) $match->article_id > 0) {
				$existingId = (int) $db->setQuery(
					$db->createQuery()->select($db->quoteName('id'))->from($db->quoteName('#__content'))
						->where($db->quoteName('id') . ' = :aid')
						->where($db->quoteName('state') . ' <> -2')
						->bind(':aid', $match->article_id, ParameterType::INTEGER)
				)->loadResult();
			}

			$isUpdate = $existingId > 0;
			$user = $this->app->getIdentity();

			if ($isUpdate ? !$user->authorise('core.edit', 'com_content') : !$user->authorise('core.create', 'com_content')) {
				throw new RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
			}

			$fmt = static fn ($v): string => $v === null ? '' : ((float) $v == (int) $v ? (string) (int) $v : (string) (float) $v);
			$score = ($match->team1_result !== null && $match->team2_result !== null)
				? ' ' . $fmt($match->team1_result) . ':' . $fmt($match->team2_result)
				: '';
			$title = trim(($match->home ?: '?') . ' – ' . ($match->away ?: '?') . $score);

			if ($isUpdate) {
				// Data jsou v článku živá přes {jlmatch}, takže obnovujeme jen titulek
				// (skóre); tělo článku (shortcode + případný komentář autora) neměníme.
				$data = [
					'id' => $existingId,
					'title' => $title,
				];
			} else {
				$catid = (int) $db->setQuery(
					$db->createQuery()->select($db->quoteName('id'))->from($db->quoteName('#__categories'))
						->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
						->where($db->quoteName('published') . ' = 1')
						->where($db->quoteName('level') . ' > 0')
						->order($db->quoteName('lft') . ' ASC'),
					0,
					1
				)->loadResult();

				if ($catid < 1) {
					$catid = 2;
				}

				// Tělo = shortcode s živými daty (žádná kopie); autor okolo může
				// dopsat komentář. Detail (skóre, sestavy, události) je vždy aktuální.
				$data = [
					'id' => 0,
					'title' => $title,
					'alias' => OutputFilter::stringURLSafe($title . '-' . $id),
					'introtext' => '{jlmatch id=' . $id . '}',
					'catid' => $catid,
					'language' => '*',
					'state' => 0,
					'access' => 1,
				];
			}

			$articleModel = Factory::getApplication()->bootComponent('com_content')->getMVCFactory()
				->createModel('Article', 'Administrator', ['ignore_request' => true]);

			if (!$articleModel->save($data)) {
				throw new RuntimeException($articleModel->getError() ?: Text::_('COM_JOOMLEAGUE_MATCH_ARTICLE_FAILED'));
			}

			$articleId = (int) $articleModel->getState('article.id');

			if ($articleId > 0 && $articleId !== (int) $match->article_id) {
				$db->setQuery(
					$db->createQuery()->update($db->quoteName('#__joomleague_match'))
						->set($db->quoteName('article_id') . ' = :aid')
						->where($db->quoteName('id') . ' = :id')
						->bind(':aid', $articleId, ParameterType::INTEGER)
						->bind(':id', $id, ParameterType::INTEGER)
				)->execute();
			}

			$this->setRedirect(
				Route::_('index.php?option=com_content&task=article.edit&id=' . $articleId, false),
				Text::_($isUpdate ? 'COM_JOOMLEAGUE_MATCH_ARTICLE_UPDATED' : 'COM_JOOMLEAGUE_MATCH_ARTICLE_CREATED')
			);
		} catch (Throwable $exception) {
			$message = $exception->getMessage();
			$this->setRedirect(
				Route::_('index.php?option=com_joomleague&view=matches', false),
				str_starts_with($message, 'COM_') || str_starts_with($message, 'JLIB') || str_starts_with($message, 'JINVALID') ? Text::_($message) : $message,
				'error'
			);
		}
	}
}
