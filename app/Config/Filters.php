<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf' => CSRF::class,
        'toolbar' => DebugToolbar::class,
        'honeypot' => Honeypot::class,
        'invalidchars' => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors' => Cors::class,
        'forcehttps' => ForceHTTPS::class,
        'pagecache' => PageCache::class,
        'performance' => PerformanceMetrics::class,
        'auth' => \App\Filters\Auth::class,
        'readonly' => \App\Filters\ReadOnlyFilter::class,
        'license' => \App\Filters\LicenseFilter::class,
        'gzip' => \App\Filters\GzipFilter::class,
        'passwordchange' => \App\Filters\PasswordChangeFilter::class,
        'maintenance' => \App\Filters\MaintenanceFilter::class,
        'ratelimit' => \App\Filters\RateLimitFilter::class,
        'sessiontimeout' => \App\Filters\SessionTimeoutFilter::class, // ✅ Session timeout check
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            'toolbar',     // Debug Toolbar
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            // 'honeypot',
            // csrf HARUS jalan pertama agar session diinisialisasi oleh CI4 secara benar
            // sebelum filter lain (maintenance, license, dll) memanggil session()
            'csrf' => ['except' => [
                'siswa/cbt/refreshCsrf', // Exclude refreshCsrf from CSRF check (needed to get new token)
                'api/dapodik/receive',   // Sync Agent dari PowerShell bridge — auth via token header
                'admin/updater/*',       // Updater — admin only, auth sudah dijaga oleh filter auth:1
            ]],
            // 'invalidchars',
            'maintenance', // Check maintenance mode (setelah csrf agar session sudah siap)
            'license',     // License check AFTER csrf so session is already initialized
            'gzip', // Enable Gzip compression for all responses
            'sessiontimeout', // ✅ Check session timeout for all authenticated requests
            'passwordchange', // Check if user must change password
            'secureheaders', // ✅ ENABLED: Security headers for all responses
        ],
        'after' => [
            // 'honeypot',
            'secureheaders', // ✅ ENABLED: Security headers after response
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        'readonly' => ['before' => ['admin/*']],
    ];
}
