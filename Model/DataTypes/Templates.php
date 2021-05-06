<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\PageBuilder\Api\Data\TemplateInterface;
use Magento\PageBuilder\Model\TemplateFactory;
use Magento\PageBuilder\Model\ResourceModel\Template\CollectionFactory as TemplateCollection;
use MagentoEse\DataInstall\Model\Converter;
use Magento\PageBuilder\Model\TemplateRepository;
use MagentoEse\DataInstall\Helper\Helper;

class Templates
{

    const TEMPLATE_DIR = ".template-manager/";

    /** @var TemplateFactory */
    protected $templateFactory;

    /** @var TemplateCollection */
    protected $templateCollection;

    /** @var Converter */
    protected $converter;

    /** @var TemplateRepository */
    protected $templateRepository;

    /** @var Helper */
    protected $helper;

    /**
     * Templates constructor.
     * @param TemplateFactory $templateFactory
     * @param TemplateCollection $templateCollection
     * @param Converter $converter
     * @param TemplateRepository $templateRepository
     * @param Helper $helper
     */
    public function __construct(
        TemplateFactory $templateFactory,
        TemplateCollection $templateCollection,
        Converter $converter,
        TemplateRepository $templateRepository,
        Helper $helper
    ) {

        $this->templateFactory = $templateFactory;
        $this->templateCollection = $templateCollection;
        $this->converter = $converter;
        $this->templateRepository = $templateRepository;
        $this->helper = $helper;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function install(array $row, array $settings)
    {
        if(empty($row['name'])){
            $this->helper->printMessage("Page Builder Template is missing a name. Row skipped", "warning");
        }
        if(empty($row['content'])){
            $row['content'] = '';
        }
        if(empty($row['created_for'])){
            $row['created_for'] = 'any';
        }
        if(empty($row['preview_image'])){
            $row['preview_image'] = 1;
        }
        
        $template = $this->templateCollection->create()
            ->addFieldToFilter(TemplateInterface::KEY_NAME, ['eq' => $row['name']])->getFirstItem();
        if(!$template){
            $template = $this->templateFactory->create();
        }
        $template->setTemplate($this->converter->convertContent($row['content']));
        $template->setName($row['name']);
        $template->setCreatedFor($row['created_for']??'any');
        $template->setPreviewImage(self::TEMPLATE_DIR.$row['preview_image']);
        $this->templateRepository->save($template);

        return true;
    }
}
