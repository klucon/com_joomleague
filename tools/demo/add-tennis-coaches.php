<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
define('_JEXEC',1);
define('JPATH_BASE','/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/UuidFactory.php';
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
$db = Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$name = 'Tennis team encounter demo 2026';
$q = $db->getQuery(true)->select(['m.id','p.sport_type_id'])->from('#__joomleague_project_match AS m')->join('INNER','#__joomleague_project AS p ON p.id=m.project_id')->where('p.name = :name')->bind(':name',$name);
$matches = $db->setQuery($q)->loadObjectList();
if(count($matches)!==1) throw new RuntimeException('Expected one owned demo match.');
$match=(int)$matches[0]->id; $sport=(int)$matches[0]->sport_type_id;
$participants=$db->setQuery($db->getQuery(true)->select('id')->from('#__joomleague_match_participant')->where('match_id = ' . $match)->order('slot_number'))->loadColumn();
if(count($participants)!==2) throw new RuntimeException('Expected two teams.');
if((int)$db->setQuery($db->getQuery(true)->select('COUNT(*)')->from('#__joomleague_match_lineup_member')->where('match_id = ' . $match)->where("member_person_type='staff'"))->loadResult()) throw new RuntimeException('Demo coaches already exist.');
$insert=static function(string $table,array $data)use($db):int {
    $q=$db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($data)));$holders=[];
    foreach($data as $key=>&$value){$holders[]=':'.$key;$q->bind(':'.$key,$value);}unset($value);
    $db->setQuery($q->values(implode(',',$holders)))->execute();return(int)$db->insertid();
};
$db->transactionStart();
try {
    $insert('#__joomleague_sport_position',['uuid'=>UuidFactory::v4(),'sport_type_id'=>$sport,'code'=>'head_coach','name'=>'Head coach','person_type'=>'staff','role_domain'=>'team_staff','lineup_group'=>'staff','published'=>1]);
    foreach([['Robert','Lake'],['Thomas','Meadow']] as $i=>$coach){
        $person=$insert('#__joomleague_person',['uuid'=>UuidFactory::v4(),'first_name'=>$coach[0],'last_name'=>$coach[1],'published'=>1,'access'=>1]);
        $insert('#__joomleague_match_lineup_member',['uuid'=>UuidFactory::v4(),'match_id'=>$match,'match_participant_id'=>(int)$participants[$i],'person_id'=>$person,'member_person_type'=>'staff','role_code'=>'head_coach','published'=>1]);
    }
    $db->transactionCommit();echo "Two head coaches added to demo staff only.\n";
}catch(Throwable $e){$db->transactionRollback();throw $e;}
