=== File Change Notifier ===
Contributors: Techbreeze IT Solutions
Tags: file monitoring, security, file change, notifier
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 1.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Monitors file changes in the WordPress installation directory and notifies the admin.

== Description ==

File Change Notifier is a WordPress plugin that monitors file changes in the WordPress installation directory. It detects any additions, modifications, or deletions of files and notifies the admin via email. The plugin also provides a settings page to manage ignored files and configure email notifications.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/file-change-notifier` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings -> File Change Notifier screen to configure the plugin.

== Frequently Asked Questions ==

= How do I configure email notifications? =

Go to the plugin's settings page and add one or more email addresses where you want to receive notifications. You can also set an alert threshold to control how many changes trigger an email.

= Can I ignore certain files or directories? =

Yes, you can specify files or directories to be ignored on the plugin's settings page.

= How often does the plugin check for file changes? =

The plugin checks for file changes every hour by default.

== Screenshots ==

1. **Settings Page** - Configure email notifications, ignored files, and alert thresholds.
   ![Settings Page](screenshot-1.png)
2. **Dashboard Widget** - View recent file changes directly from your WordPress dashboard.
   ![Dashboard Widget](screenshot-2.png)

== Changelog ==

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.0 =
* Initial release.

== License ==

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
