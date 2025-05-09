<?php

/*
  MarketPress PayPal Chained Payments Gateway Plugin
  Author: DerN3rd 
 */

class MP_Gateway_Paypal_Chained_Payments extends MP_Gateway_API {

	//build
	var $build = 2;
	//private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name = 'paypal_chained';
	//name of your gateway, for the admin side.
	var $admin_name = '';
	//public name of your gateway, for lists and such.
	var $public_name = '';
	//url for an image for your checkout method. Displayed on checkout form if set
	var $method_img_url = '';
	//url for an submit button image for your checkout method. Displayed on checkout form if set
	var $method_button_img_url = '';
	//whether or not ssl is needed for checkout page
	var $force_ssl = false;
	//always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
	//whether if this is the only enabled gateway it can skip the payment_form step
	var $skip_form = true;
	//paypal vars
	var $API_Username, $API_Password, $API_Signature, $appId, $SandboxFlag, $API_Endpoint, $paypalURL, $currencyCode, $locale;

	/**
	 * Gateway currencies
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */
	var $currencies = array();

	/*	 * **** Below are the public methods you may overwrite via a plugin ***** */

	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {

		//set names here to be able to translate
		if ( is_super_admin() ) {
			$this->admin_name = __( 'PayPal Chained Payments', 'mp' );
		} else {
			$this->admin_name = __( 'PayPal', 'mp' );
		}

		$this->public_name = __( 'PayPal', 'mp' );

		//dynamic button img, see: https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_ECButtonIntegration
		$this->method_img_url        = 'https://fpdbs.paypal.com/dynamicimageweb?cmd=_dynamic-image&buttontype=ecmark&locale=' . get_locale();
		$this->method_button_img_url = 'https://fpdbs.paypal.com/dynamicimageweb?cmd=_dynamic-image&locale=' . get_locale();

		//set paypal vars
		$this->currencyCode = mp_get_setting( 'currency', 'USD' );
		$this->locale       = mp_get_setting( 'locale' );
		$this->returnURL    = $this->return_url;
		$this->cancelURL    = mp_store_page_url( 'checkout', false ); //$this->cancel_url;

		$this->currencies = array(
			'AUD' => __( 'AUD - Australian Dollar', 'mp' ),
			'BRL' => __( 'BRL - Brazilian Real', 'mp' ),
			'CAD' => __( 'CAD - Canadian Dollar', 'mp' ),
			'CHF' => __( 'CHF - Swiss Franc', 'mp' ),
			'CZK' => __( 'CZK - Czech Koruna', 'mp' ),
			'DKK' => __( 'DKK - Danish Krone', 'mp' ),
			'EUR' => __( 'EUR - Euro', 'mp' ),
			'GBP' => __( 'GBP - Pound Sterling', 'mp' ),
			'ILS' => __( 'ILS - Israeli Shekel', 'mp' ),
			'HKD' => __( 'HKD - Hong Kong Dollar', 'mp' ),
			'HUF' => __( 'HUF - Hungarian Forint', 'mp' ),
			'JPY' => __( 'JPY - Japanese Yen', 'mp' ),
			'MYR' => __( 'MYR - Malaysian Ringgits', 'mp' ),
			'MXN' => __( 'MXN - Mexican Peso', 'mp' ),
			'NOK' => __( 'NOK - Norwegian Krone', 'mp' ),
			'NZD' => __( 'NZD - New Zealand Dollar', 'mp' ),
			'PHP' => __( 'PHP - Philippine Pesos', 'mp' ),
			'PLN' => __( 'PLN - Polish Zloty', 'mp' ),
			'RUB' => __( 'RUB - Russian Rubles', 'mp' ),
			'SEK' => __( 'SEK - Swedish Krona', 'mp' ),
			'SGD' => __( 'SGD - Singapore Dollar', 'mp' ),
			'TWD' => __( 'TWD - Taiwan New Dollars', 'mp' ),
			'THB' => __( 'THB - Thai Baht', 'mp' ),
			'TRY' => __( 'TRY - Turkish lira', 'mp' ),
			'USD' => __( 'USD - U.S. Dollar', 'mp' ),
		);

		//set api urls
		//if ( mp_get_setting( 'gateways->paypal_chained->mode' ) == 'sandbox' ) {
		if ( $this->get_setting( 'mode' ) == 'sandbox' ) {
			$this->API_Endpoint  = "https://svcs.sandbox.paypal.com/AdaptivePayments/";
			$this->paypalURL     = "https://www.sandbox.paypal.com/webscr?cmd=_ap-payment&paykey=";
			$this->API_Username  = $this->get_network_setting( 'api_user_sandbox' );
			$this->API_Password  = $this->get_network_setting( 'api_pass_sandbox' );
			$this->API_Signature = $this->get_network_setting( 'api_sig_sandbox' );
			$this->appId         = 'APP-80W284485P519543T'; //this is PayPals generic test app id for sandbox
		} else {
			$this->API_Endpoint  = "https://svcs.paypal.com/AdaptivePayments/";
			$this->paypalURL     = "https://www.paypal.com/webscr?cmd=_ap-payment&paykey=";
			$this->API_Username  = $this->get_network_setting( 'api_user' );
			$this->API_Password  = $this->get_network_setting( 'api_pass' );
			$this->API_Signature = $this->get_network_setting( 'api_sig' );
			$this->appId         = $this->get_network_setting( 'app_id' );
		}
	}

