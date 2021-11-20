<?php
namespace Artpi\WPDAO;

/**
 * Plugin Name:     WP DAO
 * Plugin URI:      https://piszek.com
 * Description:     Make your site web3-ready: Log in with Ethereum or create users based on governance tokens.
 * Version:         0.0.1
 * Author:          Artur Piszek (artpi)
 * Author URI:      https://piszek.com
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     wp-dao
 *
 * @package         artpi
 */


add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wp-dao',
			'/message-to-sign',
			array(
				'methods'  => 'GET',
				'callback' => __NAMESPACE__ . '\generate_message',
			)
		);
	}
);

function generate_message() {
	$nonce     = wp_create_nonce();
	$uri       = get_site_url();
	$domain    = parse_url( $uri, PHP_URL_HOST );
	$statement = 'Log In with your Ethereum wallet'; // TBD
	$version   = 1; // Per https://github.com/ethereum/EIPs/blob/9a9c5d0abdaf5ce5c5dd6dc88c6d8db1b130e95b/EIPS/eip-4361.md#example-message-to-be-signed
	$issued_at = gmdate( 'Y-m-d\TH:i:s\Z' );
	$address   = 'test test';

	// This is copy-pasted from https://github.com/ethereum/EIPs/blob/9a9c5d0abdaf5ce5c5dd6dc88c6d8db1b130e95b/EIPS/eip-4361.md#informal-message-template
	$message = "{$domain} wants you to sign in with your Ethereum account:
{$address}

{$statement}

URI: {$uri}
Version: {$version}
Nonce: {$nonce}
Issued At: {$issued_at}
";
	return $message;
}

