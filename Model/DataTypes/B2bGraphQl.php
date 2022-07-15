<?php
namespace MagentoEse\DataInstall\Model\DataTypes;

use MagentoEse\DataInstall\Helper\Helper;
use MagentoEse\DataInstall\Model\Conf;

class B2bGraphQl
{
    /** @var Helper */
    protected $helper;

    /**
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Thoughts on processing b2b
     * have pre-processing that converts to arrays, whether csv or json
     * $b2bData array contains key of filename with rows/header array
     */

    /**
     * @param string $json
     * @return array
     */
    public function processB2BGraphql($json)
    {
        try {
            //convert to array of objects. Remove the parent query name node
            $fileData = json_decode($json, true);
        } catch (\Exception $e) {
            $this->helper->logMessage("The JSON in your b2b file is invalid", "error");
            return true;
        }
        $b2bData=[];
        $b2bData['b2b_companies.csv'] = $this->parseB2BCompanyGraphql($fileData);
        $b2bData['b2b_sales_reps.csv'] = $this->parseB2BSalesRepGraphql($fileData);
        $b2bData['b2b_customers.csv'] = $this->parseB2BCompanyCustomers($fileData);
        $b2bData['b2b_company_roles.csv'] = $this->parseB2BCompanyRoles($fileData);
        $b2bData['b2b_teams.csv'] = $this->parseB2BTeams($fileData);
        $b2bData['b2b_requisition_lists.csv'] = $this->parseB2BRequisitionLists($fileData);
        $b2bData['b2b_shared_catalogs.csv'] = $this->parseB2BSharedCatalogs($fileData);
        $b2bData['b2b_shared_catalog_categories.csv'] = $this->parseB2BSharedCatalogCategories($fileData);
        //$b2bData['b2b_approval_rules.csv'] = $this->parseB2BApprovalRules($fileData);
        
        return $b2bData;
    }

    /**
     * @param array $fileData
     * @return array
     */
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh
    private function parseB2BCompanyGraphql($fileData)
    {
        $rows = [];
        $header = [];
        $setHeader = true;
        $rowCount = 0;
        $inputData = $fileData['data']['companies']['items'];
        foreach ($inputData as $company) {
            if (!empty($header)) {
                $setHeader = false;
            }
            foreach ($company as $key => $value) {
                if (in_array($key, Conf::B2B_COMPANY_COLUMNS)) {
                    switch ($key) {
                        case 'address':
                            $address = $this->parseGraphqlAddress($value);
                            if ($setHeader) {
                                // phpcs:ignore Magento2.Performance.ForeachArrayMerge.ForeachArrayMerge
                                $header = array_merge($header, $address['header']);
                            }
                            // phpcs:ignore Magento2.Performance.ForeachArrayMerge.ForeachArrayMerge
                            $rows[$rowCount] = array_merge($rows[$rowCount], $address['rows']);
                            break;

                        case 'credit_limit':
                            if ($setHeader) {
                                $header[] = 'credit_limit';
                            }
                            $rows[$rowCount][] = $value['credit_limit']['value'];
                            break;

                        case 'company_admin':
                            if ($setHeader) {
                                $header[] = 'company_admin';
                            }
                            $rows[$rowCount][] = $value['email'];
                            break;

                        default:
                            if ($setHeader) {
                                $header[] = $key;
                            }
                            $rows[$rowCount][] = $value;
                    }
                }
            }
            $rowCount++;
        }
        $val['header'] = $header;
        $val['rows'] = $rows;
        return $val;
    }

