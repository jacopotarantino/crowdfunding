#### 1.8.2: April 7, 2014

* Fix: Make sure failed funds are properly re-processed when trying again.
* Fix: If no rewards are added before saving/previewing allow them to be added when viewing again.
* Fix: Make sure quantity is always set when creating preapproval logs.
* Fix: Account for longer decimals if filtered via EDD

#### 1.8.1: February 4, 2014

* Fix: Properly set "bought" count to 0 on all pledge levels on new submissions.
* Fix: Remove default EDD stats box to avoid confusion.
* Fix: Make sure Campaign data can properly be deleted when edited.

#### 1.8: January 2, 2014

* New: Single payment processing for PayPal Adaptive Payments, Stripe, and WePay.
* New: Automatic payment processing (in batches) when a campaign is complete.
* Fix: Make sure all data is properly stored/saved when previewing/editing.
* Fix: Translation fixes.
* Tweaks: Various improvements and language updates.

#### 1.7.3.1: October 25, 2013

* Fix: Make sure the install script is properly run on activation.

#### 1.7.3: October 7, 2013

* Fix: Avoid activation of EDD is not active.
* Fix: Add filter to backer retrieval.
* Fix: Round default length.
* Fix: Various submission tweaks.
* Tweaks: Various improvements and language updates.

#### 1.7.2: September 12, 2013

https://github.com/Astoundify/crowdfunding/issues?milestone=17&page=1&state=closed

* Fix: Better payment tracking when pledging multiple times, deleting, etc.
* Fix: More consistent dates when editing/submitting/updating.
* Fix: Don't 404 when only the name is filled out when submitting a campaign.
* Fix: Go back to clearing the cart when viewing a new pledge.
* Fix: Make sure all data is exported via CSV.
* Fix: Always make sure the goal is an integer to avoid errors.
* Fix: When there are more than 11 pledge levels, make sure they can properly be selected.
* Fix: Add the Terms of Service back to the submission process.
* Tweaks: Various improvements and language updates.

#### 1.7.1: September 1, 2013

* Fix: Make sure translations are properly loaded.
* Fix: Make sure the goal on frontend and backend is always numeric when saved/output.
* Fix: Don't show blank contributions on the profile page.
* Fix: Proper default length when creating a campaign on the frontend.
* Fix: Valid markup for multicheck items. Props @Studio164a.
* Tweaks: Remove upgrade warning/blocker when activating/upgrading.

#### 1.7: August 27, 2013

https://github.com/Astoundify/crowdfunding/issues?milestone=16&page=1&state=closed

* New: Major: Submission process completely rewritten. Now much easier to include extra fields, rearrange fields, etc.
* Tweaks: Update language files, various other fixes.

#### 1.6.2: August 15, 2013

* New: Filter added to set minimum amount on donations.
* New: If donations only, have input blank by default.
* Fix: Make sure formatCurrency script is always loaded when needed.
* Fix: Make sure a default donation reward is added automatically in the backend.
* Fix: If a campaign expires, then the date is set to the future, remove expired flag.

#### 1.6.1: August 14, 2013

* Fix: Avoid duplicate function error when upgrading.

#### 1.6: August 12, 2013

https://github.com/Astoundify/crowdfunding/issues?milestone=14&page=1&state=closed

