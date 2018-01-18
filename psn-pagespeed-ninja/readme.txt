=== PageSpeed Ninja ===

Contributors: pagespeed
Tags: page speed, optimizer, minification, gzip, render blocking css
Requires at least: 4.0.1
Tested up to: 5.0
Stable tag: 0.9.19
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Make your site load faster & fix PageSpeed issues with one click: Gzip compression, render blocking critical CSS/JavaScript, browser caching & more.

== Description ==

PageSpeed Ninja is the ultimate Wordpress performance plugin. You can make your site load faster on desktop and mobile, fixing Google PageSpeed Insights issues with one click.

* Easily enable Gzip file compression
* Fix render blocking CSS and JavaScript
* Improve Critical Rendering Path and auto-generate above-the-fold critical CSS
* Minify HTML, JavaScript and CSS files
* Combine and inline Javascript and CSS
* Defer loading of Javascript and CSS
* Optimize style / script order
* Compress all images to optimize size
* Defer images by lazy loading with optional low-quality image placeholders
* Leverage browser caching and server-side caching
* Optimize your images accurately for nearly 10,000 different mobile browsers thanks to the included AMDD database – one of the most comprehensive mobile device databases available.
* And MUCH more, based on 10+ years of experience in mobile-optimizing over 200 000 websites.

## Why PageSpeed Ninja?

We’ve been optimizing web on mobile for over a decade now (you might know one of our popular projects, [Lazy Load XT](https://github.com/ressio/lazy-load-xt) on Github). PageSpeed Ninja for Wordpress is the result of 10+ years of experience in optimizing the performance over 200 000 websites on mobile. We believe you won't find a similar, easy to use, all-in-one package of performance boosting features anywhere else.

We’ve built heaps of unique features to make sure your site loads super fast, like the above-the-fold critical CSS generation method, not seen in any other plugins.

We’d love your feedback – always feel free to send us your questions, thoughts and suggestions.

## Before you install

According to our stats, our plugin improves speed of 4 out of 5 sites. However in some cases, certain theme and plugin combinations (particularly related to caching and optimization) cause incompatibility issues. Therefore our plugin might not be suitable for everyone. In order for you to see how PageSpeed Ninja could work on your site, we created a simple tool where you can test your site before installation. **We highly recommend** you visit [PageSpeed.Ninja](http://pagespeed.ninja) and test your site beforehand.

## Uninstallation

When the plugin is deleted, it will automatically revert all settings on your site back to way they were before installing this plugin. It restores all optimized images and removes /s directory with optimized JS and CSS files. Also all changes in .htaccess files are reverted back.
Please note that this restoration may not work reliably if in the meanwhile there have been any conflicts with other plugins, e.g. if the other plugins dynamically create/edit/remove files (including the files backed up by this plugin).

## Feedback, Bug reports, Logging possible issues

We welcome all questions, comments, suggestions, issue reports. Contact us to be added to our private tester Facebook group.

PageSpeed Ninja logs all php errors in wp-content/plugins/pagespeedninja/includes/error_log.php file (see Troubleshooting section in Advanced tab of the PageSpeed Ninja settings page)
If you find a problem, would be great if you can also send that file along.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/psn-pagespeed-ninja` directory, or install the plugin through the WordPress plugins screen directly. We recommend taking a backup of your site first, just like with any other new plugin.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->PageSpeed Ninja screen to configure the plugin
4. After you have installed the plugin, navigate to Settings->PageSpeed Ninja and enable optimization levels suggested by Google's PageSpeed Insights. By default all optimizations are disabled. The plugin then optimizes images, JS and CSS files, and modifies .htaccess files as required to fix issues identified by Google PageSpeed Insights.

== Frequently Asked Questions ==

= Does this plugin have any conflicts with Yoast or any of the other SEO plugins out there? =

The PageSpeed Ninja plugin should work pretty well with most other plugins without issues. However, if some SEO plugins try to do some of the same things as this plugin, then conflicts could be possible especially if gzip compression is enabled. However that is pretty unlikely.

== Screenshots ==

1. See improvement suggestions in one place and fix with single click
2. Fine tune to get the best performance using advanced settings

== Upgrade Notice ==

None