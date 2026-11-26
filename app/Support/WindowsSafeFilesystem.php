<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use Throwable;

/**
 * The Blade compiler's filesystem on Windows only (see AppServiceProvider).
 *
 * Filesystem::replace() writes a temp file and rename()s it over the compiled
 * view. On Windows that rename fails with "Access is denied" while another
 * request has the target open — which is exactly what happens when two
 * requests compile the same view at once (right after a template edit or a
 * view:clear, with Livewire and a page load in flight together), and the page
 * answers 500. Here the rename is retried briefly, and if the target already
 * holds the same compiled code (the other request won the race) that counts as
 * done.
 */
class WindowsSafeFilesystem extends Filesystem
{
    public function replace($path, $content, $mode = null)
    {
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;
        $tempPath = tempnam(dirname($path), basename($path));

        @chmod($tempPath, is_null($mode) ? 0777 - umask() : $mode);

        file_put_contents($tempPath, $content);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            if (@rename($tempPath, $path)) {
                return;
            }

            if (is_file($path) && @file_get_contents($path) === $content) {
                @unlink($tempPath);

                return;
            }

            usleep(50_000 * $attempt);
        }

        try {
            rename($tempPath, $path);
        } catch (Throwable $e) {
            @unlink($tempPath);

            throw $e;
        }
    }
}
