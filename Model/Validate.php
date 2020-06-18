<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

class Validate
{
    public function validateStores($data)
    {
        //check the header for required columns
        //site_code,site_name,store_code,store_name,store_root_category,view_code,view_name,is_default_view
        //site_code needs to be in the file and populated
        //site requires name and code
        //store requires site, name, code, and root category
        //view requires store, name, code
        //update site name
        //create site
        //update store name
        //create store
        //update view name
        //create view
    }
}
