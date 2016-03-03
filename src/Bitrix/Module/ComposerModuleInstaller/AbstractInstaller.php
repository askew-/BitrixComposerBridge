<?php

namespace MediaSoft\Bitrix\Module\ComposerModuleInstaller;

use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class AbstractInstaller
 * @package MediaSoft\Bitrix\Module\ComposerModuleInstaller
 */
abstract class AbstractInstaller implements InstallerInterface
{
    /**
     * @param PackageInterface $package
     * @return bool
     */
    protected function isPackageWithBitrixModule(PackageInterface $package)
    {
        $extra = $package->getExtra();

        return isset($extra)
        && is_array($extra)
        && isset($extra['bitrix-module'])
        && is_array($extra['bitrix-module'])
        && isset($extra['bitrix-module']['name'])
        && isset($extra['bitrix-module']['path']);
    }

    /**
     * @param PackageInterface $package
     * @return string|null
     */
    protected function getModuleNameFromPackage(PackageInterface $package)
    {
        if ($this->isPackageWithBitrixModule($package)) {
            $extra = $package->getExtra();
            return $extra['bitrix-module']['name'];
        }

        return null;
    }

    /**
     * @param PackageInterface $package
     * @return string|null
     */
    protected function getModulePathFromPackage(PackageInterface $package)
    {
        if ($this->isPackageWithBitrixModule($package)) {
            $extra = $package->getExtra();
            return $extra['bitrix-module']['path'];
        }

        return null;
    }

    /**
     * @param InstallationManager $installationManager
     * @param PackageInterface $package
     * @param $bitrixRoot
     * @return bool
     */
    protected function installModuleAsSymlink(InstallationManager $installationManager, PackageInterface $package, $bitrixRoot)
    {
        $bitrixModulesDir = $bitrixRoot.'/modules';

        $fs = new Filesystem();
        $packageDir = $installationManager->getInstallPath($package);
        $packageModuleDir = $packageDir.'/'.$this->getModulePathFromPackage($package);

        $targetDir = $bitrixModulesDir.'/'.$this->getModuleNameFromPackage($package);
        $originDir = $fs->makePathRelative($packageModuleDir, realpath($bitrixModulesDir));

        try {
            if ($fs->exists($targetDir)) {
                $fs->remove($targetDir);
            }

            $fs->symlink(
                $originDir,
                $targetDir
            );
        } catch (IOException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param InstallationManager $installationManager
     * @param PackageInterface $package
     * @param $bitrixRoot
     * @return bool
     */
    protected function installModuleAsCopy(InstallationManager $installationManager, PackageInterface $package, $bitrixRoot)
    {
        $bitrixModulesDir = $bitrixRoot.'/modules';

        $fs = new Filesystem();
        $packageDir = $installationManager->getInstallPath($package);

        $packageModuleDir = $packageDir.'/'.$this->getModulePathFromPackage($package);
        $targetDir = $bitrixModulesDir.'/'.$this->getModuleNameFromPackage($package);

        try {
            if ($fs->exists($targetDir)) {
                $fs->remove($targetDir);
            }

            $fs->mirror(
                $packageModuleDir,
                $targetDir
            );
        } catch (IOException $e) {
            return false;
        }

        return true;
    }
}