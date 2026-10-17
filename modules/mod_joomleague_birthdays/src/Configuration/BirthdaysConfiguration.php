<?php
declare(strict_types=1);
namespace Joomleague\Module\Birthdays\Site\Configuration;
defined('_JEXEC') or die;
use Joomla\Registry\Registry;
final class BirthdaysConfiguration
{
	/** @return array<string,mixed> */
	public static function fromRegistry(Registry $p):array
	{
		return ['project_id'=>self::id($p->get('project_id',0)),'club_id'=>self::id($p->get('club_id',0)),'team_id'=>self::id($p->get('team_id',0)),'person_types'=>self::codes($p->get('person_types',[])),'role_codes'=>self::codes($p->get('role_codes',[])),'period_mode'=>self::choice((string)$p->get('period_mode','rolling'),['rolling','current_month','selected_month'],'rolling'),'selected_month'=>self::integer($p->get('selected_month',1),1,12),'start_offset'=>self::integer($p->get('start_offset',0),0,366),'days'=>self::integer($p->get('days',30),0,366),'limit'=>self::integer($p->get('limit',10),1,100),'order'=>self::choice((string)$p->get('order','next_birthday'),['next_birthday','name','age'],'next_birthday'),'direction'=>self::choice((string)$p->get('direction','asc'),['asc','desc'],'asc'),'include_deceased'=>self::boolean($p,'include_deceased',false),'orientation'=>self::choice((string)$p->get('orientation','vertical'),['vertical','horizontal'],'vertical'),'density'=>self::choice((string)$p->get('density','standard'),['compact','standard','detailed'],'standard'),'group_by'=>self::choice((string)$p->get('group_by','none'),['none','day','month','club','team','project'],'none'),'date_format'=>self::choice((string)$p->get('date_format','medium'),['short','medium','long'],'medium'),'image_size'=>(int)self::choice((string)$p->get('image_size','48'),['32','40','48','64','80'],'48'),'image_style'=>self::choice((string)$p->get('image_style','cover'),['contain','cover','circle'],'cover'),'heading'=>self::truncate(trim((string)$p->get('heading','')),150),'show_images'=>self::boolean($p,'show_images',true),'show_nickname'=>self::boolean($p,'show_nickname',false),'show_birth_year'=>self::boolean($p,'show_birth_year',false),'show_age'=>self::boolean($p,'show_age',false),'show_days_until'=>self::boolean($p,'show_days_until',true),'show_club'=>self::boolean($p,'show_club',true),'show_team'=>self::boolean($p,'show_team',false),'show_project'=>self::boolean($p,'show_project',false),'show_person_type'=>self::boolean($p,'show_person_type',false),'show_role'=>self::boolean($p,'show_role',false),'show_deceased'=>self::boolean($p,'show_deceased',true),'show_person_link'=>self::boolean($p,'show_person_link',true),'show_empty'=>self::boolean($p,'show_empty',true)];
	}
	private static function id(mixed $v):int{return max(0,(int)$v);} private static function integer(mixed $v,int $min,int $max):int{return min($max,max($min,(int)$v));}
	/** @param list<string> $allowed */ private static function choice(string $v,array $allowed,string $default):string{return in_array($v,$allowed,true)?$v:$default;}
	private static function boolean(Registry $p,string $name,bool $default):bool{return(int)$p->get($name,$default?1:0)===1;}
	/** @return list<string> */ private static function codes(mixed $values):array{$values=is_array($values)?$values:preg_split('/\s*,\s*/',(string)$values,-1,PREG_SPLIT_NO_EMPTY);return array_values(array_unique(array_filter(array_map(static function(mixed $v):string{$v=trim((string)$v);return preg_match('/^[a-z0-9][a-z0-9_.-]{0,99}$/i',$v)===1?$v:'';},$values?:[]))));}
	private static function truncate(string $v,int $length):string{return preg_match('/^.{0,'.$length.'}/us',$v,$m)===1?$m[0]:substr($v,0,$length);}
}
