<?php
class MM_CmsContentFileMode_Helper_Tailwindcss_Tinymcetemplates extends Mage_Core_Helper_Abstract
{
    const FILENAME_MD5 = '.tinymceTemplatesChecksum';
    const FILEPATH_TAILWINDPREVIEWCSS = '/adminhtml/default/default/tailwindPreview.css';
    
    /**
     * Get tailwindcss preview css
     *
     * @return string|false
     */
    public function getPreviewCss(){
        if ($this->shouldRecompileTailwindcss()) {
            $this->compileTailwindCssTinymceTemplate();
        }
        return file_exists(Mage::getBaseDir('skin').self::FILEPATH_TAILWINDPREVIEWCSS) ? Mage::getBaseUrl('skin').self::FILEPATH_TAILWINDPREVIEWCSS : false;
    }

    /**
     * Compile tailwindcss templates
     *
     * @return void
     */
    public function compileTailwindCssTinymceTemplate()
	{
        $_tailwindCli = $this->getTailwindCli();
        $_tailwindCli->setContent("../../app/design/frontend/*/*/template/cms/tinymce_templates/*.{htm,html,txt}");
		$cssOutputPath = Mage::getBaseDir('skin').self::FILEPATH_TAILWINDPREVIEWCSS;
        $_tailwindCli->setOutput($cssOutputPath);

        $result = $this->getTailwindCli()->run();
        $output = $this->getTailwindCli()->getOutput();
        if ($result) {
			Mage::helper('mm_cmscontentfilemode')->getSessionMessage()->addNotice(
				sprintf("%s > %s", 
					implode("\n", $output), 
					$cssOutputPath,
				)
			);                
		}
	}
    
    /**
     * Detect if tailwindcss templates changed
     *
     * @return boolean
     */
    protected function shouldRecompileTailwindcss() {
        $templateChecksum = $this->_getChecksumTinymceTemplates();
        $oldTemplateChecksum = file_exists($this->_getChecksumFileName()) ? file_get_contents($this->_getChecksumFileName()) : '';

        if ($oldTemplateChecksum !== $templateChecksum) {
            Mage::helper('mm_cmscontentfilemode')->getSessionMessage()->addNotice("Tailwindcss templates changed, recompiling...");
            file_put_contents($this->_getChecksumFileName(), $templateChecksum);
            return true;
        }
        return false;
    }
    
    /**
     * Get checksum of tinymce templates
     *
     * @return string
     */
    private function _getChecksumTinymceTemplates() {
        $checksum = '';
		$_searchDirs = Mage::getBaseDir('design').'/frontend/*/*/template/cms/tinymce_templates/*.{htm,html,txt}';
		foreach (glob($_searchDirs, GLOB_BRACE) as $filename) {
			$checksum.= hash_file('sha1', $filename);
		}
        return $checksum;
    }

    /**
     * Get checksum file name
     *
     * @return string
     */
    private function _getChecksumFileName() {
        return Mage::getBaseDir('tmp').'/'.self::FILENAME_MD5;
    }

    /**
     * Interface to tailwind cli
     *
     * @return MM_CmsContentFileMode_Helper_Tailwindcss_Cli
     */
    protected function getTailwindCli() {
        return Mage::helper('mm_cmscontentfilemode/tailwindcss_cli');
    }
}
