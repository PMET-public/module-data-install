<?php
/**
 * Copyright © Adobe, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\ResourceModel\Rating\CollectionFactory as RatingCollectionFactory;
use Magento\Review\Model\ResourceModel\Rating as RatingResourceModel;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\OptionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEse\DataInstall\Helper\Helper;

class Reviews
{

    /** @var Helper */
    protected $helper;

    /**
     * @var ReviewFactory
     */
    protected $reviewFactory;

    /**
     * @var ReviewCollectionFactory
     */
    protected $reviewCollectionFactory;


    /**
     * @var RatingCollectionFactory
     */
    protected $ratingCollectionFactory;


    /**
     * @var RatingResourceModel
     */
    protected $ratingResourceModel;


    /**
     * @var RatingFactory
     */
    protected $ratingFactory;

    /**
     * @var array
     */
    protected $productIds;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollection;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var OptionFactory
     */
    protected $ratingOptionsFactory;

    /**
     * @var array
     */
    protected $ratings;

    /**
     * @var int
     */
    protected $ratingProductEntityId;

    /**
     * @var int
     */
    protected $reviewProductEntityId;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /** @var Stores */
    protected $stores;

    /**
     * Reviews constructor.
     * @param Helper $helper
     * @param ReviewFactory $reviewFactory
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param RatingFactory $ratingFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CustomerRepositoryInterface $customerAccount
     * @param OptionFactory $ratingOptionsFactory
     * @param StoreManagerInterface $storeManager
     * @param Stores $stores
     * @param RatingCollectionFactory $ratingCollectionFactory
     * @param RatingResourceModel $ratingResourceModel
     */
    public function __construct(
        Helper $helper,
        ReviewFactory $reviewFactory,
        ReviewCollectionFactory $reviewCollectionFactory,
        RatingFactory $ratingFactory,
        ProductCollectionFactory $productCollectionFactory,
        CustomerRepositoryInterface $customerAccount,
        OptionFactory $ratingOptionsFactory,
        StoreManagerInterface $storeManager,
        Stores $stores,
        RatingCollectionFactory $ratingCollectionFactory,
        RatingResourceModel $ratingResourceModel
    ) {
        $this->helper = $helper;
        $this->reviewFactory = $reviewFactory;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->ratingFactory = $ratingFactory;
        $this->productCollection = $productCollectionFactory->create()->addAttributeToSelect('sku');
        $this->customerRepository = $customerAccount;
        $this->ratingOptionsFactory = $ratingOptionsFactory;
        $this->storeManager = $storeManager;
        $this->stores = $stores;
        $this->ratingCollectionFactory = $ratingCollectionFactory;
        $this->ratingResourceModel = $ratingResourceModel;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Exception
     */
    public function install(array $row, array $settings)
    {
        //check for required columns
        if(empty($row['sku']) || empty($row['reviewer']) || empty($row['summary']) || empty($row['review']) | empty($row['rating_code'])){
            $this->helper->printMessage("Review skipped -- one or more of the required values is missing", "warning");
            return true;
        }
        //get view id from view code, use admin if not defined
        if (!empty($row['store_view_code'])) {
            $storeId = $this->stores->getViewId(trim($row['store_view_code']));
        } else {
            $storeId = $this->stores->getViewId(trim($settings['store_view_code']));
        }
        
        $review = $this->prepareReview($row, $storeId);
        $this->createRating($row['rating_code'], $storeId);
        $productId = $this->getProductIdBySku($row['sku']);

        if (empty($productId)) {
            $this->helper->printMessage("Review skipped -- Product ".$row['sku']." not found", "warning");
            return true;
        }
        /** @var \Magento\Review\Model\ResourceModel\Review\Collection $reviewCollection */
        //skip duplicate reviews
        $reviewCollection = $this->reviewCollectionFactory->create();
        $reviewCollection->addFilter('entity_pk_value', $productId)
            ->addFilter('entity_id', $this->getReviewEntityId())
            ->addFieldToFilter('detail.title', ['eq' => $row['summary']]);
        if ($reviewCollection->getSize() > 0) {
            $this->helper->printMessage("Review skipped -- Duplicate", "warning");
            return true;
        }

        if (!empty($row['email']) && ($this->getCustomerIdByEmail($row['email']) != null)) {
            $review->setCustomerId($this->getCustomerIdByEmail($row['email']));
        }
        $review->save();
        $this->setReviewRating($review, $row,$storeId);

        return true;
    }

    /**
     * Retrieve product ID by sku
     *
     * @param string $sku
     * @return int|null
     */
    protected function getProductIdBySku($sku)
    {
        if (empty($this->productIds)) {
            foreach ($this->productCollection as $product) {
                $this->productIds[$product->getSku()] = $product->getId();
            }
        }
        if (isset($this->productIds[$sku])) {
            return $this->productIds[$sku];
        }
        return null;
    }

    /**
     * @param array $row
     * @return Review
     */
    protected function prepareReview($row, $storeId)
    {
        /** @var $review Review */
        $review = $this->reviewFactory->create();
        //$storeId = $this->storeManager->getDefaultStoreView()->getStoreId();
        $review->setEntityId(
            $review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE)
        )->setEntityPkValue(
            $this->getProductIdBySku($row['sku'])
        )->setNickname(
            $row['reviewer']
        )->setTitle(
            $row['summary']
        )->setDetail(
            $row['review']
        )->setStatusId(
            Review::STATUS_APPROVED
        )->setStoreId(
            $storeId
        )->setStores(
            [$storeId]
        );
        return $review;
    }

    /**
     * @param $rating
     * @return \Magento\Framework\DataObject|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRating($rating,$storeId)
    {
        $ratingCollection = $this->ratingCollectionFactory->create();
        
        $ratingId = $ratingCollection->addFieldToFilter('rating_code', $rating)->getFirstItem()->getId();
        $rating = $this->ratingFactory->create()->load($ratingId);
        $ratingStores = $rating->getStores();
        if(is_array($ratingStores)){
            if(!in_array($storeId,$ratingStores)){
                $ratingStores[]=$storeId;
                $rating->setStores($ratingStores);
                $this->ratingResourceModel->save($rating);
            }
        }
        return $rating;
    }

    /**
     * @param Review $review
     * @param $row
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function setReviewRating(Review $review, $row,$storeId)
    {
        $rating = $this->getRating($row['rating_code'],$storeId);
        foreach ($rating->getOptions() as $option) {
            $optionId = $option->getOptionId();
            if (($option->getValue() == $row['rating_value']) && !empty($optionId)) {
                $rating->setReviewId($review->getId())->addOptionVote(
                    $optionId,
                    $this->getProductIdBySku($row['sku'])
                );
            }
        }
        $review->aggregate();
    }

    /**
     * @param $ratingCode
     * @param $storeId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createRating($ratingCode, $storeId)
    {
        //$stores[] = $storeId;
        $rating = $this->getRating($ratingCode,$storeId);
        if (!$rating->getData()) {
            $rating->setRatingCode(
                $ratingCode
            )->setStores(
                [$storeId]
            )->setIsActive(
                '1'
            )->setEntityId(
                $this->getRatingEntityId()
            )->save();

            /**Create rating options*/
            $options = [
                1 => '1',
                2 => '2',
                3 => '3',
                4 => '4',
                5 => '5',
            ];
            foreach ($options as $key => $optionCode) {
                $optionModel = $this->ratingOptionsFactory->create();
                $optionModel->setCode(
                    $optionCode
                )->setValue(
                    $key
                )->setRatingId(
                    $rating->getId()
                )->setPosition(
                    $key
                )->save();
            }
        }
    }

    /**
     * @param $customerEmail
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCustomerIdByEmail($customerEmail)
    {
        try {
            $customerData = $this->customerRepository->get($customerEmail);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        if ($customerData) {
            return $customerData->getId();
        }
        return null;
    }

    /**
     * @return int
     */
    protected function getRatingEntityId()
    {
        if (!$this->ratingProductEntityId) {
            $rating = $this->ratingFactory->create();
            $this->ratingProductEntityId = $rating->getEntityIdByCode(
                \Magento\Review\Model\Rating::ENTITY_PRODUCT_CODE
            );
        }
        return $this->ratingProductEntityId;
    }

    /**
     * @return int
     */
    protected function getReviewEntityId()
    {
        if (!$this->reviewProductEntityId) {
            /** @var $review Review */
            $review = $this->reviewFactory->create();
            $this->reviewProductEntityId = $review->getEntityIdByCode(
                Review::ENTITY_PRODUCT_CODE
            );
        }
        return $this->reviewProductEntityId;
    }
}
