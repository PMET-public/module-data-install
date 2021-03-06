<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\PageBuilder\Model\Template;
use Magento\PageBuilder\Model\TemplateFactory;
use Magento\PageBuilder\Model\TemplateRepository;

class Templates
{

    const TEMPLATE_DIR = ".template-manager/";

    /** @var TemplateFactory */
    protected $templateFactory;

    /** @var TemplateRepository */
    protected $templateRepository;

    /** @var Converter */
    protected $converter;

    public function __construct(
        TemplateFactory $templateFactory,
        TemplateRepository $templateRepository,
        Converter $converter
    ) {

        $this->templateFactory = $templateFactory;
        $this->templateRepository = $templateRepository;
        $this->converter = $converter;
    }

    public function install(array $row, array $settings)
    {
        /** @var Template $template */
        $template = $this->templateFactory->create();
        $template->setTemplate($this->converter->convertContent($row['content']));
        $template->setName($row['name']);
        $template->setCreatedFor($row['created_for']??'any');
        $template->setPreviewImage(self::TEMPLATE_DIR.$row['preview_image']);
        $this->templateRepository->save($template);

        return true;
    }
}
