<?php
/*
Plugin Name: Yo!Payments gateway
Plugin URI: http://www.iwarecorporation.com/
Description: Extends WooCommerce with Yo!Payments gateway.
Version: 1.0.2
Author: Bbaale Emmy
Author URI: #
*/

if ( ! defined( 'ABSPATH' ) )
{
	exit;
}

add_action( 'plugins_loaded', 'init_yo_payments', 0);
 
function init_yo_payments() {
	
	/**
 	 * Gateway class
 	 */
	class WC_Gateway_Yopayments extends WC_Payment_Gateway {
		//Drama Starts Here.
		public function __construct() {
        global $woocommerce;
		
		$this->id = 'wc_yo-payment';
		$this->icon = plugins_url( 'assets/logo.jpg' , __FILE__ );
		$this->has_fields = true;
		$this->method_title =__('Yo Payments Gateway','yo_payment');
		
		// Load the form fields.
        $this->init_form_fields();
		 // Load the settings.
        $this->init_settings();
		
		$this -> title = $this -> settings['title'];
        $this -> description  = $this -> settings['description'];
		$this->instructions  = $this->get_option( 'instructions' );
        $this -> username  = $this -> settings['username'];
        $this -> password  = $this -> settings['password'];
            
		//Live URL	
        $this -> liveurl  = 'https://paymentsapi1.yo.co.ug/ybs/task.php';
		
		//Test Url 
		//$this -> liveurl  = 'https://41.220.12.206/services/yopaymentsdev/task.php';
		
		//Logs
    	//$this->log = $woocommerce->logger();
       
		//Actions.
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
            } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
			
		if ( !$this->is_valid_for_use() ) $this->enabled = false;
		}
		//Construct Stops Here.
		
		function is_valid_for_use() {
            if (!in_array(get_woocommerce_currency(), array('ARS', 'BRL', 'CLP', 'MXN', 'USD','UGX'))) return false;
            return true;
        }
		
		function init_form_fields(){
            $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'yo_payment'),
                    'type' => 'checkbox',
                    'label' => __('Enable Yo!Payment.', 'yo_payment'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:', 'yo_payment'),
                    'type'=> 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'yo_payment'),
                    'default' => __('Yo Payments Gateway', 'yo_payment')),
                'description' => array(
                    'title' => __('Description:', 'yo_payment'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'yo_payment'),
                    'default' => __('Pay with MTN mobile money', 'yo_payment')),
                'username' => array(
                    'title' => __('Username', 'yo_payment'),
                    'type' => 'text',
                    'description' => __('Your Yo!Payment Username.')),
                'password' => array(
                    'title' => __('Password', 'yo_payment'),
                    'type' => 'text',
                    'description' =>  __('Your Yo!payment password', 'yo_payment')),
				'instructions' => array(
				'title' => __( 'Instructions', 'yo_payment' ),
				'type' => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'yo_payment' ),
				'default' => __( 'Instructions for Yo!payment Gateway shown on Thank you page.', 'yo_payment' )
			       ));
				   }
		 
		  /* Admin Panel Options.*/
		function admin_options() {
		?>
		<h3><?php _e('Yo! Payment.','yo-payment'); ?></h3>
    	<table class="form-table">
    		<?php $this->generate_settings_html(); ?>
		</table> <?php
        }
		
		/*Payment  Fields*/
		 function payment_fields(){
            if($this -> description) echo wpautop(wptexturize($this -> description));
			echo wpautop(wptexturize('<select name="mobinetwork" id="mobinetwork"><option value="">Select Mobile Network.</option><option value="MTN_UGANDA">MTN Mobile Money</option></select>'));
			echo wpautop(wptexturize('<input name="cust_number" id="cust_number" style="max-width: 219px ! important; width: 219px;" maxlength="10" type="text" placeholder="Enter your mobile number." />'));
        }
		
		/*Validate Form*/
		function  validate_fields()
		{
			global $woocommerce;
			if (!$_POST['cust_number'])
			{
			//$woocommerce->add_error( __('Please provide mobile number to make payment.') );
			$error_message = "Please provide valid mobile number to make payment.";
			wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
			return;
			}
			
			// Check if Number is Valid
			$c_no = $_POST['cust_number'];
			$string_length = strlen($c_no) - substr_count($c_no, ' ');
			if (!is_numeric($_POST['cust_number']) || $string_length < 10)
			{
				//$woocommerce->add_error( __('Please provide a valid number.') );
				$error_message = "Please provide a valid number.";
				wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
				return;
			}
			
			//Check if Mobile network is provided.
			if (!$_POST['mobinetwork'])
			{
			//$woocommerce->add_error( __('Please provide mobile number to make payment.') );
			$error_message = "Please select a mobile network";
			wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
			return;
			}
		}
		
		/**
         * Process the payment and return the result
         **/
        function process_payment($order_id)
		{ 
			global $woocommerce;
			$order = new WC_Order( $order_id );
			
			//API Code here goes here.	
		
			/*Required Fields for API Request*/
			$API_username = $this -> username;
			$API_password = $this -> password;
			$link = $this -> liveurl;
			$Amt_Shs =  $order->order_total;
			$customer_number = $_POST['cust_number'];
			$AccProviderCode = $_POST['mobinetwork'];
			$c_num = '';
			for ($x=1; $x<=9; $x++)
			{
  				$c_num.= $customer_number[$x];
			}
			$num = '256'.$c_num;
			
			try
			{
			/**Get Money from Payer**/
			$response_array = deposit($API_username,$API_password,$link,$Amt_Shs,$num,$order_id,$AccProviderCode);
			/** Status Response **/
			$Status = $response_array['Response']['Status'];
				if($Status == 'OK')
					{
						/**Sucessful / Pending Response **/
						$TransactionStatus = $response_array['Response']['TransactionStatus'];
						
						if($TransactionStatus == 'SUCCEEDED')
						{
						//Complete Order.
						
						$order->update_status('completed', __( 'Order Payment Completed', 'woocommerce' ));
						//$order->payment_complete();

						// Return thankyou redirect
						return array(
						'result' 	=> 'success',
						'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks')))));					
						}
							else if($TransactionStatus == 'PENDING')
							{
							//We are not sure of the transaction Admin has to Cancel or Complete Order manually.
							$order->update_status('on-hold', __('Awaiting payment', 'woothemes'));
						
							//Set pending message here.
							return array(
							'result' 	=> 'success',
							'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks')))));	
							}
								
					}
					else if ($Status == 'ERROR')
					{
						/**Error Request**/
						$err_StatusMsg = $response_array['Response']['StatusMessage'];
						$err_TransactionStatus = $response_array['Response']['TransactionStatus'];
						
						if(!empty($err_TransactionStatus) && $err_TransactionStatus == 'FAILED')
						{
							//Order failed
							$order->update_status('failed', __('Payment failed', 'woothemes'));
							//$woocommerce->add_error(__('Payment error: '.$err_StatusMsg, 'woothemes') . $error_message);
							$error_message = "Payment failed";
							wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
							return;	
						}
						else if(!empty($err_TransactionStatus) && $err_TransactionStatus == 'INDETERMINATE')
						{
							//Order indeterminate
							$order->update_status('failed', __('Payment in indeterminate state', 'woothemes'));
							$error_message = "Payment in indeterminate state";
							wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
							//$woocommerce->add_error(__('Payment error: '.$err_StatusMsg, 'woothemes') . $error_message);
							return;	
						}
						else
						{
							//Its a normal error output that error.
							$order->update_status('failed', __('Payment failed', 'woothemes'));
							$error_message = "Payment failed. Please try again later.";
							wc_add_notice( __('Payment error: ', 'woothemes') . $error_message, 'error' );
							//$woocommerce->add_error(__('Payment error: '.$err_StatusMsg.$err_TransactionStatus, 'woothemes') . $error_message);
							return;	
						}
											
					}
			}
			catch(Exception $ex)
			{
				//Show the error here if payment was not processed.
				$order->update_status('failed', __('Payment failed', 'woothemes'));
				$error_message = 'An error occurred order not processed. Please try again.';
				//$woocommerce->add_error(__($err, 'woothemes') . $error_message);
				wc_add_notice( __('Payment error: ', 'woothemes') . $error_message, 'error' );
				return;
			}
			
		/**End Of Payment**/
        }
		
		 /* Output for the order received page.   */
	function thankyou() {
		echo $this->instructions != '' ? wpautop( $this->instructions ) : '';
		}
	}
	
	/**
 	* Add the Gateway to WooCommerce
 	**/
	function woocommerce_add_gateway_yopayment_gateway($methods) {
		$methods[] = 'WC_Gateway_Yopayments';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_yopayment_gateway' );
	
		/*
	YO!payments functions
	*/
	function deposit($username,$password,$url,$amount,$no,$order_id,$AccProviderCode)
	{
	$xml = '<?xml version="1.0" encoding="UTF-8"?>
	<AutoCreate>
	<Request>
	<APIUsername>'.$username.'</APIUsername>
	<APIPassword>'.$password.'</APIPassword>
	<Method>acdepositfunds</Method>
	<Amount>'.$amount.'</Amount>
	<Account>'.$no.'</Account>
	<AccountProviderCode>'.$AccProviderCode.'</AccountProviderCode>
	<Narrative>Payment to Worth Avenue Limited</Narrative>
	<ExternalReference>'.$order_id.'</ExternalReference>
	</Request>
	</AutoCreate>';
	
	//Using curl post Resquest
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	//curl_setopt($ch, CURLOPT_MUTE, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //ssl stuff
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml\nContent-transfer-encoding: text\n\n'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	
	// your return response
	$output = curl_exec($ch);
	if(curl_errno($ch))
	{
		echo 'error:' . curl_error($ch);
		//exit;
	}
	//Convert to xml
	$return_array = new SimpleXMLElement( $output );
	//Close Connection	
	curl_close($ch);
	//Convert to Array
	$array  = json_decode(json_encode($return_array), true);
	return $array;
	}
}
