=== CDN Tools ===
Contributors: reaperhulk
Donate link: http://langui.sh/cdn-tools
Tags: cdn, sideload, speed, content, distribution, performance, cloudfiles, files, media, accelerator, cloud
Requires at least: 2.8
Tested up to: 2.9.1
Stable tag: 0.99

CDN Tools is a plugin designed to help you drastically speed up your blog's load time by loading data onto a content distribution network (CDN).

== Description ==

[CDN Tools](http://langui.sh/cdn-tools/ "CDN Tools Home") is a WordPress plugin that allows you to load javascript and media files to an external server to drastically speed page loads.  You can sideload data to a commercial CDN or just load your larger JS libraries (prototype, jquery) for **free** from Google's servers.  CDN Tools has been designed to be as easy to use as possible, so give it a shot!  Your blog's readers will thank you.  At this time the only commercial CDN supported is Cloud Files.  Check out the plugin homepage to view a screencast.

[View complete changelog](http://langui.sh/cdn-tools/ "CDN Tools Home").

== Installation ==

1. Upload cdn-tools/ into wp-content/plugins/
2. Activate the plugin via the 'Plugins' menu.
3. Go to the 'CDN Tools' option under settings.
4. Enable the Google AJAX libs if you wish to load jquery and prototype from Google's servers.
5. If you have a CDN, enable your CDN and enter the login and password.
6. Once you have saved the prefs a "Load Files" button will appear.  Click it!
7. You're all set!

== Frequently Asked Questions ==

= Could you add XXXX CDN compatibility? =

Unfortunately I'm likely too busy to do this for your CDN, but I'd be happy to answer questions if you need some assistance in developing your own!  If you write a plugin, please let me know so I can merge it into the main distribution.

= I found a bug. What do I do? =

Contact me!  This is a complex plugin and the only way I can ensure it's robust is to get bug reports and fix the problems that crop up.  Please include as much information as you can when reporting (PHP version, your blog's site, the error you saw and how you got to the page that caused it, et cetera).

== CDN Loader Dev FAQ ==

= How do I create a new CDN class? =

Create a directory in cdn_classes named whatever the shortname will be.  Then create a file named loader.php inside with the same basic structure as the cloudfiles loader and do everything right. Then edit the cdn-tools.php file to add your item.  Easy as pie (if your pie is made of complexity).  I promise I'll write some better instructions so third parties can contribute CDN plugins at some point.  Currently CDN Tools probably makes some assumptions that are Cloud Files specific.

== Changelog ==
= 0.99 =
* Log retention support (in advanced options)
* Servicenet flag (in advanced options)
* Both these features were added courtesy of John Dickinson

= 0.98 =
* WordPress 2.9 support.  CDN Tools can now handle the image edit system introduced in WP 2.9.
* Better error handling for a few scenarios.
* As of this release WP 2.7 is no longer supported.  It might work, but future releases will almost certainly break it.

= 0.97 - 12/9/2009 =
* Initial sideload AJAX used an absolute path with the siteurl.  This should have been a relative path or the adminurl to accommodate situations where FORCE_SSL_ADMIN is defined.  Thanks to John for identifying the bug.

= 0.96 - 12/4/2009 =
* Upgraded and (temporarily) forked CloudFiles API to fix bugs related to safe_mode and content type detection.  This means you shouldn't see badcontenttype exceptions or "stuck at 0%" errors any more!
* Improved error reporting for AJAX uploads (including timeout errors, which are now set at 90 seconds)
* Removed loading.gif in favor of WP native spinner

= 0.95 - 10/25/2009 =
* Upgraded to CF API 1.5.1. For users within the Rackspace network who have $_ENV['RACKSPACE_SERVICENET'] defined, CDN Tools will now use the servicenet instead of the public network.  This should speed sideloading of large files as well as not consuming your bandwidth.

= 0.94 - 10/19/2009 =
* Upgraded to CF API 1.4.  This should resolve some cURL issues users have experienced and removes the dependence on a png hack for RHEL4 users.
* Fixed major issue with Google AJAX CDN.  Previously users were unable to switch between visual and HTML mode on the edit post page.

= 0.93 - 10/8/2009 =
* A partial fix for issues with customers who have full file paths stored in their postmeta table.  This is not a complete fix, but should help some (most?) users.

= 0.92 – 10/5/2009 =
* Major upgrade to support WP 2.8. If you are a previous user of CDN Tools you MUST unload files and then load them again. Please let me know if you have issues because several major changes were made.

= 0.81 – 5/17/2009 =
* Major upgrade to the initial load for attachments. Now done via AJAX with percentage progress.
* Additional exception handling (this is still incomplete)
* Upgrade to CF API 1.3.0.
* Wordpress MU compatible (credit John Keyes)

= 0.72 – 2/25/2009 =
* First public release, no changes from previous.

= 0.72 – 2/22/2009 =
* Small bugfixes
* WP Super Cache compatibility.

= 0.71 – 2/22/2009 =
* Workaround for a MIME/PHP bug on RHEL4.

= 0.7 – 2/17/2009 =
* Nearing completion of initial feature set.
* UI rework (again)

= 0.6 – 2/15/2009 =
* Huge improvements on all fronts

= 0.4 – 2/8/2009 =
* Initial sideload support.

= 0.2 – 2/7/2009 =
* First release outside of my own work (seeded to Major Hayden).
* GoogleAJAX for Prototype, jQuery, Dojo, and mootools (free for anyone to use)
* Uploading JS to CloudFiles (only supported CDN presently)
* Supports opting out of CDN’ing wp-admin scripts.



== Screenshots ==

1. Settings panel with only Google turned on.
2. Settings panel with Cloud Files turned on but no data loaded yet.
3. Settings panel with Cloud Files on and all files loaded.


== License ==

    Copyright 2009 Paul Kehrer

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