	/**
	 * Return fields you need to add to the payment screen, like your credit card info fields.
	 *    If you don't need to add form fields set $skip_form to true so this page can be skipped
	 *    at checkout.
	 *
	 * @param array $cart . Contains the cart contents for the current blog, global cart if mp()->global_cart is true
	 * @param array $shipping_info . Contains shipping info and email in case you need it
	 */
	function payment_form( $cart, $shipping_info ) {
		if ( mp_get_request_value( 'mp_checkout_cancel_' . $this->plugin_name ) == '1' ) {
			mp_checkout()->add_error( __( 'Deine PayPal-Transaktion wurde abgebrochen.', 'mp' ), 'order-review-payment' );

			return false;
		} else {
			return __( 'Du wirst zur PayPal-Webseite weitergeleitet, um Deine Zahlung abzuschließen.', 'mp' );
		}
	}

	/**
	 * Use this to do the final payment. Create the order then process the payment. If
	 * you know the payment is successful right away go ahead and change the order status
	 * as well.
	 *
	 * @param MP_Cart $cart . Contains the MP_Cart object.
	 * @param array $billing_info . Contains billing info and email in case you need it.
	 * @param array $shipping_info . Contains shipping info and email in case you need it
	 */
	function process_payment( $cart, $billing_info, $shipping_info ) {
		global $current_user;

		// Create a new order object
		$order    = new MP_Order();
		$order_id = $order->get_id();

		//set it up with PayPal
		$result = $this->Pay( $cart, $shipping_info, $order_id );

		//check response
		if ( $result["responseEnvelope_ack"] == "Success" || $result["responseEnvelope_ack"] == "SuccessWithWarning" ) {

			$paykey = urldecode( $result["payKey"] );

			if ( session_id() == '' ) {
				session_start();
			}

			$_SESSION['PAYKEY'] = $paykey;

			//setup transients for ipn in case checkout doesn't redirect (ipn should come within 12 hrs!)
			set_transient( 'mp_order_' . $order_id . '_cart', $cart, 60 * 60 * 12 );
			set_transient( 'mp_order_' . $order_id . '_billing_info', $billing_info, 60 * 60 * 12 );
			set_transient( 'mp_order_' . $order_id . '_shipping_info', $shipping_info, 60 * 60 * 12 );

			//go to paypal for final payment confirmation
			$this->RedirectToPayPal( $paykey );
		} else { //whoops, error

			$error = "";
			for ( $i = 0; $i <= 5; $i ++ ) { //print the first 5 errors
				if ( isset( $result["error($i)_message"] ) ) {
					$error .= "<li>{$result[ "error($i)_errorId" ]} - {$result[ "error($i)_message" ]}</li>";
				}
			}

			if( empty( $error ) ){
				mp_checkout()->add_error( '<li>' . __( 'Es gab ein Problem beim Herstellen einer Verbindung zu PayPal, um Deinn Kauf abzuschließen. Bitte versuche es erneut.', 'mp' ) . '</li>' , 'order-review-payment' );
			} else {
				mp_checkout()->add_error( $error , 'order-review-payment' );
			}

			return false;
		}
	}

