<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Exception;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewrite;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;

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

    /**
     * Pages constructor.
     * @param SampleDataContext $sampleDataContext
     * @param PageInterfaceFactory $pageInterfaceFactory
     * @param Converter $converter
     * @param StoreRepositoryInterface $storeRepository
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteria
     * @param UrlRewrite $urlRewrite
     * @param UrlRewriteCollection $urlRewriteCollection
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        PageInterfaceFactory $pageInterfaceFactory,
        Converter $converter,
        StoreRepositoryInterface $storeRepository,
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteria,
        UrlRewrite $urlRewrite,
        UrlRewriteCollection $urlRewriteCollection
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
    }

    /**
     * @param array $row
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $row)
    {
        //TODO: set stores to use configuration if stores not in file
        //TODO: check on multiple stores for a page
        $row['content'] = $this->converter->convertContent($row['content']);
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
                            if (($key = array_search($this->getStoreIds($row['store_view_code'])[0], $storeIds)) !== false) {
                                unset($storeIds[$key]);
                            }

                            $updatePage->load($row['identifier'], 'identifier')->setStores($storeIds)->save();
                            //remove urls rewrite from existing page so new page can be added
                            $this->removeUrlRewrite($row['identifier'], $this->getStoreIds($row['store_view_code']));
                            //create a new page for the new stores
                            $page = $this->pageInterfaceFactory->create();
                            $page->addData($row)->setStores($this->getStoreIds($row['store_view_code']))->save();
                        }
                        //else is the exsiting store different than requested
                        elseif ($updatePage->getStores() != $this->getStoreIds($row['store_view_code'])) {
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
                    $page = $this->pageInterfaceFactory->create();
                    $page->addData($row)
                        ->setStores($this->getStoreIds($row['store_view_code']))
                        ->save();
                }
            }
        }

        return true;
    }

    /**
     * @param string $identifier
     * @param array $storeId
     * @throws Exception
     */
    protected function removeUrlRewrite(string $identifier, array $storeId)
    {
        $urls = $this->urlRewriteCollection->
            addFilter('request_path', $identifier, 'eq')->addFilter('store_id', $storeId, 'eq')->getItems();
        foreach ($urls as $url) {
            $this->urlRewrite->delete($url);
        }
    }

    /**
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
