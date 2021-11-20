=== DAO Login ===
Contributors:      artpi
Tags:              signin, web3, ethereum, login
Requires at least: 5.3.1
Tested up to:      5.3.1
Stable tag:        0.1.0
Requires PHP:      7.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Enable signin with Ethereum on your site.

== Description ==

This plugin enables "Sign-In with Ethereum" protocol on your WordPress site. Your users will be able to log in with their wallets - you never have to send them the password!
Enable cryptographically secure login option now!

More about sign-in with Ethereum protocol: https://login.xyz/
A video of this plugin in action: https://twitter.com/artpi/status/1462143739686699018

Future plans include:
- Importing .eth username from ENS
- Creating users based on them having a certain amount of governance tokens for a DAO, or a specific NFT
- Disabling password / email options so that your users are 100% secured by private/public key pairs.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-dao` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Now you can add Ethereum wallet addresses in the users screen (`/wp-admin/users.php`), in the "WP DAO" section
1. Every user that has that field filled out, can log in with their wallet


== Development ==

[All Development and issues in this github repository.](https://github.com/artpi/wp-dao)

== Changelog ==

= 0.1.0 =
* Release
