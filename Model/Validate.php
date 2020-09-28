<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

class Validate
{

    /** @var Stores */
    protected $stores;

    public function __construct(Stores $stores){
        $this->stores = $stores;
    }

    public function validateCsvFile($header,$rows){
        //size of header array vs. size of each row $rows[0]
        foreach($rows as $row){
            if(count($row)!=count($header)){
                return false;
            }
        }
        return true;
    }

    public function validateWebsiteCode($websiteCode){
        if($this->stores->getWebsiteId($websiteCode)){
            return true;
        } else {
            return false;
        }
    }

    public function validateStoreCode($storeCode){
        if($this->stores->getStoreId($storeCode)){
            return true;
        } else {
            return false;
        }
    }

    public function validateStoreViewCode($storeViewCode){
        if($this->stores->getViewId($storeViewCode)){
            return true;
        } else {
            return false;
        }
    }
}
