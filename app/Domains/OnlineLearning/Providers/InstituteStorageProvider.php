<?php

namespace App\Domains\OnlineLearning\Providers;

use App\Domains\OnlineLearning\Contracts\StorageProviderInterface;
use Illuminate\Support\Facades\Storage;

class InstituteStorageProvider implements StorageProviderInterface
{
    public function __construct(protected string $disk) {}

    public function store(string $directory, $file, string $visibility = 'private'): string
    {
        return Storage::disk($this->disk)->putFile($directory, $file, $visibility);
    }

    public function getUrl(string $path): ?string
    {
        return Storage::disk($this->disk)->url($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function getTemporaryUrl(string $path, int $expirationMinutes = 60): ?string
    {
        return Storage::disk($this->disk)->temporaryUrl($path, now()->addMinutes($expirationMinutes));
    }
}