* New: MAJOR: [File changes/organization](https://github.com/Astoundify/crowdfunding/issues/246)
* New: MAJOR: Custom pledge amounts have been removed from the theme, and added to the plugin. [More](https://github.com/Astoundify/crowdfunding/issues/244)
* New: Support for multiple rewards of the same amount.
* New: Campaign authors are now emailed when a campaign is completed.
* Fix: Campaign length now respects initial length after publishing.
* Fix: All users should be able to select terms when submitting a campaign.
* Tweaks: Update language files, various other fixes.

#### 1.5: July 30, 2013

https://github.com/Astoundify/crowdfunding/issues?milestone=11&page=1&state=closed

* New: Themes can define support for tags and categories (they will not show if no support is declared).
* New: Ability to select multiple categories.
* New: Less strict payment processing. Allow for continuous reprocessing until all current payments are published.
* New: Ability to filter columns of exported data.
* New: Reinstate campaigns to allow for new funds to be pledged.
* New: Ability to have just custom pledge amounts, and no rewards.
* Fix: Better media permissions. Allow all users to upload, and only view their uploads.
* Fix: Don't empty the cart to avoid the "empty cart bug", and allow for multiple pledges.
* Tweaks: Update language files, various other fixes.

#### 1.4.1: July 6, 2013

https://github.com/astoundify/crowdfunding/issues?milestone=12&page=1&state=closed

* Fix: Bug with account creation / secure password error fixed.

https://github.com/astoundify/crowdfunding/issues?milestone=9&page=1&state=closed

* New: Show contributions in user dashboard shortcode.
* New: Add the option to fund forever (no end date).
* Fix: Better redirection after login.
* Fix: Show draft campaigns in user dashboard shortcode.
* Fix: Better editing/saving of campaigns. Avoid losing data.
* Fix: Alert the user if their username is already in use.

#### 1.3.1: June 7, 2013

https://github.com/Astoundify/crowdfunding/issues?milestone=10&state=closed

* Fix: Avoid undefined indexes when adding to cart which prevented some browsers from adding to cart.
* Fix: Always show terms of service when previewing/editing.
* Fix: Spelling errors.
* Tweaks: Update language files.

#### 1.3: June 2, 2013

https://github.com/Astoundify/crowdfunding/issues?milestone=8&page=1&state=closed

* New: Themes must declare support for certain features: campaign-edit, campaign-featured-image, campaign-video, campaign-widget.
* New: Preview and update campaigns before submitting for review.
* New: Edit reward levels that haven't been pledged once a campaign has started.
* New: Set limits and track pledges/campaigns when using PayPal Adaptive Payments to avoid breaking TOS.
* New: Support for embeddable campaign widgets (provided by theme via campaign-widget.php).
* New: Tracking of failed payment collection, with the ability to try to reprocess.
* New: 'Donation' funding type when no preapproval gateway is enabled.
* Fix: Remove single price option from WordPress admin panel to avoid confusion.
* Fix: Automatically set an end date when creating a campaign via the admin panel to avoid confusion.
* Fix: Don't increase backer count when in test mode and test mode logging isn't enabled.
* Tweak: Remove file download fields from frontend form submission.
* Tweak: Don't show funding types if only one type is enabled.

* Note: If using PayPal Adaptive Payments extension, it must be upgraded to the latest version.

#### 1.2: May 8, 2013

https://github.com/Astoundify/crowdfunding/issues?milestone=7&page=1&state=closed

* New: Allow for anonymous backers. Theme's need to add this functionality, as all information is still collected.
* Fix: Don't check day count for expired campaigns, use seconds instead (and correct timezone)
* Fix: Only track a "bought" pledge level when payment is moved from pending.
* Fix: Properly collect PayPal funds.
* Fix: More granular checking if a preapproval-supported gateway is being used.
* Fix: Remove error when viewing comments in WordPress admin.
* Fix: Better goal sanitization to prevent being reset to 0.
* Fix: Track when the project has manually been closed (theme still needs to display this value).

#### 1.1.1: May 2, 2013

* Fix: Don't try to load things before we have a chance to check if EDD is active to avoid errors.

#### 1.1: May 1, 2013

https://github.com/Astoundify/crowdfunding/issues?milestone=6&state=closed

* New: Gateway agnostic. Now supports collection via multiple preapproval gateways (like Stripe!)
* Fix: Export not always working.
* Fix: Don't show the Collect Funds button once funds have been collected.
* Fix: Browse by category now works again.
* Fix: Add shortcode descriptions to settings page.
* Fix: Use atcf_get_campaign() instead of accessing class directly.
* Fix: Send correct password on user registration.
* Fix: Don't output login form on submit page, but redirect to set login page instead.

#### 1.0: April 22, 2013

https://github.com/Astoundify/crowdfunding/issues?milestone=5&state=closed

* New: Ability to require authentication (registration/login) before submitting a campaign.
* New: Login and Register shortcodes can be placed on any page.
* Fix: Don't show collect funds button once they have already been collected.
* Fix: Limit cart to one pledge per checkout to avoid errors.
* Fix: Show all qualified campaigns on Export CSV section.
* Fix: Don't limit the number of backers to 20 (in terms of count, output, etc).
* Fix: Extra hooks in profile shortcode for better themeability.
* Fix: Update language of "Fixed vs Flexible" to "All-or-nothing vs Flexible" as well as better descriptors.
* Fix: Add Terms & Conditions to submission shortcode if they exist on EDD.
* Fix: Use display name on profile instead of nicename to avoid breaking author archive permalinks.
* Fix: Logged in users can now upload media when adding a campaign via the frontend.

#### 0.9: April 8, 2013

https://github.com/Astoundify/crowdfunding/issues?milestone=4&state=closed

* New: Set a limit on number of times a pledge amount can be purchased.
* New: Separate area for adding updates to a campaign during it's running.
* New: Site administrators can choose which funding types are available.
* New: Shipping becomes optional. Can toggle when submitting via frontend/backend.
* New: More social fields in profile on campaign page, and author page.
* New: Can set minimum and maximum campaign length in settings.
* Fix: Properly set category when using the frontend submission form.

#### 0.8.1: April 1, 2013

* Fix: Only show the users campaigns using profile shortcode.

#### 0.8: March 31, 2013

* New: [appthemer_crowdfunding_profile] Shortcode to allow users to edit their profile, and see their campaigns.
* New: Users can submit multiple campaigns without creating multiple accounts.
* New: Export campaign data of completed campaigns.
* New: Request payout of campaign.

#### 0.7: March 26, 2013

* New: Funding types -- Fixed (default) or flexible funding. Allow a higher commission to be set on flexible funding.
* New: Settings to specify the page that contains the frontend submission shortcode, as well as an FAQ page.
* Fix: Some more backward compatibility stuff for DateTime functionality on hosts running PHP < 5.3

#### 0.6: March 18, 2013

* Fix: When adding rewards via frontend, make sure blank fields are added in the correct spot.
* Fix: Always show backer rewards in ascending price order, no matter how they are entered.
* Fix: Don't use getTimestamp() method on DateTime (not supported in 5.2) -- format() instead.
* New: Added french translation to plugin.
* New: Create a user account on campaign submission. This allows campaign authors to edit their campaigns
* New: jQuery validate frontend submission before server validation.
* New: Collect a contact email separate from PayPal for contacting the campaign author

#### 0.5: March 14, 2013

* Fix: Don't kill the frontend form submission when there are no errors.
* Fix: Save campaign author/organization on frontend form submission.
* Fix: Properly save preview image and/or video on frontend form submission.

#### 0.4: March 12, 2013

* Release with Fundify theme.

#### 0.3-alpha: March 11, 2013

* Fix: Better theme support. Check for some EDD templates, as well as standard WordPress files.
* Fix: Actually collect funds!
* Fix: Better errors when submitting a campaign, or collecting funds.
* New: If PayPal Adaptive Payments is not active, track normal payments.
* New: Themes without explicit support will output default EDD variable pricing.

#### 0.2-alpha: March 7, 2013

* Fix: Better loading of exports.
* Fix: Shipping fixes, backers, etc.
* Fix: Load backers at the correct time.
* Fix: Count amount based on pre-approval, not total earnings.
* New: Don't activate if EDD is not active.
* New: Text filters.
* New: Update readme.txt

#### 0.1-alpha: March 6, 2013

* First official alpha release!