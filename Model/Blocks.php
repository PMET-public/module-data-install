<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

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
    public function install(array $row)
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
        $cmsBlock = $this->blockFactory->create();
        $cmsBlock->getResource()->load($cmsBlock, $data['identifier']);

        //get view id from view code
        $_viewId = $this->storeView->load('default')->getStoreId();

        if (!$cmsBlock->getData()) {
            $cmsBlock->setData($data);
        } else {
            $cmsBlock->addData($data);
        }

        $cmsBlock->setStoreId($_viewId);
        $cmsBlock->setIsActive(1);
        $cmsBlock->save();
        return $cmsBlock;
    }
}
