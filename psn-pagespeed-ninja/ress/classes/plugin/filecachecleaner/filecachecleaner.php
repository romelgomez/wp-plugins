<?php

/*
 * RESSIO Responsive Server Side Optimizer
 * https://github.com/ressio/
 *
 * @copyright   Copyright (C) 2013-2018 Kuneri, Ltd. All rights reserved.
 * @license     GNU General Public License version 2
 */

class Ressio_Plugin_FilecacheCleaner extends Ressio_Plugin
{
    /** @var IRessio_Filelock */
    protected $filelock;
    /** @var IRessio_Filesystem */
    protected $filesystem;

    /**
     * @param Ressio_DI $di
     * @param null|stdClass $params
     * @throws ERessio_UnknownDiKey
     */
    public function __construct($di, $params)
    {
        $params = $this->loadConfig(dirname(__FILE__) . '/config.json', $params);

        parent::__construct($di, $params);

        $this->filelock = $di->filelock;
        $this->filesystem = $di->filesystem;

        register_shutdown_function(array($this, 'shutdown'));
    }

    public function shutdown()
    {
        if ($this->params->detach) {
            ignore_user_abort(true);
            flush();
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
        }

        $filelock = $this->filelock;
        $fs = $this->filesystem;

        $cachedir = $this->config->cachedir;
        $ttl = $this->config->cachettl;
        if ($ttl <= 0) {
            $ttl = ini_get('max_execution_time');
        }
        $aging_time = time() - $ttl;

        $lock = $cachedir . '/filecachecleaner.stamp';
        if (!$fs->isFile($lock)) {
            $fs->touch($lock, $aging_time);
        }
        if (!$filelock->lock($lock)) {
            return;
        }
        if ($fs->getModificationTime($lock) > $aging_time) {
            $filelock->unlock($lock);
            return;
        }
        $fs->touch($lock);
        $filelock->unlock($lock);

        // wait for double ttl to clear cache
        $aging_time -= $ttl;
        $file_list = array();

        // @todo remove widow *.lock files ????

        // iterate cache directory
        foreach (scandir($cachedir, @constant('SCANDIR_SORT_NONE')) as $subdir) {
            $subdir_path = $cachedir . '/' . $subdir;
            if ($subdir[0] === '.' || !is_dir($subdir_path)) {
                continue;
            }
            $h = opendir($subdir_path);
            while (($file = readdir($h)) !== false) {
                /** @var string $file */
                $file_path = $subdir_path . '/' . $file;
                if ($file[0] === '.' || !is_file($file_path)) {
                    continue;
                }
                if ($fs->getModificationTime($file_path) < $aging_time) {
                    unlink($file_path);
                    continue;
                }
                list($hash, $group) = explode('_', $file, 2);
                switch ($group) {
                    case 'htmljs':
                        if (preg_match_all('#src="[^">]+[/?]([0-9a-f]+\.js)#', file_get_contents($file_path), $matches)) {
                            foreach ($matches[1] as $file_ref) {
                                $file_list[$file_ref] = 1;
                            }
                        }
                        break;
                    case 'htmlcss':
                        if (preg_match_all('#href="[^">]+[/?]([0-9a-f]+\.css)#', file_get_contents($file_path), $matches)) {
                            foreach ($matches[1] as $file_ref) {
                                $file_list[$file_ref] = 1;
                            }
                        }
                        break;
                    case 'file':
                        if (preg_match('#/([0-9a-f]+\.[a-f]+)$#', file_get_contents($file_path), $matches)) {
                            $file_ref = $matches[1];
                            $file_list[$file_ref] = 1;
                        }
                }
            }
            closedir($h);
        }

        // iterate static files
        $staticdir = $this->config->webrootpath . $this->config->staticdir;
        foreach (scandir($staticdir, @constant('SCANDIR_SORT_NONE')) as $file) {
            $file_path = $staticdir . '/' . $file;
            if ($file[0] === '.' || !is_file($file_path) || $fs->getModificationTime($file_path) >= $aging_time) {
                continue;
            }
            $src_file = str_replace('.gz', '', $file);
            if (isset($file_list[$src_file])) {
                continue;
            }
            switch (pathinfo($src_file, PATHINFO_EXTENSION)) {
                case 'js':
                case 'css':
                case 'gif':
                case 'png':
                case 'jpg':
                case 'svg':
                case 'ico':
                    unlink($file_path);
                    $file_path_orig = $file_path . '.orig';
                    if (file_exists($file_path_orig)) {
                        unlink($file_path_orig);
                    }
                    break;
            }
        }
    }
}