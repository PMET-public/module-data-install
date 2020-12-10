<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MagentoEse\DataInstall\Rewrite\Magento\Company\Model\Company;

use Magento\Company\Model\ResourceModel\Structure\Tree;
use Magento\Company\Model\TeamFactory;
use phpDocumentor\Reflection\Types\Parent_;

class Structure extends \Magento\Company\Model\Company\Structure
{

private $tree;

public function __construct(
    \Magento\Company\Model\ResourceModel\Structure\Tree $tree,
    \Magento\Company\Api\Data\StructureInterfaceFactory $structureFactory,
    \Magento\Company\Model\StructureRepository $structureRepository,
    \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
    \Magento\Company\Api\TeamRepositoryInterface $teamRepository,
    \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface)
{
    $this->tree = $tree;
    parent::__construct($tree,$structureFactory,$structureRepository,$searchCriteriaBuilder,$teamRepository,$customerRepositoryInterface);
}
 /**
     * Retrieves tree by id.
     *
     * @param int $id
     * @return \Magento\Framework\Data\Tree\Node
     */
    public function getTreeById($id)
    {
        //if (!isset($this->treesByIds[$id])) {
            $node = $this->tree->getNodeById($id);
            if (!$node) {
                $this->tree->setLoaded(false);
                $structure = $this->structureRepository->get($id);
                $rootId = $this->getFirstItemFromPath($structure->getPath());
                $tree = $this->tree->loadNode($rootId);
                $tree->loadChildren();
                $node = $this->tree->getNodeById($id);
            }
            $this->treesByIds[$id] = $node;
       // }
        return $this->treesByIds[$id];
    }
}
