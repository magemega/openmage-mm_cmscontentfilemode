<?php
class MM_CmsContentFileMode_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_CONFIG_PACKAGE = 'design/package/name';
    const XML_PATH_CONFIG_THEME = 'design/theme/template';
    const XML_PATH_CONFIG_TAILWIND = 'cms/mm_cmscontentfilemode/enable_tailwindcss';
    const XML_PATH_CONFIG_TAILWIND_FORCE_COMPILE = 'cms/mm_cmscontentfilemode/force_tailwindcompile';

    const TYPE_CMSBLOCK = 'static_block';
    const TYPE_CMSPAGE = 'static_page';

    public function getTemplatePaths($type = self::TYPE_CMSBLOCK)
    {
        $templatePaths = array();

        $stores = Mage::app()->getStores();
        foreach ($stores as $store) {
            $package = Mage::getStoreConfig(self::XML_PATH_CONFIG_PACKAGE, $store->getId());
            if (!$package) {
                continue;
            }
            $theme = Mage::getStoreConfig(self::XML_PATH_CONFIG_THEME, $store->getId()) ?: 'default';
            $templatePath = sprintf('frontend/%s/%s/template/cms/%s/', 
                $package,
                $theme,
                $type);


            $templateDesignPath = Mage::getBaseDir('design') . DS . $templatePath;
            if (!is_dir($templateDesignPath)) {
                mkdir($templateDesignPath, 0777, true);
            }
            $templatePaths[$store->getId()] = $templatePath;
        }

        return $templatePaths;
    }

    public function getTemplatePathByStoreId($storeId, $type = self::TYPE_CMSBLOCK)
    {
        $templatePaths = $this->getTemplatePaths($type);
        if (!isset($templatePaths[$storeId])) {
            return null;
        }
        return $templatePaths[$storeId];
    }

    /**
     * Get template path content for tailwindcss
     *
     * @param int $storeId
     * @return string|null
     */
    public function getTemplatePathContent($storeId) 
    {
        $package = Mage::getStoreConfig(self::XML_PATH_CONFIG_PACKAGE, $storeId);
        if (!$package) {
            return null;
        }
        $theme = Mage::getStoreConfig(self::XML_PATH_CONFIG_THEME, $storeId) ?: 'default';
        $templatePath = sprintf('frontend/%s/%s/template/cms/**/*.html', 
            $package,
            $theme);
        return $templatePath;
    }

    public function getSkinPaths()
    {
        $templatePaths = array();

        $stores = Mage::app()->getStores();
        foreach ($stores as $store) {
            $package = Mage::getStoreConfig(self::XML_PATH_CONFIG_PACKAGE, $store->getId());
            if (!$package) {
                continue;
            }
            $theme = Mage::getStoreConfig(self::XML_PATH_CONFIG_THEME, $store->getId()) ?: 'default';
            $templatePath = sprintf('frontend/%s/%s/css/', 
                $package,
                $theme);


            $templateSkinPath = Mage::getBaseDir('skin') . DS . $templatePath;
            if (!is_dir($templateSkinPath)) {
                mkdir($templateSkinPath, 0777, true);
            }
            $templatePaths[$store->getId()] = $templatePath;
        }

        return $templatePaths;
    }

    public function getSkinPathByStoreId($storeId)
    {
        $skinPaths = $this->getSkinPaths();
        if (!isset($skinPaths[$storeId])) {
            return null;
        }
        return $skinPaths[$storeId];
    }

    /**
     * Generate filename from identifier
     *
     * @param string $identifier
     * @return string
     */
    public function getFilenameFromIdentifier($identifier)
    {
        $identifier = preg_replace('/[^a-z0-9]/i', '_', (string) $identifier);
        return $identifier . '.html';
    }

    public function isTailwindCompileEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CONFIG_TAILWIND, $storeId);
    }

    public function forceTailwindCompile($storeId = null)
    {
        if (!$this->isTailwindCompileEnabled()) {
            return false;
        }
        return Mage::getStoreConfigFlag(self::XML_PATH_CONFIG_TAILWIND_FORCE_COMPILE, $storeId);
    }

    public function getSessionMessage()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return Mage::getSingleton("adminhtml/session");
        } elseif (Mage::helper('core')->isDevAllowed()) {
            return Mage::getSingleton("core/session");
        }
    }

    public function stripBaseDir($path)
    {
        return str_replace(Mage::getBaseDir() . DS, '', $path);
    }

    public function isEnabledFor($type, $entityId, $storeId = null) {        
        $enabledEntityIds = Mage::getStoreConfig(
            sprintf('cms/mm_cmscontentfilemode/enabled_%s', $type),
            $storeId
        );
        if (!$enabledEntityIds) {
            return false;
        }
        $enabledEntityIds = explode(',', $enabledEntityIds);    
        
        return in_array($entityId, $enabledEntityIds);
    }
}
