=== Developer Tools ===
Contributors: JaworskiMatt, peepso, rsusanto
Tags: report, system, debug, audit, server, info, phpinfo, git, repository, branch, branches, repositories, unexpected output, unexpected, output
Requires at least: 4.4
Tested up to: 4.9
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Analyze your WordPress environment, settings, phpinfo(), git repositories and activation issues.

== Description ==
This plugin is a collection of tools useful that come in handy developing Wordpress plugins and debugging PeepSo.

Every report can be exported to a text or html file. It can also be used to help third parties debug your website without granting them wp-admin access.

The current features incude:

1. real time PeepSo logs
1. overview of most important Wordpress settings, environment variables and server config
1. complete phpinfo()
1. overview of branches for git tracked plugins and themes
1. catch & debug "unexpected output during plugin activation"


== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the "Developer Tools" menu to access the features

== Screenshots ==

1. The System Report
2. phpinfo()
3. Git branches overview
4. Sample System Report text export

== Changelog ==
= 3.0.1 =
* Better user count logic

= 3.0.0 =
* Added PeepSo Log facilities
* Rebranding

= 2.0.0 =
* Added "unexpected output during activation" debugger
* Redesigned UI
* Big refactoring aiming ant more modular code

= 1.0.1 =
* Added the git capability
= 1.0.0 =
* Original release