	/**
	 * Runs before page load incase you need to run any scripts before loading the success message page
	 */
	function process_checkout_return() {

		if ( session_id() == '' ) {
			session_start();
		}

		$result = $this->PaymentDetails( $_SESSION['PAYKEY'] );

		if ( $result["responseEnvelope_ack"] == "Success" || $result["responseEnvelope_ack"] == "SuccessWithWarning" ) {

			//setup our payment details
			$payment_info['gateway_public_name']  = $this->public_name;
			$payment_info['gateway_private_name'] = $this->admin_name;
			$payment_info['method']               = __( 'PayPal balance, Credit Card, or Instant Transfer', 'mp' );
			$payment_info['transaction_id']       = $result["paymentInfoList_paymentInfo(0)_transactionId"];

			$timestamp = time();
			$order_id  = $result["trackingId"];

			//setup status
			switch ( $result["paymentInfoList_paymentInfo(0)_transactionStatus"] ) {

				case 'PARTIALLY_REFUNDED':
					$status       = __( 'Die Zahlung wurde teilweise zurückerstattet.', 'mp' );
					$create_order = true;
					$paid         = true;
					break;

				case 'COMPLETED':
					$status       = __( 'Die Zahlung wurde abgeschlossen und das Guthaben wurde erfolgreich Deinem Kontostand hinzugefügt.', 'mp' );
					$create_order = true;
					$paid         = true;
					break;

				case 'PROCESSING':
					$status       = __( 'Die Transaktion wird ausgeführt.', 'mp' );
					$create_order = true;
					$paid         = true;
					break;

				case 'REVERSED':
					$status       = __( 'Du hast die Zahlung zurückerstattet.', 'mp' );
					$create_order = false;
					$paid         = false;
					break;

				case 'DENIED':
					$status       = __( 'Die Transaktion wurde vom Empfänger (Dir) abgelehnt..', 'mp' );
					$create_order = false;
					$paid         = false;
					break;

				case 'PENDING':
					$pending_str = array(
						'ADDRESS_CONFIRMATION' => __( 'Die Zahlung steht noch aus, da Dein Kunde keine bestätigte Versandadresse angegeben hat und Deine Zahlungsempfangseinstellungen so festgelegt sind, dass Du jede dieser Zahlungen manuell akzeptieren oder ablehnen möchtest. Um Deine Einstellungen zu ändern, gehe zum Abschnitt Einstellungen Deines Profils.', 'mp' ),
						'ECHECK'               => __( 'Die Zahlung steht noch aus, da sie von einem noch nicht eingelösten eCheck getätigt wurde.', 'mp' ),
						'INTERNATIONAL'        => __( 'Die Zahlung steht noch aus, da Du ein Konto außerhalb der USA besitzt und keinen Auszahlungsmechanismus hast. Du musst diese Zahlung in Deiner Kontoübersicht manuell akzeptieren oder ablehnen.', 'mp' ),
						'MULTI_CURRENCY'       => __( 'Du hast kein Guthaben in der gesendeten Währung und Deine Zahlungsempfangseinstellungen sind nicht so eingestellt, dass diese Zahlung automatisch konvertiert und akzeptiert wird. Du musst diese Zahlung manuell akzeptieren oder ablehnen.', 'mp' ),
						'RISK'                 => __( 'Die Zahlung steht noch aus, während sie von PayPal auf Risiko überprüft wird.', 'mp' ),
						'UNILATERAL'           => __( 'Die Zahlung steht noch aus, da sie an eine E-Mail-Adresse gesendet wurde, die noch nicht registriert oder bestätigt wurde.', 'mp' ),
						'UPGRADE'              => __( 'Die Zahlung steht noch aus, da sie per Kreditkarte erfolgt ist und Du Dein Konto auf den Business- oder Premier-Status aktualisieren musst, um das Geld zu erhalten. Dies kann auch bedeuten, dass Du das monatliche Limit für Transaktionen auf Deinem Konto erreicht hast.', 'mp' ),
						'VERIFY'               => __( 'Die Zahlung steht noch aus, da Du noch nicht überprüft wurdest. Du musst Dein Konto verifizieren, bevor Du diese Zahlung akzeptieren kannst.', 'mp' ),
						'OTHER'                => __( 'Die Zahlung steht aus einem unbekannten Grund aus. Weitere Informationen erhältst Du vom PayPal-Kundendienst.', 'mp' )
					);

					$status = __( 'Die Zahlung steht noch aus.', 'mp' );
					$status .= '<br />' . $pending_str[ $result["paymentInfoList_paymentInfo(0)_pendingReason"] ];
					$create_order = true;
					$paid         = false;
					break;

				default:
					// case: various error cases
					$create_order = false;
					$paid         = false;
			}

			$status = $result["paymentInfoList_paymentInfo(0)_transactionStatus"] . ': ' . $status;

			//status's are stored as an array with unix timestamp as key
			$payment_info['status'][ $timestamp ] = $status;
			$payment_info['total']                = $result["paymentInfoList_paymentInfo(0)_receiver_amount"];
			$payment_info['currency']             = $result["currencyCode"];

			//succesful payment, create our order now
			if ( $create_order ) {
				$order_id = $result["trackingId"];

				$cart          = get_transient( 'mp_order_' . $order_id . '_cart' );
				$shipping_info = get_transient( 'mp_order_' . $order_id . '_shipping_info' );
				$billing_info  = get_transient( 'mp_order_' . $order_id . '_billing_info' );

				$order = new MP_Order( $order_id );

				if ( ! $order->exists() ) {
					$order->save( array(
						'cart'          => $cart,
						'payment_info'  => $payment_info,
						'billing_info'  => $billing_info,
						'shipping_info' => $shipping_info,
						'paid'          => true,
					) );
					//delete_transient( 'mp_order_' . $order_id . '_cart' );
					//delete_transient( 'mp_order_' . $order_id . '_shipping_info' );
					//delete_transient( 'mp_order_' . $order_id . '_billing_info' );
				}

				wp_redirect( $order->tracking_url( false ) );
				exit;
			} else {
				mp_checkout()->add_error( __( 'Entschuldigung, Deine Bestellung wurde nicht abgeschlossen.', 'mp' ) );

				return false;
			}
		} else { //whoops, error
			for ( $i = 0; $i <= 5; $i ++ ) { //print the first 5 errors
				if ( isset( $result["error($i)_message"] ) ) {
					$error .= "<li>{$result[ "error($i)_errorId" ]} - {$result[ "error($i)_message" ]}</li>";
				}
			}
			$error = '<br /><ul>' . $error . '</ul>';
			mp_checkout()->add_error( sprintf( __( 'Beim Herstellen einer Verbindung zu PayPal ist ein Problem aufgetreten, um den Status Deines Kaufs zu überprüfen. Bitte <a href="%s">Überprüfe hier den Status Deiner Bestellung &raquo;</a>', 'mp' ) . $error ) ); // mp_orderstatus_link( false, true )
			return false;
		}
	}

