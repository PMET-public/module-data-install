<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Exception;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use MagentoEse\DataInstall\Model\DataTypes\Stores;
use MagentoEse\DataInstall\Model\Converter;
use Magento\Cms\Api\Data\BlockInterface;
use  Magento\Cms\Api\GetBlockByIdentifierInterface;

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


    /**
     * Blocks constructor.
     * @param BlockInterfaceFactory $blockFactory
     * @param BlockRepositoryInterface $blockRepositoryInterface
     * @param GetBlockByIdentifierInterface $getBlockByIdentifierInterface
     * @param Converter $converter
     * @param Stores $stores
     */
    public function __construct(
        BlockInterfaceFactory $blockFactory,
        BlockRepositoryInterface $blockRepositoryInterface,
        GetBlockByIdentifierInterface $getBlockByIdentifierInterface,
        Converter $converter,
        Stores $stores
    ) {
        $this->blockFactory = $blockFactory;
        $this->converter = $converter;
        $this->stores = $stores;
        $this->blockRepository = $blockRepositoryInterface;
        $this->getBlockByIdentifier = $getBlockByIdentifierInterface;
    }

    /**
     * @param array $row
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $row, array $settings)
    {
        $row['content'] = $this->converter->convertContent($row['content']);

        
         //get view id from view code, use admin if not defined
         if (!empty($row['store_view_code'])) {
            $viewId = $this->stores->getViewId(trim($row['store_view_code']));
        } else {
            $viewId = $this->stores->getViewId(trim($settings['store_view_code']));
        }

        //if the requested view doesnt exist, default it to 0
        if(!$viewId){
            $viewId=0;
        }

        try{
            /** @var BlockInterface $cmsBlock */
            $cmsBlock = $this->getBlockByIdentifier->execute($row['identifier'],$viewId);
        }catch(Exception $e){
            //if block isnt found, create a new one
            $cmsBlock = $this->blockFactory->create();
        }
        
        //set status as active if not defined
        if (empty($row['is_active']) || $row['is_active']=='Y') {
            $row['is_active'] = 1;
        }
        $cmsBlock->setIdentifier($row['identifier']);
        $cmsBlock->setContent($row['content']);
        $cmsBlock->setTitle($row['title']);
        $cmsBlock->setData('stores',$viewId);
        //$cmsBlock->setIsActive(!empty($row['is_active']) ?? 'Y');
        $cmsBlock->setIsActive($row['is_active']);
        $this->blockRepository->save($cmsBlock);
        unset($cmsBlock);
        return true;
    }
}
