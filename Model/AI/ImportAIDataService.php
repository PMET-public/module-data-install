<?php

namespace MagentoEse\DataInstall\Model\AI;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use MagentoEse\DataInstall\Model\DataTypes\ProductAttributes as ProductAttributesImport;
use MagentoEse\DataInstall\Model\DataTypes\Products as ProductImport;
use MagentoEse\DataInstall\Model\DataTypes\Reviews as ReviewImport;

class ImportAIDataService
{
    public const IMAGE_API = 'https://api.openai.com/v1/images/generations';
    public const IMAGE_SIZE = '1024x1024';
    public const CHAT_API = 'https://api.openai.com/v1/chat/completions';
    //public const CHAT_API = 'https://api.openai.com/v1/models';

    protected array $productAttributeHeader = [
        'frontend_label',
        'frontend_input',
        'is_required',
        'option',
        'default',
        'attribute_code',
        'is_global',
        'default_value_text',
        'default_value_yesno',
        'default_value_date',
        'default_value_textarea',
        'is_unique',
        'is_searchable',
        'is_visible_in_advanced_search',
        'is_comparable',
        'is_filterable',
        'is_filterable_in_search',
        'position',
        'is_used_for_promo_rules',
        'is_html_allowed_on_front',
        'is_visible_on_front',
        'used_in_product_listing',
        'used_for_sort_by',
        'attribute_set'
    ];

    protected array $simpleHeader = [
        'sku',
        'name',
        'price',
        'product_type',
        'attribute_set_code',
        'product_websites',
        'qty',
        'product_online',
        'visibility',
        'is_in_stock',
        'categories',
        'short_description',
        'weight',
        'base_image',
        'small_image',
        'thumbnail_image',
        'additional_attributes'
    ];

    protected array $configHeader = [
        'sku',
        'name',
        'price',
        'product_type',
        'attribute_set_code',
        'product_websites',
        'qty',
        'product_online',
        'visibility',
        'is_in_stock',
        'categories',
        'short_description',
        'weight',
        'base_image',
        'small_image',
        'thumbnail_image',
        'configurable_variation_labels',
        'configurable_variations'
    ];

    protected $ratingHeader = ['sku','rating_code','rating_value','summary','review','reviewer'];

    private ImportImageService $importImageService;
    private Curl $curl;
    private ProductImport $productImport;
    private ScopeConfigInterface $scopeConfig;
    private ProductAttributesImport $productAttributesImport;
    private ReviewImport $reviewsImport;

    public function __construct(
        ReviewImport $reviewsImport,
        ProductAttributesImport $productAttributesImport,
        Curl                 $curl,
        ImportImageService  $importImageService,
        ProductImport        $productImport,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->curl = $curl;
        $this->importImageService = $importImageService;
        $this->productImport = $productImport;
        $this->scopeConfig = $scopeConfig;
        $this->productAttributesImport = $productAttributesImport;
        $this->reviewsImport = $reviewsImport;
    }

    /**
     * @throws LocalizedException
     */
    protected function importAttributes($rowsAttributes): void
    {
        foreach ($rowsAttributes as $attributeCode => $rowsAttribute) {
            $row = array_combine($this->productAttributeHeader, [
                ucwords(str_replace("_", " ", $attributeCode)),
                'select',
                '0',
                implode("\n", $rowsAttribute),
                '',
                $attributeCode,
                '1',
                '',
                '',
                '',
                '',
                '0',
                '0',
                '0',
                '0',
                '0',
                '0',
                '',
                '0',
                '0',
                '1',
                '1',
                '0',
                'Default'
            ]);
            $this->productAttributesImport->install($row);
        }
    }

    /**
     * @throws LocalizedException
     */
    protected function importReviews($rowsReviews, $settings): void
    {
        foreach ($rowsReviews as $rowReview) {
            $rowReview['store_view_code'] = 'default';
            $this->reviewsImport->install($rowReview, $settings);
        }
    }

