<?php
/** Copyright © Adobe  All rights reserved */
namespace MagentoEse\DataInstall\Model\DataTypes;

use MagentoEse\DataInstall\Helper\Helper;
use Magento\Reward\Model\Reward\RateFactory as RateModelFactory;
use Magento\Reward\Model\Reward\Rate;
use Magento\Reward\Model\ResourceModel\Reward\Rate as RateResourceModel;
use Magento\Reward\Model\ResourceModel\Reward\Rate\CollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;

class RewardExchangeRate
{
    /** @var CustomerGroups */
    protected $customerGroups;

    /** @var Stores */
    protected $stores;

    /** @var Helper */
    protected $helper;

    /** @var RateModelFactory */
    protected $rateModel;

     /** @var CollectionFactory */
     protected $collectionFactory;

    /** @var RateResourceModel */
    protected $rateResourceModel;

    /**
     * RewardExchangeRate constructor
     *
     * @param CustomerGroups $customerGroups
     * @param Stores $stores
     * @param Helper $helper
     * @param RateModelFactory $rateModel
     * @param RateResourceModel $rateResourceModel
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CustomerGroups $customerGroups,
        Stores $stores,
        Helper $helper,
        RateModelFactory $rateModel,
        RateResourceModel $rateResourceModel,
        CollectionFactory $collectionFactory
    ) {
        $this->customerGroups = $customerGroups;
        $this->stores = $stores;
        $this->helper = $helper;
        $this->rateModel = $rateModel;
        $this->rateResourceModel = $rateResourceModel;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function install(array $row, array $settings)
    {
        //required points and currency_amount
        //direction 1 = points to currency
        //direction 2 = currency to points
        if (empty($row['points'])) {
            $this->helper->logMessage("A value for points is required for a reward exchange rate. ".
            "Row has been skipped.", "warning");
            return true;
        }

        if (empty($row['currency_amount'])) {
            $this->helper->logMessage("A value for currency_amount is required for a reward exchange rate. ".
            "Row has been skipped.", "warning");
            return true;
        }

        if (empty($row['direction']) || ($row['direction'] != 'points_to_currency' && $row['direction']
        != 'currency_to_points')) {
            $this->helper->logMessage("A direction of either points_to_currency or currency_to_points is required ".
            "for a reward exchange rate. Row has been skipped.", "warning");
            return true;
        }

        //if websites not defined, use default
        if (empty($row['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }
        $websiteId = $this->stores->getWebsiteId(trim($row['site_code']));

        if (empty($row['customer_group']) || $row['customer_group']=='' || $row['customer_group']=='all') {
            $groupId =0;
        } else {
            $groupId = $this->customerGroups->getCustomerGroupId(trim($row['customer_group']));
        }

        $direction = $row['direction'] == 'points_to_currency' ?
        Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY:Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS;
                //WEBSITE_ID_CUSTOMER_GROUP_ID_DIRECTION
        /** @var RateModel $rateModel */
        $rate = $this->collectionFactory->create()
        ->addFieldToFilter('website_id', ['eq' => $websiteId])
        ->addFieldToFilter('customer_group_id', ['eq' => $groupId])
        ->addFieldToFilter('direction', ['eq' => $direction])->getFirstItem();
        if (!$rate) {
            $rate = $this->rateModel->create();
        }

        $rate->setWebsiteId($websiteId);
        $rate->setCustomerGroupId($groupId);
        $rate->setDirection($direction);
        if ($row['direction'] == 'points_to_currency') {
            $rate->setValue($row['points']);
            $rate->setEqualValue($row['currency_amount']);
        } else {
            $rate->setValue($row['currency_amount']);
            $rate->setEqualValue($row['points']);
        }
        //try{
            $this->rateResourceModel->save($rate);
        //}catch(AlreadyExistsException $e){
        //    $this->helper->logMessage("already exists","warning");
        //    $this->rateResourceModel->delete($rate);
        //}

        return true;
    }
}
