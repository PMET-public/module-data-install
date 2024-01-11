<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEse\DataInstall\Helper\Helper;
use MagentoEse\DataInstall\Model\Process;

class JobEnd implements ObserverInterface
{
    
    /** @var string */
    protected $hookType = 'magentoese_datainstall_job_end';

    /** @var Helper */
    protected $helper;

    /** @var Process */
    protected $process;
   
   /**
    *
    * @param Helper $helper
    * @param Process $process
    * @return void
    */
    public function __construct(Helper $helper, Process $process)
    {
        $this->helper = $helper;
        $this->process = $process;
    }

    /**
     * Observer run
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $dataPack = $observer->getData('eventData');
        $this->helper->logMessage(
            "End Data Installer Job - id: ".$dataPack['jobId']." file: ".$dataPack['location'],
            "warning"
        );
    }
}
