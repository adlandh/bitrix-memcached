<?
/**
 * Created by PhpStorm.
 * User: dh
 * Date: 21.12.16
 * Time: 13:41
 */

namespace DHCache;

use Bitrix\Main\Config;
use Bitrix\Main\Data;

class CacheEngineMemcached implements ICacheEngine, ICacheEngineStat
{
    /*
     * @var obMemcached - connection to memcached.
     */
    private static $obMemcached = null;

    /*
     * @var isConnected -  is already connected
     */
    private static $isConnected = false;

    /*
     * @var sid - for using with several websites
     */
    private $sid = "BX";
    /*
     * @var read - bytes read
     */

    private $read = false;

    /*
     * @var written - bytes written
    */

    private $written = false;

    /*
     * @var baseDirVersion - array of base_dir
     */
    private static $baseDirVersion = array();


    /*
     * Constructor
     */

    function __construct()
    {
        $cacheConfig = Config\Configuration::getValue("cache");

        if (self::$obMemcached == null)
        {
            self::$obMemcached = new \Memcached();

            if (isset($cacheConfig["hosts"]))
            {
                self::$isConnected = self::$obMemcached->addServers($cacheConfig["hosts"]);
            }
            else
            {
                self::$isConnected = self::$obMemcached->addServer("127.0.0.1", "11211");
            }
        }

        if ($cacheConfig && is_array($cacheConfig))
        {
            if (!empty($cacheConfig["sid"]))
            {
                $this->sid = $cacheConfig["sid"];
            }
        }
    }

    /*
     * Close connection
     *
     * @return void
     */

    function close()
    {
        if (self::$obMemcached != null)
        {
            self::$obMemcached = null;
        }
    }

    /*
     * Returns number of bytes read from memcached or false if there were no read operation
     *
     * @return integer|false
     */

    public function getReadBytes()
    {
        return $this->read;
    }

    /*
     * Returns number of bytes written to memcached or false if there were no write operation
     *
     * @return integer|false
     */
    public function getWrittenBytes()
    {
        return $this->written;
    }

    /*
     * Always return ""
     *
     * @return ""
     *
     */
    public function getCachePath()
    {
        return "";
    }

    /*
     * Returns true if there's connection to memcached
     *
     * @return boolean
     */
    public function isAvailable()
    {
        return self::$isConnected;
    }

    /**
     * Cleans (removes) cache directory or file.
     *
     * @param string $baseDir Base cache directory.
     * @param string $initDir Directory within base.
     * @param string $filename File name.
     *
     * @return void
     */
    function clean($baseDir, $initDir = false, $filename = false)
    {
        if (is_object(self::$obMemcached))
        {
            if (strlen($filename))
            {
                if (!isset(self::$baseDirVersion[$baseDir]))
                {
                    self::$baseDirVersion[$baseDir] = self::$obMemcached->get($this->sid . $baseDir);
                }

                if (self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
                {
                    return;
                }

                if ($initDir !== false)
                {
                    $initDirVersion = self::$obMemcached->get(self::$baseDirVersion[$baseDir] . "|" . $initDir);
                    if ($initDirVersion === false || $initDirVersion === '')
                    {
                        return;
                    }
                }
                else
                {
                    $initDirVersion = "";
                }

                $key = self::$baseDirVersion[$baseDir] . "|" . $initDirVersion . "|" . $filename;
                self::$obMemcached->replace($key, "", 0, 1);
            }
            else
            {
                if (strlen($initDir))
                {
                    if (!isset(self::$baseDirVersion[$baseDir]))
                    {
                        self::$baseDirVersion[$baseDir] = self::$obMemcached->get($this->sid . $baseDir);
                    }

                    if (self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
                    {
                        return;
                    }

                    self::$obMemcached->replace(self::$baseDirVersion[$baseDir] . "|" . $initDir, "", 0, 1);
                }
                else
                {
                    if (isset(self::$baseDirVersion[$baseDir]))
                    {
                        unset(self::$baseDirVersion[$baseDir]);
                    }

                    self::$obMemcached->replace($this->sid . $baseDir, "", 0, 1);
                }
            }
        }
    }

    /**
     * Reads cache from the memcached. Returns true if key value exists, not expired, and successfully read.
     *
     * @param mixed &$arAllVars Cached result.
     * @param string $baseDir Base cache directory.
     * @param string $initDir Directory within base.
     * @param string $filename File name.
     * @param integer $TTL Expiration period in seconds.
     *
     * @return boolean
     */
    function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL)
    {
        if (!isset(self::$baseDirVersion[$baseDir]))
        {
            self::$baseDirVersion[$baseDir] = self::$obMemcached->get($this->sid . $baseDir);
        }

        if (self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
        {
            return false;
        }

        if ($initDir !== false)
        {
            $initDirVersion = self::$obMemcached->get(self::$baseDirVersion[$baseDir] . "|" . $initDir);
            if ($initDirVersion === false || $initDirVersion === '')
            {
                return false;
            }
        }
        else
        {
            $initDirVersion = "";
        }

        $key = self::$baseDirVersion[$baseDir] . "|" . $initDirVersion . "|" . $filename;

        $arAllVars = self::$obMemcached->get($key);

        if ($arAllVars === false || $arAllVars === '')
        {
            return false;
        }

        return true;
    }

    /**
     * Puts cache into the memcached.
     *
     * @param mixed $arAllVars Cached result.
     * @param string $baseDir Base cache directory.
     * @param string $initDir Directory within base.
     * @param string $filename File name.
     * @param integer $TTL Expiration period in seconds.
     *
     * @return void
     */
    function write($arAllVars, $baseDir, $initDir, $filename, $TTL)
    {
        if (!isset(self::$baseDirVersion[$baseDir]))
            self::$baseDirVersion[$baseDir] = self::$obMemcached->get($this->sid.$baseDir);

        if (self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
        {
            self::$baseDirVersion[$baseDir] = $this->sid.md5(mt_rand());
            self::$obMemcached->set($this->sid.$baseDir, self::$baseDirVersion[$baseDir]);
        }

        if ($initDir !== false)
        {
            $initDirVersion = self::$obMemcached->get(self::$baseDirVersion[$baseDir]."|".$initDir);
            if ($initDirVersion === false || $initDirVersion === '')
            {
                $initDirVersion = $this->sid.md5(mt_rand());
                self::$obMemcached->set(self::$baseDirVersion[$baseDir]."|".$initDir, $initDirVersion);
            }
        }
        else
        {
            $initDirVersion = "";
        }

        $key = self::$baseDirVersion[$baseDir]."|".$initDirVersion."|".$filename;
        self::$obMemcached->set($key, $arAllVars, $TTL);
    }

    /**
     * Returns true if cache has been expired.
     * Stub function always returns true.
     *
     * @param string $path Absolute physical path.
     *
     * @return boolean
     */
    function isCacheExpired($path)
    {
        return false;
    }

}