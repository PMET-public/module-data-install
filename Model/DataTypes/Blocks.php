<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use MagentoEse\DataInstall\Model\Converter;

class Blocks
{

    /** @var BlockFactory  */
    protected $blockFactory;

    /** @var Converter  */
    protected $converter;

    /** @var Store  */
    protected $storeView;

    /**
     * Blocks constructor.
     * @param BlockFactory $blockFactory
     * @param Converter $converter
     * @param Store $storeView
     */
    public function __construct(
        BlockFactory $blockFactory,
        Converter $converter,
        Store $storeView
    ) {
        $this->blockFactory = $blockFactory;
        $this->converter = $converter;
        $this->storeView = $storeView;
    }

    /**
     * @param array $row
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $row, array $settings)
    {
        $row['content'] = $this->converter->convertContent($row['content']);
        $cmsBlock = $this->saveCmsBlock($row);
        $cmsBlock->unsetData();
        return true;
    }

    /**
     * @param $data
     * @return mixed
     * @throws LocalizedException
     */
    protected function saveCmsBlock($data)
    {
        /** @var \Magento\Cms\Model\Block $cmsBlock */
        $cmsBlock = $this->blockFactory->create();
        $cmsBlock->getResource()->load($cmsBlock, $data['identifier']);

        //get view id from view code, use admin if not defined
        if (!empty($data['store_view_code'])) {
            $viewId = $this->storeView->load($data['store_view_code'])->getStoreId();
        } else {
            $viewId = $this->storeView->load('admin')->getStoreId();
        }

        //set status as active if not defined
        if (empty($data['is_active']) || $data['is_active']=='Y') {
            $data['is_active'] = 1;
        }

        if (!$cmsBlock->getData()) {
            $cmsBlock->setData($data);
        } else {
            $cmsBlock->addData($data);
        }

        $cmsBlock->setStoreId($viewId);
        //$cmsBlock->setIsActive(!empty($data['is_active']) ?? 'Y');
        $cmsBlock->setIsActive($data['is_active']);
        $cmsBlock->save();
        return $cmsBlock;
    }
}
