<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Exception;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use MagentoEse\DataInstall\Model\DataTypes\Stores;
use MagentoEse\DataInstall\Model\Converter;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use MagentoEse\DataInstall\Helper\Helper;

class Blocks
{

    /** @var BlockInterfaceFactory  */
    protected $blockFactory;

    /** @var Converter  */
    protected $converter;

    /** @var Stores  */
    protected $stores;

    /** @var BlockRepositoryInterface  */
    protected $blockRepository;

    /** @var GetBlockByIdentifierInterface  */
    protected $getBlockByIdentifier;

    /** @var Helper  */
    protected $helper;

    /**
     * Blocks constructor.
     * @param BlockInterfaceFactory $blockFactory
     * @param BlockRepositoryInterface $blockRepositoryInterface
     * @param GetBlockByIdentifierInterface $getBlockByIdentifierInterface
     * @param Converter $converter
     * @param Stores $stores
     * @param Helper $helper
     */
    public function __construct(
        BlockInterfaceFactory $blockFactory,
        BlockRepositoryInterface $blockRepositoryInterface,
        GetBlockByIdentifierInterface $getBlockByIdentifierInterface,
        Converter $converter,
        Stores $stores,
        Helper $helper
    ) {
        $this->blockFactory = $blockFactory;
        $this->converter = $converter;
        $this->stores = $stores;
        $this->blockRepository = $blockRepositoryInterface;
        $this->getBlockByIdentifier = $getBlockByIdentifierInterface;
        $this->helper = $helper;
    }

    /**
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $row, array $settings)
    {

        if (empty($row['identifier']) || empty($row['title'])) {
            $this->helper->logMessage("Block missing identifier or title, row skipped", "warning");
            return true;
        }

        //set status as active if not defined properly
        $row['is_active']??='Y';
        $row['is_active'] = 'Y' ? 1:0;

        $row['content'] = $this->converter->convertContent($row['content']??'');

         //get view id from view code, use admin if not defined. Override if requested
        if (!empty($row['store_view_code'])) {
            $storeCodes = explode(",", $row['store_view_code']);
            $viewId = [];
            foreach ($storeCodes as $storeCode) {
                $viewId[] = $this->stores->getViewId(trim($storeCode));
            }
            if (!empty($settings['is_override'])) {
                if (!empty($settings['store_view_code'])) {
                    $viewId = [$this->stores->getViewId(trim($settings['store_view_code']))];
                }
            }
        } else {
            $viewId = [$this->stores->getViewId(trim($settings['store_view_code']))];
        }

        //if the requested view doesnt exist, default it to 0
        if (!$viewId) {
            $viewId=[0];
        }

        try {
            /** @var BlockInterface $cmsBlock */
            $cmsBlock = $this->getBlockByIdentifier->execute($row['identifier'], $viewId[0]);
        } catch (Exception $e) {
            //if block isnt found, create a new one
            $cmsBlock = $this->blockFactory->create();
        }

        $cmsBlock->setIdentifier($row['identifier']);
        $cmsBlock->setContent($row['content']);
        $cmsBlock->setTitle($row['title']);
        $cmsBlock->setData('stores', $viewId);
        $cmsBlock->setStoreIds($viewId);
        $cmsBlock->setIsActive($row['is_active']);
        $this->blockRepository->save($cmsBlock);
        unset($cmsBlock);
        return true;
    }

     /**
      * Override settings
      *
      * @param array $row
      * @param array $settings
      * @return array
      */
    private function overrideSettings(array $row, array $settings) : array
    {
        if (!empty($settings['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }
        if (!empty($settings['store_code'])) {
            $row['store_code'] = $settings['store_code'];
        }
        if (!empty($settings['store_view_code'])) {
            $row['store_view_code'] = $settings['store_view_code'];
        }
        if (!empty($settings['site_name'])) {
            $row['site_name'] = $settings['site_name'];
        }
        if (!empty($settings['store_name'])) {
            $row['store_name'] = $settings['store_name'];
        }
        if (!empty($settings['store_view_name'])) {
            $row['view_name'] = $settings['store_view_name'];
        }
        return $data;
    }
}
