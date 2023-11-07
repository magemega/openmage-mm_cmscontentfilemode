<?php
class MM_CmsContentFileMode_Model_Observer
{
    public function saveContentToFile(Varien_Event_Observer $observer)
    {
        $object = $observer->getObject();
        $blockType = $this->_detectBlockType($object);
        
        if ($blockType) {
            $identifier = $object->getIdentifier();
            if (!$identifier) {
                return;
            }

            $content = $object->getContent();

            $filename = $this->_generateFilename($identifier);
            $stores = $object->getResource()->lookupStoreIds($object->getId());
            if ($stores[0] == 0) {
                $stores = array_keys(Mage::app()->getStores());
            }
            $templatePaths = $this->getHelper()->getTemplatePaths($blockType);

            foreach ($stores as $storeId) {
                if(!$this->getHelper()->isEnabledFor($blockType, $object->getId(), $storeId)) {
                    return;
                }

                $templatePath = isset($templatePaths[$storeId]) ? $templatePaths[$storeId] : null;
                if ($templatePath !== null) {
                    
                    $filePath = Mage::getBaseDir('design') . DS . $templatePath . $filename;

                    if ($object->dataHasChangedFor('identifier')) {
                        $oldIdentifier = $object->getOrigData('identifier');
                        $oldFilename = $this->_generateFilename($oldIdentifier);
                        $oldFilePath = Mage::getBaseDir('design') . DS . $templatePath . $oldFilename;
                        if (file_exists($oldFilePath)) {
                            rename($oldFilePath, $filePath);
                            $this->getHelper()->getSessionMessage()->addNotice("Rename " . $templatePath . $oldFilename . " to " . $templatePath . $filename);
                        }
                    }

                    if (file_exists($filePath)) {
                       
                        
                        $fileContent = file_get_contents($filePath);
                        if ($fileContent !== $content || $this->getHelper()->forceTailwindCompile($storeId)) {
                            
                            file_put_contents($filePath, $content);
                            $this->getHelper()->getSessionMessage()->addNotice("Static content updated to file: " . $templatePath . $filename);
                            
                            if($this->getHelper()->isTailwindCompileEnabled($storeId)) {
                                $this->compileTailwindcss($storeId);
                            }
                        }
                    } else {
                        file_put_contents($filePath, $content);
                        $this->getHelper()->getSessionMessage()->addNotice("Static content saved to file: " . $templatePath . $filename);
                    }
                }  
            }

        }
    }

    public function loadContentFromFile(Varien_Event_Observer $observer)
    {
        $object = $observer->getObject();
        $blockType = $this->_detectBlockType($object);
        
        if ($blockType) {
            $identifier = $object->getIdentifier();
            if (!$identifier) {
                return;
            }
            $content = $object->getContent();

            $filename = $this->_generateFilename($identifier);
            $stores = $object->getResource()->lookupStoreIds($object->getId());
            if ($stores[0] == 0) {
                $stores = array_keys(Mage::app()->getStores());
            }
            $templatePaths = $this->getHelper()->getTemplatePaths($blockType);
            
            foreach ($stores as $storeId) {
                if(!$this->getHelper()->isEnabledFor($blockType, $object->getId(), $storeId)) {
                    return;
                }

                $templatePath = isset($templatePaths[$storeId]) ? $templatePaths[$storeId] : null;
                if ($templatePath !== null) {
                    
                    $filePath = Mage::getBaseDir('design') . DS . $templatePath . $filename;
                    
                    // Check if the file exists
                    if (file_exists($filePath)) {
                        $fileContent = file_get_contents($filePath);
                        
                        
                        // Write the content to the file
                        if ($fileContent !== $content || $this->getHelper()->forceTailwindCompile($storeId)) {
                            
                            if($this->getHelper()->isTailwindCompileEnabled($storeId)) {
                                $this->compileTailwindcss($storeId);
                            }                           

                            $object->setContent($fileContent);
                            $object->save();
                            $this->getHelper()->getSessionMessage()->addNotice("Static content updated from file: " . $templatePath . $filename);
                        }
                    } else {
                        // silently create new file
                        $this->getHelper()->getSessionMessage()->addNotice("Static content file not found, silently created: " . $templatePath . $filename);
                        file_put_contents($filePath, $content);
                        if($this->getHelper()->isTailwindCompileEnabled($storeId)) {
                            $this->compileTailwindcss($storeId);
                        }
                    }
                }  
            } 
        }
    }

