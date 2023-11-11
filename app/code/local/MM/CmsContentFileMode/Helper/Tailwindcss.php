<?php
class MM_CmsContentFileMode_Helper_Tailwindcss extends Mage_Core_Helper_Abstract
{

    const DEBUG = true;
    const TAILWIND_REGISTER_KEY = 'tailwindcss_registered';
    const COMPILER_LOCK_KEY = 'tailwindcss_compile_lock';

    public function compileTailwindcss($storeId) {

        $skinPaths = $this->getHelper()->getSkinPaths();
        $skinPath = isset($skinPaths[$storeId]) ? $skinPaths[$storeId] : null;
        $cssOutputPath = "../../skin" . DS . $skinPath . "tailwind.css";
        
        $templatePath = "../../app/design" . DS . $this->getHelper()->getTemplatePathContent($storeId);
        if ($skinPath !== null && $templatePath !== null) {
            // Run compile tailwindcss only once per request
            $_lockName = $this->_getCompilerLockName($skinPath, $storeId);
            if (Mage::registry($_lockName)) {
                return;
            }   
            Mage::register($_lockName, true);

            $_tailwindCli = $this->getTailwindCli();

            $customTailwindConfig = Mage::getBaseDir('skin') . DS . $skinPath . "tailwind.config.js";
            $extraMessages = [];
            if (file_exists($customTailwindConfig)) {
                $_tailwindCli->setConfig($customTailwindConfig);
                $extraMessages[] = $skinPath . "tailwind.config.js";
            }

            $customTailwindBaseCss = Mage::getBaseDir('skin') . DS . $skinPath . "tailwind-base.css";
            if (file_exists($customTailwindBaseCss)) {
                $_tailwindCli->setInput($customTailwindBaseCss);
                $extraMessages[] = $skinPath . "tailwind-base.css";
            }

            $_tailwindCli->setContent($templatePath);
            $_tailwindCli->setOutput($cssOutputPath);

            $result = $this->getTailwindCli()->run();
            $output = $this->getTailwindCli()->getOutput();
            if ($result) {
                $this->getHelper()->getSessionMessage()->addNotice(
                    sprintf("%s > %s %s", 
                        implode("\n", $output), 
                        $skinPath . "tailwind.css",
                        count($extraMessages) > 0 ? '([' . implode(", ", $extraMessages).'] loaded)' : '',
                    )
                );
            }
            
        }
    }

    /**
     * Interface to tailwind cli
     *
     * @return MM_CmsContentFileMode_Helper_Tailwindcss_Cli
     */
    protected function getTailwindCli() {
        return Mage::helper('mm_cmscontentfilemode/tailwindcss_cli');
    }

    /**
     * Get compiler lock name
     *
     * @param string $skinPath
     * @param int $storeId
     * @return string
     */
    private function _getCompilerLockName($skinPath, $storeId) {
        return self::COMPILER_LOCK_KEY.'-'.md5($skinPath.$storeId);
    }
    
    /**
     * Register tailwind css
     *
     * @param int $storeId
     * @return array|null
     */
    public function registerTailwindCss($storeId)
    {
        if (Mage::registry(self::TAILWIND_REGISTER_KEY) !== null) {
            return;
        }
        $additionalCss = array();
        $skinPath = $this->getHelper()->getSkinPathByStoreId($storeId);
        if ($skinPath) {
            $_cssTailwindPath = $skinPath . 'tailwind.css';
            if (file_exists(Mage::getBaseDir('skin') . DS . $_cssTailwindPath)) {
                $filemtime = filemtime(Mage::getBaseDir('skin') . DS . $_cssTailwindPath);
                $additionalCss[] = Mage::getBaseUrl('skin') . $_cssTailwindPath.'?rand='.$filemtime;
            } 
        }
        $additionalCss = array_unique($additionalCss);
        Mage::register(self::TAILWIND_REGISTER_KEY, $additionalCss);
        return $additionalCss;
    }

    /**
     * @return array|null
     */
    public function getTinyMceAdditionalCss()
    {
        return Mage::registry(self::TAILWIND_REGISTER_KEY) ? Mage::registry(self::TAILWIND_REGISTER_KEY) : null;
    }

    /**
     * @return MM_CmsContentFileMode_Helper_Data
     */
    protected function getHelper() {
        return Mage::helper('mm_cmscontentfilemode');
    }
}