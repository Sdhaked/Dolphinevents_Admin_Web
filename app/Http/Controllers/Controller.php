<?php

namespace App\Http\Controllers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

abstract class Controller
{
    protected function replacePublicFile(?string $currentPath, UploadedFile $file, string $directory): string
    {
        $newPath = $file->store($directory, 'public');
        $this->deletePublicFile($currentPath, $newPath);

        return $newPath;
    }

    protected function deletePublicFile(?string $path, ?string $exceptPath = null): void
    {
        if (!$path || $path === $exceptPath) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
