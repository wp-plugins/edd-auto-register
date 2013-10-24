=== EDD Auto Register ===
Contributors: sumobi
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EFUPMPEZPGW7L
Tags: easy digital downloads, digital downloads, e-downloads, edd, sumobi, purchase, auto, register, registration, e-commerce
Requires at least: 3.3
Tested up to: 3.6
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically creates a WP user account at checkout, based on customer's email address.

== Description ==

This plugin requires [Easy Digital Downloads](http://wordpress.org/extend/plugins/easy-digital-downloads/ "Easy Digital Downloads"). 

Once activated, EDD Auto Register will create a WordPress user account for your customer at checkout, without the need for the customer to enter any additional information. This eliminates the need for the default EDD registration form, and drastically reduces the time it takes your customers to complete their purchase.

The customer's email address is used as the WordPress username (required by EDD to send the purchase receipt to) and a random password is automatically created. When the purchase is completed, an email is sent to the customer containing their login credentials (uses the same email template as the purchase confirmation). The customer is also auto-logged into your website, just like the standard behaviour of the EDD registration form.

There are filters available for developer's to disable the email, modify the email subject line, email body, error messages, default user level etc. See the FAQ tab.

**More add-ons for Easy Digital Downloads**

You can find more add-ons (both free and commercial) from [Easy Digital Downloads' website](https://easydigitaldownloads.com/extensions/?ref=166 "Easy Digital Downloads")

**Free theme for Easy Digital Downloads**

[http://sumobi.com/shop/shop-front/](http://sumobi.com/shop/shop-front/ "Shop Front")

Shop Front was designed to be simple, responsive and lightweight. It has only the bare essentials, making it the perfect starting point for your next digital e-commerce store. It’s also easily extensible with a growing collection of add-ons to enhance the functionality and styling.

**Stay up to date**

*Become a fan on Facebook* 
[http://www.facebook.com/sumobicom](http://www.facebook.com/sumobicom "Facebook")

*Follow me on Twitter* 
[http://twitter.com/sumobi_](http://twitter.com/sumobi_ "Twitter")

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name


== Frequently Asked Questions ==

= How can I modify some of the key aspects of the plugin? =

There are filters available to modify the behaviour of the plugin, see the list below:

1. edd_auto_register_role
1. edd_auto_register_email_subject
1. edd_auto_register_headers
1. edd_auto_register_send_email
1. edd_auto_register_email_body
1. edd_auto_register_error_email_exists
1. edd_auto_register_error_must_login
1. edd_auto_register_login_form

= Can you provide a filter example of how to change the email's subject? =

Add the following to your child theme's functions.php

    function my_child_theme_edd_auto_register_email_subject( $subject ) {

        // enter your new subject below
	    $subject = 'Here are your new login details';

	    return $subject;

    }
    add_filter( 'edd_auto_register_email_subject', 'my_child_theme_edd_auto_register_email_subject' );


= Can you provide a filter example of how to change the email's body? =

Add the following to your child theme's functions.php

	function my_child_theme_edd_auto_register_email_body( $default_email_body, $first_name, $username, $password ) {

		// Modify accordingly
		$default_email_body = __( "Dear", "edd-auto-register" ) . ' ' . $first_name . ",\n\n";
		$default_email_body .= __( "Below are your login details:", "edd-auto-register" ) . "\n\n";
		$default_email_body .= __( "Your Username:", "edd-auto-register" ) . ' ' . $username . "\n\n";
		$default_email_body .= __( "Your Password:", "edd-auto-register" ) . ' ' . $password . "\n\n";
		$default_email_body .= get_bloginfo( 'name' ) . "\n\n";
		$default_email_body .= site_url();

		return $default_email_body;

	}
	add_filter( 'edd_auto_register_email_body', 'my_child_theme_edd_auto_register_email_body', 10, 4 );

= How can I disable the email from sending to the customer? =

There may be an instance where you do not want the customer to be sent an email. Add the following to your child theme's functions.php

    function my_child_theme_edd_auto_register_send_email() {

	    return false;

    }
    add_filter( 'edd_auto_register_send_email', 'my_child_theme_edd_auto_register_send_email' );

== Changelog ==

= 1.0.1 =
* Fixed filter names for error messages

= 1.0 =
* Initial release