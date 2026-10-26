<?php
declare(strict_types=1);
try {
    require '/source/tools/install-encounter-local.php';
} catch (Throwable $error) {
    echo json_encode(Joomla\CMS\Factory::getApplication()->getMessageQueue(), JSON_THROW_ON_ERROR) . "\n";
    throw $error;
}
