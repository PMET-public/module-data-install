<?php
namespace MagentoEse\DataInstall\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEse\DataInstall\Helper\Helper;

class ProcessStart implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    public function execute(Observer $observer)
    {
        $this->helper->setSettings = $observer->getData('eventData');
        $this->helper->logMessage("Start Data Installer process",
            "warning"
        );
        //$myEventData = $observer->getData('myEventData');
        // Additional observer execution code...
    }
}