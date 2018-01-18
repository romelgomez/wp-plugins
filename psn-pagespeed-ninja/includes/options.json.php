[
  {
    "title": "<?php _e('General'); ?>",
    "items": [
      {
        "name": "htmloptimizer",
        "title": "<?php _e('HTML Parser'); ?>",
        "tooltip": "<?php _e('Switch to new experimental HTML parser or to fast page optimizer with full JavaScript, CSS, and images optimization, but with limited subset of HTML optimizations (removing of HTML comments and IE conditional comments are supported only).'); ?>",
        "type": "select",
        "values": [
          {
            "pharse": "<?php _e('Use Standard full HTML parser'); ?>"
          },
          {
            "stream": "<?php _e('Use Fast simple HTML parser'); ?>"
          },
          {
            "dom": "<?php _e('Use libxml HTML parser (experimental)'); ?>"
          }
        ],
        "class": "streamoptimizer",
        "default": "stream",
        "presets": [
          "pharse",
          "stream",
          "stream",
          "dom"
        ]
      },
      {
        "name": "distribmode",
        "title": "<?php _e('Distribute method'); ?>",
        "tooltip": "<?php _e('Distribution method of the compressed JS and CSS files to the client device. Different methods perform better on different server setup. \'Direct\' method distributes them in the standard method of the webserver (like any other files), but most likely then gzip compression and caching may be disabled. \'Apache mod_rewrite+mod_headers\' is the fastest method, but requires Apache with both mod_rewrite and mod_headers modules enabled. \'Apache mod_rewrite\' and \'PHP\' are identical from the performance point of view; the only difference is that \'Apache mod_rewrite\' requires Apache webserver, while \'PHP\' generates not-so-beautiful URLs like /s/get.php?abcdef.js instead of just /s/abcdef.js.'); ?>",
        "type": "select",
        "values": [
          {
            "direct": "<?php _e('Direct'); ?>"
          },
          {
            "apache": "<?php _e('Apache mod_rewrite+mod_headers'); ?>"
          },
          {
            "rewrite": "<?php _e('Apache mod_rewrite'); ?>"
          },
          {
            "php": "<?php _e('PHP'); ?>"
          }
        ],
        "default": "direct"
      },
      {
        "name": "staticdir",
        "title": "<?php _e('Static files directory'); ?>",
        "tooltip": "<?php _e('Directory path for stored combined JS and CSS files (relative to WordPress installation directory).'); ?>",
        "type": "text",
        "default": "/s"
      },
      {
        "name": "http2",
        "title": "<?php _e('HTTP/2 Server Push'); ?>",
        "tooltip": "<?php _e('Support HTTP/2 Server Push by using HTTP Link header (according to W3C Preload Working Draft).'); ?>",
        "type": "checkbox",
        "default": 0
      },
      {
        "name": "footer",
        "title": "<?php _e('Support badge'); ?>",
        "tooltip": "<?php _e('Displays a small text link to the PageSpeed Ninja website in the footer (\'Optimized with PageSpeed Ninja\').'); ?>",
        "type": "checkbox",
        "default": 0
      },
      {
        "name": "allow_ext_atfcss",
        "title": "<?php _e('Remote critical CSS generation'); ?>",
        "tooltip": "<?php _e('Allow use of pagespeed.ninja critical CSS generation service.'); ?>",
        "type": "checkbox",
        "default": 0
      },
      {
        "name": "allow_ext_stats",
        "title": "<?php _e('Send anonymous statistics'); ?>",
        "tooltip": "<?php _e('Allow Send anonymous usage data to pagespeed.ninja.'); ?>",
        "type": "checkbox",
        "default": 0
      }
    ]
  },
  {
    "title": "<?php _e('Troubleshooting'); ?>",
    "items": [
      {
        "name": "errorlogging",
        "title": "<?php _e('Error logging'); ?>",
        "tooltip": "<?php _e('Log all PHP\'s errors, exceptions, warnings, and notices. Please, check content of this file and send it to us if there are messages related to pagespeedninja plugin.'); ?>",
        "type": "errorlogging",
        "default": 0
      },
      {
        "name": "clear_cache",
        "title": "<?php _e('Cache'); ?>",
        "tooltip": "<?php _e('Clear cache of optimized javascript, css, and other internal files.'); ?>",
        "type": "clear_cache",
        "default": ""
      },
      {
        "name": "drop_database",
        "title": "<?php _e('AMDD Database'); ?>",
        "tooltip": "<?php _e('Clear cache of mobile devices capabilities.'); ?>",
        "type": "drop_database",
        "default": ""
      },
      {
        "name": "exclude_js",
        "title": "<?php _e('Manage Javascript URLs'); ?>",
        "tooltip": "<?php _e('Exclude marked URLs from merging and minifying.'); ?>",
        "type": "exclude_js",
        "default": ""
      },
      {
        "name": "exclude_css",
        "title": "<?php _e('Manage CSS URLs'); ?>",
        "tooltip": "<?php _e('Exclude marked URLs from merging and minifying.'); ?>",
        "type": "exclude_css",
        "default": ""
      }
    ]
  },
  {
    "id": "AvoidLandingPageRedirects",
    "title": "<?php _e('Avoid landing page redirects'); ?>",
    "type": "speed"
  },
  {
    "id": "EnableGzipCompression",
    "title": "<?php _e('Enable compression'); ?>",
    "type": "speed",
    "items": [
      {
        "name": "html_gzip",
        "title": "<?php _e('Gzip compression'); ?>",
        "tooltip": "<?php _e('Compress mobile pages using Gzip for better performance. Recommended.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "htaccess_gzip",
        "title": "<?php _e('Gzip compression in .htaccess'); ?>",
        "tooltip": "<?php _e('Update .htaccess files in wp-includes, wp-content, and uploads directories for better front-end performance (for Apache webserver).'); ?>",
        "type": "checkbox",
        "default": 0,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "html_sortattr",
        "title": "<?php _e('Reorder attributes'); ?>",
        "tooltip": "<?php _e('Reorder attributes for better gzip compression. Recommended. Disable if there is a conflict with another extension that rely on exact attribute order.'); ?>",
        "type": "checkbox",
        "class": "streamdisabled",
        "default": 0,
        "presets": [
          0,
          0,
          1,
          1
        ]
      }
    ]
  },
  {
    "id": "LeverageBrowserCaching",
    "title": "<?php _e('Leverage browser caching'); ?>",
    "type": "speed",
    "items": [
      {
        "name": "htaccess_caching",
        "title": "<?php _e('Caching in .htaccess'); ?>",
        "tooltip": "<?php _e('Update .htaccess files in wp-includes, wp-content, and uploads directories for better front-end performance (for Apache webserver).'); ?>",
        "type": "checkbox",
        "default": "auto",
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "loadexternal",
        "title": "<?php _e('Load external files'); ?>",
        "tooltip": "<?php _e('Allow to load, cache and optimize external URLs.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      }
    ]
  },
  {
    "id": "MainResourceServerResponseTime",
    "title": "<?php _e('Reduce server response time'); ?>",
    "type": "speed",
    "items": [
      {
        "name": "caching",
        "title": "<?php _e('Caching'); ?>",
        "tooltip": "<?php _e('Enable server-side page caching.'); ?>",
        "type": "cachingcheckbox",
        "default": 1
      },
      {
        "name": "caching_ttl",
        "title": "<?php _e('Caching time-to-live'); ?>",
        "tooltip": "<?php _e('Caching time-to-live in minutes. Cached data will be invalidated after specified time interval.'); ?>",
        "type": "number",
        "units": "<?php _e('min'); ?>",
        "default": 15
      },
      {
        "name": "dnsprefetch",
        "title": "<?php _e('DNS Prefetch'); ?>",
        "tooltip": "<?php _e('Use DNS prefetching for external domain names.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      }
    ]
  },
  {
    "id": "MinifyCss",
    "title": "<?php _e('Minify CSS'); ?>",
    "type": "speed",
    "items": [
      {
        "name": "css_merge",
        "title": "<?php _e('Merge CSS files'); ?>",
        "tooltip": "<?php _e('Merge several CSS files into single one for faster loading.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          1,
          1,
          1,
          1
        ]
      },
      {
        "name": "css_excludelist",
        "title": "<?php _e('Exclude files list'); ?>",
        "tooltip": "<?php _e('Exclude listed files (one per line) from merging. Partial matching is used, so \'debug\' line affects all URLs containing \'debug\' as a substring. It is recommended to list CSS files that are used only on few pages to have single merged file containing CSS files included on every page.'); ?>",
        "type": "textarea",
        "default": ""
      },
      {
        "name": "css_mergeinline",
        "title": "<?php _e('Merge embedded styles'); ?>",
        "tooltip": "<?php _e('Merge embedded CSS styles in &lt;style&gt;...&lt;/style&gt; blocks. Disable for dynamically-generated embedded CSS styles.'); ?>",
        "type": "select",
        "values": [
          {
            "0": "<?php _e('Disable'); ?>"
          },
          {
            "head": "<?php _e('In &lt;head&gt; only'); ?>"
          },
          {
            "1": "<?php _e('Everywhere'); ?>"
          }
        ],
        "default": "head",
        "presets": [
          "0",
          "head",
          "1",
          "1"
        ]
      },
      {
        "name": "css_di_cssMinify",
        "title": "<?php _e('Minify CSS Method'); ?>",
        "tooltip": "<?php _e('Optimizes CSS for better performance. This optimizes CSS correspondingly (removes unnecessary whitespaces, unused code etc.).'); ?>",
        "type": "select",
        "values": [
          {
            "none": "<?php _e('None'); ?>"
          },
          {
            "ress": "<?php _e('RESS'); ?>"
          },
          {
            "csstidy": "<?php _e('CSS Tidy'); ?>"
          },
          {
            "both": "<?php _e('RESS + CSSTidy'); ?>"
          }
        ],
        "default": "ress",
        "presets": [
          "none",
          "ress",
          "both",
          "both"
        ]
      },
      {
        "name": "css_minifyattribute",
        "title": "<?php _e('Minify style attributes'); ?>",
        "tooltip": "<?php _e('Optimizes CSS styles in \'style\' attributes.'); ?>",
        "type": "checkbox",
        "default": 0,
        "presets": [
          0,
          0,
          1,
          1
        ]
      },
      {
        "name": "css_inlinelimit",
        "title": "<?php _e('Inline limit'); ?>",
        "tooltip": "<?php _e('Inline limit allows to inline small CSS (up to the specified limit) into the page directly in order to avoid sending additional requests to the server (i.e. speeds up loading). 1024 bytes is likely optimal for most cases, allowing inlining of small files while not inlining large ones.'); ?>",
        "type": "number",
        "units": "<?php _e('bytes'); ?>",
        "default": 4096,
        "presets": [
          4096,
          4096,
          4096,
          4096
        ]
      },
      {
        "name": "css_crossfileoptimization",
        "title": "<?php _e('Cross-files optimization'); ?>",
        "tooltip": "<?php _e('Optimize generated combined css file.'); ?>",
        "type": "checkbox",
        "default": 0,
        "presets": [
          0,
          0,
          1,
          1
        ]
      },
      {
        "name": "css_loadurl",
        "title": "<?php _e('Load external URLs'); ?>",
        "tooltip": "<?php _e('Load external files for merging. Disable if you use external dynamically generated CSS files.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "css_checklinkattributes",
        "title": "<?php _e('Keep extra link tag attributes'); ?>",
        "tooltip": "<?php _e('Don\'t merge stylesheet if its \'link\' tag contains extra attribute(s) (e.g. \'id\', in rare cases it might mean that javascript code may refer to this stylesheet html node).'); ?>",
        "type": "checkbox",
        "default": 0,
        "presets": [
          1,
          0,
          0,
          0
        ]
      },
      {
        "name": "css_checkstyleattributes",
        "title": "<?php _e('Keep extra style tag attributes'); ?>",
        "tooltip": "<?php _e('Don\'t merge stylesheet if its \'style\' tag contains extra attribute(s) (e.g. \'id\', in rare cases it might mean that javascript code may refer to this stylesheet html node).'); ?>",
        "type": "checkbox",
        "default": 0,
        "presets": [
          1,
          1,
          0,
          0
        ]
      }
    ]
  },
  {
    "id": "MinifyHTML",
    "title": "<?php _e('Minify HTML'); ?>",
    "type": "speed",
    "items": [
      {
        "name": "html_mergespace",
        "title": "<?php _e('Merge whitespaces'); ?>",
        "tooltip": "<?php _e('Removes empty spaces from the HTML code for faster loading. Recommended. Disable if there is a conflict with \'white-space: pre\' rule in CSS.'); ?>",
        "type": "checkbox",
        "class": "streamdisabled",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "html_removecomments",
        "title": "<?php _e('Remove comments'); ?>",
        "tooltip": "<?php _e('Removes comments from the HTML code for faster loading. Disable if there is a conflict with another extension (e.g. which uses JavaScript to extract content of comments).'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          1,
          1,
          1,
          1
        ]
      },
      {
        "name": "html_minifyurl",
        "title": "<?php _e('Minify URLs'); ?>",
        "tooltip": "<?php _e('Replaces absolute URLs (http://www.google.com/link) into relative URLs (/link) to reduce page size. Disable if there is a conflict with another extension (e.g. which uses JavaScript that requires all links to have a full domain name).'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          1,
          1,
          1,
          1
        ]
      },
      {
        "name": "html_removedefattr",
        "title": "<?php _e('Remove default attributes'); ?>",
        "tooltip": "<?php _e('Remove attributes with default values, e.g. type=\'text\' in &lt;input&gt; tag. It reduces total page size. Disable in the case of conflicts with CSS (e.g. \'input[type=text]\' selector doesn\'t match \'input\' element without \'type\' attribute).'); ?>",
        "type": "checkbox",
        "class": "streamdisabled",
        "default": 0,
        "presets": [
          0,
          0,
          1,
          1
        ]
      },
      {
        "name": "html_removeiecond",
        "title": "<?php _e('Remove IE conditionals'); ?>",
        "tooltip": "<?php _e('Remove IE conditional commenting tags for non-IE browsers. Disable if there is a conflict with another extension that rely on this tags.'); ?>",
        "type": "checkbox",
        "default": 0,
        "presets": [
          0,
          0,
          1,
          1
        ]
      }
    ]
  },
  {
    "id": "MinifyJavaScript",
    "title": "<?php _e('Minify JavaScript'); ?>",
    "type": "speed",
    "items": [
      {
        "name": "js_merge",
        "title": "<?php _e('Merge script files'); ?>",
        "tooltip": "<?php _e('Merge several JavaScript files into single one for faster loading. Recommended.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          1,
          1,
          1,
          1
        ]
      },
      {
        "name": "js_excludelist",
        "title": "<?php _e('Exclude files list'); ?>",
        "tooltip": "<?php _e('Exclude listed files (one per line) from merging. Partial matching is used, so \'tinymce\' line affects all URLs containing \'tinymce\' as a substring. It is recommended to list JavaScript files that are used only on few pages to have single merged file containing JavaScript files included on every page.'); ?>",
        "type": "textarea",
        "default": ""
      },
      {
        "name": "js_mergeinline",
        "title": "<?php _e('Merge embedded scripts'); ?>",
        "tooltip": "<?php _e('Merge embedded JavaScripts in &lt;script&gt;...&lt;/script&gt; code blocks. Disable for dynamically-generated embedded JavaScript codes.'); ?>",
        "type": "select",
        "values": [
          {
            "0": "<?php _e('Disable'); ?>"
          },
          {
            "head": "<?php _e('In &lt;head&gt; only'); ?>"
          },
          {
            "1": "<?php _e('Everywhere'); ?>"
          }
        ],
        "default": "head",
        "presets": [
          "0",
          "head",
          "1",
          "1"
        ]
      },
      {
        "name": "js_autoasync",
        "title": "<?php _e('Auto async'); ?>",
        "tooltip": "<?php _e('Allows to relocate script tags for better merging. Blocking scripts generates \'inplace\' html content and in general should not be relocated. Disable if you use blocking scripts, e.g. synchronous Google Adsense ad code.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "js_di_jsMinify",
        "title": "<?php _e('Minify Javascript Method'); ?>",
        "tooltip": "<?php _e('Optimizes JavaScript for better performance. This optimizes JavaScript correspondingly (removes unnecessary whitespaces, unused code etc.).'); ?>",
        "type": "select",
        "values": [
          {
            "none": "<?php _e('None'); ?>"
          },
          {
            "jsmin": "<?php _e('JsMin'); ?>"
          }
        ],
        "default": "none",
        "presets": [
          "none",
          "none",
          "jsmin",
          "jsmin"
        ]
      },
      {
        "name": "js_minifyattribute",
        "title": "<?php _e('Minify event attributes'); ?>",
        "tooltip": "<?php _e('Optimizes JavaScript in event attributes (e.g. \'onclick\' or \'onsubmit\').'); ?>",
        "type": "checkbox",
        "class": "streamdisabled",
        "default": 0,
        "presets": [
          0,
          0,
          1,
          1
        ]
      },
      {
        "name": "js_inlinelimit",
        "title": "<?php _e('Inline limit'); ?>",
        "tooltip": "<?php _e('Inline limit allows to inline small JavaScript (up to the specified limit) into the page directly in order to avoid sending additional requests to the server (i.e. speeds up loading). 1024 bytes is likely optimal for most cases, allowing inlining of small JavaScript files while not inlining large files like jQuery.'); ?>",
        "type": "number",
        "units": "<?php _e('bytes'); ?>",
        "default": 4096,
        "presets": [
          4096,
          4096,
          4096,
          4096
        ]
      },
      {
        "name": "js_crossfileoptimization",
        "title": "<?php _e('Cross-files optimization'); ?>",
        "tooltip": "<?php _e('Optimize generated combined javascript file.'); ?>",
        "type": "checkbox",
        "default": 0,
        "presets": [
          0,
          0,
          1,
          1
        ]
      },
      {
        "name": "js_loadurl",
        "title": "<?php _e('Load external URLs'); ?>",
        "tooltip": "<?php _e('Load external files for merging. Disable if you use external dynamically generated JavaScript files.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "js_wraptrycatch",
        "title": "<?php _e('Wrap to try/catch'); ?>",
        "tooltip": "<?php _e('Browser stope executing of JavaScript code if a parsing or executiong error is found, so that merged JavaScript files may be stopped in tha case of error in one of source files. This option enables wrapping of each merged JavaScript files into try/catch block to continue execution after error, but note that it may reduce performance in some browsers.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          1,
          1,
          1,
          1
        ]
      },
      {
        "name": "js_checkattributes",
        "title": "<?php _e('Keep extra script tag attributes'); ?>",
        "tooltip": "<?php _e('Don\'t merge javascript if its \'script\' tag contains extra attribute (e.g. \'id\', in rare cases it might mean that javascript code may refer to this stylesheet html node).'); ?>",
        "type": "checkbox",
        "default": 0,
        "presets": [
          1,
          0,
          0,
          0
        ]
      },
      {
        "name": "js_widgets",
        "title": "<?php _e('Optimize integrations (Facebook. Google Plus, etc.)'); ?>",
        "tooltip": "<?php _e('Optimize loading of popular javascripts widgets like integration with Facebook. Twitter, Google Plus, Gravatar, etc.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "wp_mergewpemoji",
        "title": "<?php _e('Optimize Emoji loading'); ?>",
        "tooltip": "<?php _e('Change the way WP Emoji script is loaded.'); ?>",
        "type": "select",
        "values": [
          {
            "default": "<?php _e('Default Wordpress behaviour'); ?>"
          },
          {
            "merge": "<?php _e('Merge with other scripts'); ?>"
          },
          {
            "disable": "<?php _e('Don\'t load'); ?>"
          }
        ],
        "default": "merge",
        "presets": [
          "merge",
          "merge",
          "merge",
          "merge"
        ]
      }
    ]
  },
  {
    "id": "MinimizeRenderBlockingResources",
    "title": "<?php _e('Eliminate render-blocking JavaScript and CSS in above-the-fold content'); ?>",
    "type": "speed",
    "items": [
      {
        "name": "css_abovethefold",
        "title": "<?php _e('Above-the-fold CSS'); ?>",
        "tooltip": "<?php _e('Use generated above-the-fold CSS styles.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "css_abovethefoldcookie",
        "title": "<?php _e('Above-the-fold CSS cookie'); ?>",
        "tooltip": "<?php _e('Use cookie to embed above-the-fold CSS styles for first-time visitors only.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          1,
          1,
          1,
          1
        ]
      },
      {
        "name": "css_abovethefoldlocal",
        "title": "<?php _e('Local above-the-fold generation'); ?>",
        "tooltip": "<?php _e('Above-the-fold CSS styles may be generated either locally (directly in your browser), or externally using PageSpeed Ninja\'s service.'); ?>",
        "experimental": 1,
        "type": "checkbox",
        "default": 1,
        "presets": [
          1,
          1,
          1,
          1
        ]
      },
      {
        "name": "css_abovethefoldstyle",
        "title": "<?php _e('Above-the-fold CSS styles:'); ?>",
        "tooltip": "<?php _e('Above-the-fold CSS styles. Edit them manually or use link below to get autogenerated ones.'); ?>",
        "type": "abovethefoldstyle",
        "default": ""
      },
      {
        "name": "css_abovethefoldautoupdate",
        "title": "<?php _e('Auto update Above-the-fold CSS'); ?>",
        "tooltip": "<?php _e('Update above-the-fold CSS styles daily.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          1,
          1,
          1,
          1
        ]
      },
      {
        "name": "css_googlefonts",
        "title": "<?php _e('Google Fonts loading'); ?>",
        "tooltip": "<?php _e('The way to optimize loading of Google Fonts. \'Flash of invisible text\' to load font in a standard way at the beginning of html page, most browsers do not display text until the font is loaded. \'Flash of unstyled text\' to load font asynchronouslty and switch from default font to loaded one when ready. \'WebFont Loader\' to load fonts asynchronously using webfont.js library. \'None\' to disable optimization.'); ?>",
        "type": "select",
        "values": [
          {
            "none": "<?php _e('None'); ?>"
          },
          {
            "foit": "<?php _e('Flash of invisible text'); ?>"
          },
          {
            "fout": "<?php _e('Flash of unstyled text'); ?>"
          },
          {
            "async": "<?php _e('WebFont Loader'); ?>"
          }
        ],
        "default": "fout",
        "presets": [
          "none",
          "fout",
          "fout",
          "fout"
        ]
      },
      {
        "name": "css_nonblockjs",
        "title": "<?php _e('Non-blocking Javascript'); ?>",
        "tooltip": "<?php _e('Load javascripts asynchronously with few seconds delay after webpage is displayed in browser.'); ?>",
        "experimental": 1,
        "type": "checkbox",
        "default": 0,
        "presets": [
          0,
          0,
          0,
          1
        ]
      }
    ]
  },
  {
    "id": "OptimizeImages",
    "title": "<?php _e('Optimize images'); ?>",
    "type": "speed",
    "items": [
      {
        "name": "img_minify",
        "title": "<?php _e('Optimization'); ?>",
        "tooltip": "<?php _e('Reduce image sizes using the selected rescaling quality. The original image will be backed up with suffix \'.orig\' (image.jpg->image.orig.jpg).'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "img_driver",
        "title": "<?php _e('Images handler'); ?>",
        "tooltip": "<?php _e('Method to deal with images. By default PHP supports GD2 only, but may be configured to support ImageMagick API as well.'); ?>",
        "type": "imgdriver",
        "values": [
          {
            "gd2": "<?php _e('GD2'); ?>"
          },
          {
            "imagick": "<?php _e('ImageMagick'); ?>"
          }
        ],
        "default": "gd2"
      },
      {
        "name": "img_jpegquality",
        "title": "<?php _e('JPEG quality'); ?>",
        "tooltip": "<?php _e('You can set image rescaling quality between 0 (low) and 100 (high). Higher means better quality. Recommended level is 80%-95%.'); ?>",
        "type": "number",
        "units": "<?php _e('%'); ?>",
        "default": 90,
        "presets": [
          95,
          90,
          90,
          90
        ]
      },
      {
        "name": "img_scaletype",
        "title": "<?php _e('Scale large images'); ?>",
        "tooltip": "<?php _e('By default images are rescaled on mobile for faster loading.'); ?>",
        "type": "select",
        "values": [
          {
            "none": "<?php _e('None'); ?>"
          },
          {
            "fit": "<?php _e('Fit'); ?>"
          },
          {
            "prop": "<?php _e('Fixed Ratio'); ?>"
          },
          {
            "remove": "<?php _e('Remove'); ?>"
          }
        ],
        "default": "fit",
        "presets": [
          "fit",
          "fit",
          "fit",
          "fit"
        ]
      },
      {
        "name": "img_bufferwidth",
        "type": "hidden",
        "default": 0
      },
      {
        "name": "img_templatewidth",
        "title": "<?php _e('Template width (reference)'); ?>",
        "tooltip": "<?php _e('Desktop template width. Required for \'Fixed Ratio\' image option.'); ?>",
        "type": "number",
        "units": "<?php _e('px'); ?>",
        "default": 960
      },
      {
        "name": "img_wrapwide",
        "title": "<?php _e('Wrap wide images'); ?>",
        "tooltip": "<?php _e('Wrap images that are wider than half of the screen into a centered &lt;span&gt;. This makes a floating (align=right or align=left attribute) image to fill full horizontal size if it\'s wider than 50% of the screen width (otherwise there is a narrow column of text near the image).'); ?>",
        "type": "checkbox",
        "default": 0
      },
      {
        "name": "img_wideimgclass",
        "title": "<?php _e('Wide image wrapper class'); ?>",
        "tooltip": "<?php _e('Value of \'class\' attribute for wrapped wide images.'); ?>",
        "type": "text",
        "default": "wideimg"
      }
    ]
  },
  {
    "id": "PrioritizeVisibleContent",
    "title": "<?php _e('Prioritize visible content'); ?>",
    "type": "speed",
    "items": [
      {
        "name": "img_lazyload",
        "title": "<?php _e('Lazy Load Images'); ?>",
        "tooltip": "<?php _e('Lazy load images with the Lazy Load XT script. Significantly speeds up the loading of image and/or video-heavy webpages.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "img_lazyload_iframe",
        "title": "<?php _e('Lazy Load Iframes'); ?>",
        "tooltip": "<?php _e('Lazy load iframes with the Lazy Load XT script.'); ?>",
        "type": "checkbox",
        "default": 1,
        "presets": [
          0,
          1,
          1,
          1
        ]
      },
      {
        "name": "img_lazyload_lqip",
        "title": "<?php _e('Low-quality image placeholders'); ?>",
        "tooltip": "<?php _e('Use low-quality image placeholders instead of empty areas.'); ?>",
        "type": "checkbox",
        "default": 0
      },
      {
        "name": "img_lazyload_edgey",
        "title": "<?php _e('Vertical lazy loading threshold'); ?>",
        "tooltip": "<?php _e('Expand visible page area (viewport) in vertical direction by specified amount of pixels, so that images start to load even if they are not actually visible yet.'); ?>",
        "type": "number",
        "units": "<?php _e('px'); ?>",
        "default": 0
      },
      {
        "name": "img_lazyload_skip",
        "title": "<?php _e('Skip first images'); ?>",
        "tooltip": "<?php _e('Skip lazy loading of specified number of images from the beginning of html page (useful for logos and other images that are always visible in the above-the-fold area).'); ?>",
        "type": "number",
        "default": 3,
        "presets": [
          10,
          3,
          1,
          0
        ]
      },
      {
        "name": "img_lazyload_noscript",
        "title": "<?php _e('Noscript position'); ?>",
        "tooltip": "<?php _e('Position to insert original image wrapped in noscript tag for browsers with disabled javascript (may be useful if your images styles rely on CSS selectors :first or :last). To don\'t generate noscript tag, set this option to \'None\'.'); ?>",
        "default": "after",
        "type": "select",
        "values": [
          {
            "after": "<?php _e('After'); ?>"
          },
          {
            "before": "<?php _e('Before'); ?>"
          },
          {
            "none": "<?php _e('None'); ?>"
          }
        ]
      },
      {
        "name": "img_lazyload_addsrcset",
        "title": "<?php _e('Generate srcset'); ?>",
        "tooltip": "<?php _e('Automatically generate srcset attribute with rescaled images.'); ?>",
        "experimental": 1,
        "type": "checkbox",
        "default": 0,
        "presets": [
          0,
          0,
          0,
          1
        ]
      }
    ]
  },
  {
    "id": "AvoidPlugins",
    "title": "<?php _e('Avoid plugins'); ?>",
    "type": "usability",
    "items": [
      {
        "name": "remove_objects",
        "title": "<?php _e('Remove embedded plugins'); ?>",
        "tooltip": "<?php _e('Remove all embedded plugins like Flash, ActiveX, Silverlight, etc.'); ?>",
        "type": "checkbox",
        "default": 1
      }
    ]
  },
  {
    "id": "ConfigureViewport",
    "title": "<?php _e('Configure the viewport'); ?>",
    "type": "usability",
    "items": [
      {
        "name": "viewport_width",
        "title": "<?php _e('Viewport width'); ?>",
        "tooltip": "<?php _e('Viewport width in pixels. Set to 0 (zero) to use device screen width (default).'); ?>",
        "type": "number",
        "units": "<?php _e('px'); ?>",
        "default": 0
      }
    ]
  },
  {
    "id": "SizeContentToViewport",
    "title": "<?php _e('Size content to viewport'); ?>",
    "type": "usability"
  },
  {
    "id": "SizeTapTargetsAppropriately",
    "title": "<?php _e('Size tap targets appropriately'); ?>",
    "type": "usability"
  },
  {
    "id": "UseLegibleFontSizes",
    "title": "<?php _e('Use legible font sizes'); ?>",
    "type": "usability"
  }
]