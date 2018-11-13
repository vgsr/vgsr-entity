=== VGSR Entity ===
Contributors: offereins
Tags: vgsr, bestuur, dispuut, kast
Requires at least: 4.6
Tested up to: 4.9.8
Stable tag: 2.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Structured organization and presentation of VGSR entities.

== Description ==

The following entities are setup with their structured details:

1. Bestuur - Logo, season year, positions with users or names
2. Dispuut - Logo, since date, ceased date
3. Kast - Logo, address, since date, ceased date

A coupling with BuddyPress enables you to associate members with Disputen and Kasten. In the respective settings page you can select the profile field which holds the post ID by which to match the post type item with the members.

=== Theme compatibility ===

This plugin is developed with no particular design in mind, so the entities should fit nicely in any theme. Themes can support this plugin by adding the necessary style adjustments in their theme's `style.css` file.

== Installation ==

If you download VGSR Entity manually, make sure it is uploaded to "/wp-content/plugins/vgsr-entity/".

Activate VGSR Entity in the "Plugins" admin panel using the "Activate" link.

This plugin is not hosted in the official WordPress repository. Instead, updating is supported through use of the [GitHub Updater](https://github.com/afragen/github-updater/) plugin by @afragen and friends.

== Changelog ==

= 2.1.0 =
* Bestuur: Added post search results for matched (user) names assigned in positions
* Bestuur: Added column to the All Posts admin page list to display signed positions
* Dispuut: Added post search results for matched user names assigned as members
* Kast: Added post search results for matched user names assigned as residents
* Kast: Added column to the All Posts admin page list to display address details
* Kast: Fixed errors when opening the New Post admin page
* Fixed losing the correct post status when updating an archived entity
* BuddyPress: include only current members/residents in the displayed entity members count
* BuddyPress: enabled searching member profiles by entity post title
* BuddyPress: limited the members avatar list when on non-singular pages
* BuddyPress: separated display of leden and oud-leden in avatar lists
* WPSEO: fixed breadcrumb issues on plugin and non-plugin pages

= 2.0.0 =
* Full rewrite of entity logic and entity metadata
* Added Archived post status
* Changed template for entity parent page to use archive.php
* Added integration with BuddyPress
* Added support for WordPress SEO

= 1.0.2 =
* Fixed bug in adjacency logic

= 1.0.1 =
* Fixed menu widget logic
* Fixed template loading
* Added entity adjacency sorting

= 1.0.0 =
* Refactored code to use the singleton pattern
* Fixed refreshing rewrite rules logic on bestuur update
* Added kast meta: current occupants
* Added kast meta: previous occupants

= 0.1 =
* Initial release
