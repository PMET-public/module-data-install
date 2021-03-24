<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

class Validate
{

    /** @var Stores */
    protected $stores;

    /**
     * __construct
     *
     * @param  Stores $stores
     * @return void
     */
    public function __construct(Datatypes\Stores $stores)
    {
        $this->stores = $stores;
    }

    /**
     * validateB2bData
     *
     * @param  array $data
     * @return void
     */
    public function validateB2bData($data)
    {
        //Company
            //are required fields included
            //is there a company admin flagged in the customer data
            //is there only one company admin per company
        //Customers
            //are the requried fields included
            //is the company defined included in the company data
            //does the role match for the correct company
        //Sales Reps
            //is the company defined included in the company data
        //Roles
            //is the company defined included in the company data
        //Company Structure
            //is the company defined included in the company data
            //do the customers exist and are the with the correct company
        return true;
    }

    public function validateCsvFile($header, $rows)
    {
        //size of header array vs. size of each row $rows[0]
        foreach ($rows as $row) {
            if (count($row)!=count($header)) {
                return false;
            }
        }
        return true;
    }

    public function validateWebsiteCode($websiteCode)
    {
        if ($this->stores->getWebsiteId($websiteCode)) {
            return true;
        } else {
            return false;
        }
    }

    public function validateStoreCode($storeCode)
    {
        if ($this->stores->getStoreId($storeCode)) {
            return true;
        } else {
            return false;
        }
    }

    public function validateStoreViewCode($storeViewCode)
    {
        if ($this->stores->getViewId($storeViewCode)) {
            return true;
        } else {
            return false;
        }
    }
}
