<?php

namespace App\Domains\OnlineLearning\Contracts;

interface StorageProviderInterface
{
    public function store(string $directory, $file, string $visibility = 'private'): string;
    public function getUrl(string $path): ?string;
    public function delete(string $path): bool;
    public function exists(string $path): bool;
    public function getTemporaryUrl(string $path, int $expirationMinutes = 60): ?string;
}
