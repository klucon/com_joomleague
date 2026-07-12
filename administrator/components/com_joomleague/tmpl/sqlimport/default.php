<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Sqlimport\HtmlView $this */

$style     = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.6';
$token     = Session::getFormToken();
$base      = rtrim(Uri::base(), '/') . '/index.php?option=com_joomleague&format=json&' . $token . '=1&task=sqlimport.';
$dashboard = Route::_('index.php?option=com_joomleague&view=dashboard');
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
<style>
.jl-sql-list{list-style:none;margin:0;padding:0;border:1px solid var(--template-bg-dark-7,#dee2e6);border-radius:.5rem;overflow:hidden}
.jl-sql-list li{display:grid;grid-template-columns:2rem 1fr auto;align-items:center;gap:.8rem;
  padding:.55rem .9rem;border-bottom:1px solid var(--template-bg-dark-7,#eef1f5)}
.jl-sql-list li:last-child{border-bottom:0}
.jl-sql-name{font-family:var(--bs-font-monospace,monospace)}
.jl-sql-count{color:var(--gray-600,#6c757d);font-size:.9rem}
.jl-dot{width:1.6rem;height:1.6rem;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;
  font-weight:800;font-size:.9rem;background:#e9ecef;color:#8a92a0}
.jl-dot.run{background:#cfe2ff;color:#0d6efd;animation:jlspin 1s linear infinite}
.jl-dot.ok{background:#198754;color:#fff}
.jl-dot.fail{background:#dc3545;color:#fff}
@keyframes jlspin{to{transform:rotate(360deg)}}
</style>

<div class="com-joomleague-dashboard com-joomleague-workflow">
	<div class="jl-section-panel mb-4">
		<span class="jl-section-panel__icon icon-database" aria-hidden="true"></span>
		<div class="jl-section-panel__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_TITLE'); ?></p>
			<h1 class="h3 mb-2"><?php echo Text::_('COM_JOOMLEAGUE_SQLIMPORT_TITLE'); ?></h1>
			<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_SQLIMPORT_INTRO'); ?></p>
		</div>
	</div>

	<div class="main-card p-4 mb-4">
		<div class="alert alert-warning" role="alert"><?php echo Text::_('COM_JOOMLEAGUE_SQLIMPORT_WARNING'); ?></div>
		<div class="row g-3 align-items-end">
			<div class="col-md-8">
				<label class="form-label" for="jl-file"><?php echo Text::_('COM_JOOMLEAGUE_SQLIMPORT_FILE'); ?></label>
				<input class="form-control" type="file" id="jl-file" accept=".sql,text/plain">
			</div>
			<div class="col-md-4">
				<button class="btn btn-primary w-100" id="jl-analyze"><?php echo Text::_('COM_JOOMLEAGUE_SQLIMPORT_ANALYZE'); ?></button>
			</div>
		</div>
		<div class="text-danger mt-2 fw-semibold" id="jl-err"></div>
	</div>

	<div class="main-card p-4" id="jl-result" style="display:none">
		<h2 class="h5 mb-2"><?php echo Text::_('COM_JOOMLEAGUE_SQLIMPORT_TABLES'); ?></h2>
		<p class="text-muted" id="jl-prefix"></p>
		<ul class="jl-sql-list" id="jl-tables"></ul>
		<div class="mt-3">
			<button class="btn btn-primary" id="jl-start"><?php echo Text::_('COM_JOOMLEAGUE_SQLIMPORT_START'); ?></button>
			<a class="btn btn-success" id="jl-finish" href="<?php echo $dashboard; ?>" style="display:none"><?php echo Text::_('COM_JOOMLEAGUE_SQLIMPORT_FINISH'); ?></a>
		</div>
	</div>
</div>

<script>
(function(){
  var BASE=<?php echo json_encode($base); ?>, DASH=<?php echo json_encode($dashboard); ?>;
  var file=document.getElementById('jl-file'), analyze=document.getElementById('jl-analyze'),
      err=document.getElementById('jl-err'), result=document.getElementById('jl-result'),
      list=document.getElementById('jl-tables'), start=document.getElementById('jl-start'),
      finish=document.getElementById('jl-finish'), prefix=document.getElementById('jl-prefix');
  var TOKEN='', TABLES=[];
  var LBL_ANALYZE=<?php echo json_encode(Text::_('COM_JOOMLEAGUE_SQLIMPORT_ANALYZE')); ?>;

  analyze.addEventListener('click', function(){
    err.textContent=''; if(!file.files.length){err.textContent=<?php echo json_encode(Text::_('COM_JOOMLEAGUE_SQLIMPORT_ERROR_FILE')); ?>;return;}
    analyze.disabled=true; analyze.textContent='…';
    var fd=new FormData(); fd.append('sql_file', file.files[0]);
    fetch(BASE+'analyze',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();}).then(function(j){
        analyze.disabled=false; analyze.textContent=LBL_ANALYZE;
        if(!j.ok){err.textContent=j.error||'Error';return;}
        TOKEN=j.token; TABLES=j.tables; prefix.textContent=<?php echo json_encode(Text::_('COM_JOOMLEAGUE_SQLIMPORT_PREFIX')); ?>+' '+j.prefix;
        list.innerHTML='';
        TABLES.forEach(function(t,i){
          var li=document.createElement('li'); li.id='jl-row-'+i;
          li.innerHTML='<span class="jl-dot" id="jl-dot-'+i+'">•</span>'+
            '<span class="jl-sql-name">'+t.name+'</span>'+
            '<span class="jl-sql-count">'+t.count+'</span>';
          list.appendChild(li);
        });
        result.style.display=''; finish.style.display='none'; start.style.display='';
      }).catch(function(){analyze.disabled=false;err.textContent='Network error';});
  });

  start.addEventListener('click', function(){
    start.disabled=true;
    var i=0;
    function next(){
      if(i>=TABLES.length){
        fetch(BASE+'cleanupjob',{method:'POST',credentials:'same-origin',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'token='+encodeURIComponent(TOKEN)}).catch(function(){});
        start.style.display='none'; finish.style.display='inline-block'; return;
      }
      var dot=document.getElementById('jl-dot-'+i); dot.className='jl-dot run'; dot.textContent='';
      fetch(BASE+'importtable',{method:'POST',credentials:'same-origin',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'token='+encodeURIComponent(TOKEN)+'&table='+encodeURIComponent(TABLES[i].name)})
        .then(function(r){return r.json();}).then(function(j){
          if(j.ok){dot.className='jl-dot ok';dot.textContent='✓';}
          else{dot.className='jl-dot fail';dot.textContent='✗';dot.title=j.error||'';}
          i++; next();
        }).catch(function(){dot.className='jl-dot fail';dot.textContent='✗';i++;next();});
    }
    next();
  });
})();
</script>