    protected function compileTailwindcss($storeId) {

        $skinPaths = $this->getHelper()->getSkinPaths();
        $skinPath = isset($skinPaths[$storeId]) ? $skinPaths[$storeId] : null;
        $cssOutputPath = "../../skin" . DS . $skinPath . "tailwind.css";
        
        $templatePath = "../../app/design/" . DS . $this->getHelper()->getTemplatePathContent($storeId);
        if ($skinPath !== null && $templatePath !== null) {
            // Run compile tailwindcss only once per request
            $_lockName = $this->_getCompilerLockName($skinPath, $storeId);
            if (Mage::registry($_lockName)) {
                return;
            }   
            Mage::register($_lockName, true);

            $extraCustomCmds = [];

            $customTailwindConfig = Mage::getBaseDir('skin') . DS . $skinPath . "tailwind.config.js";
            if (file_exists($customTailwindConfig)) {
                $extraCustomCmds[] = sprintf(
                    '--config %s',
                    $customTailwindConfig
                );
            }

            $customTailwindBaseCss = Mage::getBaseDir('skin') . DS . $skinPath . "tailwind-base.css";
            if (file_exists($customTailwindBaseCss)) {
                $extraCustomCmds[] = sprintf(
                    '--input %s',
                    $customTailwindBaseCss
                );
            }

            $tailwindCli =  "cd " . Mage::getBaseDir('lib') . DS . "tailwindcss; ./tailwindcss";
            $cmd =  sprintf(
                '%s %s --content \'%s\' -o %s --minify 2>&1',
                $tailwindCli,
                implode(" ", $extraCustomCmds),
                $templatePath,
                $cssOutputPath
            );
            exec($cmd, $output, $return);            
            if ($return === 0) {
                $this->getHelper()->getSessionMessage()->addNotice(
                    sprintf("%s%s - styles compiled to %s", 
                        ($cmdCustomConfig ? "Load Custom config " . $skinPath . "tailwind.config.js" . " " : ""),
                        implode("\n", $output), 
                        $skinPath . "tailwind.css"
                    )
                );                
            } else {
                $this->getHelper()->getSessionMessage()->addError("Problem with compiling tailwindcss, check chmod +x permission for ./lib/tailwindcss/tailwindcss");
                Mage::logException(new Exception( $output[0] ." [..truncate..] \n\n"));
            }
            
            return $return === 0;
        }
    }

    private function _getCompilerLockName($skinPath, $storeId) {
        return 'tailwind_compile_lock_'.md5($skinPath.$storeId);
    }

    /**
     * Detect block type
     *
     * @param Varien_Object $object
     * @return string|null
     */
    private function _detectBlockType(Varien_Object $object) {
        switch (true) {
            case $object instanceof Mage_Cms_Model_Block:
                return MM_CmsContentFileMode_Helper_Data::TYPE_CMSBLOCK;
                break;
            case $object instanceof Mage_Cms_Model_Page:
                return MM_CmsContentFileMode_Helper_Data::TYPE_CMSPAGE;
                break;            
            default:
                return null;
                break;
        }
    }

    /**
     * Generate filename from identifier
     *
     * @param string $identifier
     * @return string
     */
    protected function _generateFilename($identifier)
    {
        $identifier = preg_replace('/[^a-z0-9]/i', '_', $identifier);
        return $identifier . '.html';
    }

    /**
     * @return MM_CmsContentFileMode_Helper_Data
     */
    protected function getHelper() {
        return Mage::helper('mm_cmscontentfilemode');
    }
}
