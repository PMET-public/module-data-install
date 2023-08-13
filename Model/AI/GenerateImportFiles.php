<?php

namespace MagentoEse\DataInstall\Model\AI;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

class GenerateImportFiles
{
    /**
     * @var DirectoryList
     */
    protected DirectoryList $directoryList;
    protected Filesystem $filesystem;
    private LoggerInterface $logger;

    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    public function execute(string $fileName, $generatedContent = ''): void
    {
        try {
            $media = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
            $media->writeFile($fileName, $generatedContent, 'a+');
        } catch (FileSystemException $e) {
            $this->logger->error($e->getMessage(), ['filename' => $fileName]);
        }
    }
}
