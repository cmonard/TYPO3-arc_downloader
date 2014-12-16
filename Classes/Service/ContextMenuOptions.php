<?php

namespace Archriss\ArcDownloader\Service;

class ContextMenuOptions {

        protected $fileName = '.htaccess';

        /**
         * Adds directory mask on clic menu
         *
         * @param \TYPO3\CMS\Backend\ClickMenu\ClickMenu $parentObject Back-reference to the calling object
         * @param array $menuItems Current list of menu items
         * @param string $table Name of the table the clicked on item belongs to
         * @param integer $uid Id of the clicked on item
         *
         * @return array Modified list of menu items
         */
        public function main(\TYPO3\CMS\Backend\ClickMenu\ClickMenu $parentObject, $menuItems, $table, $uid) {
                // Activate the menu item only for file module
                if (!$parentObject->isDBmenu) {
                        $fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($table);
                        // add item only for directory
                        if ($fileObject && $fileObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
                                $menuItems[] = 'spacer';
                                list($mode, $icon) = $this->tryToFindMode($table);
                                $menuItems[$mode] = $this->FILE_protect($table, $mode, $icon, $parentObject);
                        }
                }
                return $menuItems;
        }

        public function tryToFindMode($path) {
                $return = array('htprotect', 'unhide');
                // get the repository
                $targetFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($path);
                if (!$targetFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
                        return FALSE;
                }
                // test if is htaccess file and if it's already protected
                if ($targetFolderObject->hasFile($this->fileName)) {
                        $resultObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject(rtrim($path, '/') . '/' . $this->fileName); // make sure we have ending slash on directory name
                        $content = $targetFolderObject->getStorage()->getFileContents($resultObject);
                        foreach (explode("\n", $content) as $line) {
                                if (preg_match('/^deny[ ]+from[ ]+all/', $line)) {
                                        $return = array('htunprotect', 'hide');
                                        break;
                                }
                        }
                }
                return $return;
        }

        public function FILE_protect($path, $action, $icon, $parentObject) {
                $editOnClick = '';
                $loc = 'top.content.list_frame';
                $editOnClick = '' . $loc . '.location.href=top.TS.PATH_typo3+\'tce_file.php?redirect=\'+top.rawurlencode(' . $parentObject->frameLocation(($loc . '.document')) . '.pathname+' . $parentObject->frameLocation(($loc . '.document')) . '.search)+\'' . '&file[' . $action . '][0][data]=' . rawurlencode($path) . '&vC=' . $GLOBALS['BE_USER']->veriCode() . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction') . '\'; hideCM();';
                return $parentObject->linkItem($this->label($action), $parentObject->excludeIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-' . $icon)), $editOnClick . 'return false;');
        }

        public function label($label) {
                return $GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:arc_downloader/Resources/Private/Language/locallang.xlf:cm.' . $label, TRUE));
        }

}
