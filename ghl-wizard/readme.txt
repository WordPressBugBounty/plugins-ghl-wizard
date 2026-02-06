=== Connector Wizard (formerly LC Wizard) ===
Plugin Name: Connector Wizard (formerly LC Wizard)
Contributors: betterwizard, niaj, obiplabon, jorgelazarodiaz2
Tags: automation, highlevel, lead connector, membership plugin, woocommerce, high level 
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Connect WordPress with LeadConnector CRM to automate memberships, content protection, WooCommerce, and more for a seamless and powerful experience.

== Description ==

🚀 <a href="https://betterwizard.com/lead-connector-wizard/?aff=aba89e63">Official Website</a> | 📚 <a href="https://connectorwizard.app/docs/connector-wizard/" target='_blank'>Documentation</a> | 🚀 <a href="https://www.facebook.com/groups/betterwizard" target='_blank'>Community</a>

This plugin will connect the popular CRM LeadConnector to the most popular content management software, WordPress. It will elevate your automation experience to the next level. including memberships, content protection, custom field integration and automate WooCommerce.


== See who use this plugin ==

https://youtu.be/7OfzDpzFt20


== 🚀 Key Features ==

== 🔗 Subaccount Connection ==
Seamlessly connect your WordPress site to your subaccount. This essential first step unlocks the full potential of the plugin's automation capabilities.

== ⚙️ Custom Values ==
Custom values are great for storing your subaccount or website variables. Use your subaccount custom values inside WordPress with this simple shortcode: `[lcw_custom_value key="your_custom_value_key"]`

== 🔒 Smart Content Protection ==
Implement tag-based access control on WordPress pages, allowing you to differentiate between paid and free users for content visibility.

== 🛒 WooCommerce Integration ==
When someone purchases a product from WooCommerce:

* That user will be added (if not exists) to your subaccount as a contact
* You can apply one or more tags to that contact if that customer purchases a specific product
* You can set specific tags for specific products
* You can add that customer directly to a specific workflow in your CRM

== 👥 Automate WordPress User Creation ==
Create WordPress users directly from your workflow. There are a few powerful use cases:

* Let someone purchase anything from your funnel, run a workflow with that purchase trigger, and send a webhook to your WordPress website. A new WordPress user will be created for that customer.
* If someone books an appointment, you can create a WordPress user for that contact.
* Based on any possible conditions, you can send a webhook to WordPress to create a WordPress user.

== 📋 Display only protected posts/pages in a post grid ==
If you protect your pages by tags, those protected pages can only be displayed in a post grid using the shortcode `[lcw_post_grid post_type="page"]`. Feel free to check the full documentation <a href="https://connectorwizard.app/docs/connector-wizard/shortcodes/lcw_post_grid/" target="_blank">here</a>.

== 🔄 Redirect Customers ==
Redirect your customers to another page. You can display a message before the redirection.
Example: `[lcw_redirect url="/thank-you" delay="5" target="_self"]
    Please wait 5 seconds... redirecting.
[/lcw_redirect]`

== 🔑 Reset Password ==
Customers can reset their password with this simple password reset shortcode: `[lcw_reset_password]`. This shortcode allows you to:

* Change password
* Redirect to a page after the password is changed
* Apply or remove a tag for that user upon password change

== 🛠️ Developer Tools ==
Extend the plugin's functionality with developer-friendly features:

* For WooCommerce orders, there are available action hooks to automate the data flow to your CRM. 
* For details see the <a href="https://connectorwizard.app/docs/connector-wizard/deloper-resources/action-hook-lcw_update_order_meta/" target="_blank">docs</a>


== Screenshots ==
1. LeadConnector connection process
2. Location selection interface
3. Successful connection confirmation
4. Plugin configuration options
5. Content protection settings
6. Membership management
7. WooCommerce product integration

== 💎 Premium Features ==

== 🏅 Advanced Membership Management ==
Create and manage membership plans using tags from your CRM:

- Manage membership lifecycle (Active, Payment Failed, Suspended, Cancelled)
- Automated access control based on payment status
- Granular content restriction tied to membership levels

== 📝 Apply Tags to Different WooCommerce Order Statuses ==
Elevate your WooCommerce automation by applying tags to contacts based on various order statuses, including custom ones:

- Dynamically assign tags for successful orders, failed payments, or cancelled orders
- Apply tags to custom order statuses
- Enhance customer segmentation and targeted marketing with precise tag application

== 🏷️ Variant-Specific Product Tagging ==
Enhance your WooCommerce integration with granular product variant tagging:

- Unique tags for individual product variations
- Personalize communication based on specific product choices/ variations.

== 🔐 Advanced Page/ custom posts Protection ==
Implement robust content protection across your entire WordPress site:

- Enable content protection for all custom post types
- Secure courses, premium resources, and any other custom pages.

== 🔐 Associations Support ==
Unlock powerful group access management with Associations:

- Effortlessly support parent/child account structures—when a parent has access to content (such as a page or course), all associated child accounts automatically inherit access. If access is revoked from the parent, it is also revoked from all children.
- Flexible association types: works seamlessly for relationships like parent/child, husband/wife, team leader/team members, and more.
- Perfect for selling group or family accounts, ensuring streamlined access control and management for all related users.

== 🔐 Display Contact fields/ Custom fields in WordPress ==
It's true, you can display any custom fields inside WordPress from your CRM.
It'll help you create a personalized customer dashboard, show customer-centric data to your logged-in customers. By the shortcode `[lcw_contact_field]`.
Example: `[lcw_contact_field key='email']`, this will display the contact email address.
More Example: `[lcw_contact_field key='next_billing_date']` here you need to get the key from `{{contact.next_billing_date}}`, where the next_billing_date is a custom field.

== 🔐 Display content based on access ==
You can display a text/ image/ video or a block of content based on access by the shortcode `[lcw_has_any_tags]`
Example:
```
[lcw_has_any_tags tags='purchased_gold']
This content will be visible to the users if they have the tag purchased_gold. Otherwise, this content won't be displayed.
[/lcw_has_any_tags]
```
You can do the similar things by the membership with this shortcode `[lcw_has_any_memberships]`
Example:
```
[lcw_has_any_memberships memberships='level_1']
This content will be visible to the users if they have the membership level_1. otherwise, this content won't be displayed.
[/lcw_has_any_memberships]
```

And there are similar tags `[lcw_has_not_any_tags]` and `[lcw_has_not_any_memberships]` works in a similar way.

== 💳 Display Transactions ==
`[lcw_transactions]` shortcode allows us to display the transactions in WordPress from the CRM. You need to place the shortcode on any page/post, and it will do the rest.

== 🏷️ Set Tags ==
Assign tags to a logged-in WordPress user using the shortcode `[lcw_set_tags]`.
Example: `[lcw_set_tags tags='tag_names']` You can also set multiple tags: `[lcw_set_tags tags='tag_name1, tag_name2, tag_3']`.


== 🏷️ Remove Tags ==
Similarly, you can remove tags from a logged-in WordPress user using the shortcode `[lcw_remove_tags]`.
Example: `[lcw_remove_tags tags='tag_names']`. You can also remove multiple tags: `[lcw_remove_tags tags='tag_name1, tag_name2, tag_3']`.

== 📝 Assign and Remove WordPress User Roles ==
Assign and remove WordPress user roles directly from the CRM workflow:

- Seamlessly manage user roles based on CRM workflow triggers
- Automate role assignments and removals for efficient user management
- Enhance user experience with dynamic role changes
- Compatible with popular membership plugins that utilize user roles for access control, ensuring a unified and streamlined membership management experience

== 📝 Auto Login Magic Link for Customers ==
Generate a unique magic link for customers to auto-login to your WordPress site, eliminating the need for traditional login credentials:

- Enhance user experience with seamless Access
- Redirect to any specific page after auto-login.

== 📝 Invoice Creation for WooCommerce ==
- Create invoices for WooCommerce orders inside the CRM
- Send invoices to customers from the CRM.

== 🔄 WordPress User Sync ==
Automatically sync WordPress user data with your CRM:

- Real-time updates on user login
- Bulk import option for existing WordPress users

== 📝 Form Submission Integration ==
- Contact form 7 integration
- Manual form
- other forms (coming...)

== 🛍️ SureCart Integration ==
Seamlessly integrate SureCart with LeadConnector to apply tags on customer engagement:

- Add tags to contacts on successful purchases
- Remove tags on subscription revocation
- Add the tag back when the subscription is reactivated

== 🎓 LearnDash Integration ==
- Automatically enroll users in LearnDash courses based on their access to specific courses or content.
- Remove users from courses when their access is revoked, ensuring they no longer have access to the course content.
- Enhance the learning experience by dynamically managing course enrollment based on user access levels.

== 🖌️ Elementor Page Builder Integration ==

- Use content protection on any widget or container.
- Dynamically display widgets exclusively to your paid customers.
- Display dynamic content and grids inside your Elementor layouts.

== 🧩 Powerful Shortcodes ==
See the documentation for other shortcodes.

== 🛒 Abandoned Cart Recovery ==
Coming soon

== 📅 Display Customer Appointments ==
Coming soon

== Installation ==

- Go to plugins in your dashboard and select "Add New"
- Search for "Connector Wizard", Install & Activate it
- Go to "Connector Wizard" settings page and connect it with your HighLevel location.
- See the documentation to know how to use it.


