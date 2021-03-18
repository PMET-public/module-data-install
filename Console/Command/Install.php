<?php
namespace MagentoEse\DataInstall\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MagentoEse\DataInstall\Model\Process;

class Install extends Command
{
    const MODULE = 'module';

    /** @var Process  */
    protected $process;
    
    /** @var ModuleStatus */
    protected $moduleStatus;

    public function __construct(Process $process)
    {
        $this->process = $process;
        parent::__construct();
    }
    //bin/magento gxd:datainstall MagentoEse_Base
    //bin/magento gxd:datainstall vendor/story-store/grocery
    //bin/magento gxd:datainstall does/it/matter/what/directory

    //dir=fixtures - can be any other sub directory
    //-f force reload if already loaded

	protected function configure()
	{

		$options = [
            new InputArgument(
                self::MODULE,
                InputArgument::REQUIRED,
                'Module'
            )
		];

		$this->setName('gxd:datainstall')
			->setDescription('GXD Data Install')
			->setDefinition($options);

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$module = $input->getArgument(self::MODULE);
        $output->writeln("Installing data from " . $module);
        //exit;
        // $this->process->loadFiles('MagentoEse_Base','data',['stores.csv','config_vertical.json','config_secret.json','config.csv',
        // 'admin_roles.csv','admin_users.csv','customer_groups.csv','customer_attributes.csv','customers.csv','product_attributes.csv',
        // 'blocks.csv','categories.csv','products.csv','products2.csv','msi_inventory.csv','upsells.csv','blocks.csv','dynamic_blocks.csv',
        // 'pages.csv','templates.csv','reviews.csv','b2b_companies.csv','b2b_shared_catalogs.csv',
        // 'b2b_shared_catalog_categories.csv','b2b_requisition_lists.csv','advanced_pricing.csv','orders.csv']);
		// $this->process->loadFiles('MagentoEse_Custom','data',['stores.csv','config_vertical.json','config_secret.json','config.csv',
        // 'admin_roles.csv','admin_users.csv','customer_groups.csv','customer_attributes.csv','customers.csv','product_attributes.csv',
        // 'blocks.csv','categories.csv','products.csv','products2.csv','msi_inventory.csv','upsells.csv','blocks.csv','dynamic_blocks.csv',
        // 'pages.csv','templates.csv','reviews.csv','b2b_companies.csv','b2b_shared_catalogs.csv',
        // 'b2b_shared_catalog_categories.csv','b2b_requisition_lists.csv','advanced_pricing.csv','orders.csv']);
		$this->process->loadFiles($module,'data',['stores.csv','config_vertical.json','config_secret.json','config.csv',
        'admin_roles.csv','admin_users.csv','customer_groups.csv','customer_attributes.csv','customers.csv','product_attributes.csv',
        'blocks.csv','categories.csv','products.csv','products2.csv','msi_inventory.csv','upsells.csv','blocks.csv','dynamic_blocks.csv',
        'pages.csv','templates.csv','reviews.csv','b2b_companies.csv','b2b_shared_catalogs.csv',
        'b2b_shared_catalog_categories.csv','b2b_requisition_lists.csv','advanced_pricing.csv','orders.csv']);
        

		return $this;

	}
}