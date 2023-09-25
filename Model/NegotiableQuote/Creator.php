<?php
/**
 * Copyright 2023 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\NegotiableQuote;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\User\Api\Data\UserInterfaceFactory;

class Creator
{

    /**
     * @var UserInterfaceFactory
     */
    private UserInterfaceFactory $userFactory;

    /**
     * @var IntegrationServiceInterface
     */
    private IntegrationServiceInterface $integration;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     *
     * @param UserInterfaceFactory        $userFactory
     * @param IntegrationServiceInterface $integration
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        UserInterfaceFactory $userFactory,
        IntegrationServiceInterface $integration,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->userFactory        = $userFactory;
        $this->integration        = $integration;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Get customer by username
     *
     * @param  mixed  $type
     * @param  string $username
     * @return string
     */
    public function retrieveCreatorByUsername($type, $username): string
    {
        switch ($type) {
            case UserContextInterface::USER_TYPE_ADMIN:
                $user = $this->userFactory->create()->loadByUsername($username);
                return $user->getId();

            case UserContextInterface::USER_TYPE_INTEGRATION:
                $integration = $this->integration->findByName($username);
                return $integration->getId();

            case UserContextInterface::USER_TYPE_CUSTOMER:
                try {
                    $customer = $this->customerRepository->get($username);
                    return $customer->getId();
                } catch (NoSuchEntityException | LocalizedException $e) {
                    return '0';
                }
                break;
        }

        return '0';
    }

    /**
     * Get Customer
     *
     * @param  string $email
     * @param  string $websiteId
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomer($email, $websiteId = ''): CustomerInterface
    {
        return $this->customerRepository->get($email, $websiteId);
    }
}