    /**
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function execute($params)
    {
        $additionalAttributes = ($params['configurable_type_attributes']) ?: '';
        $promptMessage = $this->getPromptMessage(
            //$params['is_fictional'] . ' ' . $params['prompt'],
            $params['prompt'],
            $params['numberOfProducts'],
            $params['product_type'],
            $additionalAttributes
        );

        $content = $this->getProductApi(self::CHAT_API, $promptMessage);
        $rows = $rowsConfig = $configVariations = $rowsAttributes = $rowsReviews = [];

        $settings = [
            'site_code' =>'base',
            'store_code'=>'main_website-store',
            'store_view_code'=>'default',
            'root_category'=>'Default Category',
            'root_category_id'=>'2',
            'product_image_import_directory'=>$this->importImageService->getMediaDirTmpDir(),
            'product_validation_strategy'=>'validation-stop-on-errors'
        ];

        if (!empty($content)) {
            foreach ($content as $product) {
                try {
                    $image = $this->getImageApi(self::IMAGE_API, $product->name . ' ' . $product->category . ' ' . $product->description);
                    $localImage = $this->importImageService->execute($product->sku, $image, true, ['image', 'small_image', 'thumbnail']);
                } catch (\Exception $e) {
                    $localImage = '';
                }

                if ($product->product_type == "configurable") {
                    $rowConfig = [
                        $product->sku,
                        $product->name,
                        $product->price,
                        $product->product_type,
                        'Default',
                        'base',
                        $product->qty,
                        "1",
                        "Catalog, Search",
                        "1",
                        'Default Category/' . $product->category, $product->description,
                        "5",
                        $localImage,
                        $localImage,
                        $localImage
                    ];

                    $rowsConfig[$product->sku] = $rowConfig;
                } else {
                    $addAttrLabels = [];
                    $addAttrRow = '';
                    if (!empty($additionalAttributes)) {
                        $additionalAttributesArray = explode(',', $additionalAttributes);
                        foreach ($additionalAttributesArray as $att) {
                            $addAttrLabels[] = $att . "=" . ucwords(str_replace("_", " ", $att));

                            $addAttrRow .= ((!empty($addAttrRow)) ? ',' : '') . $att . "=" . $product->$att;
                            $rowsAttributes[$att][] = $product->$att;
                        }
                    }

                    $row = [
                        $product->sku,
                        $product->name,
                        $product->price,
                        'simple',
                        'Default',
                        'base',
                        $product->qty,
                        "1",
                        "Catalog, Search",
                        "1",
                        'Default Category/' . $product->category, $product->description,
                        "5",
                        $localImage,
                        $localImage,
                        $localImage,
                        $addAttrRow
                    ];

                    $rows[] = $row;

                    if (!empty($product->reviews)) {
                        foreach ($product->reviews as $review) {
                            $rowsReviews[] = array_combine($this->ratingHeader, [
                                $review->sku,
                                'Value',
                                $review->rating_value,
                                $review->summary,
                                $review->review,
                                $review->reviewer,
                            ]);
                        }
                    }

                    if (!empty($product->parent_sku)) {
                        //$configVariations[$product->parent_sku] = ((!empty($configVariations[$product->parent_sku])) ? $configVariations[$product->parent_sku] . "|" : "") . "sku=" . $product->sku . "," . $addAttrRow;
                        $configVariations[$product->parent_sku]['variations'][] = "sku=" . $product->sku . "," . $addAttrRow;
                        $configVariations[$product->parent_sku]['label'] = implode(",", $addAttrLabels);
                    }
                }
            }
        }
        if (!empty($rowsAttributes)) {
            $this->importAttributes($rowsAttributes);
        }

        if (!empty($configVariations)) {
            foreach ($configVariations as $sku => $configVariation) {
                if (!empty($rowsConfig[$sku])) {
                    $rowsConfig[$sku][] = $configVariation['label'];
                    $rowsConfig[$sku][] = implode('|', $configVariation['variations']);
                }
            }
        }

        //import config after simple
        $this->productImport->install($rows, $this->simpleHeader, '', $settings);

        if (!empty($rowsConfig)) {
            $this->productImport->install($rowsConfig, $this->configHeader, '', $settings);
        }

        $this->importReviews($rowsReviews, $settings);
        exit;
    }

    private function getPromptMessage($prompt, $size, $productType = "simple", $additionalAttributes = '')
    {
        if ($productType == "configurable") {
            $sampleAns = '[{\"product_type\": \"configurable\",\"sku\": \"string\",\"name\": \"string\",\"category\": \"string\",\"price\": numeric,\"qty\": numeric,\"description\": \"string\",\"parent_sku\": \"string\",\"any_other_attribute\": \"value\"},{\"product_type\": \"simple\",\"sku\": \"string\",\"name\": \"string\",\"category\": \"string\",\"price\": numeric,\"qty\": numeric,\"description\": \"string\",\"parent_sku\": \"string\",\"any_other_attribute\": \"value\"}]';
            return '{
            "model": "gpt-3.5-turbo",
            "messages": [{"role": "user", "content": "Generate a set of configurable products list of size ' . $size . ' for ' . $prompt . ' in a .json format enclosed by quotes that includes these indexes only: product_type,sku,name,category,price,qty,description,parent_sku,' . $additionalAttributes . ',reviews. Where "reviews" is an array of at most two reviews in the format "sku,store_view_code,rating_code,rating_value,summary,review,reviewer". "rating_value" is numeric value 1 to 5. Keep the child and parent mapping. For example - Product: Shoes \n Answer: ' . $sampleAns . '\n Product: ' . $prompt . '"}],
            "max_tokens": 1000
          }';
        }

        return '{
            "model": "gpt-3.5-turbo",
            "messages": [{"role": "user", "content": "Generate sample list of ' . $size . ' ' . $prompt . ' in .json format enclosed by quotes that includes these values only: \"product_type,sku,name,category,price,qty,description,reviews\". Where \"reviews\" is array of max five reviews in format \"sku,store_view_code,rating_code,rating_value,summary,review,reviewer\". \"rating_value\" is numeric from 1-5"}],
            "max_tokens": 4000
          }';
    }

    /**
     *
     * @param mixed $url
     * @param mixed $prompt
     * @param mixed $size
     * @return mixed
     * @throws FileSystemException
     * @throws Exception
     */
    protected function getProductApi(mixed $url, $message)
    {
        $this->curl->setOption(CURLOPT_URL, $url);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->curl->setOption(CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $this->getAuthentication(),"Content-Type: application/json"]);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->post($url, $message);
        $result1=$this->curl->getBody();
        $result=json_decode($result1);
        $beforeTrim = $result->choices[0]->message->content;
        $beforeTrim = strstr($beforeTrim, "[");
        $beforeTrim = substr($beforeTrim, 0, strrpos($beforeTrim, "]")+1);
        $result = json_decode($beforeTrim);
        return $result;
    }

    /**
     *
     * @param mixed $url
     * @param mixed $prompt
     * @return mixed
     * @throws FileSystemException
     * @throws Exception
     * @throws LocalizedException
     */
    protected function getImageApi(mixed $url, $prompt)
    {
        $message =  '{
            "prompt": "' . $prompt . '",
            "n": 1,
            "size": "' . SELF::IMAGE_SIZE . '"
          }';
        $this->curl->setOption(CURLOPT_URL, $url);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->curl->setOption(CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $this->getAuthentication(),"Content-Type: application/json"]);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);

        $this->curl->post($url, $message);
        $result1=$this->curl->getBody();
        $result=json_decode($result1);

        if ($result=='Not Found' || empty($result)) {
            throw new
            LocalizedException(__('Data could not be retrieved.'));
        }
        $imageUrl = $result->data[0]->url;
        return $imageUrl;
    }

    /**
     * Return authentication token. Defaults to github token for now, but can be expanded to support additional methods
     *
     * @param string $token
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getAuthentication(): mixed
    {
        return $this->scopeConfig->getValue(
            'magentoese/datainstall/openai_api_key',
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }
}
