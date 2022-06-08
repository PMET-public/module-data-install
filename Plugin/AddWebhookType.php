<?php
namespace MagentoEse\DataInstall\Plugin;

use Mageplaza\Webhook\Model\Config\Source\HookType;

class AddWebhookType
{
    public function __construct()
    {
        
    }

    public function afterToArray(HookType $subject, $result){
        $returnArray = $result;
        $returnArray['magentoese_datainstall_install_start'] = 'Data Install Start';
        $returnArray['magentoese_datainstall_install_end'] = 'Data Install End';
        return $returnArray;
    }
}
