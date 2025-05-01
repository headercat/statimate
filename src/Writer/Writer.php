<?php

declare(strict_types=1);

namespace Headercat\Statimate\Writer;

use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Writer\Hooks\BeforeCopyHook;
use Headercat\Statimate\Writer\Hooks\BeforeWriteHook;
use RuntimeException;

final readonly class Writer
{
    public function __construct(private StatimateConfig $config)
    {
    }

    /**
     * Clear the build target directory.
     *
     * @param string|null $path Internal use.
     *
     * @return void
     */
    public function clear(string|null $path = null): void
    {
        $path ??= $this->config->buildDir;
        if (is_dir($path)) {
            $paths = array_diff(scandir($path), ['..', '.']);
            foreach ($paths as $value) {
                $value = $path . '/' . $value;
                if (is_dir($value) && !is_link($value)) {
                    $this->clear($value);
                } else {
                    if (!unlink($value)) {
                        throw new RuntimeException(
                            'Unable to clear build directory "' . $this->config->buildDir . '"'
                        );
                    }
                }
            }
            if (!rmdir($path)) {
                throw new RuntimeException(
                    'Unable to clear build directory "' . $this->config->buildDir . '"'
                );
            }
        }
    }

    /**
     * Write the content to the provided destination.
     *
     * @param string $dest    Destination to write on.
     * @param string $content Content to write.
     *
     * @return void
     */
    public function write(string $dest, string $content): void
    {
        $dest = $this->config->buildDir . $dest;
        ['dest' => $dest, 'content' => $content] = BeforeWriteHook::dispatch(['dest' => $dest, 'content' => $content]);

        $this->createDirectory($dest);
        if (file_put_contents($dest, $content) === false) {
            throw new RuntimeException('Unable to write file "' . $dest . '".');
        }
    }

    /**
     * Copy the file to the provided destination.
     *
     * @param string $dest Destination to copy to.
     * @param string $from Original file to be copied.
     *
     * @return void
     */
    public function copy(string $dest, string $from): void
    {
        $dest = $this->config->buildDir . $dest;
        ['dest' => $dest, 'from' => $from] = BeforeCopyHook::dispatch(['dest' => $dest, 'from' => $from]);

        $this->createDirectory($dest);
        if (!copy($from, $dest)) {
            throw new RuntimeException('Unable to copy file "' . $dest . '".');
        }
    }

    /**
     * Create a directory to make the provided destination file should be written properly.
     *
     * @param string $dest File to write on.
     *
     * @return void
     */
    private function createDirectory(string $dest): void
    {
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new RuntimeException('Unable to create directory "' . $dir . '".');
            }
        }
    }
}
