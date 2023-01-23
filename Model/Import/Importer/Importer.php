<?php
/**
 * Forked and adapted from https://github.com/firegento/FireGento_FastSimpleImport2
 */
namespace MagentoEse\DataInstall\Model\Import\Importer;

use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\ImportFactory;

class Importer
{
    /**
     * @var ImportError
     */
    protected $errorHelper;

    /**
     * @var mixed
     */
    protected $errorMessages;

    /**
     * @var ArrayAdapterFactory
     */
    protected $arrayAdapter;

    /**
     * @var mixed
     */
    protected $validationResult;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var string
     */
    protected $logTrace = "";

    /**
     * @var \ImportFactory
     */
    private $importModelFactory;

    /**
     * @param ImportFactory $importModelFactory
     * @param ImportError $errorHelper
     * @param ArrayAdapterFactory $arrayAdapter
     * @param Config $configHelper
     */
    public function __construct(
        ImportFactory $importModelFactory,
        ImportError $errorHelper,
        ArrayAdapterFactory $arrayAdapter,
        Config $configHelper
    ) {
        $this->errorHelper = $errorHelper;
        $this->arrayAdapter = $arrayAdapter;
        $this->configHelper = $configHelper;
        $this->importModelFactory = $importModelFactory;
        $this->settings = [
            'entity' => $this->configHelper->getEntity(),
            'behavior' => $this->configHelper->getBehavior(),
            'ignore_duplicates' => $this->configHelper->getIgnoreDuplicates(),
            'validation_strategy' => $this->configHelper->getValidationStrategy(),
            'allowed_error_count' => $this->configHelper->getAllowedErrorCount(),
            'import_images_file_dir' => $this->configHelper->getImportFileDir(),
            'category_path_seperator' => $this->configHelper->getCategoryPathSeperator(),
            '_import_multiple_value_separator' =>  Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR
        ];
    }

    /**
     * Getter for default Delimiter
     *
     * @return mixed
     */

    public function getMultipleValueSeparator()
    {
        return $this->settings['_import_multiple_value_separator'];
    }

   /**
    * Set Multiple value Separator
    *
    * @param mixed $multipleValueSeparator
    * @return void
    */
    public function setMultipleValueSeparator($multipleValueSeparator)
    {
        $this->settings['_import_multiple_value_separator'] = $multipleValueSeparator;
    }

    /**
     * Get Import Adapter
     *
     * @return ArrayAdapter
     */
    public function getImportAdapterFactory()
    {
        return $this->arrayAdapter;
    }

    /**
     * Set Import Adapter
     *
     * @param ArrayAdapterFactory $arrayAdapter
     */
    public function setImportAdapterFactory($arrayAdapter)
    {
        $this->arrayAdapter = $arrayAdapter;
    }

    /**
     * Process Import
     *
     * @param mixed $dataArray
     * @return bool
     * @throws LocalizedException
     */
    public function processImport($dataArray)
    {
        $validation = $this->validateData($dataArray);
        if ($validation) {
            $this->importData();
        }

        return $validation;
    }

    /**
     * Validate Data
     *
     * @param mixed $dataArray
     * @return bool
     * @throws LocalizedException
     */
    protected function validateData($dataArray)
    {
        $importModel = $this->createImportModel();
        $source = $this->arrayAdapter->create(
            [
                'data' => $dataArray,
                'multipleValueSeparator' => $this->getMultipleValueSeparator()
            ]
        );
        $this->validationResult = $importModel->validateSource($source);
        $this->addToLogTrace($importModel);
        return $this->validationResult;
    }

    /**
     * Create Import Model
     *
     * @return Import
     */
    public function createImportModel()
    {
        $importModel = $this->importModelFactory->create();
        $importModel->setData($this->settings);
        return $importModel;
    }

    /**
     * Add to Log Trace
     *
     * @param mixed $importModel
     * @return void
     */
    public function addToLogTrace($importModel)
    {
        $this->logTrace = $this->logTrace . $importModel->getFormatedLogTrace();
    }

    /**
     * Import Data
     *
     * @return void
     * @throws LocalizedException
     */
    protected function importData()
    {
        $importModel = $this->createImportModel();
        $importModel->importSource();
        $this->handleImportResult($importModel);
    }

    /**
     * Handle Import Result
     *
     * @param mixed $importModel
     * @return void
     */
    protected function handleImportResult($importModel)
    {
        $errorAggregator = $importModel->getErrorAggregator();
        $this->errorMessages = $this->errorHelper->getImportErrorMessages($errorAggregator);
        $this->addToLogTrace($importModel);
        if (!$importModel->getErrorAggregator()->hasToBeTerminated()) {
            $importModel->invalidateIndex();
        }
    }

    /**
     * Set Entity Code
     *
     * @param string $entityCode
     */
    public function setEntityCode($entityCode)
    {
        $this->settings['entity'] = $entityCode;
    }

    /**
     * Set Behavior
     *
     * @param string $behavior
     */
    public function setBehavior($behavior)
    {
        $this->settings['behavior'] = $behavior;
    }

    /**
     * Set Ignore Duplicates
     *
     * @param string $value
     */
    public function setIgnoreDuplicates($value)
    {
        $this->settings['ignore_duplicates'] = $value;
    }

    /**
     * Set Validation Strategy
     *
     * @param string $strategy
     */
    public function setValidationStrategy($strategy)
    {
        $this->settings['validation_strategy'] = $strategy;
    }

    /**
     * Set Allowed Error Count
     *
     * @param int $count
     */
    public function setAllowedErrorCount($count)
    {
        $this->settings['allowed_error_count'] = $count;
    }

    /**
     * Set Import Images Dir
     *
     * @param string $dir
     */
    public function setImportImagesFileDir($dir)
    {
        $this->settings['import_images_file_dir'] = $dir;
    }

    /**
     * Set Validation Result
     *
     * @return mixed
     */
    public function getValidationResult()
    {
        return $this->validationResult;
    }

    /**
     * Get Log Trace
     *
     * @return string
     */
    public function getLogTrace()
    {
        return $this->logTrace;
    }

    /**
     * Get Error Messages
     *
     * @return mixed
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }
}
