<?php
/**
 * Copyright © Adobe  All rights reserved.
 */
namespace MagentoEse\DataInstall\Block\Adminhtml\Form;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class UploadButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
        'label' => __('Import'),
        'class' => 'save primary',
        'data_attribute' => [
          'mage-init' => ['button' => ['event' => 'save']],
          'form-role' => 'save',
        ],
        'sort_order' => 90,
        ];
    }
}
