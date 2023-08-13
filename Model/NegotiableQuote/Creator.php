<?php

namespace MagentoEse\DataInstall\Model\NegotiableQuote;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\NegotiableQuote\Model\Purged\Provider;
use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\User\Model\ResourceModel\User;

class Creator
{
    private User $userResource;
    private UserInterfaceFactory $userFactory;
    private IntegrationServiceInterface $integration;
    private CustomerRepositoryInterface $customerRepository;
    private Provider $provider;

    public function __construct(
        User $userResource,
        UserInterfaceFactory $userFactory,
        IntegrationServiceInterface $integration,
        CustomerRepositoryInterface $customerRepository,
        Provider $provider
    ) {
        $this->userResource = $userResource;
        $this->userFactory = $userFactory;
        $this->integration = $integration;
        $this->customerRepository = $customerRepository;
        $this->provider = $provider;
    }

    /**
     * @param $type
     * @param $id
     * @param $quoteId
     * @return string
     */
    public function retrieveCreatorById($type, $id, $quoteId = null): string
    {
        if ($type == UserContextInterface::USER_TYPE_ADMIN) {
            try {
                $user = $this->userFactory->create();
                $this->userResource->load($user, $id);
                return $user->getUserName();
            } catch (NoSuchEntityException $e) {
                if ($quoteId) {
                    return $this->provider->getSalesRepresentativeName($quoteId);
                }
            }
        } elseif ($type == UserContextInterface::USER_TYPE_INTEGRATION) {
            try {
                $integration = $this->integration->get($id);
                return $integration->getName();
            } catch (IntegrationException $e) {
                return 'System';
            }
        } elseif ($type == UserContextInterface::USER_TYPE_CUSTOMER) {
            try {
                $customer = $this->customerRepository->getById($id);
                return $customer->getEmail();
            } catch (NoSuchEntityException|LocalizedException $e) {
                if ($quoteId) {
                    return $this->provider->getCompanyEmail($quoteId);
                }
            }
        }

        return 'System';
    }

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
                } catch (NoSuchEntityException|LocalizedException $e) {
                    return '0';
                }
                break;
        }

        return '0';
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCustomer($email, $websiteId = ''): CustomerInterface
    {
        return $this->customerRepository->get($email, $websiteId);
    }
}
