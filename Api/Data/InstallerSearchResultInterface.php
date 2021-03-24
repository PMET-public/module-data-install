<?php
/**
 * Copyright © Adobe  All rights reserved.
 */
namespace MagentoEse\DataInstall\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface InstallerSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return \MagentoEse\DataInstall\Api\Data\InstallerInterface[]
     */
    public function getItems();

    /**
     * @param \MagentoEse\DataInstall\Api\Data\InstallerInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