    /**
     * @param array $fileData
     * @return array
     */
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh
    private function parseB2BCompanyCustomers($fileData)
    {
        $rows = [];
        $header = [];
        $setHeader = true;
        $rowCount = 0;
        $inputData = $fileData['data']['companies']['items'];
        foreach ($inputData as $company) {
            foreach ($company['users_export']['items'] as $user) {
                if (!empty($header)) {
                    $setHeader = false;
                }
                foreach ($user as $key => $value) {
                    switch ($key) {
                        case 'addresses':
                            $address = $this->parseGraphqlAddress($value[0]);
                            if ($setHeader) {
                                // phpcs:ignore Magento2.Performance.ForeachArrayMerge.ForeachArrayMerge
                                $header = array_merge($header, $address['header']);
                            }
                            // phpcs:ignore Magento2.Performance.ForeachArrayMerge.ForeachArrayMerge
                            $rows[$rowCount] = array_merge($rows[$rowCount], $address['rows']);
                            break;

                        case 'role':
                            if ($setHeader) {
                                $header[] = 'role';
                            }
                            if (!empty($value['name'])) {
                                $rows[$rowCount][]=$value['name'];
                            } else {
                                $rows[$rowCount][]= '';
                            }
                            break;
                            
                        case 'team':
                            //skip
                            break;
                        
                        case 'requisition_lists_export':
                            //skip
                            break;

                        default:
                            if ($setHeader) {
                                $header[] = $key;
                            }
                            $rows[$rowCount][]=$value;
                    }
                }
                //add columns and data not directly tied query results
                //company,company_admin,website,add_to_autofill
                if ($setHeader) {
                    array_push($header, 'company', 'site_code', 'company_admin');
                }
                //company
                $rows[$rowCount][]=$company['company_name'];
                //website
                $rows[$rowCount][]=$company['site_code'];
                //company_admin
                if ($company['company_admin']['email']==$user['email']) {
                    $rows[$rowCount][]='Y';
                } else {
                    $rows[$rowCount][]='N';
                }
               
                $rowCount ++;
            }
        }
        $val['header'] = $header;
        $val['rows'] = $rows;
        return $val;
    }

    /**
     * @param array $fileData
     * @return array
     */
    private function parseB2BSalesRepGraphql($fileData)
    {
        $rows = [];
        $header = [];
        $setHeader = true;
        $rowCount = 0;
        $inputData = $fileData['data']['companies']['items'];
        foreach ($inputData as $company) {
            if (!empty($header)) {
                $setHeader = false;
            }
            foreach ($company['sales_representative'] as $key => $value) {
                if ($setHeader) {
                    $header[] = $key;
                }
                $rows[$rowCount][]=$value;
            }
            if ($setHeader) {
                $header[]='company';
            }
            $rows[$rowCount][]=$company['company_name'];
            $rowCount ++;
        }
        $val['header'] = $header;
        $val['rows'] = $rows;
        return $val;
    }

    /**
     * @param array $fileData
     * @return array
     */
    private function parseB2BCompanyRoles($fileData)
    {
        $this->companyRoles = [];
        $header = ['company_name','role','resource_id'];
        $inputData = $fileData['data']['companies']['items'];
        foreach ($inputData as $company) {
            foreach ($company['roles_export']['items'] as $role) {
                array_walk_recursive(
                    $role,
                    [$this,'companyRoleCallback'],
                    ['company'=>$company['company_name'],'role'=>$role['name']]
                );
            }
        }
        $val['header'] = $header;
        $val['rows'] = $this->companyRoles;
        return $val;
    }

    /**
     * @param string $item
     * @param string $key
     * @param array $args
     */
    public function companyRoleCallback($item, $key, $args)
    {
        if ($key=='id') {
            array_push($this->companyRoles, [$args['company'],$args['role'],$item]);
        }
    }

    /**
     * @param array $fileData
     * @return array
     */
    private function parseB2BTeams($fileData)
    {
        $rows = [];
        $companyArray = [];
        $header = ['site_code','company_name','name','members'];
        $rowCount = 0;
        $inputData = $fileData['data']['companies']['items'];
        foreach ($inputData as $company) {
            foreach ($company['users_export']['items'] as $user) {
                //create nexted array for company/team/users
                if (!empty($user['team'])) {
                    $companyArray[$company['company_name']][$user['team']['name']][] = $user['email'];
                }
            }
        }
        //pivot array into row
        foreach ($companyArray as $companyName => $team) {
            foreach ($team as $teamName => $members) {
                $rows[$rowCount][]=$company['site_code'];
                $rows[$rowCount][]=$companyName;
                $rows[$rowCount][]=$teamName;
                $rows[$rowCount][]=implode(',', $members);
                $rowCount ++;
            }
        }
        $val['header'] = $header;
        $val['rows'] = $rows;
        return $val;
    }

