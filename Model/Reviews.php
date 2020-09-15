<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\OptionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Reviews
{

    /**
     * @var ReviewFactory
     */
    protected $reviewFactory;

    /**
     * @var ReviewCollectionFactory
     */
    protected $reviewCollectionFactory;

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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /** @var Stores */
    protected $stores;

    /**
     * Reviews constructor.
     * @param ReviewFactory $reviewFactory
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param RatingFactory $ratingFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CustomerRepositoryInterface $customerAccount
     * @param OptionFactory $ratingOptionsFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Stores $stores
     */
    public function __construct(
        ReviewFactory $reviewFactory,
        ReviewCollectionFactory $reviewCollectionFactory,
        RatingFactory $ratingFactory,
        ProductCollectionFactory $productCollectionFactory,
        CustomerRepositoryInterface $customerAccount,
        OptionFactory $ratingOptionsFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Stores $stores
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->ratingFactory = $ratingFactory;
        $this->productCollection = $productCollectionFactory->create()->addAttributeToSelect('sku');
        $this->customerRepository = $customerAccount;
        $this->ratingOptionsFactory = $ratingOptionsFactory;
        $this->storeManager = $storeManager;
        $this->stores = $stores;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Exception
     */
    public function install(array $row, array $settings)
    {
        $storeId = $this->stores->getViewId($settings['store_view_code']);
        //$storeId = [$this->storeManager->getDefaultStoreView()->getStoreId()];
        $review = $this->prepareReview($row,$storeId);
        $this->createRating($row['rating_code'], $storeId);
        $productId = $this->getProductIdBySku($row['sku']);

        if (empty($productId)) {
            print_r("Review skipped -- Product ".$row['sku']." not found\n");
            return true;
        }
        /** @var \Magento\Review\Model\ResourceModel\Review\Collection $reviewCollection */
        //skip duplicate reviews
        $reviewCollection = $this->reviewCollectionFactory->create();
        $reviewCollection->addFilter('entity_pk_value', $productId)
            ->addFilter('entity_id', $this->getReviewEntityId())
            ->addFieldToFilter('detail.title', ['eq' => $row['summary']]);
        if ($reviewCollection->getSize() > 0) {
            print_r("Review skipped -- Duplicate\n");
            return true;
        }

        if (!empty($row['email']) && ($this->getCustomerIdByEmail($row['email']) != null)) {
            $review->setCustomerId($this->getCustomerIdByEmail($row['email']));
        }
        $review->save();
        $this->setReviewRating($review, $row);

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
    protected function prepareReview($row,$storeId)
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
     * @param string $rating
     * @return array
     */
    protected function getRating($rating)
    {
        $ratingCollection = $this->ratingFactory->create()->getResourceCollection();
        if (empty($this->ratings[$rating])) {
            $this->ratings[$rating] = $ratingCollection->addFieldToFilter('rating_code', $rating)->getFirstItem();
        }
        return $this->ratings[$rating];
    }

    /**
     * @param Review $review
     * @param array $row
     * @return void
     */
    protected function setReviewRating(Review $review, $row)
    {
        $rating = $this->getRating($row['rating_code']);
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
     * @param string $ratingCode
     * @param array $stores
     * @return void
     */
    protected function createRating($ratingCode, $storeId)
    {
        //$stores[] = $storeId;
        $rating = $this->getRating($ratingCode);
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
     * @param string $customerEmail
     * @return int|null
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
