<?php

namespace App\Support;

class CareerShare
{
    /**
     * Official share endpoints with the URL and title properly encoded.
     *
     * @return array<int, array{key: string, label: string, href: string}>
     */
    public static function links(string $title, string $url): array
    {
        $encodedUrl = rawurlencode($url);
        $encodedTitle = rawurlencode($title);

        return [
            ['key' => 'whatsapp', 'label' => 'واتساب', 'href' => 'https://wa.me/?text='.rawurlencode($title."\n".$url)],
            ['key' => 'facebook', 'label' => 'فيسبوك', 'href' => 'https://www.facebook.com/sharer/sharer.php?u='.$encodedUrl],
            ['key' => 'linkedin', 'label' => 'لينكدإن', 'href' => 'https://www.linkedin.com/sharing/share-offsite/?url='.$encodedUrl],
            ['key' => 'x', 'label' => 'إكس', 'href' => 'https://x.com/intent/post?text='.$encodedTitle.'&url='.$encodedUrl],
        ];
    }

    /**
     * Absolute URL on the configured public APP_URL; null when that is local/private,
     * so crawlers never receive localhost addresses.
     */
    public static function absoluteUrl(string $path): ?string
    {
        $url = rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');

        return self::isPublicUrl($url) ? $url : null;
    }

    /**
     * Open Graph / Twitter Card data. Url and image are null without a public APP_URL.
     *
     * @return array{title: string, description: string, url: ?string, image: ?string, type: string}
     */
    public static function meta(string $title, ?string $description, ?string $url, ?string $image, string $type = 'website'): array
    {
        return [
            'title' => $title,
            'description' => (string) $description,
            'url' => $url,
            'image' => $image ?? self::absoluteUrl('/android-chrome-512x512.png'),
            'type' => $type,
        ];
    }

    /**
     * Local, private or placeholder hosts never make a shareable public URL.
     */
    public static function isPublicUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]', '0.0.0.0'], true)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        foreach (['.test', '.localhost', '.local', '.example', '.invalid'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return false;
            }
        }

        return str_starts_with(strtolower($url), 'https://') || str_starts_with(strtolower($url), 'http://');
    }
}