    /**
     * @param array $fileData
     * @return array
     */
    private function parseB2BRequisitionLists($fileData)
    {
        $rows = [];
        $reqLists = [];
        $header = ['name','site_code','customer_email','description','skus'];
        $rowCount = 0;
        $inputData = $fileData['data']['companies']['items'];
        foreach ($inputData as $company) {
            foreach ($company['users_export']['items'] as $user) {
                //create nexted array for company/team/users
                if (!empty($user['requisition_lists_export'])) {
                    foreach ($user['requisition_lists_export'] as $allLists) {
                        foreach ($allLists as $list) {
                            $rows[$rowCount][] = $list['name'];
                            $rows[$rowCount][]=$company['site_code'];
                            $rows[$rowCount][]=$user['email'];
                            $rows[$rowCount][] = $list['description'];
                            $rows[$rowCount][] = $this->getRequisitionListSkus($list['items']['items']);
                            $rowCount ++;
                        }
                    }
                }
            }
        }
        $val['header'] = $header;
        $val['rows'] = $rows;
        return $val;
    }

    /**
     * @param array $items
     * @return string
     */

    private function getRequisitionListSkus($items)
    {
        $products = [];
        foreach ($items as $item) {
            $products[] = $item['product']['sku'].'|'.$item['quantity'];
        }
        return implode(",", $products);
    }

    /**
     * @param array $fileData
     * @return array
     */
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh
    private function parseB2BSharedCatalogs($fileData)
    {
        $tempArray = [];
        $rows = [];
        $header = ['name','companies','type','description'];
        $inputData = $fileData['data']['companies']['items'];
        //create associative arrays of all company catalog assignments
        //public catalog
        $tempArray[] = ['name'=>$fileData['data']['publicSharedCatalog']['name'],
        'companies'=>[],'type'=>'Public','description'=>$fileData['data']['publicSharedCatalog']['description']];
        //get info from company
        foreach ($inputData as $company) {
            //if catalog is already defined, add to company array. else add element
            $foundCompany = 0;
            foreach ($tempArray as $key => $currentCatalog) {
                if ($currentCatalog['name'] == $company['shared_catalog']['name']) {
                    $tempArray[$key]['companies'][]=$company['company_name'];
                    $foundCompany = 1;
                    break;
                }
            }
            if ($foundCompany==0) {
                $tempArray[] = ['name'=>$company['shared_catalog']['name'],
                'companies'=>[$company['company_name']],'type'=>$company['shared_catalog']['type'],
                'description'=>$company['shared_catalog']['description']];
            }
        }
        //convert to rows
        foreach ($tempArray as $catalog) {
            $rows[] = [$catalog['name'],
            implode(',', $catalog['companies']),$catalog['type'],
            $catalog['description']];
        }
        $val['header'] = $header;
        $val['rows'] = $rows;
        return $val;
    }

    /**
     * @param array $fileData
     * @return array
     */
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh
    private function parseB2BSharedCatalogCategories($fileData)
    {
        //shared_catalog,category
        $rows = [];
        $header = ['shared_catalog','category'];
        $inputData = $fileData['data']['companies']['items'];
        foreach ($inputData as $company) {
            //if catalog is already defined, add to company array. else add element
            $sharedCatalogName = $company['shared_catalog']['name'];
            $catalogCategories = $company['shared_catalog']['categories'];
            foreach ($catalogCategories as $category) {
                $rows[] = [$sharedCatalogName,$category['path']];
            }
        }
        $val['header'] = $header;
        $val['rows'] = $rows;
        return $val;
    }

    /**
     * @param array $address
     * @return array
     */
    private function parseGraphqlAddress($address)
    {
        foreach ($address as $key => $value) {
            switch ($key) {
                case 'street':
                    $header[] = $key;
                    $rows[] = $address['street'][0];
                    break;
                case 'region':
                    foreach ($value as $regionKey => $regionValue) {
                        $header[ ]= $regionKey;
                        $rows[] = $regionValue;
                    }
                    break;
                default:
                    $header[]=$key;
                    $rows[] = $value;
            }
        }
        $val['header'] = $header;
        $val['rows'] = $rows;
        return $val;
    }
}
