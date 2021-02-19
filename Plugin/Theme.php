<?php


namespace MagentoEse\DataInstall\Plugin;

use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State;

class Theme
{

    public function __construct(State $state)
    {
        try{
            $state->setAreaCode(AppArea::AREA_ADMINHTML);
        }
        catch(\Magento\Framework\Exception\LocalizedException $e){
            // left empty
        }
    }
    public function beforeGetArea(\Magento\Theme\Model\Theme $subject)
    {
        //Your plugin code
        return [];
    }
}