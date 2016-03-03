<?php


namespace MediaSoft\Bitrix\Module\ComposerModuleInstaller;

use Composer\Package\PackageInterface;

interface InstallerInterface
{
    const NOT_INSTALLED = 0;
    const SYMLINK = 1;
    const COPY = 2;

    /**
     * @void
     */
    public function installModules();

    /**
     * @param PackageInterface $package
     * @return int self::NOT_INSTALLED|self::SYMLINK|self::COPY
     */
    public function installModule(PackageInterface $package);
}