=== AppThemer Crowdfunding ===

Author URI: http://appthemer.com
Plugin URI: http://appthemer.com
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=contact@appthemer.com&item_name=Donation+for+Crowdfunding
Contributors: SpencerFinnell, adampickering
Tags: download, downloads, e-store, eshop, digital downloads, crowd funding, crowdfunding, crowdsource, 
Requires at least: 3.5
Tested up to: 3.5
Stable Tag: 0.7
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A crowdfunding platform in the likes of Kickstarter and Indigogo

== Description ==

Crowdfunding by [AppThemer](http://appthemer.com/fundify.html) is a funding platform for WordPress. An extension that seamlessly integrates with Easy Digital Downloads, the Crowdfunding plugin lets you crowd source everything from films, games, and music to art, design, and technology.

"Backers" can pledge specified amounts of money towards a project and receive rewards for their contributions. Pledge amounts are only collected if a campaign reaches its goal in the time specified.

Run your own crowdfunding website for anything you can imagine.

Note: The AppThemer Crowdfunding plugin is designed to work alongside the PayPal Adaptive Payments gateway so that pledges are collected but no funds are taken until the campaign reaches its goal. The plugin will also work along side any other gateway that Easy Digital Downloads supports but funds will be taken immediately.

= Features =

Features of the plugin include:

* Crowd fund anything you want!
* Frontend form submission to easily collect campaign information.
* Compatible with PayPal Adaptive Payments API for pre-approved purchases. When a person commits to funding your project they are only charged if your campaign reaches its pre-defined goal.
* Compatible with all standard payment gateways that Easy Digital Downloads supports, i.e Stripe, PayPal Pro/Express (payments will be charged immediately).
* Works both as a donation plugin and a Crowdfunding plugin.
* Easy to theme and integrate into your own site which uses Easy Digital Downloads.

= Premium Themes =

The first compatible theme that has been released is called "Fundify" from [AppThemer](http://appthemer.com/fundify.html).

= Get Involved =

Developers can contribute to the source code of the project on [GitHub](https://github.com/appthemer/crowdfunding)

== Installation ==

1. Install the Easy Digital Downloads plugin and activate it, if you haven't already.
2. Install the AppThemer Crowdfunding plugin and activate it.
3. (Optional) Install the PayPal Adaptive Payments plugin. (https://easydigitaldownloads.com/extension/paypal-adaptive-payments/)
4. Create a new page for your frontend submission form add this shortcode to the page: [appthemer_crowdfunding_submit] - (this is optional).
5. Go to Campaigns > Add New to create a new campaign.

== Frequently Asked Questions ==

= Does this plugin work with any theme? =

Yes. However, it won't look much different than a standard EDD install. Templates must be modified/created to output relevant crowdfunding information (display amount funded, etc).

== Changelog ==

= 0.7: X =

* New: Funding types -- Fixed (default) or flexible funding. Allow a higher commission to be set on flexible funding.
* New: Settings to specifiy the page that contains the frontend submission shortcode, as well as an FAQ page.

= 0.6: March 18 =

* Fix: When adding rewards via frontend, make sure blank fields are added in the correct spot.
* Fix: Always show backer rewards in ascending price order, no matter how they are entered.
* Fix: Don't use getTimestamp() method on DateTime (not supported in 5.2) -- format() instead.
* New: Added french translation to plugin.
* New: Create a user account on campaign submission. This allows campaign authors to edit their campaigns
* New: jQuery validate frontend submission before server validation.
* New: Collect a contact email separate from PayPal for contacting the campaign author

= 0.5: March 14, 2013 =

* Fix: Don't kill the frontend form submission when there are no errors.
* Fix: Save campaign author/organization on frontend form submission.
* Fix: Properly save preview image and/or video on frontend form submission.

= 0.4: March 12, 2013 =

* Release with Fundify theme.

= 0.3-alpha: March 11, 2013 =

* Fix: Better theme support. Check for some EDD templates, as well as standard WordPress files.
* Fix: Actually collect funds!
* Fix: Better errors when submitting a campaign, or collecting funds.
* New: If PayPal Adaptive Payments is not active, track normal payments.
* New: Themes without explicit support will output default EDD variable pricing.

= 0.2-alpha: March 7, 2013 =

* Fix: Better loading of exports.
* Fix: Shipping fixes, backers, etc.
* Fix: Load backers at the correct time.
* Fix: Count amount based on preapproval, not total earnings.
* New: Dont activate if EDD is not active.
* New: Text filters.
* New: Update readme.txt

= 0.1-alpha: March 6, 2013 =

* First official alpha release!
