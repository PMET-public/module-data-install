<?php
/**
 * Copyright © Adobe, Inc. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Installer extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore,Magento2.Annotation.MethodArguments.NoCommentBlock
    protected function _construct()
    {
        $this->_init('magentoese_data_installer_recurring', 'id');
    }
}
