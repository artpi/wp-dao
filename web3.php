<?php
namespace Artpi\WPDAO;

use Elliptic\EC;
use kornrunner\Keccak;
use WP_Error;

class Web3 {
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	private function hash2number( $hex ) {
		$hex = substr( $hex, 2 ); // to strip 0x;
		$ret = 0;
		$len = strlen( $hex );
		for ( $i = 1; $i <= $len; $i++ ) {
			$ret = bcadd( $ret, bcmul( strval( hexdec( $hex[ $i - 1 ] ) ), bcpow( '16', strval( $len - $i ) ) ) );
		}
		return $ret / pow( 10, 18 ); // Because numbers are in Wei (10^-18)
	}

	public function api( $data = [] ) {
		$data = array_merge(
			array(
				'id'      => 0,
				'jsonrpc' => '2.0',
			),
			$data
		);

		$url      = $this->settings->get_alchemy_url();
		$response = wp_remote_post(
			$url,
			[
				'headers' => [
					'content-type' => 'application/json',
				],
				'body'    => json_encode( $data ),
			]
		);
		return wp_remote_retrieve_body( $response );
	}

	public function get_token_balances( $owner, $tokens ) {
		// TODO need some error hangling here.
		$payload  = [
			'method' => 'alchemy_getTokenBalances',
			'params' => [ $owner, $tokens ],
		];
		$response = $this->api( $payload );
		$json     = json_decode( $response );
		$balances = $json->result->tokenBalances;
		foreach ( $balances as $balance ) {
			$balance->tokenBalance = $this->hash2number( $balance->tokenBalance );
		}
		return $balances;
	}

	public static function generate_message( $request ) {
		$nonce     = wp_create_nonce( 'eth_login' );
		$uri       = get_site_url();
		$domain    = parse_url( $uri, PHP_URL_HOST );
		$statement = esc_attr__( 'Log In with your Ethereum wallet', 'dao-login' ); // TBD
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
			'nonce'   => $nonce,
		);
	}

	/**
	 * This will verify Ethereum signed message according to the specification.
	 * From https://github.com/simplito/elliptic-php#verifying-ethereum-signature
	 */
	public static function verify_signature( $message, $signature, $address ) {
		require_once __DIR__ . '/vendor/autoload.php';
		$msglen = strlen( $message );
		$hash   = Keccak::hash( "\x19Ethereum Signed Message:\n{$msglen}{$message}", 256 );
		$sign   = [
			'r' => substr( $signature, 2, 64 ),
			's' => substr( $signature, 66, 64 ),
		];
		$recid  = ord( hex2bin( substr( $signature, 130, 2 ) ) ) - 27;
		if ( $recid != ( $recid & 1 ) ) {
			return false;
		}

		$ec     = new EC( 'secp256k1' );
		$pubkey = $ec->recoverPubKey( $hash, $sign, $recid );

		return $address == self::pub_key_address( $pubkey );
	}

	public static function pub_key_address( $pubkey ) {
		return '0x' . substr( Keccak::hash( substr( hex2bin( $pubkey->encode( 'hex' ) ), 1 ), 256 ), 24 );
	}
}
