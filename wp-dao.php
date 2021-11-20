<?php
namespace Artpi\WPDAO;
use Elliptic\EC;
use kornrunner\Keccak;
use WP_Error;

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
	$nonce     = wp_create_nonce( 'eth_login' );
	$uri       = get_site_url();
	$domain    = parse_url( $uri, PHP_URL_HOST );
	$statement = 'Log In with your Ethereum wallet'; // TBD
	$version   = 1; // Per https://github.com/ethereum/EIPs/blob/9a9c5d0abdaf5ce5c5dd6dc88c6d8db1b130e95b/EIPS/eip-4361.md#example-message-to-be-signed
	$issued_at = gmdate( 'Y-m-d\TH:i:s\Z' );

	// This is copy-pasted from https://github.com/ethereum/EIPs/blob/9a9c5d0abdaf5ce5c5dd6dc88c6d8db1b130e95b/EIPS/eip-4361.md#informal-message-template
	$message = "{$domain} wants you to sign in with your Ethereum account:
{$request['address']}

{$statement}

URI: {$uri}
Version: {$version}
Nonce: {$nonce}
Issued At: {$issued_at}
";
	// This attempt will auto expire in 5 minutes. This way, we'll save the message server-side to check after the login attempt.
	set_transient( 'wp_dao_message_' . $request['address'], $message, 60 * 5 );
	return array(
		'address' => $request['address'],
		'message' => $message,
		'nonce' => $nonce,
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

/**
 * This is the actual magic of signing in - here we check everything. This is a WP Hook for logging in that gets verified with different signin options.
 */
function authenticate( $user, $username, $password ) {
	global $_POST;
	if ( ! isset( $_POST['eth_login_address'], $_POST['eth_login_nonce'], $_POST['eth_login_signature'] ) ) {
		// Not an ETH login flow, we have nothing to do here.
		return $user;
	}
	$address = sanitize_title( $_POST['eth_login_address'] );
	// We stored the message in the DB before sending it to the client.
	$message = get_transient( 'wp_dao_message_' . $address );
	$signature = sanitize_title( $_POST['eth_login_signature'] );
	delete_transient( 'wp_dao_message_' . $address ); // This is one-time thing and we want to clean it up.

	if ( ! wp_verify_nonce( $_POST['eth_login_nonce'], 'eth_login' ) || ! $message ) {
		return new \WP_Error( 'eth_login_nonce', 'ETH Nonce failed - are you refreshing like crazy?' );
	}

	// Now let's check the signature.
	if ( ! verifySignature( $message, $signature, $address) ) {
		return new \WP_Error( 'eth_login_sig', 'ETH Signature doesent match!' );
	}

	// User is Authenticated, but not authorized. Is this user even a user on our site?
	// We don't want to check this earlier because we don't want to let them know this data.
	// We will query users table:
	$user_query = new \WP_User_Query(
		array(
			'number'    => 1,
			'meta_key'	  => 'eth_address',
			'meta_value' => $address,
		)
	);
	$users = $user_query->get_results();
	if( isset( $users[0] ) ) {
		return $users[0];
	} 
	return $user;
}

add_filter( 'authenticate', __NAMESPACE__ . '\authenticate', 20, 3 );


/**
 * From https://github.com/simplito/elliptic-php#verifying-ethereum-signature
 */
function verifySignature($message, $signature, $address) {
	require_once __DIR__ . '/vendor/autoload.php';
    $msglen = strlen($message);
    $hash   = Keccak::hash("\x19Ethereum Signed Message:\n{$msglen}{$message}", 256);
    $sign   = ["r" => substr($signature, 2, 64), 
               "s" => substr($signature, 66, 64)];
    $recid  = ord(hex2bin(substr($signature, 130, 2))) - 27; 
    if ($recid != ($recid & 1)) 
        return false;

    $ec = new EC('secp256k1');
    $pubkey = $ec->recoverPubKey($hash, $sign, $recid);

    return $address == pubKeyToAddress($pubkey);
}

function pubKeyToAddress($pubkey) {
    return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256), 24);
}
