<?php
namespace Klucon\Component\Kluconcountries\Administrator\View\History;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use Klucon\Component\Kluconcountries\Administrator\Service\Registry;
final class HtmlView extends \Joomla\CMS\MVC\View\HtmlView
{
    public object $item;public array $revisions=[];public $pagination;
    public function display($tpl=null){
        $app=Factory::getApplication();$user=$app->getIdentity();if(!$user->authorise('core.manage','com_kluconcountries')||!$user->authorise('countries.history','com_kluconcountries'))throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        $registry=new Registry(Factory::getContainer()->get(DatabaseInterface::class));$item=$registry->find($app->input->getString('uuid'));if(!$item)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $this->item=$item;$total=$registry->historyTotal($item->uuid);$limit=max(5,min(100,$app->input->getInt('limit',25)));$start=max(0,$app->input->getInt('limitstart'));if($start>=$total)$start=max(0,(int)floor(($total-1)/$limit)*$limit);
        $this->pagination=new \Joomla\CMS\Pagination\Pagination($total,$start,$limit);$this->revisions=$registry->history($item->uuid,$limit,$start);ToolbarHelper::title(Text::_('COM_KLUCONCOUNTRIES_HISTORY'),'clock');
        if($user->authorise('core.edit','com_kluconcountries'))ToolbarHelper::link('index.php?option=com_kluconcountries&view=territory&uuid='.rawurlencode($item->uuid),Text::_('COM_KLUCONCOUNTRIES_EDIT'),'edit');
        ToolbarHelper::link('index.php?option=com_kluconcountries&view=territories',Text::_('JTOOLBAR_BACK'),'arrow-left');parent::display($tpl);
    }
}
