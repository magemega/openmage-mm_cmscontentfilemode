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
}