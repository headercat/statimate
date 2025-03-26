<?php

declare(strict_types=1);

namespace Headercat\Statimate\Supports;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class Directory
{
    /**
     * Trim the directory path and validate it's exists.
     *
     * @param string $dir Directory path to trim and validate.
     *
     * @return string Trimmed directory path.
     */
    public static function trim(string $dir): string
    {
        $dir = '/' . trim($dir, '/');
        if (!is_dir($dir)) {
            throw new InvalidArgumentException('Cannot find the directory "' . $dir . '".');
        }
        return $dir;
    }

    /**
     * Create the directory if it does not exist, and remove all files from the directory.
     *
     * @param string $dir              Directory path to clean.
     * @param bool   $createAfterClean Create the directory after cleaning.
     *
     * @return string Real absolute directory path.
     */
    public static function clean(string $dir, bool $createAfterClean = true): string
    {
        if (is_file($dir)) {
            throw new InvalidArgumentException('Path "' . $dir . '" is not a directory.');
        }
        if (!is_dir($dir) && $createAfterClean) {
            @mkdir($dir, 0777, true);
            return realpath($dir) ?: throw new RuntimeException('Cannot determine the real absolute path.');
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            assert($file instanceof SplFileInfo);
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        if ($createAfterClean) {
            @mkdir($dir, 0777, true);
        }
        return realpath($dir) ?: throw new RuntimeException('Cannot determine the real absolute path.');
    }

    /**
     * Create a recursive directory scanner.
     *
     * @param string $dir Directory to scan.
     *
     * @return RecursiveIteratorIterator<RecursiveDirectoryIterator>
     */
    public static function createRecursiveIterator(string $dir): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        );
    }
}
