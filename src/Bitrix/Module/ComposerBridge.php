<?php

namespace MediaSoft\Bitrix\Module;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use MediaSoft\Bitrix\Module\ComposerModuleInstaller\InteractiveInstaller;
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
        $mode = 'interactive';

        switch ($mode) {
            case 'interactive':
            default:
                $installer = new InteractiveInstaller($event->getComposer(), $event->getIO());
                break;
        }

        $installer->installModules();
    }
}