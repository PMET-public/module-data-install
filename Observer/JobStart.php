<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEse\DataInstall\Helper\Helper;

class JobStart implements ObserverInterface
{
    
    /** @var string */
    protected $hookType = 'magentoese_datainstall_job_start';

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
        $dataPack = $observer->getData('eventData');
        $this->helper->logMessage(
            "Start Data Installer Job - id: ".$dataPack['jobId']." file: ".$dataPack['location'],
            "warning"
        );
    }
}
