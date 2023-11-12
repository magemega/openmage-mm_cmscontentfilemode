<?php
class MM_CmsContentFileMode_Block_Adminhtml_Tinymcetemplatelist extends Mage_Adminhtml_Block_Abstract
{
	public function getTinyMCETemplateList(){
		$templates = [];
		$_searchDirs = Mage::getBaseDir('design').'/frontend/*/*/template/cms/tinymce_templates/*.{htm,html,txt}';
		foreach (glob($_searchDirs, GLOB_BRACE) as $filename) {
			$_content = file_get_contents($filename);
		    array_push($templates, [
	    		'title' => basename($filename),
            	'content' => $_content,
	    		'description' => basename($filename)
            ]);
		}
	    return $templates;
	}

	public function isEnabled() {
		return Mage::helper('mm_cmscontentfilemode')->isTailwindCompileEnabled();
	}

	/**
	 * get additional css for render tailwindcss preview
	 *
	 * @return string|false
	 */
	public function getAdditionalCss() {
		$_additionalCss = [];
		if(is_array($this->getHelperTailwind()->getTinyMceAdditionalCss())) {
            $_additionalCss = array_merge($_additionalCss, $this->getHelperTailwind()->getTinyMceAdditionalCss());
	    }
		if ($this->getHelperTailwindTinymcetemplates()->getPreviewCss()) {
			$_additionalCss[] = $this->getHelperTailwindTinymcetemplates()->getPreviewCss();
	    }
	    return count($_additionalCss) > 0 ? implode(",", $_additionalCss) : false;
	}

	/**
     * @return MM_CmsContentFileMode_Helper_Tailwindcss
     */
    public function getHelperTailwind() {
        return Mage::helper('mm_cmscontentfilemode/tailwindcss');
    }

    /**
     * @return MM_CmsContentFileMode_Helper_Tailwindcss_Tinymcetemplates
     */
    public function getHelperTailwindTinymcetemplates() {
        return Mage::helper('mm_cmscontentfilemode/tailwindcss_tinymcetemplates');
    }
}