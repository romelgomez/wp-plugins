<?php

/*
 * RESSIO Responsive Server Side Optimizer
 * https://github.com/ressio/
 *
 * @copyright   Copyright (C) 2013-2018 Kuneri, Ltd. All rights reserved.
 * @license     GNU General Public License version 2
 */

class Ressio_Plugin_UrlLoader extends Ressio_Plugin
{
    protected $mimeToExt = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
        'image/vnd.microsoft.icon' => 'ico',
        'image/x-icon' => 'ico',
        'text/css' => 'css',
        'text/javascript' => 'js',
        'application/javascript' => 'js',
        'application/x-javascript' => 'js'
    );

    /**
     * @param Ressio_DI $di
     * @param null|stdClass $params
     * @throws ERessio_UnknownDiKey
     * @throws ERessio_UnknownClassName
     */
    public function __construct($di, $params = null)
    {
        $params = $this->loadConfig(dirname(__FILE__) . '/config.json', $params);

        parent::__construct($di, $params);
    }

    /**
     * @param $event Ressio_Event
     * @param $optimizer IRessio_HtmlOptimizer
     * @param $node IRessio_HtmlNode
     * @throws ERessio_UnknownDiKey
     */
    public function onHtmlIterateTagIMGBefore($event, $optimizer, $node)
    {
        if ($optimizer->nodeIsDetached($node)) {
            return;
        }

        // @todo: parse srcset attribute
        if ($node->hasAttribute('src')) {
            $url = $node->getAttribute('src');
            $url = $this->loadUrl($url);
            if ($url !== null) {
                $node->setAttribute('src', $url);
            }
        }
    }

    /**
     * @param $event Ressio_Event
     * @param $optimizer IRessio_HtmlOptimizer
     * @param $node IRessio_HtmlNode
     * @throws ERessio_UnknownDiKey
     */
    public function onHtmlIterateTagSCRIPTBefore($event, $optimizer, $node)
    {
        // @todo find common patterns in embedded <script></script> blocks (GA, etc.)

        if ($optimizer->nodeIsDetached($node)) {
            return;
        }

        if ($node->hasAttribute('src')) {
            $url = $node->getAttribute('src');
            $url = $this->loadUrl($url, 'js');
            if ($url !== null) {
                $node->setAttribute('src', $url);
            }
        }
    }

    /**
     * @param $event Ressio_Event
     * @param $optimizer IRessio_HtmlOptimizer
     * @param $node IRessio_HtmlNode
     * @throws ERessio_UnknownDiKey
     */
    public function onHtmlIterateTagLINKBefore($event, $optimizer, $node)
    {
        if ($optimizer->nodeIsDetached($node)) {
            return;
        }

        if ($node->hasAttribute('rel') && $node->hasAttribute('href') && $node->getAttribute('rel') === 'stylesheet') {
            $url = $node->getAttribute('href');
            $url = $this->loadUrl($url, 'css', array($this, 'cssRebase'));
            if ($url !== null) {
                $node->setAttribute('href', $url);
            }
        }
    }

    /**
     * @param $url string
     * @param $defaultExt string|null
     * @param $callback callback|null
     * @return string|null
     * @throws ERessio_UnknownDiKey
     */
    protected function loadUrl($url, $defaultExt = null, $callback = null)
    {
        if (strpos($url, '//') === 0) {
            $url = 'http:' . $url;
        } elseif (strpos($url, '://') === false) {
            return null;
        }

        $parsed = @parse_url($url);
        $host = $parsed['host'];
        if (!in_array($host, $this->params->allowedhosts, true)) {
            return null;
        }

        /** @var string[] $deps */
        $deps = array(
            'plugin_urlloader',
            $url
        );

        $cache = $this->di->cache;
        $cache_id = $cache->id($deps, 'file');
        $result = $cache->getOrLock($cache_id);

        if (!is_string($result)) {

            switch ($this->params->mode) {
                case 'curl':
                    list($content, $contentType) = $this->loadCurl($url);
                    break;
                case 'fsock':
                    list($content, $contentType) = $this->loadFsock($url);
                    break;
                case 'stream':
                default:
                    list($content, $contentType) = $this->loadStream($url);
                    break;
            }

            if ($defaultExt === null) {
                if (isset($this->mimeToExt[$contentType])) {
                    $defaultExt = $this->mimeToExt[$contentType];
                } else {
                    $content = null;
                }
            }

            $hash = substr(sha1($url), 0, $this->config->filehashsize);
            // @todo use another dir for loaded files???
            $targetFile = $this->config->webrootpath . $this->config->staticdir . '/' . $hash . '.' . $defaultExt;

            if ($callback !== null && $content !== null) {
                $content = call_user_func_array($callback, array(&$content, $url, &$targetFile));
            }

            if ($content !== null) {
                $this->di->filesystem->putContents($targetFile, $content);
                $s = $this->di->urlRewriter->filepathToUrl($targetFile);
            } else {
                $s = '';
            }

            if ($result) {
                $cache->storeAndUnlock($cache_id, $s);
            }

            $result = $s;
        }

        return $result === '' ? null : $result;
    }

    /**
     * @param $content string
     * @param $origUrl string
     * @param $targetFile string
     * @return string
     * @throws ERessio_UnknownDiKey
     */
    public function cssRebase($content, $origUrl, $targetFile)
    {
        $targetUrl = $this->di->urlRewriter->filepathToUrl($targetFile);

        $minifyCss = new Ressio_CssMinify_None;
        $minifyCss->setDI($this->di);
        return $minifyCss->minify($content, dirname($origUrl), dirname($targetUrl));
    }

    /**
     * @param $url
     * @return string[]|null[]
     */
    protected function loadStream($url)
    {
        $opts = array('http' =>
            array(
                'timeout' => $this->params->timeout,
                'ignore_errors' => true
            )
        );
        $context = stream_context_create($opts);

        $content = file_get_contents($url, false, $context);
        if ($content === false) {
            return array(null, null);
        }

        $contentType = null;
        foreach ($http_response_header as $line) {
            if (strpos($line, 'Content-Type: ') === 0) {
                $contentType = substr($line, 14);
            }
        }
        return array($content, $contentType);
    }

    /**
     * @param $url
     * @return string[]|null[]
     */
    protected function loadCurl($url)
    {
        if (!function_exists('curl_init')) {
            return array(null, null);
        }

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_HEADER, 0);
        curl_setopt($c, CURLOPT_AUTOREFERER, 1);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $this->params->timeout);
        curl_setopt($c, CURLOPT_TIMEOUT, $this->params->timeout);
        $content = curl_exec($c);
        $contentType = curl_getinfo($c, CURLINFO_CONTENT_TYPE);

        curl_close($c);

        if ($content === false) {
            return array(null, null);
        }

        return array($content, $contentType);
    }

    /**
     * @param $url
     * @return string[]|null[]
     */
    protected function loadFsock($url)
    {
        $parsed = @parse_url($url);
        if (isset($parsed['user'], $parsed['pass'])) {
            return array(null, null);
        }

        $ssl = false;
        if (isset($parsed['scheme'])) {
            $scheme = $parsed['scheme'];
            if ($scheme === 'https') {
                $ssl = true;
            }
        }

        $host = $parsed['host'];
        $port = isset($parsed['port']) ? $parsed['port'] : ($ssl ? 443 : 80);
        $path = $parsed['path'];
        if (isset($parsed['query'])) {
            $path .= '?' . $parsed['query'];
        }

        $fp = @fsockopen(($ssl ? 'ssl://' : '') . $host, $port, $errno, $errstr, $this->params->timeout);
        if (!$fp) {
            return array(null, null);
        }

        $request = "GET {$path} HTTP/1.0\r\n";
        $request .= "Host: {$host}\r\n";
        $request .= "Connection: close\r\n\r\n";

        if (!fwrite($fp, $request)) {
            return array(null, null);
        }

        $rs = '';
        while ($d = fread($fp, 32768)) {
            $rs .= $d;
        }

        $info = stream_get_meta_data($fp);
        fclose($fp);
        if ($info['timed_out']) {
            return array(null, null);
        }

        list($header, $content) = explode("\r\n\r\n", $rs, 2);
        $header = explode("\r\n", $header);

        $contentType = null;
        foreach ($header as $line) {
            if (strpos('Content-Type: ', $line) === 0) {
                $contentType = substr($line, 14);
            }
        }

        return array($content, $contentType);
    }
}
