<?php
class MM_CmsContentFileMode_Model_Observer
{

    private $_shouldRecompileTailwind = false;

    /**
     * Load content from file
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function afterLoad(Varien_Event_Observer $observer)
    {
        $object = $observer->getObject();

        $this->_processEnabledObjectForStoreId($object, [$this, 'syncFileToContent']);
        if ($this->_shouldRecompileTailwind) {
            $this->_processEnabledObjectForStoreId($object, [$this, 'processTailwindCss']);
        }
        $this->_processEnabledObjectForStoreId($object, [$this, 'registerTailwindCss']);
    }
    
    /**
     * Save content to file
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function afterSave(Varien_Event_Observer $observer)
    {
        $object = $observer->getObject();
        
        $this->_processEnabledObjectForStoreId($object, [$this, 'renameFile']);

        $this->_processEnabledObjectForStoreId($object, [$this, 'syncContentToFile']);
        if ($this->_shouldRecompileTailwind) {
            $this->_processEnabledObjectForStoreId($object, [$this, 'processTailwindCss']);
        }
    }

    protected function syncFileToContent($object, $filePath, $storeId)
    {        
        if (file_exists($filePath)) {
            $fileContent = file_get_contents($filePath);
            // Write the content to the file
            $_contentDiffers = $fileContent !== $object->getContent();
            if ($_contentDiffers) {             
                $object->setContent($fileContent);
                $object->save();
                
                $this->getHelper()->getSessionMessage()->addNotice(
                    sprintf("Loaded new content from file: %s",  $this->getHelper()->stripBaseDir($filePath))
                );
            } 
            $this->_shouldRecompileTailwind = $_contentDiffers || $this->getHelper()->forceTailwindCompile($storeId);
        } else {
            // silently create new file
            file_put_contents($filePath, $object->getContent());
            $this->_shouldRecompileTailwind = true;
            
            $this->getHelper()->getSessionMessage()->addSuccess(
                sprintf("Static content file not found, silently created new file: %s",  $this->getHelper()->stripBaseDir($filePath))
            );
        }
    }

    protected function syncContentToFile($object, $filePath, $storeId)
    {
        if (file_exists($filePath)) {
            $fileContent = file_get_contents($filePath);
            $_contentDiffers = $fileContent !== $object->getContent();
            if ($_contentDiffers) {
                file_put_contents($filePath, $object->getContent());
                
                $this->getHelper()->getSessionMessage()->addNotice(
                    sprintf("Change saved to file: %s", $this->getHelper()->stripBaseDir($filePath))
                );
            }
            $this->_shouldRecompileTailwind = $_contentDiffers || $this->getHelper()->forceTailwindCompile($storeId);
        } else {
            file_put_contents($filePath, $object->getContent());
            $this->_shouldRecompileTailwind = true;
            
            $this->getHelper()->getSessionMessage()->addNotice(
                sprintf("Change saved to file: %s", $this->getHelper()->stripBaseDir($filePath))
            );
        }
    }

    protected function renameFile($object, $filePath, $storeId)
    {
        if ($object->dataHasChangedFor('identifier') AND $object->getOrigData('identifier') !== null) {
            $origFilename = $this->getHelper()->getFilenameFromIdentifier($object->getIdentifier());
    
            $oldIdentifier = $object->getOrigData('identifier');
            $oldFilename = $this->getHelper()->getFilenameFromIdentifier($oldIdentifier);
            $oldFilePath = str_replace($origFilename, $oldFilename, $filePath);
    
            if (file_exists($oldFilePath)) {
                rename($oldFilePath, $filePath);
                $this->getHelper()->getSessionMessage()->addWarning(
                    sprintf("Rename %s → %s",  $this->getHelper()->stripBaseDir($oldFilePath),  $this->getHelper()->stripBaseDir($filePath))
                );
            } else {
                $this->getHelper()->getSessionMessage()->addError(
                    sprintf("Rename error: File %s not found",  $this->getHelper()->stripBaseDir($oldFilePath))
                );
            }
        }
    }

    protected function processTailwindCss($object, $filePath, $storeId)
    {
        if(!$this->getHelper()->isTailwindCompileEnabled($storeId)) {
            return;
        }
        if (!file_exists($filePath)) {
            $this->getHelper()->getSessionMessage()->addError("Process tailwindcss error: File " . $filePath . " not found");
            return;
        }
        $this->getHelperTailwind()->compileTailwindcss($storeId);
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

    private function _processEnabledObjectForStoreId($object, $_callback)
    {
        $blockType = $this->_detectBlockType($object);
        if ($blockType === null) {
            return $object;
        }
        $identifier = $object->getIdentifier();
        if ($identifier === null) {
            return;
        }
        
        $stores = $object->getResource()->lookupStoreIds($object->getId());
        if ($stores[0] == 0) {
            $stores = array_keys(Mage::app()->getStores());
        }
        
        foreach ($stores as $storeId) {
            if(!$this->getHelper()->isEnabledFor($blockType, $object->getId(), $storeId)) {
                return;
            }
            $templatePath = $this->getHelper()->getTemplatePathByStoreId($storeId, $blockType);
            if (!$templatePath) {
                return;
            }
            $filename = $this->getHelper()->getFilenameFromIdentifier($object->getIdentifier());
            $filePath = Mage::getBaseDir('design') . DS . $templatePath . $filename;

            call_user_func_array($_callback, [$object, $filePath, $storeId]);

        }

        return $object;
    }

	public function configTinyMceEditor(Varien_Event_Observer $observer)
	{
        // add tinymce tailwindcss content preview
	    $config = $observer->getConfig();
        if(is_array($this->getHelperTailwind()->getTinyMceAdditionalCss())){

	    	$config->setContentCss( 
                implode(',', [
                    $config->getContentCss(),
                    implode(',', $this->getHelperTailwind()->getTinyMceAdditionalCss())
                ])
            );
	    }

        // config template tailwindcss
        $configPlugins = $config->getData('plugins');
	    $templateWysiwygPlugin = array(
	    	array(
	    		'name' => 'template',
            	'src' => Mage::getBaseUrl('js').'tinymce/plugins/template/plugin.min.js'
            )
	    );
        $config->setPlugins(array_merge($configPlugins, $templateWysiwygPlugin));
        $config->setContentCss( 
            implode(',', [
                $config->getContentCss(),
                $this->getHelperTailwindTinymcetemplates()->getPreviewCss()
            ])
        );

	}

    protected function registerTailwindCss($object, $filePath, $storeId)
    {
        if($this->getHelper()->isTailwindCompileEnabled($storeId)) {
            $this->getHelperTailwind()->registerTailwindCss($storeId);
        }
    }

    /**
     * @return MM_CmsContentFileMode_Helper_Data
     */
    protected function getHelper() {
        return Mage::helper('mm_cmscontentfilemode');
    }

    /**
     * @return MM_CmsContentFileMode_Helper_Tailwindcss
     */
    protected function getHelperTailwind() {
        return Mage::helper('mm_cmscontentfilemode/tailwindcss');
    }

    /**
     * @return MM_CmsContentFileMode_Helper_Tailwindcss_Tinymcetemplates
     */
    protected function getHelperTailwindTinymcetemplates() {
        return Mage::helper('mm_cmscontentfilemode/tailwindcss_tinymcetemplates');
    }

}
