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
    const FIXTURES = 'fixtures';
    const FILES = 'files';
    const RELOAD_FLAG = 'reload';
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
    //bin/magento gxd:datainstall relative/to/magento
    //bin/magento gxd:datainstall /Absolute/Path

    //dir=fixtures - can be any other sub directory
    //-f force reload if already loaded

    ///test if module loaded by composer will still work without setup:upgrade
    //https://magento.stackexchange.com/questions/155654/console-command-waiting-for-input-from-user

	protected function configure()
	{

		$options = [
            new InputArgument(
                self::MODULE,
                InputArgument::REQUIRED,
                'Module'
            ),
            new InputOption(self::FIXTURES,null,InputOption::VALUE_OPTIONAL,'Fixtures Directory','fixtures'),
            new InputOption(self::FILES,null,InputOption::VALUE_OPTIONAL,'Comma delimited list of individual files to load'),
            new InputOption(self::RELOAD_FLAG,'-r',InputOption::VALUE_OPTIONAL,'Force Reload',0)
		];

		$this->setName('gxd:datainstall')
			->setDescription('GXD Data Install')
			->setDefinition($options);

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$module = $input->getArgument(self::MODULE);
        $fixtures = $input->getOption(self::FIXTURES);
        $reload = $input->getOption(self::RELOAD_FLAG);
        $files = $input->getOption(self::FILES);
        if($files==''){
            $fileArray=[];
        }else{
            $fileArray = explode(",",$files);
        }
        //$files = explode(",",$input->getOption(self::FILES));
        // $output->writeln("Installing data from " . $module);
        // $output->writeln("fixtures " . $fixtures);
        // $output->writeln("reload " . $reload);
        // $output->writeln("files " . $files);
        ///convert files to array
        //exit;
		if($this->process->loadFiles($module,$fixtures,$fileArray,$reload)==0){
            $output->writeln("No files found to load in " . $module);
        }
        

		return $this;

	}
}