	/**
	 * Get the confirm order html
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_checkout/confirm_order_html/{plugin_name}
	 */
	public function confirm_order_html( $html ) {
		//print payment details
		$html = '<a href="#" onclick="javascript:window.open(\'https://www.paypal.com/cgi-bin/webscr?cmd=xpt/Marketing/popup/OLCWhatIsPayPal-outside\',\'olcwhatispaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=400, height=350\');return false;"><img	 src="https://www.paypal.com/en_US/i/bnr/horizontal_solution_PPeCheck.gif" border="0" alt="PayPal"></a>';

		//$html .= parent::confirm_order_html( $html );
		return $html;
	}

	/**
	 * Initialize the settings metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_settings_metabox() {

		$default_desc = __( "Bitte beachte, dass wir zusätzlich zu den Gebühren, die PayPal Dir möglicherweise berechnet, eine Gebühr von ?% Von der Gesamtsumme jeder Transaktion abziehen. Wenn Du aus irgendeinem Grund einen Kunden für eine Bestellung erstatten musst, kontaktiere uns bitte mit einem Screenshot des Rückerstattungsbelegs in Deiner PayPal-Historie sowie der Transaktions-ID unseres Gebührenabzugs, damit wir Dir eine Rückerstattung ausstellen können. Danke!", 'mp' );
		$desc_msg     = $this->get_network_setting( 'msg' );

		$metabox = new PSOURCE_Metabox( array(
			'id'          => $this->generate_metabox_id(),
			'page_slugs'  => array( 'shop-einstellungen-payments', 'shop-einstellungen_page_shop-einstellungen-payments' ),
			'title'       => sprintf( __( '%s Einstellungen', 'mp' ), $this->admin_name ),
			'option_name' => 'mp_settings',
			'desc'        => ! empty( $desc_msg ) ? $desc_msg : $default_desc,
			'conditional' => array(
				'name'   => 'gateways[allowed][' . $this->plugin_name . ']',
				'value'  => 1,
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'advanced_select', array(
			'name'          => $this->get_field_name( 'currency' ),
			'label'         => array( 'text' => __( 'Währung', 'mp' ) ),
			'width'         => 'element',
			'multiple'      => false,
			'options'       => $this->currencies,
			'default_value' => mp_get_setting( 'currency' ),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'          => $this->get_field_name( 'mode' ),
			'label'         => array( 'text' => __( 'Modus', 'mp' ) ),
			'options'       => array(
				'sandbox' => __( 'Sandbox', 'mp' ),
				'live'    => __( 'Live', 'mp' ),
			),
			'default_value' => 'sandbox',
		) );

		$metabox->add_field( 'text', array(
			'name'       => $this->get_field_name( 'email' ),
			'label'      => array( 'text' => __( 'Email Addresse', 'mp' ) ),
			'validation' => array(
				'required' => true,
				'email'    => true
			),
		) );
	}

	/**
	 * Updates the gateway settings
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	function update( $settings ) {
		if ( ( $mode = $this->get_setting( 'mode' ) ) && ( $email = $this->get_setting( 'email' ) ) ) {
			// Update api user
			mp_push_to_array( $settings, 'gateways->paypal_chained->email', $email );

			// Update api pass
			mp_push_to_array( $settings, 'gateways->paypal_chained->mode', $mode );

			// Unset old keys
			unset( $settings['gateways']['paypal_chained']['email'], $settings['gateways']['paypal_chained']['mode'] );
		}

		return $settings;
	}

	/**
	 * Use to handle any payment returns from your gateway to the ipn_url. Do not echo anything here. If you encounter errors
	 *    return the proper headers to your ipn sender. Exits after.
	 */
	function process_ipn_return() {

		$txn_type    = mp_get_post_value( 'transaction_type' );
		$tracking_id = mp_get_post_value( 'tracking_id' );

		if ( empty( $txn_type ) || empty( $tracking_id ) ) {
			header( 'Status: 404 Not Found' );
			echo 'Error: Missing POST variables. Identification is not possible.';
			exit;
		}

		// Read POST data
		// reading posted data directly from $_POST causes serialization
		// issues with array data in POST. Reading raw POST data from input stream instead.
		$raw_post_data  = file_get_contents( 'php://input' );
		$raw_post_array = explode( '&', $raw_post_data );
		$myPost         = array();

		foreach ( $raw_post_array as $keyval ) {
			$keyval = explode( '=', $keyval );
			if ( count( $keyval ) == 2 ) {
				$myPost[ $keyval[0] ] = urldecode( $keyval[1] );
			}
		}

		// read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-validate';
		if ( function_exists( 'get_magic_quotes_gpc' ) ) {
			$get_magic_quotes_exists = true;
		}

		foreach ( $myPost as $key => $value ) {
			if ( $get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1 ) {
				$value = urlencode( stripslashes( $value ) );
			} else {
				$value = urlencode( $value );
			}
			$req .= "&$key=$value";
		}

		// Post IPN data back to PayPal to validate the IPN data is genuine
		// Without this step anyone can fake IPN data
		if ( 'sandbox' == $this->get_setting( 'mode' ) ) {
			$paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		} else {
			$paypal_url = "https://www.paypal.com/cgi-bin/webscr";
		}

		$response = wp_remote_post( $paypal_url, array(
			'user-agent' => 'MarketPress/' . MP_VERSION . ': http://premium.psource.org/project/e-commerce | PayPal Chained Payments Plugin/' . MP_VERSION,
			'body'       => $req,
			'sslverify'  => false,
			'timeout'    => mp_get_api_timeout( $this->plugin_name ),
		) );

		//check results
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 || $response['body'] != 'VERIFIED' ) {
			header( "HTTP/1.1 503 Service Unavailable" );
			_e( 'Beim Überprüfen der IPN-Zeichenfolge mit PayPal ist ein Problem aufgetreten. Bitte versuche es erneut.', 'mp' );
			exit;
		}

