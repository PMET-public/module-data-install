<?php
/**
 * Forked and adapted from https://github.com/firegento/FireGento_FastSimpleImport2
 */
namespace MagentoEse\DataInstall\Model\Import\Importer;

use Magento\Framework\App\Helper\Context;
use Magento\ImportExport\Helper\Report;
use Magento\Backend\Helper\Data;
use Magento\ImportExport\Model\History;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Report\ReportProcessorInterface;

class ImportError extends \Magento\Framework\App\Helper\AbstractHelper
{
    private const IMPORT_HISTORY_FILE_DOWNLOAD_ROUTE = '*/history/download';

    /**
     * Limit view errors
     */
    private const LIMIT_ERRORS_MESSAGE = 100;

    /**
     * @var ReportProcessorInterface
     */
    protected $reportProcessor;

    /**
     * @var History
     */
    protected $historyModel;

    /**
     * @var Report
     */
    protected $reportHelper;

    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param Context $context
     * @param ReportProcessorInterface $reportProcessor
     * @param ModelHistory $historyModel
     * @param Report $reportHelper
     * @param Data $helper
     * @return void
     */
    public function __construct(
        Context $context,
        ReportProcessorInterface $reportProcessor,
        History $historyModel,
        Report $reportHelper,
        Data $helper
    ) {

        $this->reportProcessor = $reportProcessor;
        $this->historyModel = $historyModel;
        $this->reportHelper = $reportHelper;
        $this->helper = $helper;

        parent::__construct($context);
    }

    /**
     * GetImportErrorMessages
     *
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return $this
     */
    public function getImportErrorMessages(
        ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        $resultText = '';
        if ($errorAggregator->getErrorsCount()) {
            $message = '';
            $counter = 0;
            foreach ($this->getErrorMessages($errorAggregator) as $error) {
                $message .= (++$counter) . '. ' . $error . '<br>';
                if ($counter >= self::LIMIT_ERRORS_MESSAGE) {
                    break;
                }
            }
            if ($errorAggregator->hasFatalExceptions()) {
                foreach ($this->getSystemExceptions($errorAggregator) as $error) {
                    $message .= $error->getErrorMessage()
                        . __('Additional data') . ': '
                        . $error->getErrorDescription() . '</div>';
                }
            }
            try {
                $resultText.=
                    '<strong>' . __('Following Error(s) has been occurred during importing process:') . '</strong><br>'
                    . '<div class="import-error-wrapper">' . __('Only first 100 errors are displayed here. ')
                    . '<a href="'
                    //. $this->createDownloadUrlImportHistoryFile($this->createErrorReport($errorAggregator))
                    . '">' . __('Download full report') . '</a><br>'
                    . '<div class="import-error-list">' . $message . '</div></div>'
                ;
            } catch (\Exception $e) {
                foreach ($this->getErrorMessages($errorAggregator) as $errorMessage) {
                    $resultText.= $errorMessage;
                }
            }
        }

        return $resultText;
    }

    /**
     * GetErrorMessages
     *
     * @param \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface $errorAggregator
     * @return array
     */
    protected function getErrorMessages(ProcessingErrorAggregatorInterface $errorAggregator)
    {
        $messages = [];
        $rowMessages = $errorAggregator->getRowsGroupedByErrorCode([], [AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
        foreach ($rowMessages as $errorCode => $rows) {
            $messages[] = $errorCode . ' ' . __('in rows:') . ' ' . implode(', ', $rows);
        }
        return $messages;
    }

    /**
     * Get System Exceptions
     *
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError[]
     */
    protected function getSystemExceptions(ProcessingErrorAggregatorInterface $errorAggregator)
    {
        return $errorAggregator->getErrorsByCode([AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
    }

    /**
     * CreateErrorReport
     *
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return string
     */
    protected function createErrorReport(ProcessingErrorAggregatorInterface $errorAggregator)
    {
        $this->historyModel->loadLastInsertItem();
        $sourceFile = $this->reportHelper->getReportAbsolutePath($this->historyModel->getImportedFile());
        $writeOnlyErrorItems = true;
        if ($this->historyModel->getData('execution_time') == ModelHistory::IMPORT_VALIDATION) {
            $writeOnlyErrorItems = false;
        }
        $fileName = $this->reportProcessor->createReport($sourceFile, $errorAggregator, $writeOnlyErrorItems);
        $this->historyModel->addErrorReportFile($fileName);
        return $fileName;
    }

    /**
     * Create Download history file
     *
     * @param string $fileName
     * @return string
     */
    protected function createDownloadUrlImportHistoryFile($fileName)
    {
        return $this->getUrl(self::IMPORT_HISTORY_FILE_DOWNLOAD_ROUTE, ['filename' => $fileName]);
    }
    
    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->helper->getUrl($route, $params);
    }
}
