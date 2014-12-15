<?php
if (!defined('TYPO3_MODE')) {
        die('Access denied.');
}

$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][$_EXTKEY] = array(
    'name' => 'Archriss\\ArcDownloader\\Service\\ContextMenuOptions',
);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][$_EXTKEY] = 'Archriss\\ArcDownloader\\HtProtect';
