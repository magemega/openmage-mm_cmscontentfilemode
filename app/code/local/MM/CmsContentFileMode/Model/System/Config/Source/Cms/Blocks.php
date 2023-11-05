<?php
class MM_CmsContentFileMode_Model_System_Config_Source_Cms_Blocks
{
    public function toOptionArray()
    {
        $options = [];
        $blocks = Mage::getModel('cms/block')->getCollection();
        foreach ($blocks as $block) {
            $options[] = [
                'value' => $block->getId(),
                'label' => $block->getTitle()
            ];
        }
        return $options;
    }
}