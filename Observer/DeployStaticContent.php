<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\Framework\Shell;

class DeployStaticContent implements ObserverInterface
{
    
    /** @var Helper */
    protected $helper;

    /** @var string */
    protected $functionCallPath;

    /** @var Shell */
    protected $shell;
   
   /**
    *
    * @param Helper $helper
    * @param Shell $shell
    * @return void
    */
    public function __construct(Helper $helper, Shell $shell)
    {
        $this->helper = $helper;
        $this->shell = $shell;
        $this->functionCallPath =
        PHP_BINARY . ' -f ' . BP . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'magento ';
    }

    /**
     * Observer run
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $item = $observer->getData('eventData');
        $this->helper->logMessage("Deploying Static Content", "info");
        $this->deployContent();
        $this->flushCache();
    }

    /**
     * Deploys static content
     *
     * @return void
     */
    private function deployContent()
    {
        $cmd = $this->functionCallPath . 'setup:static-content:deploy -f';
        $execOutput = $this->shell->execute($cmd);
        $this->helper->logMessage($execOutput, "info");
    }

    /**
     * Flushes cache
     *
     * @return void
     */
    private function flushCache()
    {
        $cmd = $this->functionCallPath . 'cache:flush';
        $execOutput = $this->shell->execute($cmd);
        $this->helper->logMessage($execOutput, "info");
    }
}
