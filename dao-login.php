<?php
namespace Artpi\WPDAO;

/**
 * Plugin Name:     DAO Login
 * Description:     Make your site web3-ready: Log in with Ethereum or create users based on governance tokens.
 * Version:         0.2.1
 * Author:          Artur Piszek (artpi)
 * Author URI:      https://piszek.com
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     dao-login
 *
 * @package         artpi
 */

require_once __DIR__ . '/dao-permissions.php';
require_once __DIR__ . '/members-only.php';
require_once __DIR__ . '/web3.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\add_roles_on_plugin_activation' );

class DaoLogin {
	public static $settings;
	public static $web3;

	public static function init() {
		self::$settings = new Settings();
		self::$web3     = new Web3( self::$settings );
	}
}
add_action( 'init', __NAMESPACE__ . '\DaoLogin::init' );

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'dao-login',
			'/message-to-sign',
			array(
				'methods'   => 'GET',
				'callback'  => __NAMESPACE__ . '\Web3::generate_message',
				'arguments' => array(
					'address' => array(
						'type'        => 'string',
						'description' => esc_attr__( 'Your Ethereum Wallet Address', 'dao-login' ),
					),
				),
			)
		);
	}
);


/**
 * Inject JavaScript to allow login with Ethereum
 * We are setting 'wp-api-fetch' as a dependency, so we have the function wp.apiFetch ready in JS to call the API endpoint.
 */
function login_stylesheet() {
	wp_enqueue_script( 'custom-login', plugin_dir_url( __FILE__ ) . 'login-script.js', array( 'wp-api-fetch' ), 1, true );
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
	$nonce   = sanitize_title( $_POST['eth_login_nonce'] );
	$address = sanitize_title( $_POST['eth_login_address'] );
	// We stored the message in the DB before sending it to the client.
	$message   = get_transient( 'wp_dao_message_' . $address );
	$signature = sanitize_title( $_POST['eth_login_signature'] );
	delete_transient( 'wp_dao_message_' . $address ); // This is one-time thing and we want to clean it up.

	if ( ! wp_verify_nonce( $nonce, 'eth_login' ) || ! $message ) {
		return new \WP_Error( 'eth_login_nonce', esc_attr__( 'ETH Nonce failed - are you refreshing like crazy?', 'dao-login' ) );
	}

	// Now let's check the signature.
	if ( ! Web3::verify_signature( $message, $signature, $address ) ) {
		return new \WP_Error( 'eth_login_sig', esc_attr__( 'ETH Signature doesent match!', 'dao-login' ) );
	}

	// User is Authenticated, but not authorized. Is this user even a user on our site?
	// We don't want to check this earlier because we don't want to let them know this data.
	// We will query users table:
	$user_query = new \WP_User_Query(
		array(
			'number'     => 1,
			'meta_key'   => 'eth_address',
			'meta_value' => $address,
		)
	);
	$users      = $user_query->get_results();
	if ( isset( $users[0] ) ) {
		return $users[0];
	} elseif ( DaoLogin::$settings->is_registering_enabled() ) {
		// Allow registering through the API.
		$balances = DaoLogin::$web3->get_token_balances( $address, DaoLogin::$settings->get_token_list() );
		$role     = balances_to_role( DaoLogin::$settings->get_tokens_array(), $balances );
		if ( $role ) {
			$user_id = wp_create_user( $address, wp_generate_password(), "{$address}@ethmail.cc" );
			add_user_meta( $user_id, 'eth_address', $address, true );
			$user = get_user_by( 'ID', $user_id );
			$user->set_role( $role );
			return $user;
		} else {
			return new \WP_Error( 'eth_login_insufficient_funds', esc_attr__( 'Insufficient tokens to register on this site.', 'dao-login' ) );
		}
	} else {
		return new \WP_Error( 'eth_login_nouser', esc_attr__( 'No user connected to this Ethereum wallet.', 'dao-login' ) );
	}
	return $user;
}

add_filter( 'authenticate', __NAMESPACE__ . '\authenticate', 20, 3 );


function balances_to_role( $tokens, $balances ) {
	$roles = wp_roles()->roles;
	// I am assuming roles are going down with the order of importance.
	foreach ( $roles as $role_id => $role ) {
		foreach ( $tokens as $token_id => $token ) {
			foreach ( $balances as $balance ) {
				if (
					$balance->contractAddress === $token_id &&
					! empty( $token[ "role_{$role_id}" ] ) &&
					$balance->tokenBalance >= $token[ "role_{$role_id}" ]
				) {
					return $role_id;
				}
			}
		}
	}
	return false;
}



// For the profile page editing:
function additional_profile_fields( $user ) {
	$address = get_user_meta( $user->ID, 'eth_address', true );

	?>
	<h3><?php esc_attr_e( 'DAO Login Settings', 'dao-login' ); ?></h3>
		<table class="form-table">
		<tr class="user-last-name-wrap">
		<th><label for="eth_address"><?php esc_attr_e( 'Your Ethereum Wallet Address', 'dao-login' ); ?></label></th>
		<td><input type="text" name="eth_address" id="eth_address" value="<?php echo esc_attr( $address ); ?>" class="regular-text"></td>
		</tr>
	</table>
	<?php
}

add_action( 'show_user_profile', __NAMESPACE__ . '\additional_profile_fields' );
add_action( 'edit_user_profile', __NAMESPACE__ . '\additional_profile_fields' );
function save_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	// We disallow setting empty field here if user does not have a previous entry - so that they can delete the wallet if they need to, but we won't be spinning up empty records.
	if ( empty( $_POST['eth_address'] ) && ! get_user_meta( $user_id, 'eth_address', true ) ) {
		return false;
	}

	update_user_meta( $user_id, 'eth_address', sanitize_title( $_POST['eth_address'] ) );
}

add_action( 'personal_options_update', __NAMESPACE__ . '\save_profile_fields' );
add_action( 'edit_user_profile_update', __NAMESPACE__ . '\save_profile_fields' );

