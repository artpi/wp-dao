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
				'arguments' => array(
					'address' => array(
						'type'        => 'string',
						'description' => 'Wallet Address',
					),
				),
			)
		);
	}
);

function generate_message( $request ) {
	$nonce     = wp_create_nonce();
	$uri       = get_site_url();
	$domain    = parse_url( $uri, PHP_URL_HOST );
	$statement = 'Log In with your Ethereum wallet'; // TBD
	$version   = 1; // Per https://github.com/ethereum/EIPs/blob/9a9c5d0abdaf5ce5c5dd6dc88c6d8db1b130e95b/EIPS/eip-4361.md#example-message-to-be-signed
	$issued_at = gmdate( 'Y-m-d\TH:i:s\Z' );
	$address   = 'test test';

	// This is copy-pasted from https://github.com/ethereum/EIPs/blob/9a9c5d0abdaf5ce5c5dd6dc88c6d8db1b130e95b/EIPS/eip-4361.md#informal-message-template
	$message = "{$domain} wants you to sign in with your Ethereum account:
{$request['address']}

{$statement}

URI: {$uri}
Version: {$version}
Nonce: {$nonce}
Issued At: {$issued_at}
";
	return array(
		'address' => $request['address'],
		'message' => $message,
	);
}

/**
 * Inject JavaScript to allow login with Ethereum
 * We are setting 'wp-api-fetch' as a dependency, so we have the function wp.apiFetch ready in JS to call the API endpoint.
 */
function login_stylesheet() {
	wp_enqueue_script( 'custom-login', plugin_dir_url( __FILE__ ) . 'login-script.js', array( 'wp-api-fetch' ), time(), true );
}
add_action( 'login_enqueue_scripts', __NAMESPACE__ . '\login_stylesheet' );
