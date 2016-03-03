<?php

namespace MediaSoft\Bitrix\Module\ComposerModuleInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use MediaSoft\Bitrix\Finder\BitrixFinder;

/**
 * Class InteractiveInstaller
 * @package MediaSoft\Bitrix\Module\ComposerModuleInstaller
 */
class InteractiveInstaller extends AbstractInstaller
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * InteractiveInstaller constructor.
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public function installModules()
    {
        /** @var PackageInterface[] $packages */
        $packages = array_filter(
            $this->composer->getLocker()->getLockedRepository(true)->getPackages(),
            [__CLASS__, 'isPackageWithBitrixModule']
        );

        if (empty($packages)) {
            return;
        }

        // Force interactive questions
        $this->getBitrixRoot();
        foreach ($packages as $package) {
            $this->installModule($package);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function installModule(PackageInterface $package)
    {
        $question = sprintf(
            'Package %s(%s) suggest install a Bitrix module "%s"? Are you wanna install it? (y|n): ',
            $package->getPrettyName(),
            $package->getPrettyVersion(),
            $this->getModuleNameFromPackage($package)
        );

        if (!$this->io->askConfirmation($question, false)) {
            return InstallerInterface::NOT_INSTALLED;
        }

        $installationManager = $this->composer->getInstallationManager();
        $bitrixRoot = $this->getBitrixRoot();

        if ($this->io->askConfirmation('Do you wanna try to install it as symlink? (y|n): ')) {
            if ($this->installModuleAsSymlink($installationManager, $package, $bitrixRoot)) {
                $this->io->write(sprintf('Bitrix module "%s" successfully installed as symlink.', $this->getModuleNameFromPackage($package)));
                return InstallerInterface::SYMLINK;
            }

            $question = sprintf('Bitrix module "%s" can\'t be installed as symlink. Try as copy? (y|n): ');
            if (!$this->io->askConfirmation($question)) {
                return InstallerInterface::NOT_INSTALLED;
            }
        }

        if ($this->installModuleAsCopy($installationManager, $package, $bitrixRoot)) {
            $this->io->write(sprintf('Bitrix module "%s" successfully installed as copy.', $this->getModuleNameFromPackage($package)));
            return InstallerInterface::COPY;
        }

        $this->io->write(sprintf('Bitrix module "%s" can\'t be installed.'));

        return InstallerInterface::NOT_INSTALLED;
    }

    /**
     * @return bool|mixed|string
     */
    public function getBitrixRoot()
    {
        static $bitrixRoot = null;

        if (null === $bitrixRoot) {
            $bitrixRoot = BitrixFinder::findBitrixRootAround($this->composer->getConfig()->get('vendor-dir'));

            if (false === $bitrixRoot) {
                $bitrixRoot = $this->io->askAndValidate(
                    'Bitrix root not found, please enter the absolute path to bitrix folder: ',
                    [BitrixFinder::class, 'isBitrixRoot']
                );
            } elseif (!$this->io->askConfirmation(sprintf('Bitrix root founded at "%s". Confirm it? (y|n) ', $bitrixRoot))) {
                $bitrixRoot = $this->io->askAndValidate(
                    'Please enter the absolute path to bitrix folder: ',
                    [BitrixFinder::class, 'isBitrixRoot']
                );
            }
        }

        return $bitrixRoot;
    }
}