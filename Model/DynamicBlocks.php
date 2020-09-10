<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Banner\Model\Banner as BannerModel;
use Magento\Banner\Model\BannerFactory;
use Magento\Banner\Model\ResourceModel\Banner as BannerResourceModel;
use Magento\Banner\Model\ResourceModel\Banner\CollectionFactory as BannerCollection;
use Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory as SegmentCollection;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\SchemaSetupInterface;

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

    /**
     * Banner constructor.
     * @param BannerFactory $bannerFactory
     * @param BannerSegmentLink $bannerSegmentLink
     * @param Converter $converter
     * @param SegmentCollection $segmentCollection
     * @param SchemaSetupInterface $setup
     * @param BannerResourceModel $bannerResourceModel
     * @param BannerCollection $bannerCollection
     */

    public function __construct(
        BannerFactory $bannerFactory,
        BannerSegmentLink $bannerSegmentLink,
        Converter $converter,
        SegmentCollection $segmentCollection,
        SchemaSetupInterface $setup,
        BannerResourceModel $bannerResourceModel,
        BannerCollection $bannerCollection
    ) {
        $this->bannerFactory = $bannerFactory;
        $this->bannerSegmentLink = $bannerSegmentLink;
        $this->converter = $converter;
        $this->segmentCollection = $segmentCollection;
        $this->setup = $setup;
        $this->bannerResourceModel =  $bannerResourceModel;
        $this->bannerCollection = $bannerCollection;
    }

    /**
     * @param array $row
     * @return bool
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function install(array $row)
    {
        //get existing banner to see if we need to create or update content for different store view
        $bannerCollection = $this->bannerCollection->create();
        $banners = $bannerCollection->addFilter('name', $row['name'], 'eq');
        //echo $banners->count()."\n";
        if ($banners->count()!=0) {
            $bannerId = $banners->getAllIds()[0];
            $banner = $this->bannerFactory->create()->load($bannerId);
        } else {
            $banner = $this->bannerFactory->create();
        }

        /** @var BannerModel $banner */
        $banner->setName($row['name']);
        $banner->setIsEnabled(1);
        $banner->setTypes($row['type']);
        //$content = $this->replaceBlockIdentifiers($row['banner_content']);

        $banner->setStoreContents([$this->converter->getStoreidByCode($row['store']) => $this->converter->convertContent($row['banner_content'])]);
        $this->bannerResourceModel->save($banner);
        //set default if this is a new banner
        if ($banners->count()==0) {
            $this->bannerResourceModel->saveStoreContents($banner->getId(), ['0' => $this->converter->convertContent($row['banner_content'])]);
        }

        //set content for store
        // $this->bannerResourceModel->saveStoreContents($banner->getId(), [$this->replaceIds->getStoreidByCode($row['store']) => $this->replaceIds->replaceAll($row['banner_content'])]);

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
