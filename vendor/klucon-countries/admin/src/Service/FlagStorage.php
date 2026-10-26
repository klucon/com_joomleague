<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Service;
defined('_JEXEC') or die;

final class FlagStorage
{
    public static function validate(string $bytes,int $limit=1048576): array
    {
        if($bytes===''||strlen($bytes)>$limit)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_SIZE');
        if(str_contains(substr($bytes,0,300),'<svg')||str_starts_with(ltrim($bytes),'<?xml'))return self::svg($bytes);
        $info=@getimagesizefromstring($bytes);
        if(!$info||!in_array($info['mime'],['image/png','image/jpeg','image/webp'],true)||$info[0]<1||$info[1]<1||$info[0]>8192||$info[1]>8192||$info[0]*$info[1]>16000000||!function_exists('imagecreatefromstring'))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
        $image=@imagecreatefromstring($bytes);if(!$image)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
        imagealphablending($image,false);imagesavealpha($image,true);ob_start();imagepng($image,null,9);$clean=ob_get_clean();imagedestroy($image);
        if(strlen($clean)>$limit*4)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_SIZE');
        return ['bytes'=>$clean,'mime'=>'image/png','extension'=>'png'];
    }
    private static function svg(string $bytes): array
    {
        if(preg_match('/<!DOCTYPE|<!ENTITY/i',$bytes))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
        $old=libxml_use_internal_errors(true);
        try{
            $document=new \DOMDocument();$document->resolveExternals=false;$document->substituteEntities=false;
            if(!$document->loadXML($bytes,LIBXML_NONET)||!$document->documentElement||$document->documentElement->localName!=='svg')throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
            $xpath=new \DOMXPath($document);if($xpath->query('//processing-instruction()')->length)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
            $allowed=['svg','g','defs','path','rect','circle','ellipse','line','polyline','polygon','use','clipPath','linearGradient','radialGradient','stop','title','desc'];
            $attributes=['id','viewBox','width','height','x','y','cx','cy','r','rx','ry','d','points','fill','stroke','stroke-width','stroke-linecap','stroke-linejoin','stroke-miterlimit','fill-rule','clip-rule','opacity','fill-opacity','stroke-opacity','transform','preserveAspectRatio','clip-path','gradientUnits','gradientTransform','offset','stop-color','stop-opacity','x1','x2','y1','y2','fx','fy','href','version'];
            $nodes=$document->getElementsByTagName('*');if($nodes->length>20000)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
            foreach($nodes as $element){
                if($element->namespaceURI!=='http://www.w3.org/2000/svg'||!in_array($element->localName,$allowed,true))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
                $depth=0;for($p=$element->parentNode;$p instanceof \DOMElement;$p=$p->parentNode)if(++$depth>32)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
                foreach($element->attributes as $attribute){
                    if($attribute->namespaceURI==='http://www.w3.org/2000/xmlns/')continue;
                    if(!in_array($attribute->localName,$attributes,true)||($attribute->namespaceURI&&$attribute->namespaceURI!=='http://www.w3.org/1999/xlink'))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
                    $value=trim($attribute->value);
                    if($attribute->localName==='href'){
                        if(!preg_match('/\A#[A-Za-z_][A-Za-z0-9_.-]{0,100}\z/D',$value))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
                    }elseif(preg_match('/url\s*\(/i',$value)){
                        if(!preg_match('/\Aurl\(#[A-Za-z_][A-Za-z0-9_.-]{0,100}\)\z/D',$value))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
                    }elseif(preg_match('/[\\\\:;@<>]|(?:javascript|expression|data\s*:)/i',$value))throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
                }
            }
            $root=$document->documentElement;
            $box=preg_split('/[\s,]+/',trim($root->getAttribute('viewBox')));
            $width=$root->getAttribute('width');$height=$root->getAttribute('height');
            if(count($box)===4){$width=$box[2];$height=$box[3];}
            if(!is_numeric($width)||!is_numeric($height)||!is_finite((float)$width)||!is_finite((float)$height)||(float)$width<=0||(float)$height<=0||(float)$width>8192||(float)$height>8192)throw new \InvalidArgumentException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_CONTENT');
            return ['bytes'=>$document->saveXML($root),'mime'=>'image/svg+xml','extension'=>'svg'];
        }finally{libxml_clear_errors();libxml_use_internal_errors($old);}
    }
    public function __construct(private readonly Registry $registry) {}
    public function store(string $uuid,string $bytes,int $actor,int $limit=1048576): string
    {
        $unit=$this->registry->find($uuid);if(!$unit||(int)$unit->state===-2)throw new \RuntimeException('JERROR_PAGE_NOT_FOUND',404);
        $asset=self::validate($bytes,$limit);$hash=hash('sha256',$asset['bytes']);
        $relative='images/com_kluconcountries/'.$unit->kind.'/'.$uuid.'/flags/'.$hash.'.'.$asset['extension'];
        $directory=JPATH_ROOT.'/'.dirname($relative);
        for($path=$directory;$path!==JPATH_ROOT;$path=dirname($path))if(is_link($path))throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_WRITE');
        if(!is_dir($directory)&&!\Joomla\Filesystem\Folder::create($directory,0755))throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_WRITE');
        $base=realpath(JPATH_ROOT.'/images');$target=realpath($directory);
        if(!$base||!$target||!str_starts_with($target,$base.DIRECTORY_SEPARATOR))throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_WRITE');
        for($path=$directory;$path!==JPATH_ROOT;$path=dirname($path))if(is_link($path))throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_WRITE');
        $filename=JPATH_ROOT.'/'.$relative;
        if(!is_file($filename)){
            $stream=@fopen($filename,'x');if(!$stream)throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_WRITE');
            $written=false;
            try{$written=fwrite($stream,$asset['bytes'])===strlen($asset['bytes']);}finally{fclose($stream);if(!$written)unlink($filename);}
            if(!$written)throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_WRITE');
            chmod($filename,0644);
        }
        if(is_link($filename)||!hash_equals($hash,hash_file('sha256',$filename)))throw new \RuntimeException('COM_KLUCONCOUNTRIES_ERROR_UPLOAD_WRITE');
        $db=$this->registry->db;$db->transactionStart(true);
        try{$this->registry->lock();
            if(!$this->registry->flagAllowed($relative))Schema::insert($db,'#__kluconcountries_assets',['path'=>$relative,'sha256'=>$hash,'mime'=>$asset['mime'],'bytes'=>strlen($asset['bytes']),'created_at'=>gmdate('c'),'created_by'=>$actor]);
            $db->transactionCommit(true);
        }catch(\Throwable $error){$db->transactionRollback(true);throw $error;}
        return $relative;
    }
}
