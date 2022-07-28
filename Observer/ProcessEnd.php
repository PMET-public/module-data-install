<?php
namespace MagentoEse\DataInstall\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEse\DataInstall\Helper\Helper;
//use Mageplaza\Webhook\Helper\Data as WebhookHelperData;

class ProcessEnd implements \Magento\Framework\Event\ObserverInterface
{
    
    /** @var string */
    protected $hookType = 'magentoese_datainstall_install_end';

    /** @var Helper */
    protected $helper;

    /** @var WebhookHelperData */
    //protected $webhookHelper;
    
    /**
     * @param Helper $helper
     * @param WebhookHelperData $webhookHelper
     */
    public function __construct(Helper $helper
    //, WebhookHelperData $webhookHelper
    )
    {
        $this->helper = $helper;
        //$this->webhookHelper = $webhookHelper;
    }

    public function execute(Observer $observer)
    {
        $this->helper->setSettings = $observer->getData('eventData');
        $this->helper->logMessage(
            "End Data Installer process",
            "warning"
        );
        //TODO: will need to copy section from ProcessStart assuming the payload is going to be the same
        $item = $observer->getData('eventData');
        $item = $item['job_settings'];
        //$this->webhookHelper->send($item, $this->hookType);
    }
}
