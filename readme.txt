=== WP Property Feed Connector for RealHomes Theme ===
Contributors: ultimatewebuk
Tags: Vebra, Alto, Vebra, Vebralive, LetMC, Real Estate, Estate Agent, BLM, Real Homes, Realhomes, Real Places, Realplaces, Inspiry, Property, Properties, Rightmove, Zoopla
Plugin URI: http://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=RealHomes
Requires at least: 3.5
Tested up to: 6.2
Requires PHP: 5.4
Stable tag: 1.53
License: GPL2

Automatically feeds Alto, Jupix, Vebra, LetMC or BLM (Rightmove) property details into the popular Real Homes and Real Places real estate themes. Requires the WP Property Feed plugin.


== Description ==
# WP Property Feed Connector for Real Homes and Real Places Themes
Automatically feed Alto, Jupix, Vebra, LetMC or BLM (Rightmove) property details into the popular Real Homes and Real Places real estate themes. This is a zero-maintenance plugin that means estate agents can avoid having to re-enter property details from their back office software into their WordPress website. Requires the [WP Property Feed plugin](https://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=RealHomes).  If you�re using a different theme to Real Homes, our [WP Property Feed plugin](https://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=RealHomes) can be customised to automatically feed searchable property details with any WP theme.

## Requirements

This plugin requires;
  - The [WP Property Feed plugin](https://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=RealHomes)
  - The [Real Homes Theme](https://themeforest.net/item/real-homes-wordpress-real-estate-theme/5373914) OR the [Real Places Theme](https://themeforest.net/item/real-places-responsive-wordpress-real-estate-theme/12579089)

== Installation ==
Once you have installed and set up your Real Homes / Real Places theme and the WP Property Feed Plugin you simply install this connector plugin and the rest is automatic.  You can download and install this plugin using the built in WordPress plugin installer in the wp-admin, just search for \"WP Property Feed Real Homes\" and install the plugin then \"Activate\" to make it active.  Once active the connector will automatically update the Real Homes properties each time the WP Property Feed plugin updates from the feeds (normally every hour).  In the settings for WP Property Feed you will see a new Real Homes tab.  The tab will show the last 10 automatic updates that were performed and has a checkbox to allow you to run the connector immediately.
It is advised that you set a long time out (max_execution_time) in your php.ini file as feed downloads can take a long time.

== Screenshots ==
1. Settings screen. Shows log of updates

== Changelog ==
* First version released 20th January 2018
* 1.2 Version added import resume
* 1.3 Restricted image processing to new entries only
* 1.4 Fixed image viewing and importing when servered from external CDN
* 1.5 Made plugin backward compatible with early php versions
* 1.6 Made to work with realhomes child themes
* 1.7 Fixed hiding of duplicate feed properties admin menu
* 1.8 Fixed issue with separate realhomes wppf schedule
* 1.9 Added support for Real Places theme too
* 1.10 Fixed issue with city taxonomy tag not poopulating
* 1.11 Pull through POA property pricing
* 1.12 Setup additional POA scenarios and fixed taxonomy naming clash
* 1.13 Fixed dequeue of google maps
* 1.14 Made cnd urls use https
* 1.15 Re-instated google maps API key in core plugin for LetMC and BLM feeds
* 1.16 Update to stop creation of duplicate attachments
* 1.17 Update to theme name detection for latest naming
* 1.18 Removed theme detection, just assume that if the connector is active the real theme is in place
* 1.19 Fixed image preview issue with mixed protocols
* 1.20 Fixed update frequency
* 1.21 Agent details only updated at creation to allow more control with the theme
* 1.22 Minor fix to features and address formatting
* 1.23 Fix to image synching
* 1.24 Fixed issue getting thrumbnails for Vebra feeds
* 1.25 Fixed Video URL thumbnail image missing
* 1.26 Fixed issue with missing description
* 1.27 Added options for property categories and also added option to exclude let type in the features
* 1.28 Make floorplans display even if no name is given
* 1.29 Added option to show EPCs and Floorplans in the gallery
* 1.30 Excluded For Sale and To Let from property title as its obvious
* 1.31 Fixed error in the storing the epc and floorplan option
* 1.32 Fixed issue picking up the status 
* 1.33 Fixed issue with Vimeo video link
* 1.34 Added property data purge action
* 1.35 Set the published date correctly from feed
* 1.36 Featured override removed for LetMC, BLM and Hystreet
* 1.37 Fixed issue with post meta duplications
* 1.38 Prevent pdf documents appearing in the gallery
* 1.39 Added filename to gallery images and added action post update property
* 1.40 Corrected typo in new action and added PDF mime type
* 1.41 Added a check to look for private agents to prevent duplicate agent details
* 1.43 Added better feed resumption features for slow servers
* 1.44 Fixed issue with Post Prefix not displaying
* 1.45 Added schedule check which re-instates the wordpress cron if missing
* 1.46 Updated compatibility/tested up too 
* 1.47 Updated videos to use new meta data and identify 360 tours
* 1.48 Updated to add the Tenure to main body text if present
* 1.49 Make use of additional fields for tenure if set
* 1.50 Added creation of Tenure and CouncilTaxBand additional fields
* 1.51 Added Tenure mapping for old versions of Realhomes
* 1.52 Added standardised date formating to fix feed variations
* 1.53 Stop php warning on image delivery from CDN