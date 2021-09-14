<?php
namespace MagentoEse\DataInstall\Model\Import;

//use Webkul\UiForm\Model\ResourceModel\Employee\CollectionFactory;
 
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $employeeCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        //CollectionFactory $employeeCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        //$this->collection = $employeeCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
 
    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return [];
    }
    public function addFilter(\Magento\Framework\Api\Filter $filter)
{
    return null;
}
}