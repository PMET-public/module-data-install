<?php

/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Validation\ValidationException;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\InventoryApi\Api\Data\StockInterfaceFactory;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class MsiSource
{
    /** @var Helper */
    protected $helper;

    /** @var StockInterfaceFactory */
    protected $stockInterfaceFactory;

    /** @var StockRepositoryInterface */
    protected $stockRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteria;

    /** @var Stores */
    protected $stores;

    /** @var SourceRepositoryInterface */
    protected $sourceRepository;

    /** @var SourceInterfaceFactory */
    protected $sourceInterfaceFactory;

    /**
     * constructor.
     * @param Helper $helper
     * @param StockInterfaceFactory $stockInterfaceFactory
     * @param StockRepositoryInterface $stockRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteria
     * @param Stores $stores
     * @param SourceRepositoryInterface $sourceRepository
     * @param SourceInterfaceFactory $sourceInterfaceFactory
     */
    public function __construct(
        Helper $helper,
        StockInterfaceFactory $stockInterfaceFactory,
        StockRepositoryInterface $stockRepositoryInterface,
        SearchCriteriaBuilder $searchCriteria,
        Stores $stores,
        SourceRepositoryInterface $sourceRepository,
        SourceInterfaceFactory $sourceInterfaceFactory
    ) {
        $this->helper = $helper;
        $this->stockInterfaceFactory = $stockInterfaceFactory;
        $this->stockRepository = $stockRepositoryInterface;
        $this->searchCriteria = $searchCriteria;
        $this->stores = $stores;
        $this->sourceRepository = $sourceRepository;
        $this->sourceInterfaceFactory = $sourceInterfaceFactory;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function install(array $row, array $settings)
    {
        //name, source_code,country, region, postcode required
        //If its a pickup location, phone, city, street are required
        if (empty($row['source_code'])) {
            $this->helper->logMessage(
                "A row in msi_source file does not have a value for source_code. Row is skipped",
                "warning"
            );
            return true;
        }
        if (empty($row['name'])) {
            $this->helper->logMessage(
                $row['source_code']." in msi_source file does not have a value for name. Row is skipped",
                "warning"
            );
            return true;
        }
        if (empty($row['country_id'])) {
            $this->helper->logMessage(
                $row['source_code']." in msi_source file does not have a value for country_id. Row is skipped",
                "warning"
            );
            return true;
        }
        if (empty($row['region_id'])) {
            $this->helper->logMessage(
                $row['source_code']." in msi_source file does not have a value for region_id. Row is skipped",
                "warning"
            );
            return true;
        }
        if (empty($row['postcode'])) {
            $this->helper->logMessage(
                $row['source_code'].
                " in msi_source file does not have a value for postcode. Row is skipped",
                "warning"
            );
            return true;
        }
        if (empty($row['is_pickup_location_active'])) {
            $row['is_pickup_location_active']=0;
        }
        if ($row['is_pickup_location_active']==1) {
            if (empty($row['city'])) {
                $this->helper->logMessage(
                    "An msi source is flagged as a pickup location, but city is not defined. "."
                Row is skipped",
                    "warning"
                );
                return true;
            }
            if (empty($row['street'])) {
                $this->helper->logMessage(
                    "An msi source is flagged as a pickup location, but street is not defined. "."
                Row is skipped",
                    "warning"
                );
                return true;
            }
            if (empty($row['phone'])) {
                $this->helper->logMessage(
                    "An msi source is flagged as a pickup location, but phone is not defined. "."
                Row is skipped",
                    "warning"
                );
                return true;
            }
        }

        if (empty($row['enabled'])) {
            $row['enabled']=false;
        } else {
            $row['enabled']=true;
        }

        try {
            $source = $this->sourceRepository->get($row['source_code']);
        } catch (NoSuchEntityException $e) {
            $source = $this->sourceInterfaceFactory->create();
        }
        $source->setSourceCode($row['source_code']);
        $source->setName($row['name']);
        $source->setCountryId($row['country_id']);
        $source->setPostcode($row['postcode']);
        $source->setRegionId($row['region_id']);
        $source->setEnabled($row['enabled']);
        if (!empty($row['description'])) {
            $source->setDescription($row['description']);
        }
        if (!empty($row['latitude'])) {
            $source->setLatitude((float) $row['latitude']);
        }
        if (!empty($row['longitude'])) {
            $source->setLongitude((float) $row['longitude']);
        }
        if (!empty($row['street'])) {
            $source->setStreet($row['street']);
        }
        if (!empty($row['city'])) {
            $source->setCity($row['city']);
        }
        if (!empty($row['contact_name'])) {
            $source->setContactName($row['contact_name']);
        }
        if (!empty($row['email'])) {
            $source->setEmail($row['email']);
        }
        if (!empty($row['phone'])) {
            $source->setPhone($row['phone']);
        }
        if (!empty($row['fax'])) {
            $source->setFax($row['fax']);
        }

        //add in store pickup attributes
        $extensionAttributes = $source->getExtensionAttributes();
        $extensionAttributes->setIsPickupLocationActive($row['is_pickup_location_active']);
        $extensionAttributes->setFrontendDescription($row['frontend_description']);
        $extensionAttributes->setFrontendName($row['frontend_name']);
        $source->setExtensionAttributes($extensionAttributes);

        $this->sourceRepository->save($source);

        return true;
    }
}
