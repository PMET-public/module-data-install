<?php

/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Framework\Api\SearchCriteriaBuilder;
use MagentoEse\DataInstall\Helper\Helper;
//No APIs in CustomerSegment
use Magento\CustomerSegment\Model\Segment;
use Magento\CustomerSegment\Model\SegmentFactory;
use Magento\CustomerSegment\Model\SegmentMatchPublisher;
use Magento\CustomerSegment\Model\ResourceModel\Segment as SegmentResourceModel;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory as Collection;
use MagentoEse\DataInstall\Model\Converter;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State;

class CustomerSegments
{
    /** @var Helper */
    protected $helper;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var SegmentFactory */
    protected $customerSegment;

    /** @var SegmentResourceModel */
    protected $segmentResourceModel;

    /** @var Converter */
    protected $converter;

     /** @var Stores */
     protected $stores;

     /** @var Collection */
     protected $collection;

     /** @var SegmentMatchPublisher */
     protected $segmentMatchPublisher;

     /** @var State */
     protected $appState;


    public function __construct(
        Helper $helper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SegmentFactory $customerSegment,
        SegmentResourceModel $segmentResourceModel,
        Converter $converter,
        Stores $stores,
        Collection $collection,
        SegmentMatchPublisher $segmentMatchPublisher,
        State $appState
    ) {
        $this->helper = $helper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSegment = $customerSegment;
        $this->segmentResourceModel = $segmentResourceModel;
        $this->converter = $converter;
        $this->stores = $stores;
        $this->collection = $collection;
        $this->segmentMatchPublisher = $segmentMatchPublisher;
        $this->appState = $appState;
     }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function install(array $row, array $settings)
    {
        
         //if there is no name, reject it
         if(empty($row['name'])) {
            $this->helper->printMessage("A row in the Customer Segments file does not have a value for name. Row is skipped", "warning");
            return true;
        }

        //if there is no site_code, take the default
        if(empty($row['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }
        //convert site codes to ids, put in array
        $siteCodes = explode(",", $row['site_code']);
        $siteIds = [];
        foreach($siteCodes as $siteCode){
            $siteId = $this->stores->getWebsiteId($siteCode);
            if($siteId){
                $siteIds[] = $this->stores->getWebsiteId($siteCode);
            }
            
        }

        //if no is_active, default to active
        if(empty($row['is_active'])) {
            $row['is_active']=1;
        }
        if(!is_numeric($row['is_active'])){
            $row['is_active'] = $row['is_active']=='Y' ? 1:0;
        }
        //applyto set default at both visitors and registered users
        if(empty($row['apply_to'])) {
            $row['apply_to']=0;
        }
                
        //convert tags in conditions_serialized
        $row['conditions_serialized'] = $this->converter->convertContent($row['conditions_serialized']);

        //check json format of conditions_serialized
        $jsonValidate = json_decode($row['conditions_serialized'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->helper->printMessage("A row in the Customer Segments file has invalid Json data for conditions_serialized. Row is skipped", "warning");
            return true;
        }

        //load existing segment by name
        /** @var Segment $segment */
        $segment = $this->collection->create()->addFieldToFilter('name', ['eq' => $row['name']])->getFirstItem();
        if(!$segment->getName()){
            $segment = $this->customerSegment->create();
        }
      
        $segment->setName($row['name']);
        $segment->setDescription($row['description']);
        $segment->setIsActive($row['is_active']);

        $segment->setConditionsSerialized($row['conditions_serialized']);
        $segment->setApplyTo($row['apply_to']);
        $segment->addData(['website_ids'=>$siteIds]);
        $this->appState->emulateAreaCode(
            AppArea::AREA_ADMINHTML,
            [$this->segmentResourceModel, 'save'],
            [$segment]
        );
        
        //$this->segmentResourceModel->save($segment);
        //schedule bulk operation
        $this->segmentMatchPublisher->execute($segment);
    }
}
