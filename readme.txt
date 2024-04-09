=== Paid Memberships Pro - Zapier Add On ===
Contributors: strangerstudios, paidmembershipspro
Tags: paid memberships pro, pmpro, zapier
Requires at least: 5.2
Tested up to: 6.5
Stable tag: 1.2.2

Integrate activity on your membership site with thousands of other apps via Zapier.

== Description ==

Integrate activity on your membership site with thousands of other apps via Zapier (requires Paid Memberships Pro). [Extended documentation can be found at PaidMembershipsPro.com](https://www.paidmembershipspro.com/add-ons/pmpro-zapier/).
 
Our Zapier integration includes the following triggers and actions to send information to Zapier and connect with third-party apps. A "Trigger" will send data to Zapier when changes are made on your Membership site. An "Action" will process incoming data when a change is sent to your Membership site via Zapier and a connected third-party app.

= Triggers =

*New Order*

*Updated Order*

*Changed Membership Level*

*After Checkout*

= Actions =

When creating the Action component of a Zap, use the webhook URL provided on the Actions tab of the PMPro Zapier settings and pass in parameters matching those given below.

*add_member*

The following parameters can be passed into the add_member Action:

* user_email (required)
* level_id (required)
* user_login
* full_name
* first_name
* last_name

Note that user_email and level_id are required parameters; you must also pass in at least one of user_login, full_name, first_name, or last_name.

*change_membership_level*

The following parameters can be passed into the change_membership_level Action:

* user_id
* user_email
* user_login
* level_id (required)

Note that level_id is a required parameter; you must also pass in at least one of the following user identifiers is also required: user_id, user_email, or user_login.

*add_order*

The following parameters can be passed into the add_order Action:

* user_id
* user_email
* user_login
* level_id
* subtotal
* tax
* couponamount
* total
* payment_type
* cardtype
* accountnumber
* expirationmonth
* expirationyear
* status
* gateway
* gateway_environment
* payment_transaction_id
* subscription_transaction_id
* affiliate_id
* affiliate_subid
* notes
* checkout_id
* billing_name
* billing_street
* billing_city
* billing_state
* billing_zip
* billing_country
* billing_phone


*update_order*

The following parameters can be passed into the update_order Action:

* order, order_id, code, or id (required)
* user_id
* user_email
* user_login
* level_id
* subtotal
* tax
* couponamount
* total
* payment_type
* cardtype
* accountnumber
* expirationmonth
* expirationyear
* status
* gateway
* gateway_environment
* payment_transaction_id
* subscription_transaction_id
* affiliate_id
* affiliate_subid
* notes
* checkout_id
* billing_name
* billing_street
* billing_city
* billing_state
* billing_zip
* billing_country
* billing_phone

*has_membership_level*

The following parameters can be passed into the has_membership_level Action:

* user_id
* user_email
* user_login
* level_id (required)

Note that level_id is a required parameter; you must also pass in at least one of the following user identifiers is also required: user_id, user_email, or user_login.

== Installation ==
= Prerequisites =
1. Create a Zapier account at http://zapier.com

= Download, Install and Activate! =
1. Upload the `pmpro-zapier` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. The settings page is at Memberships --> PMPro Zapier in the WP dashboard.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-zapier/issues

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at http://www.paidmembershipspro.com for more documentation and our support forums.

== Changelog ==
= 1.2.2 - 2023-12-05 =
* ENHANCEMENT: Improved compatibility with Multiple Memberships Per User (@dparker1005)

= 1.2.1 - 2022-08-12 =
* ENHANCEMENT: New filter `pmproz_prepare_order_for_request` added to allow manipulating the $order data that is sent with every trigger.
* BUG FIX: Fixes an issue where order data would be blank in Zapier and similar services. Improved compatibility with v2.9+ of Paid Memberships Pro.

= 1.2.0 - 2021-14-09 =
* ENHANCEMENT: Add first_name and last_name variables to after checkout trigger.
* ENHANCEMENT: Added functionality to regenerate api key as admin. Add ?pmproz_generate_api_key=1 to a URL while logged-in as admin to regenerate api key.
* ENHANCEMENT: Added localized date function. Date formats are now translatable.
* ENHANCEMENT: Improved localization for missing strings and wrong text domains. 
* BUG FIX: Fixed an issue where the API key was regenerating whenever settings were saved.

= 1.1.1 - 2021-06-09 =
* NOTE: Bumping version to 1.1.1 to force update for users who have a broken 1.1 version.

= 1.1 =
* ENHANCEMENT: New trigger added for after checkout.
* ENHANCEMENT: Additional hooks added in to allow customizing data passed through. Please see documentation for more information about this.

= 1.0 =
* Launched in the WordPress.org repository, set to V1.0

= .3 =
* BUG FIX/ENHANCEMENT: Changed the webhook handler URL to go through '{home_url}/?pmpro_zapier_webhook=1&api_key={api_key}' instead of directly to the webhook file. IMPORTANT! You will need to update any Zaps you made prior to version .3.

= .2 =
* Getting ready for WordPress.org repository.

= .1 =
* Initial version of the plugin.
