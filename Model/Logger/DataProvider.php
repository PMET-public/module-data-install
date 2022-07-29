<?php
/** Copyright © Adobe  All rights reserved */
namespace MagentoEse\DataInstall\Model\Logger;

use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * Data Provider Constructor
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    //phpcs:ignore Magento2.Annotation.MethodArguments.NoCommentBlock,Generic.CodeAnalysis.UselessOverridingMethod.Found
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
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

    /**
     * Add Filter
     *
     * @param \Magento\Framework\Api\Filter $filter
     * @return mixed|void|null
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return null;
    }
}
