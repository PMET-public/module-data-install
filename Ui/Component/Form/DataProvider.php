<?php
/** Copyright © Adobe  All rights reserved */
namespace MagentoEse\DataInstall\Ui\Component\Form;

use Magento\Framework\Registry;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * DataProvider constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Registry $registry
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        Registry $registry,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->registry = $registry;
    }

    /**
     * Get Data
     *
     * @return array
     */
    public function getData()
    {
        return [];
    }

    /**
     * Add filter
     *
     * @param \Magento\Framework\Api\Filter $filter
     * @return mixed|void|null
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return null;
    }
}
