<?php


namespace MediaSoft\Bitrix\Finder;


/**
 * Class BitrixFinder
 * @package MediaSoft\Bitrix\Finder
 */
/**
 * Class BitrixFinder
 * @package MediaSoft\Bitrix\Finder
 */
class BitrixFinder
{
    /***
     * @param string $dir
     * @return string|bool
     */
    public static function findBitrixRootAround($dir)
    {
        $paths = [
            dirname($dir),
            dirname($dir) . '/bitrix',
            dirname(dirname($dir)) . '/bitrix',
        ];

        foreach ($paths as $path) {
            if (self::isBitrixRoot($path)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isBitrixRoot($path)
    {
        $filePath = $path . '/modules/main/classes/general/version.php';

        return file_exists($filePath);
    }
}