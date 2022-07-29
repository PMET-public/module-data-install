<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Exception;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\CmsUrlRewrite\Model\CmsPageUrlRewriteGenerator;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewrite;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory as UrlRewriteCollection;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite as UrlRewriteService;
use MagentoEse\DataInstall\Model\Converter;
use MagentoEse\DataInstall\Helper\Helper;

class Pages
{
    /** @var Csv  */
    protected $csvReader;

    /** @var FixtureManager  */
    protected $fixtureManager;

    /** @var PageInterfaceFactory  */
    protected $pageInterfaceFactory;

    /** @var Converter  */
    protected $converter;

    /** @var StoreRepositoryInterface  */
    protected $storeRepository;

    /** @var PageRepositoryInterface  */
    protected $pageRepository;

    /** @var SearchCriteriaBuilder  */
    protected $searchCriteria;

    /** @var UrlRewrite  */
    protected $urlRewrite;

    /** @var UrlRewriteCollection  */
    protected $urlRewriteCollection;

    /** @var UrlPersistInterface */
    protected $urlPersist;

    /** @var Helper */
    protected $helper;

    /**
     * Pages constructor
     *
     * @param SampleDataContext $sampleDataContext
     * @param PageInterfaceFactory $pageInterfaceFactory
     * @param Converter $converter
     * @param StoreRepositoryInterface $storeRepository
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteria
     * @param UrlRewrite $urlRewrite
     * @param UrlRewriteCollection $urlRewriteCollection
     * @param UrlPersistInterface $urlPersist
     * @param Helper $helper
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        PageInterfaceFactory $pageInterfaceFactory,
        Converter $converter,
        StoreRepositoryInterface $storeRepository,
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteria,
        UrlRewrite $urlRewrite,
        UrlRewriteCollection $urlRewriteCollection,
        UrlPersistInterface $urlPersist,
        Helper $helper
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->pageInterfaceFactory = $pageInterfaceFactory;
        $this->converter = $converter;
        $this->storeRepository = $storeRepository;
        $this->pageRepository = $pageRepository;
        $this->searchCriteria = $searchCriteria;
        $this->urlRewrite = $urlRewrite;
        $this->urlRewriteCollection = $urlRewriteCollection;
        $this->urlPersist = $urlPersist;
        $this->helper = $helper;
    }

    /**
     * Install
     *
     * @param array $row
     * @return bool
     * @throws LocalizedException
     */
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh,Magento2.Annotation.MethodArguments.NoCommentBlock
    public function install(array $row, array $settings)
    {
        //TODO: Set default layout of a page cms-full-width *check if necessary
        //TODO:Validate design layout types
        $row['content'] = $this->converter->convertContent($row['content']);
        if (empty($row['is_active']) || $row['is_active']=='Y') {
            $row['is_active'] = 1;
        } else {
            $row['is_active'] = 0;
        }
        if (!empty($row['identifier'])) {
            $foundPage=0;
            $search = $this->searchCriteria->addFilter(PageInterface::IDENTIFIER, $row['identifier'], 'eq')->create();

            $pages = $this->pageRepository->getList($search)->getItems();
            /** @var \Magento\Cms\Model\Page $page */

            if (count($pages) == 0) {
                $page = $this->pageInterfaceFactory->create();
                $page->addData($row)
                    ->setStores($this->getStoreIds($row['store_view_code']))
                    ->save();
            } else {
                foreach ($pages as $updatePage) {
                    $page = $this->pageInterfaceFactory->create();
                    //is it a single page
                    if (count($pages) == 1) {
                        //is the exiting page a store zero && are we requesting a different store/stores
                        if ($updatePage->getStores()[0]==0 && $this->getStoreIds($row['store_view_code'])[0] !=0) {
                            //Save the exiting page under all other stores

                            $storeIds = $this->getAllStoreIds();
                            if (($key = array_search($this->getStoreIds(
                                $row['store_view_code']
                            )[0], $storeIds)) !== false) {
                                unset($storeIds[$key]);
                            }

                            $updatePage->load($row['identifier'], 'identifier')->setStores($storeIds)->save();
                            //remove urls rewrite from existing page so new page can be added
                            $this->removeUrlRewrite($row['identifier'], $this->getStoreIds($row['store_view_code']));
                            //create a new page for the new stores
                            $page = $this->pageInterfaceFactory->create();
                            $page->addData($row)->setStores($this->getStoreIds($row['store_view_code']))->save();
                        } elseif ($updatePage->getStores() != $this->getStoreIds($row['store_view_code'])) {
                            //else is the exisiting store different than requested
                            //create a new page for the new stores
                            $page = $this->pageInterfaceFactory->create();
                            $page->addData($row)
                                ->setStores($this->getStoreIds($row['store_view_code']))
                                ->save();
                        } else {
                            //update current page
                            $updatePage->load($row['identifier'], 'identifier');
                            $this->pageRepository->save($updatePage->addData($row));
                        }
                    } else {
                        //multiple pages exist
                        if ($updatePage->getStores()[0]==$this->getStoreIds($row['store_view_code'])[0]) {
                            //update when store is found
                            $updatePage->load($row['identifier'], 'identifier');
                             $this->pageRepository->save($updatePage->addData($row));
                            $foundPage =1;
                        }
                    }
                }

                //if its an existing page, but needs to be created for a store
                if (count($pages) > 1 && $foundPage==0) {
                    $this->urlPersist->deleteByData(
                        [
                        UrlRewriteService::ENTITY_TYPE =>CmsPageUrlRewriteGenerator::ENTITY_TYPE,
                        UrlRewriteService::STORE_ID=>$this->getStoreIds($row['store_view_code']),
                        UrlRewriteService::REQUEST_PATH=>$row['identifier']
                        ]
                    );
                    $page = $this->pageInterfaceFactory->create();
                    try {
                        $page->addData($row)
                        ->setStores($this->getStoreIds($row['store_view_code']))
                        ->save();
                    } catch (Exception $e) {
                        $this->helper->logMessage("The Page ".$row['title']." cannot be updated.  ".
                        "It is likely conflicting with page data set elsewhere.", "warning");
                    }
                }
            }
        } else {
            $this->helper->logMessage("A row in pages.csv does not have a value in the identifier columnm. ".
                        "The row has been skipped.", "warning");
        }

        return true;
    }

