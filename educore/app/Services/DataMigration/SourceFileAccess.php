<?php

namespace App\Services\DataMigration;

use App\Models\MigrationFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SourceFileAccess
{
    public function localPath(MigrationFile $file): LocalSourcePath
    {
        $disk = Storage::disk($file->storage_disk);

        try {
            $path = $disk->path($file->storage_path);
            if (is_file($path) && is_readable($path)) {
                return new LocalSourcePath($path);
            }
        } catch (\Throwable) {
            // Non-local disks are materialised below.
        }

        $stream = $disk->readStream($file->storage_path);
        if ($stream === false) {
            throw new RuntimeException('Migration source cannot be opened.');
        }

        $extension = pathinfo($file->sanitized_filename, PATHINFO_EXTENSION);
        $path = tempnam(sys_get_temp_dir(), 'educore-migration-');
        if ($path === false) {
            fclose($stream);
            throw new RuntimeException('Temporary migration source path could not be created.');
        }
        if ($extension !== '') {
            $renamed = $path.'.'.$extension;
            rename($path, $renamed);
            $path = $renamed;
        }

        $target = fopen($path, 'wb');
        if ($target === false) {
            fclose($stream);
            throw new RuntimeException('Temporary migration source cannot be written.');
        }
        stream_copy_to_stream($stream, $target);
        fclose($stream);
        fclose($target);

        return new LocalSourcePath($path, true);
    }
}
