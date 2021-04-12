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
use Magento\CustomerSegment\Model\ResourceModel\Segment as SegmentResourceModel;
use MagentoEse\DataInstall\Model\Converter;

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


    public function __construct(
        Helper $helper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SegmentFactory $customerSegment,
        SegmentResourceModel $segmentResourceModel,
        Converter $converter
    ) {
        $this->helper = $helper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSegment = $customerSegment;
        $this->segmentResourceModel = $segmentResourceModel;
        $this->converter = $converter;
     }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function install(array $row, array $settings)
    {
        //if there is no site_code, take the default
        if(empty($row['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }
        //if there is no name, reject it
        if(empty($row['name'])) {
            $this->helper->printMessage("A row in the Customer Segments file does not have a value for name. Row is skipped", "warning");
            return true;
        }
        
        //if no is_active, default to active
        if(empty($row['is_active'])) {
            $row['is_active']=1;
        }
        if(!is_numeric($row['is_active'])){
            $row['is_active'] = $row['is_active']=='Y' ? 1:0;
        }
        
        
        //convert tags in conditions_serialized
        $row['conditions_serialized'] = $this->converter->convertContent($row['conditions_serialized']);
        
        //catch bad json in conditions
        
        /** @var Segment $segment */
        $segment= $this->customerSegment->create();
        $segment->setName($row['name']);
        $segment->setDescription($row['description']);
        $segment->setIsActive($row['is_active']);

        $segment->setConditionsSerialized($row['conditions_serialized']);
       
        //missing website and apply to
        $this->segmentResourceModel->save($segment);
    }
}