    /**
     * Remove rewrite for store
     *
     * @param string $identifier
     * @param array $storeId
     * @throws Exception
     */
    protected function removeUrlRewrite(string $identifier, array $storeId)
    {
        $urls = $this->urlRewriteCollection->create()->
            addFilter('request_path', $identifier, 'eq')->addFilter('store_id', $storeId, 'eq')->getItems();
        foreach ($urls as $url) {
            $this->urlRewrite->delete($url);
        }
    }

    /**
     * Get store ids from codes
     *
     * @param string $storeCodes
     * @return array
     */
    public function getStoreIds($storeCodes)
    {
        $storeList = explode(",", $storeCodes);
        $returnArray = [];
        foreach ($storeList as $storeCode) {
            $stores =$this->storeRepository->getList();
            foreach ($stores as $store) {
                if ($store->getCode()==$storeCode) {
                    $returnArray[]= $store->getId();
                    break;
                }
            }
        }

        if (count($returnArray)==0) {
            $returnArray[]=0;
        }

        return $returnArray;
    }

    /**
     * Get all store ids
     *
     * @return array
     */
    private function getAllStoreIds()
    {
        $storeList=[];
        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            $storeList[]=$store->getId();
        }

        //remove store zero
        if (($key = array_search(0, $storeList)) !== false) {
            unset($storeList[$key]);
        }

        return $storeList;
    }
}
