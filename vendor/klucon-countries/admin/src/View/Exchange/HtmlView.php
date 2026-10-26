<?php
namespace Klucon\Component\Kluconcountries\Administrator\View\Exchange;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends \Joomla\CMS\MVC\View\HtmlView
{
    public ?array $preview=null;
    public function display($tpl=null){
        $app=Factory::getApplication();$user=$app->getIdentity();if(!$user->authorise('core.manage','com_kluconcountries')||!$user->authorise('countries.import','com_kluconcountries'))throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $preview=$app->getSession()->get('com_kluconcountries.preview');if(is_array($preview)&&$preview['expires']>=time()&&$preview['actor']===(int)$user->id)$this->preview=$preview;
        ToolbarHelper::title(Text::_('COM_KLUCONCOUNTRIES_IMPORT'),'upload');ToolbarHelper::link('index.php?option=com_kluconcountries&view=territories',Text::_('JTOOLBAR_BACK'),'arrow-left');parent::display($tpl);
    }
}
