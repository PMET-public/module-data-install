<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Block for services-id front end loader
 *
 * @api
 */
class Index extends Template
{
    /**
     * Config Paths
     * @var string
     */
    private const APP_URL_PATH = 'magentoese/datainstall/app_url';
    private const CSS_URL_PATH = 'magentoese/datainstall/css_url';
    private const BASE_URL_PATH = 'web/secure/base_url';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @return void
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns config for app url
     *
     * @return string
     */
    public function getAppUrl(): string
    {
        return (string) $this->_scopeConfig->getValue(
            self::APP_URL_PATH,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Returns config for css url
     *
     * @return string
     */
    public function getCssUrl(): string
    {
        return (string) $this->_scopeConfig->getValue(
            self::CSS_URL_PATH,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

     /**
      * Returns Qraph!l BFF url
      *
      * @return string
      */
    public function getBaseUrl(): string
    {
        return $this->scopeConfig->getValue(
            self::BASE_URL_PATH,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }
}
