<?php

/**
 * Asset Helper
 * 
 * Helper functions untuk asset optimization
 */

if (!function_exists('lazy_img')) {
    /**
     * Generate lazy loading image tag
     * 
     * @param string $src Image source
     * @param string $alt Alt text
     * @param array $attributes Additional attributes
     * @return string HTML img tag with lazy loading
     */
    function lazy_img(string $src, string $alt = '', array $attributes = []): string
    {
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . esc($key) . '="' . esc($value) . '"';
        }
        
        // Use placeholder for lazy loading
        $placeholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E';
        
        return sprintf(
            '<img src="%s" data-src="%s" alt="%s" loading="lazy"%s class="lazy">',
            $placeholder,
            esc($src),
            esc($alt),
            $attrs
        );
    }
}

if (!function_exists('cdn_asset')) {
    /**
     * Get CDN URL for common libraries
     * 
     * @param string $library Library name (bootstrap, jquery, fontawesome, etc)
     * @param string $version Version number
     * @param string $file File path
     * @return string CDN URL
     */
    function cdn_asset(string $library, string $version, string $file): string
    {
        $cdnMap = [
            'bootstrap' => 'https://cdn.jsdelivr.net/npm/bootstrap@' . $version . '/' . $file,
            'jquery' => 'https://code.jquery.com/jquery-' . $version . '.min.js',
            'fontawesome' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/' . $version . '/' . $file,
            'datatables' => 'https://cdn.datatables.net/' . $version . '/' . $file,
            'select2' => 'https://cdn.jsdelivr.net/npm/select2@' . $version . '/' . $file,
            'sweetalert2' => 'https://cdn.jsdelivr.net/npm/sweetalert2@' . $version . '/' . $file,
        ];
        
        if (isset($cdnMap[$library])) {
            return $cdnMap[$library];
        }
        
        // Default to jsDelivr
        return 'https://cdn.jsdelivr.net/npm/' . $library . '@' . $version . '/' . $file;
    }
}

if (!function_exists('versioned_asset')) {
    /**
     * Add version query string to asset for cache busting
     * 
     * @param string $path Asset path
     * @param bool $useFileTime Use file modification time as version
     * @return string Asset URL with version
     */
    function versioned_asset(string $path, bool $useFileTime = true): string
    {
        $fullPath = FCPATH . $path;
        
        if ($useFileTime && file_exists($fullPath)) {
            $version = filemtime($fullPath);
        } else {
            // Use app version from config
            $version = config('App')->appVersion ?? '1.0.0';
        }
        
        return base_url($path) . '?v=' . $version;
    }
}

if (!function_exists('inline_critical_css')) {
    /**
     * Inline critical CSS for above-the-fold content
     * 
     * @param string $cssFile Path to critical CSS file
     * @return string Inline style tag
     */
    function inline_critical_css(string $cssFile): string
    {
        $fullPath = FCPATH . $cssFile;
        
        if (!file_exists($fullPath)) {
            return '';
        }
        
        $css = file_get_contents($fullPath);
        
        // Minify CSS (basic)
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\/\*.*?\*\//', '', $css);
        $css = str_replace([': ', ' {', '{ ', ' }', '; '], [':', '{', '{', '}', ';'], $css);
        
        return '<style>' . $css . '</style>';
    }
}

if (!function_exists('preload_asset')) {
    /**
     * Generate preload link tag for critical assets
     * 
     * @param string $href Asset URL
     * @param string $as Resource type (style, script, font, image)
     * @param string $type MIME type (optional)
     * @param bool $crossorigin Add crossorigin attribute
     * @return string Link preload tag
     */
    function preload_asset(string $href, string $as, string $type = '', bool $crossorigin = false): string
    {
        $attrs = sprintf('rel="preload" href="%s" as="%s"', esc($href), esc($as));
        
        if ($type) {
            $attrs .= ' type="' . esc($type) . '"';
        }
        
        if ($crossorigin) {
            $attrs .= ' crossorigin';
        }
        
        return '<link ' . $attrs . '>';
    }
}

if (!function_exists('defer_script')) {
    /**
     * Generate script tag with defer attribute
     * 
     * @param string $src Script source
     * @param array $attributes Additional attributes
     * @return string Script tag with defer
     */
    function defer_script(string $src, array $attributes = []): string
    {
        $attrs = 'defer';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . esc($key) . '="' . esc($value) . '"';
        }
        
        return sprintf('<script src="%s" %s></script>', esc($src), $attrs);
    }
}

if (!function_exists('async_script')) {
    /**
     * Generate script tag with async attribute
     * 
     * @param string $src Script source
     * @param array $attributes Additional attributes
     * @return string Script tag with async
     */
    function async_script(string $src, array $attributes = []): string
    {
        $attrs = 'async';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . esc($key) . '="' . esc($value) . '"';
        }
        
        return sprintf('<script src="%s" %s></script>', esc($src), $attrs);
    }
}

if (!function_exists('webp_image')) {
    /**
     * Generate picture tag with WebP fallback
     * 
     * @param string $webpSrc WebP image source
     * @param string $fallbackSrc Fallback image source (jpg/png)
     * @param string $alt Alt text
     * @param array $attributes Additional attributes
     * @return string Picture tag with WebP and fallback
     */
    function webp_image(string $webpSrc, string $fallbackSrc, string $alt = '', array $attributes = []): string
    {
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . esc($key) . '="' . esc($value) . '"';
        }
        
        return sprintf(
            '<picture><source srcset="%s" type="image/webp"><img src="%s" alt="%s"%s loading="lazy"></picture>',
            esc($webpSrc),
            esc($fallbackSrc),
            esc($alt),
            $attrs
        );
    }
}

if (!function_exists('responsive_image')) {
    /**
     * Generate responsive image with srcset
     * 
     * @param string $src Base image source
     * @param array $sizes Array of sizes ['320w' => 'image-320.jpg', '640w' => 'image-640.jpg']
     * @param string $alt Alt text
     * @param string $sizesAttr Sizes attribute value
     * @return string Img tag with srcset
     */
    function responsive_image(string $src, array $sizes, string $alt = '', string $sizesAttr = '100vw'): string
    {
        $srcset = [];
        foreach ($sizes as $descriptor => $imageSrc) {
            $srcset[] = esc($imageSrc) . ' ' . $descriptor;
        }
        
        return sprintf(
            '<img src="%s" srcset="%s" sizes="%s" alt="%s" loading="lazy">',
            esc($src),
            implode(', ', $srcset),
            esc($sizesAttr),
            esc($alt)
        );
    }
}
