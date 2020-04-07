<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Cms\Model\BlockFactory;
use Magento\Store\Model\Store;

/**
 * Class Block
 */
class Blocks
{


    /**
     * @var BlockFactory
     */
    protected $blockFactory;

    /** @var Converter  */
    protected $converter;

    /** @var Store  */
    protected $storeView;

    public function __construct(
        BlockFactory $blockFactory,
        Converter $converter,
        Store $storeView
    )
    {
        $this->blockFactory = $blockFactory;
        $this->converter = $converter;
        $this->storeView = $storeView;
    }

    public function install(array $row)
    {
        $row['content'] = $this->converter->convertContent($row['content']);
        $cmsBlock = $this->saveCmsBlock($row);
        $cmsBlock->unsetData();
        return true;
    }

    /**
     * @param array $data
     * @return \Magento\Cms\Model\Block
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
