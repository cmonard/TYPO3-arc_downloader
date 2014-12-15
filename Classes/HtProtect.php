<?php

namespace Archriss\ArcDownloader;

class HtProtect implements \TYPO3\CMS\Core\Utility\File\ExtendedFileUtilityProcessDataHookInterface {

        protected $regEx = '/([#]?)(deny[ ]+from[ ]+all)/';
        protected $fileName = '.htaccess';

        /**
         * Post-process a file action.
         *
         * @param string $action The action
         * @param array $cmdArr The parameter sent to the action handler
         * @param array $result The results of all calls to the action handler
         * @param \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $parentObject Parent object
         * @return void
         */
        public function processData_postProcessAction($action, array $cmdArr, array $result, \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $parentObject) {
                if (!$parentObject->isDBmenu) {
                        $fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($cmdArr['data']);
                        if ($fileObject && $fileObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
                                switch ($action) {
                                        case 'htprotect':
                                                $result[] = $this->func_protect($cmdArr['data'], $parentObject);
                                                break;
                                        case 'htunprotect':
                                                $result[] = $this->func_unprotect($cmdArr['data'], $parentObject);
                                                break;
                                }
                        }
                }
        }

        public function func_protect($path, $pObj) {
                if (!$pObj->isInit) {
                        return FALSE;
                }
                $targetFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($path);
                if (!$targetFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
                        return FALSE;
                }
                $resultObject = NULL;
                try {
                        // we make the file or get it's content
                        if ($targetFolderObject->hasFile($this->fileName)) {
                                $resultObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject(rtrim($path, '/') . '/' . $this->fileName); // make sure we have ending slash on directory name
                                $content = $targetFolderObject->getStorage()->getFileContents($resultObject);
                        } else {
                                $resultObject = $targetFolderObject->createFile($this->fileName);
                        }
                        // We create content
                        if (!$content) {
                                $content = 'deny from all';
                        } else {
                                $content = preg_replace($this->regEx, '$2', $content);
                        }
                        // We save the file content
                        $resultObject->setContents($content);
                } catch (\TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException $e) {
                        $this->writeLog(8, 1, 106, 'Extension of file "%s" was not allowed!', array($this->fileName));
                } catch (\TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException $e) {
                        $this->writelog(8, 1, 103, 'You are not allowed to create files!', '');
                } catch (\TYPO3\CMS\Core\Resource\Exception\NotInMountPointException $e) {
                        $this->writelog(8, 1, 102, 'Destination path "%s" was not within your mountpoints!', array($targetFolderObject->getIdentifier()));
                } catch (\TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException $e) {
                        $this->writelog(8, 1, 106, 'File name "%s" was not allowed!', $this->fileName);
                } catch (\RuntimeException $e) {
                        $this->writelog(8, 1, 100, 'File "%s" was not created! Write-permission problem in "%s"?', array($this->fileName, $targetFolderObject->getIdentifier()));
                }
                return $resultObject;
        }

        public function func_unprotect($path, $pObj) {
                if (!$pObj->isInit) {
                        return FALSE;
                }
                $targetFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($path);
                if (!$targetFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
                        return FALSE;
                }
                $resultObject = FALSE;
                try {
                        // we make the file or get it's content
                        if ($targetFolderObject->hasFile($this->fileName)) {
                                $resultObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject(rtrim($path, '/') . '/' . $this->fileName); // make sure we have ending slash on directory name
                                $content = $targetFolderObject->getStorage()->getFileContents($resultObject);
                                // We create content
                                if ($content !== '') {
                                        $content = preg_replace($this->regEx, '#$2', $content);
                                        // We save the file content
                                        $resultObject->setContents($content);
                                }
                        }
                } catch (\TYPO3\CMS\Core\Resource\Exception\InsufficientUserPermissionsException $e) {
                        $this->writelog(9, 1, 104, 'You are not allowed to edit files!', '');
                } catch (\TYPO3\CMS\Core\Resource\Exception\InsufficientFileWritePermissionsException $e) {
                        $this->writelog(9, 1, 100, 'File "%s" was not saved! Write-permission problem?', array($fileObject->getIdentifier()));
                } catch (\TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException $e) {
                        $this->writelog(9, 1, 100, 'File "%s" was not saved! File extension rejected!', array($fileObject->getIdentifier()));
                }
                return $resultObject;
        }

}