		header( 'HTTP/1.1 200 OK' );

		$result = $this->decodePayPalIPN( file_get_contents( 'php://input' ) );

		//setup our payment details
		$payment_info['gateway_public_name']  = $this->public_name;
		$payment_info['gateway_private_name'] = $this->admin_name;
		$payment_info['method']               = __( 'PayPal-Guthaben, Kreditkarte oder sofortige Überweisung', 'mp' );
		$payment_info['transaction_id']       = $result["transaction"][0]["id"];

		$timestamp = time();
		$order_id  = $tracking_id;

		//setup status
		switch ( strtoupper( $result["transaction"][0]["status"] ) ) {

			case 'PARTIALLY_REFUNDED':
				$status       = __( 'Die Zahlung wurde teilweise zurückerstattet.', 'mp' );
				$create_order = true;
				$paid         = true;
				break;

			case 'COMPLETED':
			case 'SUCCESS':
				$status       = __( 'Die Zahlung wurde abgeschlossen und das Guthaben wurde erfolgreich Deinem Kontostand hinzugefügt.', 'mp' );
				$create_order = true;
				$paid         = true;
				break;

			case 'PROCESSING':
				$status       = __( 'Die Transaktion wird ausgeführt.', 'mp' );
				$create_order = true;
				$paid         = true;
				break;

			case 'REVERSED':
				$status       = __( 'Du hast die Zahlung zurückerstattet.', 'mp' );
				$create_order = false;
				$paid         = false;
				break;

			case 'DENIED':
				$status       = __( 'Die Transaktion wurde vom Empfänger (Dir) abgelehnt..', 'mp' );
				$create_order = false;
				$paid         = false;
				break;

			case 'PENDING':
				$pending_str = array(
					'ADDRESS_CONFIRMATION' => __( 'Die Zahlung steht noch aus because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile.', 'mp' ),
					'ECHECK'               => __( 'Die Zahlung steht noch aus, da sie von einem noch nicht eingelösten eCheck getätigt wurde.', 'mp' ),
					'INTERNATIONAL'        => __( 'Die Zahlung steht noch aus, da Du ein Konto außerhalb der USA besitzt und keinen Auszahlungsmechanismus hast. Du musst diese Zahlung in Deiner Kontoübersicht manuell akzeptieren oder ablehnen.', 'mp' ),
					'MULTI_CURRENCY'       => __( 'Du hast kein Guthaben in der gesendeten Währung und Deine Zahlungsempfangseinstellungen sind nicht so eingestellt, dass diese Zahlung automatisch konvertiert und akzeptiert wird. Du musst diese Zahlung manuell akzeptieren oder ablehnen.', 'mp' ),
					'RISK'                 => __( 'Die Zahlung steht noch aus, während sie von PayPal auf Risiko überprüft wird.', 'mp' ),
					'UNILATERAL'           => __( 'Die Zahlung steht noch aus, da sie an eine E-Mail-Adresse gesendet wurde, die noch nicht registriert oder bestätigt wurde.', 'mp' ),
					'UPGRADE'              => __( 'Die Zahlung steht noch aus, da sie per Kreditkarte erfolgt ist und Du Dein Konto auf den Business- oder Premier-Status aktualisieren musst, um das Geld zu erhalten. Dies kann auch bedeuten, dass Du das monatliche Limit für Transaktionen auf Deinem Konto erreicht hast.', 'mp' ),
					'VERIFY'               => __( 'Die Zahlung steht noch aus, da Du noch nicht überprüft wurdest. Du musst Dein Konto verifizieren, bevor Du diese Zahlung akzeptieren kannst.', 'mp' ),
					'OTHER'                => __( 'Die Zahlung steht aus einem unbekannten Grund aus. Weitere Informationen erhältst Du vom PayPal-Kundendienst.', 'mp' )
				);

				$status = __( 'Die Zahlung steht noch aus', 'mp' );
				$status .= ': ' . $pending_str[ $result["transaction"][0]["pending_reason"] ];
				$create_order = true;
				$paid         = false;
				break;

			default:
				// case: various error cases
				$create_order = false;
				$paid         = false;
		}

		$status = $result["transaction"][0]["status"] . ': ' . $status;

