<?php

namespace App\DataMigration\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageMigrationService
{
    /**
     * Download an image from a URL and save it to local storage.
     *
     * @param  string  $url  The URL of the image to download
     * @param  string  $directory  The storage directory (e.g., 'employees/profile-pictures')
     * @param  string|null  $filename  Optional custom filename (without extension)
     * @return array{path: string, filename: string, mime_type: string}|null Returns null on failure
     */
    public function downloadAndStore(string $url, string $directory, ?string $filename = null): ?array
    {
        try {
            // Skip if URL is empty or invalid
            if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                return null;
            }

            // Download the image
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning("Failed to download image from {$url}: HTTP {$response->status()}");

                return null;
            }

            $content = $response->body();
            $contentType = $response->header('Content-Type');

            // Determine file extension from content type
            $extension = $this->getExtensionFromMimeType($contentType);
            if (! $extension) {
                // Try to get extension from URL
                $extension = $this->getExtensionFromUrl($url);
            }

            if (! $extension) {
                Log::warning("Could not determine file extension for image from {$url}");
                $extension = 'jpg'; // Default fallback
            }

            // Generate filename if not provided
            if (! $filename) {
                $filename = Str::uuid()->toString();
            }

            $fullFilename = "{$filename}.{$extension}";
            $path = "{$directory}/{$fullFilename}";

            // Store the file
            Storage::disk('local')->put($path, $content);

            return [
                'path' => $path,
                'filename' => $fullFilename,
                'mime_type' => $contentType ?: "image/{$extension}",
            ];
        } catch (\Exception $e) {
            Log::error("Error downloading image from {$url}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Get file extension from MIME type.
     */
    private function getExtensionFromMimeType(?string $mimeType): ?string
    {
        if (! $mimeType) {
            return null;
        }

        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/bmp' => 'bmp',
            'image/tiff' => 'tiff',
        ];

        return $mimeToExt[$mimeType] ?? null;
    }

    /**
     * Get file extension from URL.
     */
    private function getExtensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! $path) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff'];

        return in_array($extension, $validExtensions) ? $extension : null;
    }

    /**
     * Download a document (PDF, etc.) from a URL and save it to local storage.
     *
     * @param  string  $url  The URL of the document to download
     * @param  string  $directory  The storage directory
     * @param  string|null  $filename  Optional custom filename (without extension)
     * @return array{path: string, filename: string, mime_type: string}|null Returns null on failure
     */
    public function downloadDocument(string $url, string $directory, ?string $filename = null): ?array
    {
        try {
            if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                return null;
            }

            $response = Http::timeout(60)->get($url);

            if (! $response->successful()) {
                Log::warning("Failed to download document from {$url}: HTTP {$response->status()}");

                return null;
            }

            $content = $response->body();
            $contentType = $response->header('Content-Type') ?: 'application/octet-stream';

            // Get extension from URL or content type
            $extension = $this->getDocumentExtension($url, $contentType);

            if (! $filename) {
                $filename = Str::uuid()->toString();
            }

            $fullFilename = "{$filename}.{$extension}";
            $path = "{$directory}/{$fullFilename}";

            Storage::disk('local')->put($path, $content);

            return [
                'path' => $path,
                'filename' => $fullFilename,
                'mime_type' => $contentType,
            ];
        } catch (\Exception $e) {
            Log::error("Error downloading document from {$url}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Get document extension from URL or content type.
     */
    private function getDocumentExtension(string $url, string $contentType): string
    {
        // First try URL
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($extension) {
                return $extension;
            }
        }

        // Then try content type
        $mimeToExt = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
        ];

        return $mimeToExt[$contentType] ?? 'bin';
    }
}
