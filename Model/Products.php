<?php


namespace MagentoEse\DataInstall\Model;

use Magento\Framework\ObjectManagerInterface;

class Products
{
    //TODO: flexibility for other than default category
    //TODO: Check on using Export as import file
    /** @var ObjectManagerInterface  */
    protected $objectManager;

    /**
     * Products constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager=$objectManager;
    }

    public function install(array $rows, array $header, string $modulePath)
    {
        foreach ($rows as $row) {
            $productsArray[] = array_combine($header, $row);
        }
        $this->importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');
        $this->importerModel->setImportImagesFileDir($modulePath.'/media/catalog/product');
        $this->importerModel->setValidationStrategy('validation-skip-errors');
        try {
            $this->importerModel->processImport($productsArray);
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }

        print_r($this->importerModel->getLogTrace());
        print_r($this->importerModel->getErrorMessages());
        unset($_productsArray);
        unset($this->importerModel);
    }
}