		//status's are stored as an array with unix timestamp as key
		$payment_info['status'][ $timestamp ] = $status;
		$payment_info['total']                = substr( $result["transaction"][0]["amount"], 4 );
		$payment_info['currency']             = substr( $result["transaction"][0]["amount"], 0, 3 );

		$order = new MP_Order( $tracking_id );

		if ( $order->exists() ) {
			$order->change_status( ( $paid ) ? 'paid' : 'received' );

			delete_transient( 'mp_order_' . $tracking_id . '_cart' );
			delete_transient( 'mp_order_' . $tracking_id . '_billing_info' );
			delete_transient( 'mp_order_' . $tracking_id . '_shipping_info' );
		} else if ( $create_order ) {
			//succesful payment, create our order now
			$cart          = get_transient( 'mp_order_' . $tracking_id . '_cart' );
			$billing_info  = get_transient( 'mp_order_' . $tracking_id . '_billing_info' );
			$shipping_info = get_transient( 'mp_order_' . $tracking_id . '_shipping_info' );

			$order = new MP_Order( $tracking_id );

			if ( ! $order->exists() ) {
				$order->save( array(
					'cart'          => $cart,
					'payment_info'  => $payment_info,
					'billing_info'  => $billing_info,
					'shipping_info' => $shipping_info,
					'paid'          => true,
				) );
			}
		}
	}

	/*	 * ** PayPal API methods **** */

	function decodePayPalIPN( $raw_post ) {
		if ( empty( $raw_post ) ) {
			return array();
		}
		$post  = array();
		$pairs = explode( '&', $raw_post );
		foreach ( $pairs as $pair ) {
			list( $key, $value ) = explode( '=', $pair, 2 );
			$key   = urldecode( $key );
			$value = urldecode( $value );
			# This is look for a key as simple as 'return_url' or as complex as 'somekey[x].property'
			preg_match( '/(\w+)(?:\[(\d+)\])?(?:\.(\w+))?/', $key, $key_parts );
			switch ( count( $key_parts ) ) {
				case 4:
					# Original key format: somekey[x].property
					# Converting to $post[somekey][x][property]
					if ( ! isset( $post[ $key_parts[1] ] ) ) {
						$post[ $key_parts[1] ] = array( $key_parts[2] => array( $key_parts[3] => $value ) );
					} else if ( ! isset( $post[ $key_parts[1] ][ $key_parts[2] ] ) ) {
						$post[ $key_parts[1] ][ $key_parts[2] ] = array( $key_parts[3] => $value );
					} else {
						$post[ $key_parts[1] ][ $key_parts[2] ][ $key_parts[3] ] = $value;
					}
					break;
				case 3:
					# Original key format: somekey[x]
					# Converting to $post[somkey][x]
					if ( ! isset( $post[ $key_parts[1] ] ) ) {
						$post[ $key_parts[1] ] = array();
					}
					$post[ $key_parts[1] ][ $key_parts[2] ] = $value;
					break;
				default:
					# No special format
					$post[ $key ] = $value;
					break;
			}#switch
		}#foreach

		return $post;
	}

	//Purpose: 	Prepares the parameters for the Pay API Call.
	function Pay( $cart, $shipping_info, $order_id ) {
		$nvpstr = "actionType=PAY";
		$nvpstr .= "&returnUrl=" . $this->returnURL;
		$nvpstr .= "&cancelUrl=" . $this->cancelURL;
		$nvpstr .= "&ipnNotificationUrl=" . $this->ipn_url;
		$nvpstr .= "&currencyCode=" . $this->currencyCode;
		$nvpstr .= "&feesPayer=PRIMARYRECEIVER";
		$nvpstr .= "&trackingId=" . $order_id;
		$nvpstr .= "&memo=" . urlencode( sprintf( __( '%s Shopeinkauf - Bestellnummer: %s', 'mp' ), get_bloginfo( 'name' ), $order_id ) ); //cart name
		//loop through cart items

		$total      = $cart->total();
		$base_total = $cart->product_total( false );

		//calculate fees / get fees only for base price (excluding taxes and shipping)
		$percentage = $this->get_network_setting( 'percentage', 0.01 );
		$fee        = round( $percentage * 0.01 * $base_total, 2 );

		$nvpstr .= "&receiverList.receiver(0).email=" . urlencode( $this->get_setting( 'email' ) );
		$nvpstr .= "&receiverList.receiver(0).amount=" . round( $total, 2 );
		$nvpstr .= "&receiverList.receiver(0).invoiceId=" . $order_id;
		$nvpstr .= "&receiverList.receiver(0).paymentType=GOODS";
		$nvpstr .= "&receiverList.receiver(0).primary=true";

		$nvpstr .= "&receiverList.receiver(1).email=" . urlencode( $this->get_network_setting( 'email' ) );
		$nvpstr .= "&receiverList.receiver(1).amount=" . $fee;
		$nvpstr .= "&receiverList.receiver(1).paymentType=SERVICE";
		$nvpstr .= "&receiverList.receiver(1).primary=false";

		//make the call
		return $this->api_call( "Pay", $nvpstr );
	}

	//Purpose: 	Prepares the parameters for the Pay API Call.
	function PaymentDetails( $paykey ) {

		$nvpstr = "payKey=" . urlencode( $paykey ) . "&senderOptions.referrerCode=incsub_SP";

		//make the call
		return $this->api_call( "PaymentDetails", $nvpstr );
	}

	function api_call( $methodName, $nvpStr ) {

		//build args
		$args['headers'] = array(
			'X-PAYPAL-SECURITY-USERID'         => $this->API_Username,
			'X-PAYPAL-SECURITY-PASSWORD'       => $this->API_Password,
			'X-PAYPAL-SECURITY-SIGNATURE'      => $this->API_Signature,
			'X-PAYPAL-DEVICE-IPADDRESS'        => $_SERVER['REMOTE_ADDR'],
			'X-PAYPAL-REQUEST-DATA-FORMAT'     => 'NV',
			'X-PAYPAL-REQUEST-RESPONSE-FORMAT' => 'NV',
			'X-PAYPAL-APPLICATION-ID'          => $this->appId
		);

		$args['user-agent'] = "MarketPress/{mp()->version}: http://premium.psource.org/project/e-commerce | PayPal Chained Payments Plugin/{mp()->version}";
		$args['body']       = $nvpStr . '&requestEnvelope.errorLanguage=en_US';
		$args['sslverify']  = false;
		$args['timeout']    = 60;
		// Paypals sandbox stopped supporting HTTP 1.0 and only supports HTTP 1.1
		$args['httpversion']    = '1.1';

		//allow easy debugging
		if ( defined( "MP_DEBUG_API_$methodName" ) ) {
			var_dump( $args );
			die;
		}

		//use built in WP http class to work with most server setups
		$response = wp_remote_post( $this->API_Endpoint . $methodName, $args );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
			mp_checkout()->add_error( __( 'Beim Herstellen einer Verbindung zu PayPal ist ein Problem aufgetreten. Bitte versuche es erneut.', 'mp' ) );

			return false;
		} else {
			//convert NVPResponse to an Associative Array
			$nvpResArray = $this->deformatNVP( $response['body'] );

			return $nvpResArray;
		}
	}

	function RedirectToPayPal( $token ) {
		// Redirect to paypal.com here
		$payPalURL = $this->paypalURL . $token;
		wp_redirect( $payPalURL );
		exit;
	}

	//This function will take NVPString and convert it to an Associative Array and it will decode the response.
	function deformatNVP( $nvpstr ) {
		parse_str( $nvpstr, $nvpArray );

		return $nvpArray;
	}

}

