<?php
/**
 * Forked and adapted from https://github.com/firegento/FireGento_FastSimpleImport2
 */
namespace MagentoEse\DataInstall\Model\Import\Importer;

use Magento\Store\Model\ScopeInterface;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{

    private const XML_PATH_IGNORE_DUPLICATES      = 'fastsimpleimport/default/ignore_duplicates';
    private const XML_PATH_BEHAVIOR               = 'fastsimpleimport/default/behavior';
    private const XML_PATH_ENTITY                 = 'fastsimpleimport/default/entity';
    private const XML_PATH_VALIDATION_STRATEGY    = 'fastsimpleimport/default/validation_strategy';
    private const XML_PATH_ALLOWED_ERROR_COUNT    = 'fastsimpleimport/default/allowed_error_count';
    private const XML_PATH_IMPORT_IMAGES_FILE_FIR = 'fastsimpleimport/default/import_images_file_dir';
    private const XML_PATH_CATEGORY_PATH_SEPERATOR = 'fastsimpleimport/default/category_path_seperator';

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    // public function __construct(
    //     \Magento\Framework\App\Helper\Context $context
    // ) {
    //     parent::__construct($context);
    // }
    /**
     * GetCategoryPathSeperator
     *
     * @return mixed
     */
    public function getCategoryPathSeperator()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CATEGORY_PATH_SEPERATOR, ScopeInterface::SCOPE_STORE);
    }

    /**
     * GetIgnoreDuplicates
     *
     * @return string
     */
    public function getIgnoreDuplicates()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_IGNORE_DUPLICATES, ScopeInterface::SCOPE_STORE);
    }

    /**
     * GetBehavior
     *
     * @return string
     */
    public function getBehavior()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_BEHAVIOR, ScopeInterface::SCOPE_STORE);
    }

    /**
     * GgetEntity
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ENTITY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * GetValidationStrategy
     *
     * @return string
     */
    public function getValidationStrategy()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_VALIDATION_STRATEGY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * GetAllowedErrorCount
     *
     * @return string
     */
    public function getAllowedErrorCount()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ALLOWED_ERROR_COUNT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * GetImportFileDir
     *
     * @return string
     */
    public function getImportFileDir()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_IMPORT_IMAGES_FILE_FIR, ScopeInterface::SCOPE_STORE);
    }
}
