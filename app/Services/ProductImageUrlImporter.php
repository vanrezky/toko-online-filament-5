<?php

namespace App\Services;

use App\Models\Product;
use Closure;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class ProductImageUrlImporter
{
    private const MAX_IMAGES = 5;

    /** @var array<string, string> */
    private const MIME_EXTENSIONS = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /**
     * @param  (Closure(string): array<int, string>|false)|null  $hostResolver
     */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly ?Closure $hostResolver = null,
    ) {}

    /**
     * @param  array<int, array{url?: string}|string>  $entries
     */
    public function import(Product $product, array $entries): void
    {
        $urls = collect($entries)
            ->map(fn (array|string $entry): string => is_array($entry) ? (string) ($entry['url'] ?? '') : $entry)
            ->map(fn (string $url): string => trim($url))
            ->filter()
            ->values();

        if ($urls->isEmpty()) {
            return;
        }

        if ($product->getMedia()->count() + $urls->count() > self::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'image_urls' => __('admin/product-resource.notifications.image_limit_exceeded'),
            ]);
        }

        $importedMedia = collect();

        try {
            $urls->each(function (string $url) use ($product, $importedMedia): void {
                $this->assertPublicImageUrl($url);

                $response = $this->http
                    ->accept('image/*')
                    ->timeout(10)
                    ->withoutRedirecting()
                    ->get($url);

                if (! $response->successful()) {
                    throw $this->importFailedException();
                }

                $body = $response->body();
                $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($body);
                if (! is_string($mimeType) || ! array_key_exists($mimeType, self::MIME_EXTENSIONS)) {
                    throw $this->importFailedException();
                }

                $importedMedia->push(
                    $product->addMediaFromString($body)
                        ->usingFileName($this->filenameFor($url, $mimeType))
                        ->toMediaCollection(),
                );
            });
        } catch (Throwable $exception) {
            $importedMedia->each(fn (Media $media) => $media->delete());

            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            throw $this->importFailedException();
        }
    }

    private function assertPublicImageUrl(string $url): void
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || $this->isLocalHost($host)) {
            throw $this->invalidUrlException();
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : $this->resolveHost($host);

        if ($addresses === false || $addresses === []) {
            throw $this->invalidUrlException();
        }

        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw $this->invalidUrlException();
            }
        }
    }

    /**
     * @return array<int, string>|false
     */
    private function resolveHost(string $host): array|false
    {
        if ($this->hostResolver) {
            return ($this->hostResolver)($host);
        }

        return gethostbynamel($host);
    }

    private function isLocalHost(string $host): bool
    {
        return $host === 'localhost' || str_ends_with($host, '.localhost');
    }

    private function filenameFor(string $url, string $mimeType): string
    {
        $filename = pathinfo(basename((string) parse_url($url, PHP_URL_PATH)), PATHINFO_FILENAME);

        return ($filename !== '' ? $filename : 'product-image').'.'.self::MIME_EXTENSIONS[$mimeType];
    }

    private function invalidUrlException(): ValidationException
    {
        return ValidationException::withMessages([
            'image_urls' => __('admin/product-resource.notifications.invalid_image_url'),
        ]);
    }

    private function importFailedException(): ValidationException
    {
        return ValidationException::withMessages([
            'image_urls' => __('admin/product-resource.notifications.image_import_failed'),
        ]);
    }
}
