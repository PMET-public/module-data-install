<?php


namespace MagentoEse\DataInstall\Model;



use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;


class Pages
{
    /**
     * @var \Magento\Framework\File\Csv
     */
    private $csvReader;


    private $fixtureManager;

    /** @var PageInterfaceFactory  */
    private $pageInterfaceFactory;

    /** @var Converter  */
    private $converter;

    /** @var StoreRepositoryInterface  */
    private $storeRepository;

    /** @var PageRepositoryInterface  */
    private $pageRepository;

    /** @var SearchCriteriaBuilder  */
    private $searchCriteria;

    /**
     * Page constructor.
     * @param SampleDataContext $sampleDataContext
     * @param PageInterfaceFactory $pageInterfaceFactory
     * @param \MagentoEse\VMContent\Model\ReplaceIds $replaceIds
     * @param StoreRepositoryInterface $storeRepository
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteria
     */
    public function __construct( SampleDataContext $sampleDataContext,PageInterfaceFactory $pageInterfaceFactory,
                                 Converter $converter, StoreRepositoryInterface $storeRepository,
                                 PageRepositoryInterface $pageRepository, SearchCriteriaBuilder $searchCriteria)
    {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->pageInterfaceFactory = $pageInterfaceFactory;
        $this->converter = $converter;
        $this->storeRepository = $storeRepository;
        $this->pageRepository = $pageRepository;
        $this->searchCriteria = $searchCriteria;

    }

    public function install(array $row){
        //TODO: set stores to use configuration if stores not in file
        //TODO: check on multiple stores
        $row['content'] = $this->converter->convertContent($row['content']);


        $search = $this->searchCriteria->addFilter(PageInterface::IDENTIFIER,$row['identifier'],'eq')
            ->addFilter(PageInterface::TITLE, $row['title'],'eq')->create();

        $pages = $this->pageRepository->getList($search)->getTotalCount();
        $page = $this->pageInterfaceFactory->create();
        if($pages==0) {
            //$this->pageInterfaceFactory->create()
                //->load($row['identifier'], 'identifier')
                $page->addData($row)
                //->setStores([\Magento\Store\Model\Store::DEFAULT_STORE_ID])
                ->setStores($this->getStoreIds($row['stores']))
                ->save();
        } else {
            $page->load($row['identifier'], 'identifier')
                ->addData($row)
                //->setStores([\Magento\Store\Model\Store::DEFAULT_STORE_ID])
                ->setStores($this->getStoreIds($row['stores']))
                ->save();
        }
    }

    public function getStoreIds($storeCodes){
        $storeList = explode(",",$storeCodes);
        $returnArray = [];
        foreach($storeList as $storeCode){
            $stores =$this->storeRepository->getList();
            foreach($stores as $store){
                if($store->getCode()==$storeCode){
                    $returnArray[]= $store->getId();
                    break;
                }
            }
        }
        return $returnArray;
    }
}
