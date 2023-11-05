<?php
class MM_CmsContentFileMode_Model_System_Config_Source_Cms_Pages
{
    public function toOptionArray()
    {
        $options = [];
        $pages = Mage::getModel('cms/page')->getCollection();
        foreach ($pages as $page) {
            $options[] = [
                'value' => $page->getId(),
                'label' => $page->getTitle()
            ];
        }
        return $options;
    }
}