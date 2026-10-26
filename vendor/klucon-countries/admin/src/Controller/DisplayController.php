<?php
namespace Klucon\Component\Kluconcountries\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
final class DisplayController extends BaseController
{
    protected $default_view = 'territories';
    public function display($cachable = false, $urlparams = [])
    {
        if (!$this->app->getIdentity()->authorise('core.manage', 'com_kluconcountries')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        return parent::display($cachable, $urlparams);
    }
}
