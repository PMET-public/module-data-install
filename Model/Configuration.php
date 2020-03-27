<?php


namespace MagentoEse\DataInstall\Model;

class Configuration
{

    protected $defaultWebsiteCode = 'base';

    protected $defaultStoreCode = 'main_website_store';

    protected $defaultViewCode = 'default';

    protected $defaultRootCategory = 'Default Category';

    protected $defaultRootCategoryId = 2;

    /**
     * @return string
     */
    public function getDefaultRootCategory(): string
    {
        return $this->defaultRootCategory;
    }

    /**
     * @return string
     */
    public function getDefaultStoreCode(): string
    {
        return $this->defaultStoreCode;
    }

    /**
     * @return string
     */
    public function getDefaultWebsiteCode(): string
    {
        return $this->defaultWebsiteCode;
    }

    /**
     * @return int
     */
    public function getDefaultRootCategoryId(): int
    {
        return $this->defaultRootCategoryId;
    }

    /**
     * @return string
     */
    public function getDefaultViewCode(): string
    {
        return $this->defaultViewCode;
    }
}
