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

//use Mageplaza\Webhook\Helper\Data as WebhookHelperData;

class ProcessEnd implements ObserverInterface
{
    
    /** @var string */
    protected $hookType = 'magentoese_datainstall_install_end';

    /** @var Helper */
    protected $helper;

    /** @var Process */
    protected $process;

    /** @var WebhookHelperData */
    //protected $webhookHelper;
    
   /**
    *
    * @param Helper $helper
    * @param Process $process
    * @return void
    */
    public function __construct(Helper $helper, Process $process)//, WebhookHelperData $webhookHelper)
    {
        $this->helper = $helper;
        $this->process = $process;
        //$this->webhookHelper = $webhookHelper;
    }

    /**
     * Observer run
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $item = $observer->getData('eventData');
        $this->process->setModuleInstalled($item['file_path'].$item['fixture_directory']);
        $this->helper->setSettings($observer->getData('eventData'));
        $this->helper->logMessage(
            "End Data Installer process",
            "warning"
        );
        //TODO: will need to copy section from ProcessStart assuming the payload is going to be the same
        //$item = $observer->getData('eventData');
        //$item = $item['job_settings'];
        //$this->webhookHelper->send($item, $this->hookType);
    }
}
