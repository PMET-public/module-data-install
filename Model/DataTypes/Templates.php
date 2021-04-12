<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\PageBuilder\Model\Template;
use Magento\PageBuilder\Model\TemplateFactory;
use Magento\PageBuilder\Model\TemplateRepository;
use MagentoEse\DataInstall\Model\Converter;

class Templates
{

    const TEMPLATE_DIR = ".template-manager/";

    /** @var TemplateFactory */
    protected $templateFactory;

    /** @var TemplateRepository */
    protected $templateRepository;

    /** @var Converter */
    protected $converter;

    /**
     * Templates constructor.
     * @param TemplateFactory $templateFactory
     * @param TemplateRepository $templateRepository
     * @param Converter $converter
     */
    public function __construct(
        TemplateFactory $templateFactory,
        TemplateRepository $templateRepository,
        Converter $converter
    ) {

        $this->templateFactory = $templateFactory;
        $this->templateRepository = $templateRepository;
        $this->converter = $converter;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function install(array $row, array $settings)
    {
        $template = $this->templateFactory->create();
        $template->setTemplate($this->converter->convertContent($row['content']));
        $template->setName($row['name']);
        $template->setCreatedFor($row['created_for']??'any');
        $template->setPreviewImage(self::TEMPLATE_DIR.$row['preview_image']);
        $this->templateRepository->save($template);

        return true;
    }
}
