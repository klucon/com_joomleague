<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Field\MediaField;
use Joomleague\Component\Joomleague\Domain\Service\MediaPathResolver;

final class JoomleaguemediaField extends MediaField
{
	protected $type = 'Joomleaguemedia';

	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$element = clone $element;
		require_once dirname(__DIR__) . '/Service/MediaPathResolver.php';
		try {
			$base = MediaPathResolver::basePath((string) ComponentHelper::getParams('com_joomleague')->get('images_base_path', MediaPathResolver::DEFAULT_BASE));
		} catch (\InvalidArgumentException) {
			$base = MediaPathResolver::DEFAULT_BASE;
		}
		$directory = (string) $element['directory'];
		$suffix = $directory === 'com_joomleague' ? '' : substr($directory, strlen('com_joomleague/'));
		if ($suffix !== '' && !in_array($suffix, MediaPathResolver::DIRECTORIES, true)) return false;
		$element['directory'] = 'local-images:/' . substr($base, strlen('images/')) . ($suffix === '' ? '' : '/' . $suffix);
		return parent::setup($element, $value, $group);
	}
}
