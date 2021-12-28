=== DAO Login ===
Contributors:      artpi
Tags:              signin, web3, ethereum, login, sso, nft, dao
Requires at least: 5.3.1
Tested up to:      5.8.2
Stable tag:        0.2.1
Requires PHP:      7.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Enable signin with Ethereum on your site and allow users to register based on Governance tokens, NFT, and token balance.
[Demo site here](https://wpdao.artpi.net/)

== Description ==

DAO Login is a plugin that connects your site login system web3:

- Existing users can log in with their Ethereum Wallets using the [Sign in with Ethereum](https://login.xyz)
- New users can create accounts based on their token balances
- You can designate members-only areas for token holders
- Works with existing WordPress user roles and other plugins. You can create a private forum, private store, DAO blog, etc.


= Automatic onboarding =

DAO Login connects WordPress user roles to the token balances.

Whenever somebody logs in with Ethereum on your site for the first time, the plugin checks their token balances on the Ethereum mainnet (or test network or L2 of your choosing).

For any user role, you can specify the minimum amount of a token the user needs to have in order to create an account.

Your token can be a DAO Governance token, NFT, coin, or any other contract.

If you need a site for your DAO, just spin up a WordPress, install this plugin, and connect it to your governance structure. You don’t need to know the email address of anybody.

= Built-in “Members only” area =

DAO Login introduces a new “DAO Member” user role. You can mark posts or pages as “DAO Member only” and they will automatically be accessible only for users with this role, or higher.

If you want to provide a secret page, resource manual, or a perk for your DAO, NFT, or other token holders – it’s a few seconds with this plugin.

This opens a world of possibilities for your Airdrop.

= Power of WordPress in web3 =

WordPress plugins offer every functionality under the sun. By connecting user roles to tokens, you can create:

- Private forums with bbPress
- Private swag store with WooCommerce
- Private courses with Sensei

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/dao-login` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. In order to allow token holders to register on your site, you have to select contract addresses in the settings page (`/wp-admin/options-general.php?page=dao-login`)
1. You can also add Ethereum wallet addresses in the users screen (`/wp-admin/users.php`), in the "WP DAO" section
1. Every user that has that field filled out, can log in with their wallet



== Development ==

[All Development and issues in this github repository.](https://github.com/artpi/wp-dao)

== Changelog ==

= 0.1.0 =
* Release
= 0.1.1 =
* Fix security issues pointed out in WordPress security review
= 0.2.1 =
* Add an option to create users using the account balance
* A simple members-only area


