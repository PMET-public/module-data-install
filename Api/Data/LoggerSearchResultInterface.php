<?php
/**
 * Copyright © Adobe  All rights reserved.
 */
namespace MagentoEse\DataInstall\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface LoggerSearchResultInterface extends SearchResultsInterface
{
    /**
     * Get log items
     *
     * @return \MagentoEse\DataInstall\Api\Data\LoggerSearchResultInterface[]
     */
    public function getItems();

    /**
     * Set Log Items
     *
     * @param \MagentoEse\DataInstall\Api\Data\LoggerSearchResultInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
