<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Banner\Model\Banner as BannerModel;
use Magento\Banner\Model\BannerFactory;
use Magento\Banner\Model\ResourceModel\Banner as BannerResourceModel;
use Magento\Banner\Model\ResourceModel\Banner\CollectionFactory as BannerCollection;
use Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory as SegmentCollection;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\SchemaSetupInterface;
use MagentoEse\DataInstall\Model\Converter;
use MagentoEse\DataInstall\Helper\Helper;

class DynamicBlocks
{

    /** @var BannerFactory  */
    protected $bannerFactory;

    /** @var BannerSegmentLink  */
    private $bannerSegmentLink;

    /** @var Converter  */
    private $converter;

    /** @var SegmentCollection  */
    private $segmentCollection;

    /** @var SchemaSetupInterface  */
    private $setup;

    /** @var BannerResourceModel  */
    private $bannerResourceModel;

    /** @var BannerCollection  */
    private $bannerCollection;

    /** @var Helper  */
    private $helper;

    /**
     * Banner constructor
     *
     * @param BannerFactory $bannerFactory
     * @param BannerSegmentLink $bannerSegmentLink
     * @param Converter $converter
     * @param SegmentCollection $segmentCollection
     * @param SchemaSetupInterface $setup
     * @param BannerResourceModel $bannerResourceModel
     * @param BannerCollection $bannerCollection
     * @param Helper $helper
     */

    public function __construct(
        BannerFactory $bannerFactory,
        BannerSegmentLink $bannerSegmentLink,
        Converter $converter,
        SegmentCollection $segmentCollection,
        SchemaSetupInterface $setup,
        BannerResourceModel $bannerResourceModel,
        BannerCollection $bannerCollection,
        Helper $helper
    ) {
        $this->bannerFactory = $bannerFactory;
        $this->bannerSegmentLink = $bannerSegmentLink;
        $this->converter = $converter;
        $this->segmentCollection = $segmentCollection;
        $this->setup = $setup;
        $this->bannerResourceModel =  $bannerResourceModel;
        $this->bannerCollection = $bannerCollection;
        $this->helper = $helper;
    }

    /**
     * Install
     *
     * @param array $row
     * @return bool
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function install(array $row)
    {
        //skip if no name
        if (empty($row['name']) || $row['name'] == '') {
            $this->helper->logMessage(
                "A row in the Dynamic Blocks file does not have a value for name. Row is skipped",
                "warning"
            );
            return true;
        }
        if (empty($row['is_enabled'])) {
            $row['is_enabled']=1;
        }
        if (empty($row['type'])) {
            $row['type']='';
        }
        if (empty($row['segments'])) {
            $row['segments']='';
        }
        //remove spaces from type
        $row['type'] = str_replace(' ', '', $row['type']);

        if (empty($row['store_code'])) {
            //backwards compatibility
            if (!empty($row['store'])) {
                $row['store_view_code'] = $row['store'];
            }
        }
        if (empty($row['store_view_code'])) {
            $row['store_view_code']='admin';
        }

        //get existing banner to see if we need to create or update content for different store view
        $bannerCollection = $this->bannerCollection->create();
        $banners = $bannerCollection->addFilter('name', $row['name'], 'eq')->setPageSize(1)->setCurPage(1);
        //echo $banners->count()."\n";
        if ($banners->count()!=0) {
            $bannerId = $banners->getAllIds()[0];
            $banner = $this->bannerFactory->create()->load($bannerId);
        } else {
            $banner = $this->bannerFactory->create();
        }
        ///types, segments
        /** @var BannerModel $banner */
        $banner->setName($row['name']);
        $banner->setIsEnabled($row['is_enabled']);
        $banner->setTypes($row['type']);
        if (!empty($row['content'])) {
            $row['banner_content'] = $row['content'];
        }

        $storeViewCodes = explode(",", $row['store_view_code']);

        foreach ($storeViewCodes as $storeViewCode) {
            
            //admin is the recoginzed code for default store view
            if ($storeViewCode=='default') {
                $storeViewCode='admin';
            }
            $banner->setStoreContents(
                [$this->converter->getStoreidByCode(trim($storeViewCode)) =>
                $this->converter->convertContent($row['banner_content'])]
            );
        }

        $this->bannerResourceModel->save($banner);
        //set default if this is a new banner
        if ($banners->count()==0) {
            $this->bannerResourceModel->saveStoreContents(
                $banner->getId(),
                ['0' => $this->converter->convertContent($row['banner_content'])]
            );
        }

        $segments = explode(",", $row['segments']);
        $segmentIds=[];
        foreach ($segments as $segment) {
            $segmentId = $this->getSegmentIdByName($segment);
            if ($segmentId != null) {
                $segmentIds[]=$segmentId;
            }
        }

        $this->bannerSegmentLink->saveBannerSegments($banner->getId(), $segmentIds);
        $this->setup->endSetup();
        return true;
    }

    /**
     * Get segment id by name
     *
     * @param string $segmentName
     * @return mixed
     */
    public function getSegmentIdByName(string $segmentName)
    {
        $collection = $this->segmentCollection->create();
        $segment = $collection->addFilter('name', $segmentName, 'eq')->getFirstItem();
        return $segment->getId();
    }
}
