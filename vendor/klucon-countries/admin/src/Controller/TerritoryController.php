<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Klucon\Component\Kluconcountries\Administrator\Service\{Registry,Editor,FlagStorage};

final class TerritoryController extends BaseController
{
    private function guard(string $action): void {
        if($this->input->getMethod()!=='POST'||!$this->checkToken('post',false))throw new \RuntimeException(Text::_('JINVALID_TOKEN'),403);
        $this->authorise($action);
    }
    private function authorise(string $action): void {
        $user=$this->app->getIdentity();if(!$user->authorise('core.manage','com_kluconcountries')||!$user->authorise($action,'com_kluconcountries'))throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
    }
    private function registry(): Registry {return new Registry(Factory::getContainer()->get(DatabaseInterface::class));}
    public function add(): void {$this->authorise('core.create');$this->setRedirect('index.php?option=com_kluconcountries&view=territory');}
    public function edit(): void {
        $this->authorise('core.edit');$ids=$this->input->get('cid',[],'array');$id=$this->input->getString('uuid',(string)($ids[0]??''));
        if(!Registry::uuid($id))throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $this->setRedirect('index.php?option=com_kluconcountries&view=territory&uuid='.rawurlencode($id));
    }
    public function apply(): void {$this->save();}
    public function save(): void {
        $uuid=$this->input->post->getString('uuid');$this->guard($uuid?'core.edit':'core.create');$registry=$this->registry();
        $current=$uuid?$registry->find($uuid):null;if($uuid&&!$current)throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'),404);
        $form=$this->input->post->get('jform',[],'array');$version=$this->input->post->getInt('version');$db=$registry->db;$started=false;
        try{
            $form['state']??=$current?->state??0;
            $data=Editor::input($form);if($uuid){$data['uuid']=$uuid;$data['version']=$version;}
            if((int)$data['state']!==($current?(int)$current->state:0)&&!$this->app->getIdentity()->authorise('core.edit.state','com_kluconcountries'))$this->authorise('core.edit.state');
            if((int)$data['state']===-2&&(!$current||(int)$current->state!==-2))$this->authorise('core.delete');
            $files=$this->input->files->get('jform',[],'array');$file=$files['flag_file']??$this->input->files->get('flag_file',[],'array');$bytes=null;$params=ComponentHelper::getParams('com_kluconcountries');$limit=max(16,min(2048,(int)$params->get('upload_max_kb',1024)))*1024;
            if(!empty($file['name'])){
                $this->authorise('countries.upload');if(!(bool)$params->get('upload_enabled',1))throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_DISABLED');
                if((int)($file['error']??1)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??'')||(int)($file['size']??0)>$limit)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_SIZE');
                $bytes=file_get_contents($file['tmp_name']);FlagStorage::validate($bytes,$limit);
            }
            $db->transactionStart(true);$started=true;$actor=(int)$this->app->getIdentity()->id;$saved=$registry->save($data,$actor);
            if($bytes!==null){$flag=(new FlagStorage($registry))->store($saved,$bytes,$actor,$limit);$item=$registry->find($saved);$registry->save(['uuid'=>$saved,'version'=>$item->version,'flag'=>$flag,'flag_mode'=>'custom'],$actor,'upload');}
            $db->transactionCommit(true);$started=false;$this->app->getSession()->set('com_kluconcountries.form',null);$this->app->enqueueMessage(Text::_('COM_KLUCONCOUNTRIES_SAVED'));
            $this->setRedirect('index.php?option=com_kluconcountries&view='.($this->getTask()==='apply'?'territory&uuid='.rawurlencode($saved).'&return_branch='.rawurlencode($this->input->post->getString('return_branch')):'territories'));
        }catch(\Throwable $error){if($started)$db->transactionRollback(true);
            if($error instanceof \RuntimeException&&$error->getCode()===403)throw $error;
            $this->app->getSession()->set('com_kluconcountries.form',['uuid'=>$uuid,'version'=>$version,'fields'=>$form]);$this->app->enqueueMessage(Text::_(str_starts_with($error->getMessage(),'COM_KLUCONCOUNTRIES_')?$error->getMessage():'COM_KLUCONCOUNTRIES_ERROR_SAVE'),'error');
            $this->setRedirect('index.php?option=com_kluconcountries&view=territory'.($uuid?'&uuid='.rawurlencode($uuid):'').'&return_branch='.rawurlencode($this->input->post->getString('return_branch')));
        }
    }
    public function publish(): void {$this->changeState(1);}
    public function unpublish(): void {$this->changeState(0);}
    public function trash(): void {$this->changeState(-2);}
    private function changeState(int $state): void {
        $this->guard('core.edit.state');if($state===-2)$this->authorise('core.delete');
        try{$this->registry()->state($this->input->post->get('cid',[],'array'),$state,(int)$this->app->getIdentity()->id);$this->app->enqueueMessage(Text::_('COM_KLUCONCOUNTRIES_SAVED'));}
        catch(\Throwable $error){$this->app->enqueueMessage(Text::_(str_starts_with($error->getMessage(),'COM_KLUCONCOUNTRIES_')?$error->getMessage():'COM_KLUCONCOUNTRIES_ERROR_SAVE'),'error');}
        $this->setRedirect('index.php?option=com_kluconcountries&view=territories');
    }
    public function restore(): void {
        $this->guard('countries.restore');$this->authorise('core.edit');$registry=$this->registry();$id=$this->input->post->getString('uuid');
        try{$revision=$this->input->post->getInt('revision');$item=$registry->find($id);if(!$item)throw new \RuntimeException('JERROR_PAGE_NOT_FOUND',404);
            // Restoring a snapshot can change both state and administrative relationships.
            $this->authorise('core.edit.state');$registry->restore($id,$revision,$this->input->post->getInt('version'),(int)$this->app->getIdentity()->id);$this->app->enqueueMessage(Text::_('COM_KLUCONCOUNTRIES_RESTORED'));}
        catch(\Throwable $error){if($error->getCode()===403)throw $error;$this->app->enqueueMessage(Text::_(str_starts_with($error->getMessage(),'COM_KLUCONCOUNTRIES_')?$error->getMessage():'COM_KLUCONCOUNTRIES_ERROR_SAVE'),'error');}
        $this->setRedirect('index.php?option=com_kluconcountries&view=history&uuid='.rawurlencode($id));
    }
}
