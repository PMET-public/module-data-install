<?php
namespace MagentoEse\DataInstall\Ui\Component\Form;

use Magento\Framework\Registry;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        Registry $registry,
        //\MagentoEse\ImageUploader\Model\ResourceModel\Image\CollectionFactory $imageCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->registry = $registry;
        //$this->collection = $imageCollectionFactory->create();
    }

    public function getData()
    {
        return [];
    }
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return null;
    }
}
