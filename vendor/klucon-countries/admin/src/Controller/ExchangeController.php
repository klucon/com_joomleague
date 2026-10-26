<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Klucon\Component\Kluconcountries\Administrator\Service\{Registry,Exchange};

final class ExchangeController extends BaseController
{
    private function guard(string $permission,bool $post=true): void {
        if($post&&($this->input->getMethod()!=='POST'||!$this->checkToken('post',false)))throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        if(!$this->app->getIdentity()->authorise('core.manage','com_kluconcountries')||!$this->app->getIdentity()->authorise($permission,'com_kluconcountries'))throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
    }
    private function service(): Exchange {return new Exchange(new Registry(Factory::getContainer()->get(DatabaseInterface::class)));}
    public function export(): void {
        $this->guard('countries.export',false);$bytes=$this->service()->export();
        $this->app->setHeader('Content-Type','application/json; charset=utf-8',true);$this->app->setHeader('Content-Disposition','attachment; filename="klucon-countries-'.gmdate('Y-m-d').'.json"',true);$this->app->setHeader('Cache-Control','private, no-store',true);$this->app->sendHeaders();echo $bytes;$this->app->close();
    }
    public function preview(): void {
        $this->guard('countries.import');$session=$this->app->getSession();$session->set('com_kluconcountries.preview',null);
        try{
            $params=ComponentHelper::getParams('com_kluconcountries');$limit=max(16,min(5120,(int)$params->get('import_max_kb',2048)))*1024;
            $file=$this->input->files->get('import_file',[],'array');$json=$this->input->post->get('json','','raw');
            if(!empty($file['name'])){if((int)($file['error']??1)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??'')||(int)($file['size']??0)>$limit)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_IMPORT');$json=file_get_contents($file['tmp_name']);}
            if(!is_string($json))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_IMPORT');
            $preview=$this->service()->preview($json,$limit,max(1,min(10000,(int)$params->get('import_max_records',1000))));$preview['nonce']=bin2hex(random_bytes(24));$preview['expires']=time()+600;$preview['actor']=(int)$this->app->getIdentity()->id;$session->set('com_kluconcountries.preview',$preview);
        }catch(\Throwable $error){$this->app->enqueueMessage(Text::_(str_starts_with($error->getMessage(),'COM_KLUCONCOUNTRIES_')?$error->getMessage():'COM_KLUCONCOUNTRIES_ERROR_IMPORT'),'error');}
        $this->setRedirect('index.php?option=com_kluconcountries&view=exchange');
    }
    public function apply(): void {
        $this->guard('countries.import');$session=$this->app->getSession();$preview=$session->get('com_kluconcountries.preview');$session->set('com_kluconcountries.preview',null);
        try{
            if(!is_array($preview)||$preview['expires']<time()||$preview['actor']!==(int)$this->app->getIdentity()->id||!hash_equals($preview['nonce'],$this->input->post->getString('nonce')))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_PREVIEW');
            $this->service()->apply($preview,(int)$this->app->getIdentity()->id,fn($permission)=>$this->app->getIdentity()->authorise($permission,'com_kluconcountries'));$this->app->enqueueMessage(Text::_('COM_KLUCONCOUNTRIES_IMPORTED'));
        }catch(\Throwable $error){if($error->getCode()===403)throw $error;$this->app->enqueueMessage(Text::_(str_starts_with($error->getMessage(),'COM_KLUCONCOUNTRIES_')?$error->getMessage():'COM_KLUCONCOUNTRIES_ERROR_IMPORT'),'error');}
        $this->setRedirect('index.php?option=com_kluconcountries&view=territories');
    }
}
