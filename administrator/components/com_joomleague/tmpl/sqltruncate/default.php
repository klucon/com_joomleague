<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var Joomleague\Component\Joomleague\Administrator\View\Sqltruncate\HtmlView $this */

$style     = Uri::root(true) . '/media/com_joomleague/css/dashboard.css?v=0.13.9';
$token     = Session::getFormToken();
$base      = rtrim(Uri::base(), '/') . '/index.php?option=com_joomleague&format=json&' . $token . '=1&task=sqltruncate.';
$tools     = Route::_('index.php?option=com_joomleague&view=tools');
$siteName  = trim((string) Factory::getApplication()->get('sitename', ''));

$userTotal = array_sum($this->counts['user']);
$refTotal  = array_sum($this->counts['reference']);
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
<style>
.jl-truncate-list{list-style:none;margin:0;padding:0;border:1px solid var(--template-bg-dark-7,#dee2e6);border-radius:.5rem;overflow:hidden}
.jl-truncate-list li{display:grid;grid-template-columns:1fr auto;gap:.8rem;padding:.4rem .9rem;border-bottom:1px solid var(--template-bg-dark-7,#eef1f5);font-size:.9rem}
.jl-truncate-list li:last-child{border-bottom:0}
.jl-truncate-name{font-family:var(--bs-font-monospace,monospace)}
.jl-truncate-count{color:var(--gray-600,#6c757d);font-variant-numeric:tabular-nums}
.jl-truncate-count.has-rows{color:#c62828;font-weight:700}
</style>

<div class="com-joomleague-dashboard com-joomleague-workflow">
	<div class="jl-section-panel mb-4">
		<span class="jl-section-panel__icon icon-warning" aria-hidden="true"></span>
		<div class="jl-section-panel__content">
			<p class="jl-dashboard-eyebrow mb-2"><?php echo Text::_('COM_JOOMLEAGUE_TOOLS_TITLE'); ?></p>
			<h1 class="h3 mb-2"><?php echo Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_TITLE'); ?></h1>
			<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_INTRO'); ?></p>
		</div>
	</div>

	<div class="alert alert-danger" role="alert">
		<strong><?php echo Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_WARNING_TITLE'); ?></strong>
		<p class="mb-1 mt-2"><?php echo Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_WARNING_TEXT'); ?></p>
		<p class="mb-0"><?php echo Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_BACKUP_WARNING'); ?></p>
	</div>

	<div class="main-card p-4 mb-4">
		<h2 class="h5 mb-1"><?php echo Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_CATEGORY_USER'); ?></h2>
		<p class="text-muted"><?php echo Text::sprintf('COM_JOOMLEAGUE_SQLTRUNCATE_TOTAL_ROWS', $userTotal); ?></p>
		<ul class="jl-truncate-list">
			<?php foreach ($this->counts['user'] as $table => $count) : ?>
				<li>
					<span class="jl-truncate-name"><?php echo $this->escape($table); ?></span>
					<span class="jl-truncate-count<?php echo $count > 0 ? ' has-rows' : ''; ?>"><?php echo number_format($count, 0, ',', ' '); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="main-card p-4 mb-4">
		<h2 class="h5 mb-1"><?php echo Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_CATEGORY_REFERENCE'); ?></h2>
		<p class="text-muted"><?php echo Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_CATEGORY_REFERENCE_DESC'); ?></p>
		<p class="text-muted"><?php echo Text::sprintf('COM_JOOMLEAGUE_SQLTRUNCATE_TOTAL_ROWS', $refTotal); ?></p>
		<ul class="jl-truncate-list">
			<?php foreach ($this->counts['reference'] as $table => $count) : ?>
				<li>
					<span class="jl-truncate-name"><?php echo $this->escape($table); ?></span>
					<span class="jl-truncate-count<?php echo $count > 0 ? ' has-rows' : ''; ?>"><?php echo number_format($count, 0, ',', ' '); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="form-check mt-3">
			<input class="form-check-input" type="checkbox" id="jl-tr-include-ref">
			<label class="form-check-label" for="jl-tr-include-ref"><?php echo Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_INCLUDE_REFERENCE_LABEL'); ?></label>
		</div>
	</div>

	<div class="main-card p-4" id="jl-tr-confirm-card">
		<label class="form-label" for="jl-tr-confirm">
			<?php echo Text::sprintf('COM_JOOMLEAGUE_SQLTRUNCATE_CONFIRM_LABEL', '<strong>' . $this->escape($siteName) . '</strong>'); ?>
		</label>
		<input class="form-control" type="text" id="jl-tr-confirm" autocomplete="off">
		<button class="btn btn-danger mt-3" id="jl-tr-submit" disabled><?php echo Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_CONFIRM_BUTTON'); ?></button>
		<div class="mt-2" id="jl-tr-msg"></div>
	</div>
</div>

<script>
(function(){
  var BASE=<?php echo json_encode($base); ?>, SITENAME=<?php echo json_encode($siteName); ?>, TOOLS=<?php echo json_encode($tools); ?>;
  var confirmInput=document.getElementById('jl-tr-confirm'), submit=document.getElementById('jl-tr-submit'),
      includeRef=document.getElementById('jl-tr-include-ref'), msg=document.getElementById('jl-tr-msg');

  confirmInput.addEventListener('input', function(){
    submit.disabled = confirmInput.value !== SITENAME;
  });

  submit.addEventListener('click', function(){
    submit.disabled = true;
    msg.textContent = '';
    var body = 'confirm=' + encodeURIComponent(confirmInput.value) + '&include_reference=' + (includeRef.checked ? '1' : '0');
    fetch(BASE + 'truncate', {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body})
      .then(function(r){return r.json();}).then(function(j){
        if (!j.ok) {
          msg.innerHTML = '<span class="text-danger">' + (j.error || 'Error') + '</span>';
          submit.disabled = confirmInput.value !== SITENAME;
          return;
        }
        msg.innerHTML = '<span class="text-success">' + <?php echo json_encode(Text::_('COM_JOOMLEAGUE_SQLTRUNCATE_SUCCESS')); ?> + '</span>';
        window.setTimeout(function(){ window.location.href = TOOLS; }, 1500);
      }).catch(function(){
        msg.innerHTML = '<span class="text-danger">Network error</span>';
        submit.disabled = confirmInput.value !== SITENAME;
      });
  });
})();
</script>