== Changelog ==

= 2.1.1 - 01 Jan 2026 =
* Bug fixed 

= 2.1.0 - 06 Dec 2025 =
* New: Option to hide the admin bar for non-admin users
* New: Login redirect to a selected page after user login
* New: Logout redirect to a selected page after user logout
* New: Option to enable/disable automated email notification for new user registration
* New: Apply tag when a user is created via the autologin feature
* New: Option to enable/disable creation of new users on autologin
* New: Apply tag when a user autologs in

= 2.0.2 - 25 Nov 2025 =
* Fix: Resolved infinite redirect loop on the CRM Connection page when no CRM credentials are configured

= 2.0.1 - 23 Nov 2025 =
* Fix: Chat widget auto enable issue after 2.0 update

= 2.0 - 22 Nov 2025 =
* ⁠New: React-powered settings panel and dashboard for improved UI/UX.
* ⁠New: Custom chat widget support.
* ⁠New: Membership UI.
* ⁠Update: WordPress 6.9 compatibility.
* ⁠Update: Minimum required WordPress version is now 6.2.
* ⁠Update: Minimum required PHP version is now 7.4.

= 1.4.3 - 15 Nov 2025 =
* ⁠Fixed data conflict

= 1.4.2 =
* Performance Improved

= 1.4.1 =
* Bug fixed

= 1.4.0 =
* Rebranded to Connector Wizard
* Associations integration live
* Elementor Integration feature updated

= 1.3.0 =
* Elementor integration added

= 1.2.19 =
* Security issue updated
* Added post__in & post__not_in filters to the [lcw_post_grid] shortcode
* Added new shortcode: [lcw_reset_password]

= 1.2.18 =
* Fixed security issue
* Updated authentication page
* Added new shortcode [lcw_redirect]

= 1.2.17 =
* Fixed bug in LearnDash auto-enrollment
* Improved code for the auto-login feature

= 1.2.16 =
* Learndash auto-enrollment tags feature added
* lcw_set_tags shortcode added
* lcw_remove_tags shortcode added

= 1.2.15 =
* Enhanced lcw_post_grid shortcode
* bug fixed

= 1.2.14 =
* LearnDash auto-enrollment and remove enrollment feature added based on page access.
* Redirect to the login page if the user isn't logged in and has tried to access a restricted page.

= 1.2.13 =
* lcw_post_grid shortcode added
* Bug Fix: updated post/page restriction logic

= 1.2.12 =
* Bug Fix: Disabled the autologin feature for Admin
* Bug Fix: Unblocked other webhooks other than Connector Wizard.

= 1.2.11 =
* Feature Added: Auto login & create new WordPress user from workflow.
* Bug Fixed: fix _load_textdomain_just_in_time was called incorrectly

= 1.2.10 =
* Feature Added: Assign and remove WordPress user roles directly from the CRM workflow.
* Bug Fixed: restricted menu visibility

= 1.2.09 =
* Feature added: Apply tags to different order statuses, including custom order statuses.

= 1.2.08 =
* Surecart Integration Added

= 1.2.07 =
* Bug Fixed

= 1.2.06 =
* Invoice creation for WooCommerce

= 1.2.05 =

* Bug Fixed

= 1.2.04 =

* bug fixed

= 1.2.03 =

* Add order meta action hook added.
* Add product meta action hook added.
* Add contact note feature has been added.
* Update custom field feature added.

= 1.2.02 =

* Bug fixed

= 1.2.01 =

* More scopes added for the API

= 1.2.0 =

* Bug fixed

= 1.1.03 =

* Chat Widget Added

= 1.1.02 =

* Tag added for the variation product
* Contact fields update on WooCommerce order

= 1.1.01 =

* Error message added if CRM isn't connected
* Appsero SDK added

= 1.1.0 =

* Syncing process enhanced
* Database table introduced
* Contact Value feature added
* Membership feature added
* Content protection feature added

= 1.0.08 =
* bug fixed

= 1.0.07 =
* Ability to set a trigger based on WooCommerce order status.

= 1.0.06 =
* Warning fixed.

= 1.0.05 =
* Bug fixed.

= 1.0.04 =
* Make GHL Wizard available for all types of WooCommerce products.

= 1.0.03 =
* Woocommerce isn't required for this plugin.

= 1.0.02 =
* Sync on profile update feature added.

= 1.0.01 =
* Contact sync with goHighLevel and WordPress on contact login.
* Add contact tags to the WordPress user meta and display tags on the user profile page.
* Create a contact on GHL when a WP user is registered.
* Refresh data button added on product edit page.

= 1.0.0 =
* Initial Release