<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Controller\Adminhtml\Aiimport;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use MagentoEse\DataInstall\Model\AI\ImportImageService;
use MagentoEse\DataInstall\Model\DataTypes\Products as ProductImport;
use MagentoEse\DataInstall\Model\DataTypes\Reviews as ReviewImport;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Generate extends \Magento\Backend\App\Action
{

    public const IMAGE_API = 'https://api.openai.com/v1/images/generations';
    public const IMAGE_SIZE = '1024x1024';
    public const CHAT_API = 'https://api.openai.com/v1/chat/completions';
    //public const CHAT_API = 'https://api.openai.com/v1/models';

    protected $productHeader = ['sku','name','price','product_type','attribute_set_code','product_websites','qty',
    'product_online','visibility','is_in_stock','categories','short_description','weight','base_image','small_image','thumbnail_image'];

    protected $ratingHeader = ['sku','rating_code','rating_value','summary','review','reviewer'];

    /** @var DataPackInterfaceFactory */
    protected $dataPack;
    
    /** @var UploaderFactory */
    protected $uploaderFactory;

    /** @var Filesystem\Directory\WriteInterface */
    protected $verticalDirectory;

    /** @var File */
    protected $file;

    /** @var ScheduleBulk */
    protected $scheduleBulk;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var Curl */
    protected $curl;

    /** @var InstallerJobInterfaceFactory */
    protected $installerJobInterface;

    /** @var ProductInterfaceFactory */
    protected $productInterface;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var ImportImageService */
    protected $importImageService;

     /** @var ReviewImport */
     protected $reviewImport;

     /** @var ProductImport */
     protected $productImport;

  /**
   * 
   * @param Context $context 
   * @param Curl $curl 
   * @param ImportImageService $importImageService 
   * @param ProductImport $productImport 
   * @param ReviewImport $reviewImport 
   * @param ScopeConfigInterface $scopeConfig 
   * @return void 
   */
    public function __construct(
        Context $context,
        Curl $curl,
        ImportImageService $importImageService,
        ProductImport $productImport,
        ReviewImport $reviewImport,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->importImageService = $importImageService;
        $this->productImport = $productImport;
        $this->reviewImport = $reviewImport;
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getMethod() !== 'POST' ||
        !$this->_formKeyValidator->validate($this->getRequest())) {
            throw new LocalizedException(__('Invalid Request'));
        }
        $params = $this->getRequest()->getParams();
        $content = $this->getProductApi(self::CHAT_API,$params['is_fictional'].' '. $params['prompt'],$params['numberOfProducts']);
        $productRows = [];
        $reviewsRows = [];
        foreach($content as $product){
                $image = $this->getImageApi(self::IMAGE_API,$product->name.' '.$product->category.' '.$product->description);
                $localImage = $this->importImageService->execute($product->sku, $image, true, ['image', 'small_image', 'thumbnail']);
                $productRow=[$product->sku,$product->name,$product->price,'simple','Default','base',$product->qty,
                "1","Catalog, Search","1",'Default Category/'.$product->category,$product->description,"5",$localImage,$localImage,$localImage];
                $productRows[] = $productRow;
                $reviewsRow = ['sku'=>$product->sku,'rating_code'=>'Value','rating_value'=>$product->rating_value,'summary'=>$product->summary,'review'=>$product->review,'reviewer'=>$product->reviewer];
                //$reviewsRow = [$product->sku,'Value',$product->rating_value,$product->summary,$product->review,$product->reviewer];
                $reviewsRows[] = $reviewsRow;
        }
        $settings = ['site_code'=>'base','store_code'=>'main_website-store','store_view_code'=>'default',
        'root_category'=>'Default Category','root_category_id'=>'2','product_image_import_directory'=>'/var/www/html/var/import/images',
        'product_validation_strategy'=>'validation-stop-on-errors'];
        $this->productImport->install($productRows,$this->productHeader,'',$settings);
        foreach($reviewsRows as $review){
            $this->reviewImport->install($review,$settings);
        }
        
        $this->messageManager->addSuccessMessage(__('Products generated'));
        return $this->_redirect('index');
        // $resultRedirect = $this->resultRedirectFactory->create();
        // $resultRedirect->setPath('*/*/index');
        // return $resultRedirect;
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
    protected function getProductApi($url,$prompt,$size)
    {
        $message = '{
            "model": "gpt-3.5-turbo",
            "messages": [{"role": "user", "content": "Generate a sample list of '.$size.' '.$prompt.' in a .json format enclosed by quotes that includes a node for each product with these values: sku,name,category,price,qty,description,rating_value,review,summary,reviewer. rating_value is a number between 1 and 5. summary is a single sentence explanation of a customers satisfaction based on the rating_value given. review is two additional sentences about the customers satisfaction. reviewer is a first and last name"}]
          }';
        $this->curl->setOption(CURLOPT_URL, $url);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->curl->setOption(CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$this->getAuthentication(),"Content-Type: application/json"]);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        //$this->curl->get($url);
        $result=$this->curl->getBody();
        $result=$this->curl->post($url,$message);
        $result=json_decode($this->curl->getBody());
        try{
            $result = json_decode($result->choices[0]->message->content);
        }  catch(Exception $e){
            print_r($e);
            print_r($result);
        }
        
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
    protected function getImageApi($url,$prompt)
    {
        $message =  '{
            "prompt": "'.$prompt.'",
            "n": 1,
            "size": "'.SELF::IMAGE_SIZE.'"
          }';
        $this->curl->setOption(CURLOPT_URL, $url);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->curl->setOption(CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$this->getAuthentication(),"Content-Type: application/json"]);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        //$this->curl->get($url);
        $result=$this->curl->getBody();
        $result=$this->curl->post($url,$message);
        $result=json_decode($this->curl->getBody());

        if ($result=='Not Found') {
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
    protected function getAuthentication()
    {
       return $this->scopeConfig->getValue(
                'magentoese/datainstall/openai_api_key',
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
    }
    
}
