<?php

namespace MediaSoft\Bitrix\Module;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ComposerBridge
 * @package MediaSoft\Bitrix\Module
 */
class ComposerBridge
{
    /**
     * @param Event $event
     */
    public static function installModules(Event $event)
    {
        $locked = $event->getComposer()->getLocker()->getLockData();

        $modulePackages = [];
        foreach (['packages', 'packages-dev'] as $key) {
            $packages = $locked[$key];
            foreach ($packages as $package) {
                if ($modules = self::isBitrixModulePackage($package)) {
                    $modulePackages[] = [
                        'name' => $package['name'],
                        'version' => $package['version'],
                        'module'  => $package['extra']['bitrix-module'],
                    ];
                }
            }
        }

        self::installBitrixModulesInteractive($event->getComposer(), $event->getIO(), $modulePackages);
    }

    /**
     * @param array $package
     * @return bool
     */
    protected static function isBitrixModulePackage(array $package)
    {
        return isset($package['extra'])
        && is_array($package['extra'])
        && isset($package['extra']['bitrix-module'])
        && is_array($package['extra']['bitrix-module']);
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param array $packages
     */
    protected static function installBitrixModulesInteractive(Composer $composer, IOInterface $io, array $packages)
    {
        foreach ($packages as $package) {
            $question = sprintf(
                'Package %s(%s) suggest install a Bitrix module "%s"? Are you wanna install it? (y|n): ',
                $package['name'],
                $package['version'],
                $package['module']['name']
            );

            if (!$io->askConfirmation($question, false)) {
                continue;
            }

            if ($io->askConfirmation('Do you wanna try to install it as symlink? (y|n): ')) {
                if (self::installModuleAsSymlink($composer, $io, $package)) {
                    $io->write(sprintf('Bitrix module "%s" successfully installed as symlink.', $package['module']['name']));
                    continue;
                }

            }

            self::installModuleAsCopy($composer, $io, $package);

            $io->write(sprintf('Bitrix module "%s" successfully installed as copy.', $package['module']['name']));
        }
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param array $package
     * @return bool
     */
    protected static function installModuleAsCopy(Composer $composer, IOInterface $io, array $package)
    {
        $bitrixRoot = self::findBitrixRoot($composer, $io);
        $modulesFolder = $bitrixRoot.'/modules';
        // TODO
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param array $modulePackage
     * @return bool
     */
    protected static function installModuleAsSymlink(Composer $composer, IOInterface $io, array $modulePackage)
    {
        $bitrixRoot = self::findBitrixRoot($composer, $io);
        $bitrixModulesDir = $bitrixRoot.'/modules';

        $fs = new Filesystem();
        $package = $composer->getLocker()->getLockedRepository(true)->findPackage($modulePackage['name'], $modulePackage['version']);
        $packageDir = $composer->getInstallationManager()->getInstallPath($package);
        $packageModuleDir = $packageDir.'/'.$modulePackage['module']['path'];

        $targetDir = $bitrixModulesDir.'/'.$modulePackage['module']['name'];
        $originDir = $fs->makePathRelative($packageModuleDir, realpath($bitrixModulesDir));

        if ($fs->exists($targetDir)) {
            if (!$io->askConfirmation(sprintf('Module "%s" already installed. Override it? (y|n) ', $modulePackage['module']['name']))) {
                return false;
            }

            $fs->remove($targetDir);
        }

        try {
            $fs->symlink(
                $originDir,
                $targetDir
            );
        } catch (IOException $e) {
            return false;
        }

        return true;
    }

    protected static function findBitrixRoot(Composer $composer, IOInterface $io)
    {
        static $bitrixRoot;

        if (null === $bitrixRoot) {
            $vendorDir = $composer->getConfig()->get('vendor-dir');

            $paths = [
                dirname($vendorDir),
                dirname($vendorDir).'/bitrix',
                dirname(dirname($vendorDir)).'/bitrix',
            ];

            $bitrixRoot = false;
            foreach ($paths as $path) {
                if (self::isBitrixRoot($path)) {
                    $bitrixRoot = $path;
                    break;
                }
            }

            $bitrixRoot = self::confirmBitrixRoot($io, $bitrixRoot);
        }

        return $bitrixRoot;
    }

    protected static function isBitrixRoot($path)
    {
        $filePath = $path.'/modules/main/classes/general/version.php';

        return file_exists($filePath);
    }

    protected static function confirmBitrixRoot(IOInterface $io, $bitrixRoot)
    {
        if (false === $bitrixRoot) {
            return $io->askAndValidate(
                'Bitrix root not found, please enter the absolute path to bitrix folder: ',
                [__CLASS__, 'isBitrixRoot']
            );
        }

        if (!$io->askConfirmation(sprintf('Bitrix root founded at "%s". Confirm it? (y|n) ', $bitrixRoot    ))) {
            return $io->askAndValidate(
                'Please enter the absolute path to bitrix folder: ',
                [__CLASS__, 'isBitrixRoot']
            );
        }

        return $bitrixRoot;
    }
}