<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;
use CodeIgniter\Session\Handlers\DatabaseHandler;
use CodeIgniter\Session\Handlers\RedisHandler;

class Session extends BaseConfig
{
    public function __construct()
    {
        parent::__construct();

        // Read dynamic configuration from admin settings
        $configFile = WRITEPATH . 'session_config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config && isset($config['driver'])) {
                if ($config['driver'] === 'file') {
                    $this->driver = \CodeIgniter\Session\Handlers\FileHandler::class;
                    $this->savePath = WRITEPATH . 'session';
                } elseif ($config['driver'] === 'database') {
                    $this->driver = \CodeIgniter\Session\Handlers\DatabaseHandler::class;
                    $this->savePath = 'ci_sessions';
                } elseif ($config['driver'] === 'redis') {
                    $this->driver = \CodeIgniter\Session\Handlers\RedisHandler::class;
                    if (isset($config['savePath']) && !empty($config['savePath'])) {
                        $this->savePath = $config['savePath'];
                    } else {
                        $this->savePath = 'tcp://127.0.0.1:6379';
                    }
                }
            }
        } else {
            // Fallback default for localhost / initial setup before admin config is created
            $this->driver = \CodeIgniter\Session\Handlers\FileHandler::class;
            $this->savePath = WRITEPATH . 'session';
        }
    }

    /**
     * --------------------------------------------------------------------------
     * Session Driver
     * --------------------------------------------------------------------------
     *
     * The session storage driver to use:
     * - `CodeIgniter\Session\Handlers\FileHandler`
     * - `CodeIgniter\Session\Handlers\DatabaseHandler`
     * - `CodeIgniter\Session\Handlers\MemcachedHandler`
     * - `CodeIgniter\Session\Handlers\RedisHandler`
     *
     * @var class-string<BaseHandler>
     
     * public string $driver = DatabaseHandler::class;
     */
    public string $driver = RedisHandler::class;

    /**
     * --------------------------------------------------------------------------
     * Session Cookie Name
     * --------------------------------------------------------------------------
     *
     * The session cookie name, must contain only [0-9a-z_-] characters
     */
    public string $cookieName = 'ci_session';

    /**
     * --------------------------------------------------------------------------
     * Session Expiration
     * --------------------------------------------------------------------------
     *
     * The number of SECONDS you want the session to last.
     * Setting to 0 (zero) means expire when the browser is closed.
     * 
     * 🔹 FIX: Increased from 7200 to 14400 to match the 4-hour cookie lifetime
     */
    public int $expiration = 14400;

    /**
     * --------------------------------------------------------------------------
     * Session Save Path
     * --------------------------------------------------------------------------
     *
     * The location to save sessions to and is driver dependent.
     *
     * For the 'files' driver, it's a path to a writable directory.
     * WARNING: Only absolute paths are supported!
     *
     * For the 'database' driver, it's a table name.
     * Please read up the manual for the format with other session drivers.
     *
     * IMPORTANT: You are REQUIRED to set a valid save path!
     
     * public string $savePath = 'ci_sessions';*/
    public string $savePath = 'tcp://127.0.0.1:6379';
    /**
     * --------------------------------------------------------------------------
     * Session Match IP
     * --------------------------------------------------------------------------
     *
     * Whether to match the user's IP address when reading the session data.
     *
     * WARNING: If you're using the database driver, don't forget to update
     *          your session table's PRIMARY KEY when changing this setting.
     */
    public bool $matchIP = false;

    /**
     * --------------------------------------------------------------------------
     * Session Time to Update
     * --------------------------------------------------------------------------
     *
     * How many seconds between CI regenerating the session ID.
     * 
     * 🔹 FIX: Increased from 600 to 14400 to completely prevent race conditions
     * caused by concurrent AJAX auto-saves dropping the new regenerated session ID mid-flight.
     */
    public int $timeToUpdate = 0;

    /**
     * --------------------------------------------------------------------------
     * Session Regenerate Destroy
     * --------------------------------------------------------------------------
     *
     * Whether to destroy session data associated with the old session ID
     * when auto-regenerating the session ID. When set to FALSE, the data
     * will be later deleted by the garbage collector.
     */
    public bool $regenerateDestroy = false;

    /**
     * --------------------------------------------------------------------------
     * Session Database Group
     * --------------------------------------------------------------------------
     *
     * DB Group for the database session.
     */
    public ?string $DBGroup = null;

    /**
     * --------------------------------------------------------------------------
     * Lock Retry Interval (microseconds)
     * --------------------------------------------------------------------------
     *
     * This is used for RedisHandler.
     *
     * Time (microseconds) to wait if lock cannot be acquired.
     * The default is 100,000 microseconds (= 0.1 seconds).
     */
    public int $lockRetryInterval = 100_000;

    /**
     * --------------------------------------------------------------------------
     * Lock Max Retries
     * --------------------------------------------------------------------------
     *
     * This is used for RedisHandler.
     *
     * Maximum number of lock acquisition attempts.
     * The default is 300 times. That is lock timeout is about 30 (0.1 * 300)
     * seconds.
     */
    public int $lockMaxRetries = 300;
}
