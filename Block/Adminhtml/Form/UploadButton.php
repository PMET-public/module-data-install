<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Block\Adminhtml\Form;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class UploadButton implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
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
