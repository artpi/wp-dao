<?php
namespace Artpi\WPDAO;

class Settings {
	private $dao_login_options;
	private $fields_to_save;

	public function __construct() {
		$this->dao_login_options = get_option( 'dao_login' );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'dao_login_add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'dao_login_page_init' ) );
		}
	}

	public function get_alchemy_url() {
		return $this->dao_login_options['alchemy_url'];
	}

	public function is_registering_enabled() {
		return (
			isset( $this->dao_login_options['allow_register'] ) &&
			$this->dao_login_options['allow_register'] &&
			isset( $this->dao_login_options['alchemy_url'] ) &&
			isset( $this->dao_login_options['tokens'] ) &&
			count( $this->dao_login_options['tokens'] ) > 0
		);
	}

	public function get_token_list() {
		return array_keys( $this->dao_login_options['tokens'] );
	}

	public function get_tokens_array() {
		return $this->dao_login_options['tokens'];
	}

	/**
	 * The following are administrative pages for settings
	 */
	public function dao_login_add_plugin_page() {
		add_options_page(
			'DAO Login', // page_title
			'DAO Login', // menu_title
			'manage_options', // capability
			'dao-login', // menu_slug
			array( $this, 'dao_login_create_admin_page' ) // function
		);
	}

	public function dao_login_create_admin_page() {
		?>

		<div class="wrap">
			<h2>DAO Login</h2>
			<p>DAO Login Settings</p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'dao_login_option_group' );
					do_settings_sections( 'dao-login-admin' );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	private function settings_for_tracked_token( $id, $label, $values = [] ) {
		$section_id = "dao_login_setting_section_{$id}";
		add_settings_section(
			$section_id, // id
			$label, // title
			array( $this, $id !== 'new' ? 'dao_login_section_balance' : 'dao_login_section_info' ), // callback
			'dao-login-admin' // page
		);

		$this->fields_to_save['tokens'][ $id ] = array();
		add_settings_field(
			$id . '_id', // id
			"{$label} contract id", // title
			array( $this, 'tracked_token_contract_callback' ), // callback
			'dao-login-admin', // page
			$section_id,
			array(
				'num' => $t,
				'id'  => 'token_' . $id . '_id',
				'val' => $values['id'],
			)
		);

		$this->fields_to_save['tokens'][ $id ]['id'] = 'token_' . $id . '_id';

		add_settings_field(
			$id . '_label', // id
			"Label for {$label}", // title
			array( $this, 'tracked_token_contract_callback' ), // callback
			'dao-login-admin', // page
			$section_id,
			array(
				'num' => $t,
				'id'  => 'token_' . $id . '_label',
				'val' => $values['label'],

			)
		);
		$this->fields_to_save['tokens'][ $id ]['label'] = 'token_' . $id . '_label';

		$roles = get_editable_roles();
		foreach ( $roles as $role_id => $role ) {
			add_settings_field(
				"{$id}_role_{$role_id}",
				'Minimum tokens for ' . $role['name'], // title
				array( $this, 'tracked_token_contract_callback' ), // callback
				'dao-login-admin', // page
				$section_id, // section
				array(
					'num' => $t,
					'id'  => "token_{$id}_role_{$role_id}",
					'val' => $values[ "role_{$role_id}" ],
				)
			);
			$this->fields_to_save['tokens'][ $id ][ "role_{$role_id}" ] = "token_{$id}_role_{$role_id}";

		}
	}

	public function dao_login_page_init() {
		register_setting(
			'dao_login_option_group', // option_group
			'dao_login', // option_name
			array( $this, 'dao_login_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'dao_login_setting_section', // id
			'Settings', // title
			array( $this, 'dao_login_section_info' ), // callback
			'dao-login-admin' // page
		);

		add_settings_field(
			'allow_register', // id
			'Allow users to register based on their governance token allowance', // title
			array( $this, 'allow_register_callback' ), // callback
			'dao-login-admin', // page
			'dao_login_setting_section' // section
		);

		add_settings_field(
			'alchemy_url', // id
			'Alchemy API URL', // title
			array( $this, 'alchemy_api_url_callback' ), // callback
			'dao-login-admin', // page
			'dao_login_setting_section' // section
		);

		if ( ! $this->dao_login_options['allow_register'] ) {
			return;
		}

		foreach ( $this->dao_login_options['tokens'] as $id => $values ) {
			$this->settings_for_tracked_token( $id, $values['label'], $values );
		}
		$this->settings_for_tracked_token( 'new', 'New Token' );

	}

	public function dao_login_sanitize( $input ) {
		$sanitary_values = array();
		if ( isset( $input['allow_register'] ) ) {
			$sanitary_values['allow_register'] = $input['allow_register'];
		}

		if ( isset( $input['alchemy_url'] ) ) {
			$sanitary_values['alchemy_url'] = sanitize_text_field( $input['alchemy_url'] );
		}

		foreach ( $this->fields_to_save['tokens'] as $token_id => $fields ) {
			foreach ( $fields as $key => $field ) {
				if ( $key && isset( $input[ "token_{$token_id}_id" ], $input[ $field ] ) && $input[ "token_{$token_id}_id" ] ) {
					$sanitary_values['tokens'][ $input[ "token_{$token_id}_id" ] ][ $key ] = sanitize_text_field( $input[ $field ] );
				}
			}
		}
		return $sanitary_values;
	}

	public function dao_login_section_info( $arg ) {
	}

	public function dao_login_section_balance( $arg ) {
		if( preg_match( '#dao_login_setting_section_(0x[0-9a-z]+)#is', $arg['id'], $match ) ) {
			$token=$match[1];
			$address = get_user_meta( get_current_user_id(), 'eth_address', true );
			$balance = DaoLogin::$web3->get_token_balances( $address, [ $token ] );
			if( isset( $balance[0]->tokenBalance ) ) {
				echo "<div><b>Your balance:</b> {$balance[0]->tokenBalance} </div>";
			}
		}
	}

	public function allow_register_callback() {
		printf(
			'<input type="checkbox" name="dao_login[allow_register]" id="allow_register" value="allow_register" %s> <label for="allow_register">This will enable anyone with X amount of token Y to automatically create a user account on your site</label>',
			( isset( $this->dao_login_options['allow_register'] ) && $this->dao_login_options['allow_register'] === 'allow_register' ) ? 'checked' : ''
		);
	}

	public function alchemy_api_url_callback() {
		printf(
			'<input class="regular-text" type="text" name="dao_login[alchemy_url]" id="alchemy_api_url_1" value="%s">',
			isset( $this->dao_login_options['alchemy_url'] ) ? esc_attr( $this->dao_login_options['alchemy_url'] ) : ''
		);
	}

	public function tracked_token_contract_callback( $options ) {
		printf(
			'<input class="regular-text" type="text" name="dao_login[%1$s]" id="%1$s" value="%2$s">',
			$options['id'],
			isset( $options['val'] ) ? esc_attr( $options['val'] ) : ''
		);
	}

}
