<?php

namespace App\Services\DataMigration;

use Illuminate\Support\Facades\File;

final class LocalSourcePath
{
    public function __construct(public readonly string $path, private readonly bool $temporary = false) {}

    public function close(): void
    {
        if ($this->temporary && File::exists($this->path)) {
            File::delete($this->path);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