//only load on multisite and if global cart is disabled
if ( is_plugin_active_for_network( mp_get_plugin_slug() ) && ! mp_cart()->is_global ) {

	//set names here to be able to translate
	if ( is_super_admin() ) {
		$admin_name = __( 'PayPal Chained Payments', 'mp' );
	} else {
		$admin_name = __( 'PayPal', 'mp' );
	}

	//register gateway plugin
	mp_register_gateway_plugin( 'MP_Gateway_Paypal_Chained_Payments', 'paypal_chained', $admin_name );

	//tie into network settings form
	add_action( 'mp_multisite_init_metaboxes', 'init_paypal_chained_payments_network_settings_metaboxes' );

	function pp_get_field_name( $name ) {
		$name_parts = explode( '->', $name );

		foreach ( $name_parts as &$part ) {
			$part = '[' . $part . ']';
		}

		return "gateways[paypal_chained]" . implode( $name_parts );
	}

	//multisite network options
	function init_paypal_chained_payments_network_settings_metaboxes() {
		$metabox = new PSOURCE_Metabox( array(
			'id'               => 'mp-network-settings-paypal-chained-payments',
			'page_slugs'       => array( 'network-shop-einstellungen' ),
			'title'            => __( 'PayPal Chained Payments', 'mp' ),
			'desc'             => __( 'Mit PayPal Chained Payments kannst Du als Netzwerkbesitzer mit mehreren Webseiten eine vordefinierte Gebühr oder einen Prozentsatz aller Verkäufe in MarketPress-Filialen im Netzwerk erheben! Dies ist für Kunden, die Artikel in einem Geschäft kaufen, unsichtbar. Alle PayPal-Gebühren werden dem Geschäftsinhaber in Rechnung gestellt. Um diese Option verwenden zu können, musst Du API-Anmeldeinformationen erstellen und alle anderen Gateways oben nicht verfügbar oder eingeschränkt machen.', 'mp' ),
			'site_option_name' => 'mp_network_settings',
			'order'            => 16,
			'conditional'      => array(
				'operator' => 'AND',
				'action'   => 'hide',
				array(
					'name'  => 'global_cart',
					'value' => 1,
				),
			),
		) );

		$metabox->add_field( 'text', array(
			'name'          => pp_get_field_name( 'percentage' ),
			'label'         => array( 'text' => __( 'Zu sammelnde Gebühren (%)', 'mp' ) ),
			'desc'          => __( 'Gib einen Prozentsatz aller Filialverkäufe ein, die als Gebühr erhoben werden sollen. Dezimalstellen erlaubt.', 'mp' ),
			'custom'        => array( 'style' => 'width:60px' ),
			'before_field'  => '',
			'default_value' => '0.01',
			'validation'    => array(
				'required' => true,
				'number'   => true,
				'min'      => 0.01,
			),
		) );

		$metabox->add_field( 'text', array(
			'name'         => pp_get_field_name( 'email' ),
			'label'        => array( 'text' => __( 'PayPal E-Mail', 'mp' ) ),
			'desc'         => __( 'Bitte gib Deine PayPal-E-Mail-Adresse oder Geschäfts-ID ein, unter der Du Gebühren erhalten möchtest.', 'mp' ),
			'custom'       => array( 'style' => 'width:250px' ),
			'before_field' => '',
			'validation'   => array(
				'required' => true,
				'email'    => true,
			),
		) );

		$metabox->add_field( 'textarea', array(
			'name'         => pp_get_field_name( 'msg' ),
			'label'        => array( 'text' => __( 'Nachricht auf der Seite "Gateway-Einstellungen"', 'mp' ) ),
			'desc'         => __( "Diese Meldung wird oben auf der Seite mit den Gateway-Einstellungen angezeigt, um Administratoren zu informieren. Es ist ein guter Ort, um sie über Deine Gebühren zu informieren oder Verkaufsnachrichten zu hinterlassen.", 'mp' ),
			'custom'       => array( 'style' => 'width:400px; height: 150px;' ),
			'before_field' => '',
		) );


		/* $metabox->add_field( 'radio_group', array(
		  'name'			 => pp_get_field_name( 'ppcn_mode' ),
		  'label'			 => array( 'text' => __( 'Gateway Mode', 'mp' ) ),
		  'default_value'	 => 'sandbox',
		  'options'		 => array(
		  'sandbox'	 => 'Sandbox',
		  'live'		 => 'Live',
		  ),
		  ) ); */

		$metabox->add_field( 'text', array(
			'name'         => pp_get_field_name( 'api_user_sandbox' ),
			'label'        => array( 'text' => __( 'API Benutzername (Sandbox)', 'mp' ) ),
			'desc'         => __( 'Du musst Dich bei PayPal anmelden und eine API-Signatur erstellen und um Deine Anmeldeinformationen abzurufen. <a target="_blank" href="https://developer.paypal.com/webapps/developer/docs/classic/api/apiCredentials/">Anleitung &raquo;</a>', 'mp' ),
			'custom'       => array( 'style' => 'width:250px' ),
			'before_field' => '',
		) );

		$metabox->add_field( 'password', array(
			'name'         => pp_get_field_name( 'api_pass_sandbox' ),
			'label'        => array( 'text' => __( 'API Passwort (Sandbox)', 'mp' ) ),
			'custom'       => array( 'style' => 'width:250px' ),
			'before_field' => '',
		) );

		$metabox->add_field( 'text', array(
			'name'         => pp_get_field_name( 'api_sig_sandbox' ),
			'label'        => array( 'text' => __( 'Signatur (Sandbox)', 'mp' ) ),
			'custom'       => array( 'style' => 'width:250px' ),
			'before_field' => '',
		) );

		$metabox->add_field( 'text', array(
			'name'         => pp_get_field_name( 'api_user' ),
			'label'        => array( 'text' => __( 'API Benutzername (Live)', 'mp' ) ),
			'desc'         => __( 'Du musst Dich bei PayPal anmelden und eine API-Signatur erstellen und um Deine Anmeldeinformationen abzurufen. <a target="_blank" href="https://developer.paypal.com/webapps/developer/docs/classic/api/apiCredentials/">Anleitung &raquo;</a>', 'mp' ),
			'custom'       => array( 'style' => 'width:250px' ),
			'before_field' => '',
		) );

		$metabox->add_field( 'password', array(
			'name'         => pp_get_field_name( 'api_pass' ),
			'label'        => array( 'text' => __( 'API Passwort (Live)', 'mp' ) ),
			'custom'       => array( 'style' => 'width:250px' ),
			'before_field' => '',
		) );

		$metabox->add_field( 'text', array(
			'name'         => pp_get_field_name( 'api_sig' ),
			'label'        => array( 'text' => __( 'Signatur (Live)', 'mp' ) ),
			'custom'       => array( 'style' => 'width:250px' ),
			'before_field' => '',
		) );

		$metabox->add_field( 'text', array(
			'name'         => pp_get_field_name( 'app_id' ),
			'label'        => array( 'text' => __( 'Anwendungs-ID (Live)', 'mp' ) ),
			'desc'         => __( 'Du musst diese Anwendung bei PayPal mit Deinem Geschäftskonto anmelden, um eine Anwendungs-ID zu erhalten, die mit Deinen API-Anmeldeinformationen funktioniert. <a target="_blank" href="https://developer.paypal.com/docs/classic/lifecycle/goingLive/#register">Mehr Informationen &raquo;</a>', 'mp' ),
			'custom'       => array( 'style' => 'width:250px' ),
			'before_field' => '',
		) );
	}

}

//register shipping plugin

if ( is_plugin_active_for_network( mp_get_plugin_slug() ) && ! mp_get_network_setting( 'global_cart' ) ) {
	mp_register_gateway_plugin( 'MP_Gateway_Paypal_Chained_Payments', 'paypal_chained', __( 'PayPal Chained Payments', 'mp' ) );
}