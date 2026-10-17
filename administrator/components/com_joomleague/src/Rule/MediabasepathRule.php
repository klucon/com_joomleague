<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Rule;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomleague\Component\Joomleague\Domain\Service\MediaPathResolver;

final class MediabasepathRule extends FormRule
{
	public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
	{
		require_once dirname(__DIR__) . '/Service/MediaPathResolver.php';
		try {
			$base = MediaPathResolver::basePath((string) $value);
			$app = Factory::getApplication();
			if (!$app->isClient('administrator') || (!$app->getIdentity()->authorise('core.admin', 'com_joomleague') && !$app->getIdentity()->authorise('core.options', 'com_joomleague'))) return false;
			MediaPathResolver::ensureDirectories(JPATH_ROOT, $base);
			return true;
		} catch (\Throwable $exception) {
			$element['message'] = $exception instanceof \InvalidArgumentException
				? 'COM_JOOMLEAGUE_MEDIA_DIRECTORY_INVALID' : Text::sprintf('COM_JOOMLEAGUE_MEDIA_DIRECTORY_CREATE_FAILED', (string) $value);
			return false;
		}
	}
}
