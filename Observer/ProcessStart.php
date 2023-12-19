<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEse\DataInstall\Helper\Helper;

class ProcessStart implements ObserverInterface
{
    
    /** @var string */
    protected $hookType = 'magentoese_datainstall_install_start';

    /** @var Helper */
    protected $helper;

    /**
     * Process Start Constructor
     *
     * @param Helper $helper
     */
    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }
    /**
     * Observer function
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $this->helper->setSettings($observer->getData('eventData'));
        $this->helper->logMessage(
            "Start Data Installer process",
            "warning"
        );
        //TODO: Will need to set data based on what will be required for the payload
        $itemData = $observer->getData()['eventData']['job_settings'];
        $item = $observer;
        $item->setData('filesource', $itemData['filesource']);
        $item->setData('jobid', $itemData['jobid']);
    }
}
