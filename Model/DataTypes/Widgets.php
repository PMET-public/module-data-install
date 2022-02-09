<?php
/** Copyright © Adobe  All rights reserved */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Exception;
use Magento\Widget\Model\Widget\InstanceFactory;
use Magento\Widget\Model\Widget\Instance;
use MagentoEse\DataInstall\Model\Converter;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Framework\App\State;
use Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory;
use Magento\Widget\Model\ResourceModel\Widget\Instance\Collection;

class Widgets
{
    /** @var InstanceFactory */
    protected $instanceFactory;

    /** @var Converter  */
    protected $converter;

    /** @var Helper */
    protected $helper;

    /** @var ThemeCollection */
    protected $themeCollection;

    /** @var Stores */
    protected $stores;

    /** @var State */
    protected $appState;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /**
     * @param InstanceFactory $instanceFactory
     * @param Helper $helper
     * @param Converter $converter
     * @param ThemeCollection $themeCollection
     * @param Stores $stores
     * @param State $appState
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        InstanceFactory $instanceFactory,
        Helper $helper,
        Converter $converter,
        ThemeCollection $themeCollection,
        Stores $stores,
        State $appState,
        CollectionFactory $collectionFactory
    ) {
        $this->instanceFactory = $instanceFactory;
        $this->helper = $helper;
        $this->converter = $converter;
        $this->themeCollection = $themeCollection;
        $this->stores = $stores;
        $this->appState = $appState;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws Exception
     */
    public function install(array $row, array $settings)
    {
    //type, theme, title, store is required
        if (empty($row['title'])) {
            $this->helper->logMessage(
                "title is required in the widgets data file. Row has been skipped.",
                "warning"
            );
            return true;
        }
        if (empty($row['instance_type'])) {
            $this->helper->logMessage(
                "instance_type is required in the widgets data file. Row has been skipped.",
                "warning"
            );
            return true;
        }
    //default to blank theme if theme not given
        if (empty($row['theme'])) {
            $themeId = 1;
        } else {
            $themeId = $this->themeCollection->getThemeByFullPath('frontend/' . $row['theme'])->getThemeId();
            if (!$themeId) {
                $themeId = 1;
            }
        }
    //if store is not given, default to all (0)
        if (empty($row['store_view_codes'])) {
            $storeIds[] = 0;
        } else {
            $storeIds = explode(",", $this->stores->getViewIdsFromCodeList($row['store_view_codes']));
        }
    //get widget if exists
    /** @var Collection $widgetCollection */
        $widgetCollection = $this->collectionFactory->create();
        $widgetCollection->addFilter('title', $row['title']);
        if ($widgetCollection->count() > 0) {
            $widget = $widgetCollection->getFirstItem();
        } else {
            $widget = $this->instanceFactory->create();
        }
    //validate type
        $type = $widget->getWidgetReference('type', $row['instance_type'], 'type');
        if (!$type) {
            $this->helper->logMessage(
                "Type ".$row['instance_type']." is invalid for widget ".$row['title'].". Row has been skipped.",
                "warning"
            );
        }
    /** @var Instance $widget */
        $widget->setTitle($row['title']);
        $widget->setType($type);
        $widget->setThemeId($themeId);
        $widget->setStoreIds($storeIds);
    //sort order
        if (!empty($row['sort_order'])) {
            $widget->setSortOrder($row['sort_order']);
        }
        $r = json_decode($this->converter->convertContent($row['widget_parameters']), true);
        $widget->setWidgetParameters(json_decode($this->converter->convertContent($row['widget_parameters']), true));
        $pageGroup=[];
        $pageGroup['page_group']=$row['page_group'];
        $pageGroup[$row['page_group']]['layout_handle']=$row['layout_handle'];
        $pageGroup[$row['page_group']]['for']=$row['page_for'];
        $pageGroup[$row['page_group']]['block']=$row['block_reference'];
        $pageGroup[$row['page_group']]['entities']=$this->converter->convertContent($row['entities']);
        $pageGroup[$row['page_group']]['layout_handle']=$row['layout_handle'];
        $pageGroup[$row['page_group']]['template']=$row['page_template'];
        $pageGroup[$row['page_group']]['page_id']=0;

        $widget->setPageGroups([$pageGroup]);

        $this->appState->emulateAreaCode(
            'frontend',
            [$widget, 'save']
        );
    }
}
