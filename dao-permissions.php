<?php
namespace Artpi\WPDAO;

class DAOLogin {
	private $dao_login_options;
	private $fields_to_save;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'dao_login_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'dao_login_page_init' ) );
	}

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
		$this->dao_login_options = get_option( 'dao_login_option_name' ); ?>

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

	public function dao_login_page_init() {
		register_setting(
			'dao_login_option_group', // option_group
			'dao_login_option_name', // option_name
			array( $this, 'dao_login_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'dao_login_setting_section', // id
			'Settings', // title
			array( $this, 'dao_login_section_info' ), // callback
			'dao-login-admin' // page
		);

		add_settings_section(
			'dao_login_setting_section_new_token', // id
			'New Token to Track', // title
			array( $this, 'dao_login_section_info' ), // callback
			'dao-login-admin' // page
		);

		add_settings_field(
			'allow_users_to_register_based_on_their_governance_token_allowance_0', // id
			'Allow users to register based on their governance token allowance', // title
			array( $this, 'allow_users_to_register_based_on_their_governance_token_allowance_0_callback' ), // callback
			'dao-login-admin', // page
			'dao_login_setting_section' // section
		);

		add_settings_field(
			'alchemy_api_url_1', // id
			'Alchemy API URL', // title
			array( $this, 'alchemy_api_url_callback' ), // callback
			'dao-login-admin', // page
			'dao_login_setting_section' // section
		);

		$tokens = 1;
		for ( $t = 0; $t < $tokens; $t++ ) {
			$id = "tracked_token_{$t}";
			add_settings_field(
				$id . '_id', // id
				"Tracked token $t contract id", // title
				array( $this, 'tracked_token_contract_callback' ), // callback
				'dao-login-admin', // page
				'dao_login_setting_section_new_token',
				array(
					'num' => $t,
					'id' => $id  . '_id',
				)
			);
			$this->fields_to_save[] = $id . '_id';

			add_settings_field(
				$id . '_label', // id
				"Label for token $t", // title
				array( $this, 'tracked_token_contract_callback' ), // callback
				'dao-login-admin', // page
				'dao_login_setting_section_new_token',
				array(
					'num' => $t,
					'id' => $id  . '_label',
				)
			);
			$this->fields_to_save[] = $id . '_label';

			$roles = get_editable_roles();
			foreach ( $roles as $role_id => $role ) {
				add_settings_field(
					"{$id}_role_{$role_id}",
					'Minimum tokens for ' . $role['name'], // title
					array( $this, 'tracked_token_contract_callback' ), // callback
					'dao-login-admin', // page
					'dao_login_setting_section_new_token', // section
					array(
						'num' => $t,
						'id' => "{$id}_role_{$role_id}",
					)
				);
				$this->fields_to_save[] = "{$id}_role_{$role_id}";

			}
		}
	}

	public function dao_login_sanitize( $input ) {
		$sanitary_values = array();
		if ( isset( $input['allow_users_to_register_based_on_their_governance_token_allowance_0'] ) ) {
			$sanitary_values['allow_users_to_register_based_on_their_governance_token_allowance_0'] = $input['allow_users_to_register_based_on_their_governance_token_allowance_0'];
		}

		if ( isset( $input['alchemy_api_url_1'] ) ) {
			$sanitary_values['alchemy_api_url_1'] = sanitize_text_field( $input['alchemy_api_url_1'] );
		}
		foreach ( $this->fields_to_save as $field ) {
			$sanitary_values[$field] = sanitize_text_field( $input[$field] );
		}

		return $sanitary_values;
	}

	public function dao_login_section_info() {

	}

	public function allow_users_to_register_based_on_their_governance_token_allowance_0_callback() {
		printf(
			'<input type="checkbox" name="dao_login_option_name[allow_users_to_register_based_on_their_governance_token_allowance_0]" id="allow_users_to_register_based_on_their_governance_token_allowance_0" value="allow_users_to_register_based_on_their_governance_token_allowance_0" %s> <label for="allow_users_to_register_based_on_their_governance_token_allowance_0">This will enable anyone with X amount of token Y to automatically create a user account on your site</label>',
			( isset( $this->dao_login_options['allow_users_to_register_based_on_their_governance_token_allowance_0'] ) && $this->dao_login_options['allow_users_to_register_based_on_their_governance_token_allowance_0'] === 'allow_users_to_register_based_on_their_governance_token_allowance_0' ) ? 'checked' : ''
		);
	}

	public function alchemy_api_url_callback() {
		printf(
			'<input class="regular-text" type="text" name="dao_login_option_name[alchemy_api_url_1]" id="alchemy_api_url_1" value="%s">',
			isset( $this->dao_login_options['alchemy_api_url_1'] ) ? esc_attr( $this->dao_login_options['alchemy_api_url_1'] ) : ''
		);
	}

	public function tracked_token_contract_callback( $options ) {
		printf(
			'<input class="regular-text" type="text" name="dao_login_option_name[%1$s]" id="%1$s" value="%2$s">',
			$options['id'],
			isset( $this->dao_login_options[$options['id']] ) ? esc_attr( $this->dao_login_options[$options['id']] ) : ''
		);
	}

	public function label_permission( $args ) {
		printf(
			'<input class="regular-text" type="text" name="dao_login_option_name[your_label_for_trakced_token_1_3]" id="your_label_for_trakced_token_1_3" value="%s">',
			isset( $this->dao_login_options['your_label_for_trakced_token_1_3'] ) ? esc_attr( $this->dao_login_options['your_label_for_trakced_token_1_3'] ) : ''
		);
	}
}
if ( is_admin() ) {
	$dao_login = new DAOLogin();
}
