=== Popups for Divi ===
Contributors: strackerphil-1
Tags: popup, marketing, divi
Requires at least: 3.0.1
Tested up to: 5.2.3
Stable tag: trunk
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A quick and easy way to create Popup layers inside the Divi Visual Builder!

== Description ==

No configuration needed. Simply activate the plugin and add the CSS class "popup" and ID tag to a divi section. To open the popup you only need to place a link on the page with the URL pointing to the section ID.

# ℹ️ How it works

1. Edit a section and set the **section ID** to "`newsletter-optin`" and the **section CSS class** to "`popup`"
2. Add a link (or button, etc) on the page and set the **link URL** to "`#newsletter-optin`"
3. **That's all**. Save the page! When a visitor clicks on your new link the popup will be opened.

# ⭐️ Additional details

Check out the [Plugin website](https://philippstracker.com/divi-popup/) for more details. You'll find:

* Examples
* CSS class options
* JS API documentation
* WP filter documentation

Tested in all major browsers on Windows and Mac: Chrome, Firefox, Safari, IE 11, Edge!

# 🎂 Popups for Divi turns 2

Wow, it's already two years since the beginning of Popups for Divi!

It has been a great time and we've learned a lot. During that time we added a ton of features and have created a stable and powerful marketing plugin.

To celebrate the anniversary, we have created a six-day course that teaches you everything about the plugin. It walks you through the basics of creating your first Popup, shows possible ways to customize your Popup layouts and goes into advanced techniques and usages of the plugin.

> "The instruction emails really helped me to understand how to use the plugin correctly!"

The course is available in your **wp-admin Dashboard** right after you install and activate the plugin. Check out the screenshots to see the form. Also, have a look in the FAQ section, if you want to disable this feature.

# 🥳 Want more?

If you want to get the most out of Divi, you need to have a look at **[Divi Areas Pro](https://philippstracker.com/divi-areas-pro)** to get additional features:

> * An **admin UI** to create and configure your popups
> * Choose **Area Type**: Popup, Inline, Fly-in, Hover
> * A **beautiful UI** that blends in perfectly with Divi
> * Add **advanced triggers** to your Areas:
>  * On click
>  * On hover
>  * On scroll
>  * After delay
>  * On Exit
> * Customize the Area **Display**
>  * Show on certain pages
>  * Show on certain devices
>  * Show for certain user roles or guests
> * Customize Area **Behavior**
>  * Show/Hide the Close button
>  * Display the Area once per hour, day, week, ...
> * Flexible **position for Inline Areas**
>  * Replace/extend the page header
>  * or Footer
>  * or Comment section
>  * or actually *any* Divi section inside the page content
> * It comes with an extended version of the **JS API**
> * **Great documentation** built into the plugin and an online knowledge base
> * and much more...
>
> 👉 [Learn more about **Divi Areas Pro**](https://philippstracker.com/divi-areas-pro/) (with screenshots!)

== Installation ==

Install the plugin from the WordPress admin page "Plugins > Install"

or

1. Upload `popups-for-divi` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How much performance does Popups for Divi need? =

Actually none! We designed it with maximum page performance in mind. Our tests did show literally no change in page loading speed.

The plugin will add a single line of CSS to the page output and load two files that currently are only about 31 kb in size (9kb gzipped). Both files are cached by the browser and used on all other pages of your website.

Those two files are the JS API and the popup CSS rules that center the popup, dim the background, etc.

= Is Popups for Divi compatible with Caching plugins? =

Yes, absolutely! There is no dynamic logic in the PHP code like other popup plugins have. Popups for Divi is a little bit of PHP but the main logic sits inside the small javascript library it provides. It works on the client-side and therefore is not restricted by caching plugins.

= Is this plugin compatible with every Divi version? =

This plugin is kept compatible with the latest Divi release.

We did test it with all releases since the initial Divi 3.0 release. Possibly it will also work with older versions

= Does this plugin also work with other themes/site builders? =

Yes, actually it will! But out of the box it is optimized to work with Divi 3.x. If you use any other theme or site builder then you need to change the default options of the plugin via the `evr_divi_popup-js_data` filter.

For more details visit https://philippstracker.com/divi-popup/

= Does this plugin display any ads? =

No. This plugin is free and does not display any ads. In fact, the plugin does not have a UI at all.

Popups for Divi is just that - a plain popup plugin, not our marketing strategy!

Since version 1.6.0 we now offer a six-day email course that shows you how the plugin works. You will see a notification in your wp-admin dashboard right after activating the plugin. This is not an advertisement - you do not land in a newsletter list, but only receive those six onboarding mails.

= Do you collect any details from my website? =

No, we do not collect or transmit any data from your website.

Since version 1.6.0 there is one exception: We now offer an email course that shows you how the plugin works. You will see a notification in your wp-admin dashboard right after activating the plugin. When you opt-in to receive the onboarding emails we will transmit the details you entered (your name and email address) to the plugin website.

Those details are only used to send you the onboarding emails (6 emails) and for nothing else.

= Is there a way to hide the onboarding notice? =

Yes, there is!

Since 1.6.0 the plugin offers an onboarding course that consists of 6 emails. We offer this course right after plugin activation in your wp-admin "Dashboard" page (nowhere else).

This onboarding notice is displayed to administrator users only. Once the user clicks on the "Dismiss" button, the message is never displayed again for them.

You can also globally disable the onboarding notice by defining the constant [`DISABLE_NAG_NOTICES`](https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices#Disable_Nag_Notices) in your wp-config.php or themes function.php

= I need to revert to an older version =

When you experience any problems with the plugin, I would love you to first head over to the [support forum](https://wordpress.org/support/plugin/popups-for-divi/) and briefly share your issue with me.

Here's how you can revert to an older version of the plugin:

1. Go to the [Advanced View](https://wordpress.org/plugins/popups-for-divi/advanced/) Page and scroll down to the bottom.
2. Pick your version and click "Download" (you can choose any version since 1.5.1)
3. Now go to your wp-admin Panel and open the Plugins list
4. Deactivate and Delete the Popups for Divi plugin! *Note: You will not lose any data, but while the plugin is deactivated/missing your Popups might be visible like normal page content.*
5. On the Plugins page click "Add New" button in the top and then click on "Upload Plugin"
6. Select the .zip file which you downloaded in Step 2 and upload it. Activate and you're done!

Alternatively you can replace the `popups-for-divi` folder via FTP: Extract the .zip file which you downloaded in Step 2 and upload it to your `/wp-content/plugins` folder.

= I have more questions or need help =

Please first visit the [**plugin website**](https://philippstracker.com/divi-popup/), as it includes examples and documentation that could answer your questions.

If that does not help, then head over to the [**support forum**](https://wordpress.org/support/plugin/popups-for-divi/) and post a new thread.

When you need additional features, then please have a look at our the Premium plugin [**Divi Areas Pro**](https://philippstracker.com/divi-areas-pro/) which comes with a lot of cool options!

We cannot provide customization and development via the forum. If you need that, [**you can hire us**](https://philippstracker.com/go/inquire-popup-change/) to create some special triggers, layouts or other changes.

== Screenshots ==

1. This is a normal Divi page, before adding a popup.
2. Step 1: Modify the section and add the "popup" class, as well as an ID.
3. Step 2: Add the popup-ID as a button link. Clicking this button will show the popup.
4. That's all. Save the page and open it up without the editor. All popups are initially hidden.
5. Click on the "Contact" button displays the popup. You can style the Divi section (change the width, background-color, etc) or customize the CSS of the popup...
6. ...check out the extensive API documentation and popup samples on philippstracker.com
7. Our free email course walks you through every aspect of the plugin - from the basics to advanced use-cases and techniques.

== Changelog ==

= 1.6.3 =
* Fix animation glitch in Safari/iPhone that displayed the Popup too small when using Divis "Zoom" open animation.
* Fix logic that did not recognize Popups with upper-case letters in the Popup IDs.
* Fix issue with transparent Popup background.
* Fix CSS rule that allows custom box-shadow styles.

= 1.6.2 =
* Fix JS error in front.js

Plugin tested with WordPress 5.2.3 and Divi 3.27.4

= 1.6.0 =
* Change Popup behavior: The size now matches the width of your Divi section! 🤩
* Add an dashboard notification to sign up for the six-day onboarding course.
* Add popup support via Blurb "Content Link".
* Add Google reCaptcha integration! Tested with CF7, but works with any plugin.
* Add new WP filter options to customize dark-mode and box-shadow style.
* Add new Popup Class: `no-shadow` removes the default box-shadow.
* Add new Popup Class: `close-alt` removes the background color of the Close Button.
* Fix Popup shadow: The box-shadow fo the Divi Section overrides the default box-shadow. *Style it your way!*
* Fix select list behavior in Firefox.
* Fix Popup width on iPhone 6 and earlier.
* Tweak the CSS that makes the page body un-scrollable while a popup is open.
* Tweak the JS library for easier maintenance and better performance.

= 1.5.1 =
* Fix broken exit-intent initialization (sorry for that!)
* Fix bug where exit intent did trigger in some form fields, e.g. when closing auto-complete suggestions or changing an option in a select list.
* Fix setting "animateSpeed" that can be set via the WP filter. It was ignored until now.

= 1.5.0 =
* Fixed the exit-intent behavior - now it triggers, even when you move the mouse very sloooowly
* New JS API to turn literally *any* element on the page into a popup: `DiviPopup.register('#the-id')`
* New JS API to configure popups after they were registered: `DiviPopup.config('#the-id', 'on-mobile', false)`

= 1.4.0 =
* In Divis Visual Builder you now see each popup ID right above the popup. This makes it easier for you to correctly link popups
* When the class "single" is added to a popup, it will close any other popup that was currently visible
* Custom styles are now applied to modules inside popups, for example, custom button styles

Thanks for your feedback and all your fantastic support for the Popups for Divi plugin! Check out the webpage for documentation, e.g. for the single class.

= 1.3.2 =
* Update plugin icon and assets
* Improved code and documentation

= 1.3.1 =
* Improve: Popup sections now support animations! Add those "Number Counters" and zoom-in images in your next popup.
* Fix: Certain Divi settings could prevent popups from being opened, as the trigger-click-event could be intercepted by a different Divi module. Not anymore. The unstoppable Popup!
* Fix: Minor javascript errors fixed, when "triggerClassPrefix" was set to false via the WP filter.

= 1.3.0 =
* Added trigger: Use class name "show-popup-demo" to show the popup "demo" on click. Can be used to turn any element into a popup trigger!
* Improve: The default value of the popup-debug option is taken from WP_DEBUG. You can see debug output in your browser console while WP_DEBUG is enabled.
* Improve: Better compatibility with Divi Child-themes (and Non-Divi themes).
* Fix: Custom "close" buttons inside a popup will now close the popup before following the link or scrolling the page.
* Fix: Popups will now work with themes that do not have the default "#page-container" div.

= 1.2.3 =
* Fix: jQuery "invalid expression" error is gone.

= 1.2.2 =
* Fix: Fully compatible with Divi 3.1.16 and higher.
* Add: Plugin is now backward compatible until PHP 5.2.4 - before this, the plugin required PHP 5.4 or higher.

= 1.2.1 =
* Improve: Faster and smoother handling of popup resizing, without an interval timer!
* Added Javascript event: $('.popup').on('DiviPopup:Init', ...)
* Added Javascript event: $('.popup').on('DiviPopup:Show', ...)
* Added Javascript event: $('.popup').on('DiviPopup:Hide', ...)
* Added Javascript event: $('.popup').on('DiviPopup:Blur', ...)
* Added Javascript event: $('.popup').on('DiviPopup:Focus', ...)

= 1.2.0 =
* Feature: Popups now support Divi loading animations!
* Improve: Popups will now correctly limit the size after the contents are changed, e.g. when accordion is expanded.
* Bugfix: The Popups For Divi plugin now waits until Divi could initialize all components, before creating popups.

= 1.1.0 =
* Feature: Yay! All Popups now have a Close button in the top-right corner by default.
* Feature: Pressing the escape key will now close the currently open popup.
* Improve: The active popup now has an additional CSS class `is-open` that can be used for styling inactive popups.
* Improve: CSS and JS code is now minified.

Thanks for your feedback and all the positive comments and reviews you posted! You are awesome :)

= 1.0.3 =
* Improve: Apply custom modules styles to elements inside a popup
* Fix: Correct popup preview in the Visual Builder

= 1.0.2.3 =
* Minor: Fixes in the readme.txt and naming of assets/language files

= 1.0.2 =
* Minor: Added link to the plugin documentation inside the plugins list
* Minor: Make plugin translatable

= 1.0.1 =
* Added: Support for lazy-load plugin

= 1.0 =
* Initial public release
* Added trigger: Click
* Added trigger: `on-exit`
* Added JavaScript API: DiviPopup.openPopup()
* Added JavaScript API: DiviPopup.closePopup()
* Added JavaScript API: DiviPopup.showOverlay()
* Added JavaScript API: DiviPopup.hideOverlay()
* Added WordPress filter: `evr_divi_popup-js_data`
