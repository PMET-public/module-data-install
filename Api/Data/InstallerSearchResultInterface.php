<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface InstallerSearchResultInterface extends SearchResultsInterface
{
    /**
     * Get Data installer jobs
     *
     * @return \MagentoEse\DataInstall\Api\Data\InstallerInterface[]
     */
    public function getItems();

    /**
     * Set Data installer jobs
     *
     * @param \MagentoEse\DataInstall\Api\Data\InstallerInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
