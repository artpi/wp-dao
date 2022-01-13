<?php
namespace Artpi\WPDAO;

function add_roles_on_plugin_activation() {
	add_role( 'dao_member', 'DAO Member', get_role( 'subscriber' )->capabilities );
	get_role( 'administrator' )->add_cap( 'access_members_only', true );
	get_role( 'editor' )->add_cap( 'access_members_only', true );
	get_role( 'author' )->add_cap( 'access_members_only', true );
	get_role( 'contributor' )->add_cap( 'access_members_only', true );
	get_role( 'dao_member' )->add_cap( 'access_members_only', true );
}

// Add simple_role capabilities, priority must be after the initial role definition.
add_action( 'init', __NAMESPACE__ . '\add_roles_on_plugin_activation', 11 );


add_action(
	'add_meta_boxes',
	function() {
		add_meta_box( 'dao-members-only', 'Members only post', __NAMESPACE__ . '\members_only_field', [ 'post', 'page' ], 'side' );
	}
);

//Meta callback function
function members_only_field( $post ) {
	$val = get_post_meta( $post->ID, 'dao-members-only', true );
	?>
	<div>
		<input type="checkbox" id="dao-members-only" name="dao-members-only" value='yes' <?php if ( $val === 'yes' ) echo 'checked'; ?>>
		<label for="dao-members-only">This post is only accesssible for the "DAO Members" role and above.</label>
	</div>
	<?php
}

//save meta value with save post hook
add_action(
	'save_post',
	function( $post_id ) {
		if ( isset( $_POST['dao-members-only'] ) ) {
			update_post_meta( $post_id, 'dao-members-only', sanitize_title( $_POST['dao-members-only'] ) );
		} else {
			delete_post_meta( $post_id, 'dao-members-only' );
		}
	}
);

// show meta value after post content
add_filter(
	'the_content',
	function( $content ) {
		if (
			get_post_meta( get_the_ID(), 'dao-members-only', true ) === 'yes' &&
			! current_user_can( 'access_members_only' )
		) {
			wp_enqueue_script( 'custom-login', plugin_dir_url( __FILE__ ) . 'login-script.js', array( 'wp-api-fetch' ), 1, true );
			return '<div class="entry-content">
			<p>This page is only accessible to logged in members.</p>
			<p>Please log in here using your Ethereum wallet:</p>
			' .
			wp_login_form(
				[
					'echo' => false,
					'value_remember' => true,
				]
			) .
			'
			<style> #loginform{ display:none;} </style>
			</div>';
		}

		return $content;
	}
);
