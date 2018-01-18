<?php

return array(
    'autostart' => false,

    // /path/to/web/root (without trailing slashes)
    'webrootpath' => '',
    // /sub/dir/where/files/are/processed/ (with both leading and trailing slashes)
    'webrooturi' => '',
    // /uri/of/static/files (./ - relative to ressio directory)
    'staticdir' => './s',

    'fileloader' => 'file',
    // (./ - relative to ressio directory)
    'fileloaderphppath' => './fetch.php',
    'filehashsize' => 6,
    // (./ - relative to ressio directory)
    'cachedir' => './cache',
    'cachettl' => 24 * 60 * 60,

    'allowedcdn' => array(
        'ajax.aspnetcdn.com',
        'ajax.googleapis.com',
        'ajax.microsoft.com',
        'cdn.bootcss.com',
        'cdn.jsdelivr.net',
        'cdn.jquerytools.org',
        'cdn.materialdesignicons.com',
        'cdn.optimizely.com',
        'cdn.rawgit.com',
        'cdn.widgets.webengage.com',
        'cdnjs.cloudflare.com',
        'code.ionicframework.com',
        'code.jquery.com',
        'maxcdn.bootstrapcdn.com',
        'oss.maxcdn.com',
        'rawgit.com',
        's.w.org',
        's0.wp.com',
        's1.wp.com',
        's2.wp.com',
        's3.wp.com',
        'twemoji.maxcdn.com',
        'www.parsecdn.com',
    ),

    'html' => array(
        'gzlevel' => 5,
        'forcehtml5' => false,
        'mergespace' => true,
        'removecomments' => true,
        'urlminify' => true,
        'sortattr' => true,
        'removedefattr' => true,
        'removeiecond' => true
    ),

    'css' => array(
        'mergeheadbody' => true,
        'crossfileoptimization' => false,
        'inlinelimit' => 4096,
        'loadurl' => false,
        'loadcdn' => true,
        'merge' => true,
        'checklinkattributes' => true,
        'checkstyleattributes' => true,
        'mergeinline' => 'head',
        'minifyattribute' => false,
        'excludeminifyregex' => null,
        'excludemergeregex' => null
    ),

    'js' => array(
        'mergeheadbody' => true,
        'autoasync' => true,
        'forceasync' => false,
        'forcedefer' => false,
        'crossfileoptimization' => false,
        'inlinelimit' => 4096,
        'loadurl' => false,
        'loadcdn' => true,
        'merge' => true,
        'wraptrycatch' => false,
        'checkattributes' => true,
        'mergeinline' => 'head',
        'minifyattribute' => false,
        'excludeminifyregex' => null,
        'excludemergeregex' => null
    ),

    'img' => array(
        'minify' => true,
        'minifyrescaled' => false,
        'jpegquality' => 90,
        'origsuffix' => '.orig',
        'execoptim' => array(
            'bmp' => null,
            'gif' => null,
            'ico' => null,
            'jpg' => null,
            'png' => null,
            'svg' => null,
            'svgz' => null,
            'tiff' => null,
            'webp' => null
        )
    ),

    'amdd' => array(
        'handler' => 'plaintext',
        'cacheSize' => 1000,
        'dbPath' => './vendor/amdd/devices',
        'dbUser' => '...',
        'dbPassword' => '...',
        'dbHost' => 'localhost',
        'dbDatabase' => '...',
        'dbTableName' => 'amdd',
        'dbDriver' => 'pgsql:host=localhost;port=5432;dbname=...',
        'dbDriverOptions' => array()
    ),

    'rddb' => array(
        'timeout' => 3,
        'proxy' => false,
        'proxy_url' => 'tcp://127.0.0.1:3128',
        'proxy_login' => false,
        'proxy_pass' => ''
    ),

    'plugins' => array(
/*
        'Ressio_Plugin_Rescale' => array(
            'bufferwidth' => 0,
            'hiresimages' => true,
            'hiresjpegquality' => 80,
            'keeporig' => false,
            'scaletype' => 'fit',
            'setdimension' => true,
            'templatewidth' => 960,
            'wideimgclass' => 'wideimg',
            'wrapwideimg' => false
        ),
*/
/*
        'Ressio_Plugin_Lazyload' => array(
            'image' => true,
            'iframe' => true,
            'srcset' => true
        )
*/
    ),

    'di' => array(
        'cache' => 'Ressio_Cache_File',
        'cssCombiner' => 'Ressio_CssCombiner',
        'cssMinify' => 'Ressio_CssMinify_Ress',
        'cssOptimizer' => 'Ressio_CssOptimizer',
        'deviceDetector' => 'Ressio_DeviceDetector_Rddb',
        'dispatcher' => 'Ressio_Dispatcher',
        'filelock' => 'Ressio_FileLock_flock',
        'filesystem' => 'Ressio_Filesystem_Native',
        'htmlOptimizer' => 'Ressio_HtmlOptimizer_Pharse',
//      'htmlOptimizer' => 'Ressio_HtmlOptimizer_Stream',
//      'htmlOptimizer' => 'Ressio_HtmlOptimizer_Dom',
        'imgOptimizer' => 'Ressio_ImgOptimizer',
        'imgOptimizer.gif' => 'Ressio_ImgOptimizer_GD',
        'imgOptimizer.jpg' => 'Ressio_ImgOptimizer_GD',
        'imgOptimizer.png' => 'Ressio_ImgOptimizer_GD',
        'imgOptimizer.webp' => 'Ressio_ImgOptimizer_GD',
        'imgOptimizer.svg' => 'Ressio_ImgOptimizer_SvgGz',
        'imgRescaler' => 'Ressio_ImgRescale_GD',
        'jsCombiner' => 'Ressio_JsCombiner',
        'jsMinify' => 'Ressio_JsMinify_Jsmin',
        'urlRewriter' => 'Ressio_UrlRewriter'
    )
);
