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
                if(!$this->getHelper()->isEnabledFor($blockType, $object->getId())) {
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
                        if ($fileContent !== $content) {
                            
                            file_put_contents($filePath, $content);
                            $this->getHelper()->getSessionMessage()->addNotice("Static content updated to file: " . $templatePath . $filename);
                            
                            if($this->getHelper()->isTailwindCompileEnabled($storeId)) {
                                $this->compileTailwindcss($templatePath . $filename, $storeId);
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
                if(!$this->getHelper()->isEnabledFor($blockType, $object->getId())) {
                    return;
                }

                $templatePath = isset($templatePaths[$storeId]) ? $templatePaths[$storeId] : null;
                if ($templatePath !== null) {
                    
                    $filePath = Mage::getBaseDir('design') . DS . $templatePath . $filename;
                    
                    // Check if the file exists
                    if (file_exists($filePath)) {
                        $fileContent = file_get_contents($filePath);
                        
                        
                        // Write the content to the file
                        if ($fileContent !== $content) {
                            
                            if($this->getHelper()->isTailwindCompileEnabled($storeId)) {
                                $this->compileTailwindcss($templatePath . $filename, $storeId);
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
                            $this->compileTailwindcss($templatePath . $filename, $storeId);
                        }
                    }
                }  
            } 
        }
    }

    protected function compileTailwindcss($filePath, $storeId) {
        // getBaseDir lib
        $skinPaths = $this->getHelper()->getSkinPaths();
        $skinPath = isset($skinPaths[$storeId]) ? $skinPaths[$storeId] : null;
        $cssOutputPath = "../../skin" . DS . $skinPath . "tailwind.css";
        $filePath = "../../app/design" . DS . $filePath;
        if ($skinPath !== null) {
            $tailwindCli =  "cd " . Mage::getBaseDir('lib') . DS . "tailwindcss; ./tailwindcss";
            $cmd =  sprintf(
                '%s --content %s -o %s --minify 2>&1',
                $tailwindCli,
                $filePath,
                $cssOutputPath
            );
            exec($cmd, $output, $return);            
            if ($return === 0) {
                $this->getHelper()->getSessionMessage()->addNotice(
                    sprintf(" %s - styles compiled to %s", 
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
