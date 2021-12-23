<?php
namespace Artpi\WPDAO;

class Web3 {
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}
	public function api( $data = [] ) {
		$data = array_merge(
			array(
				'id' => 0,
				'jsonrpc' => '2.0',
			),
			$data
		);

		$url  = $this->settings->get_alchemy_url();
		$response = wp_remote_post(
			$url,
			[
				'headers' => [
					'content-type' => 'application/json',
				],
				'body' => json_encode( $data ),
			]
		);
		return wp_remote_retrieve_body( $response );
	}
	function get_token_balances( $owner, $tokens ) {
		// TODO need some error hangling here.
		$payload = [
			'method' => 'alchemy_getTokenBalances',
			'params' => [ $owner, $tokens ],
		];
		$response = $this->api( $payload );
		$json = json_decode( $response );
		$balances = $json->result->tokenBalances;
		return $balances;
	}
}
