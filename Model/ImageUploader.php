<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;

/**
* Product Label image uploader
*/
class ImageUploader extends \Magento\Catalog\Model\ImageUploader
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var FileDriver
     */
    protected $fileDriver;

    /**
     * ImageUploader constructor
     *
     * @param Database $coreFileStorageDatabase
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param string $baseTmpPath
     * @param string $basePath
     * @param string[] $allowedExtensions
     * @param FileDriver $fileDriver
     */
    public function __construct(
        Database $coreFileStorageDatabase,
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        $baseTmpPath,
        $basePath,
        $allowedExtensions,
        FileDriver $fileDriver
    ) {
        parent::__construct(
            $coreFileStorageDatabase,
            $filesystem,
            $uploaderFactory,
            $storeManager,
            $logger,
            $baseTmpPath,
            $basePath,
            $allowedExtensions
        );
        $this->filesystem = $filesystem;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Checking file for moving and move it
     *
     * @param string $imageName
     * @param bool $returnRelativePath
     * @return string
     *
     * @throws LocalizedException
     */
    public function moveFileFromTmp($imageName, $returnRelativePath = false)
    {
        $originalImageName = $imageName;
        $baseTmpPath = $this->getBaseTmpPath();
        $basePath = $this->getBasePath();
        $baseImagePath = $this->getFilePath($basePath, $imageName);
        $baseTmpImagePath = $this->getFilePath($baseTmpPath, $imageName);

        $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $baseImageAbsolutePath = $mediaPath . $baseImagePath;
        $i = 1;

        while ($this->fileDriver->isExists($baseImageAbsolutePath)) {
            $i++;
            $p = mb_strrpos($originalImageName, '.');
            if (false !== $p) {
                $imageName = mb_substr($originalImageName, 0, $p) . $i .  mb_substr($originalImageName, $p);
            } else {
                $imageName = $originalImageName . $i;
            }
            $baseImagePath = $this->getFilePath($basePath, $imageName);
            $baseImageAbsolutePath = $mediaPath . $baseImagePath;
        }

        try {
            $this->coreFileStorageDatabase->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );
            $this->mediaDirectory->renameFile(
                $baseTmpImagePath,
                $baseImagePath
            );
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Something went wrong while saving the file(s).')
            );
        }

        return $returnRelativePath ? $baseImagePath : $imageName;
    }
}
