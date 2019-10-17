<?php
/**
 * Class WC_Gateway_Paidy file.
 *
 * @package WooCommerce\Gateways
 */

use \ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_2 as Framework;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Paidy Payment Gateway in Japanese
 *
 * Provides a Paidy Payment Gateway in Japanese. Based on code by Shohei Tanaka.
 *
 * @class 		WC_Gateway_Paidy
 * @extends		WC_Payment_Gateway
 * @version		1.0.3
 * @package		WooCommerce/Classes/Payment
 * @author 		Artisan Workshop
 */
class WC_Gateway_Paidy extends WC_Payment_Gateway {

    /**
     * Framework.
     *
     * @var class
     */
    public $jp4wc_framework;

    /**
     * debug mode
     *
     * @var string
     */
    public $debug;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
		$this->id                 = 'paidy';
		$this->icon               = apply_filters('woocommerce_paidy_icon', '');
		$this->has_fields         = false;
        $this->order_button_text = sprintf(__( 'Proceed to %s', 'woocommerce-for-japan' ), __('Paidy', 'woocommerce-for-japan' ));

		// Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();

        $this->method_title       = __( 'Paidy Payment', 'woocommerce-for-japan' );
        $this->method_description = __( '"Paidy next month payment" reduces the opportunity loss due to the payment method and contributes to sales increase.', 'woocommerce-for-japan' );

        $this->supports = array(
            'products',
            'refunds',
        );

        $this->jp4wc_framework = new Framework\JP4WC_Plugin();

		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

        // Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );

		// Actions Hook
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

	    add_action( 'woocommerce_review_order_before_submit', array( $this, 'make_order_javascript' ) );
	    add_filter( 'woocommerce_order_button_html', array( $this, 'order_button_html' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'paidy_token_scripts_method' ) );

        add_action( 'woocommerce_order_status_completed', array( $this, 'jp4wc_order_paidy_status_completed' ) );
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'jp4wc_order_paidy_status_cancelled' ) );

        add_filter( 'woocommerce_get_script_data', array( $this, 'delete_notice' ), 99, 2 );

    }
    /**
     * Initialise Gateway Settings Form Fields
     */
	public function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-for-japan' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Paidy', 'woocommerce-for-japan' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-for-japan' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'Paidy Payment', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-for-japan' ),
                'default'     => __( 'No matter how many times you shop a month, you pay once in the following month. <br /> The following payment methods are available.', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
            'order_button_text' => array(
                'title'       => __( 'Order Button Text', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-for-japan' ),
                'default'     => sprintf(__( 'Proceed to %s', 'woocommerce-for-japan' ), __('Paidy', 'woocommerce-for-japan' )),
            ),
            'environment' => array(
                'title'       => __( 'Environment', 'woocommerce-for-japan' ),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __( 'This setting specifies whether you will process live transactions, or whether you will process simulated transactions using the Paidy Sandbox.', 'woocommerce-for-japan' ),
                'default'     => 'live',
                'desc_tip'    => true,
                'options'     => array(
                    'live'    => __( 'Live', 'woocommerce-for-japan' ),
                    'sandbox' => __( 'Sandbox', 'woocommerce-for-japan' ),
                ),
            ),
            'api_public_key' => array(
                'title'       => __( 'API Public Key', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => sprintf(__( 'Please enter %s from Paidy Admin site.', 'woocommerce-for-japan' ),__( 'API Public Key', 'woocommerce-for-japan' )),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'api_secret_key' => array(
                'title'       => __( 'API Secret Key', 'woocommerce-for-japan' ),
                'type'        => 'password',
                'description' => sprintf(__( 'Please enter %s from Paidy Admin site.', 'woocommerce-for-japan' ),__( 'API Secret Key', 'woocommerce-for-japan' )),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'test_api_public_key' => array(
                'title'       => __( 'Test API Public Key', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => sprintf(__( 'Please enter %s from Paidy Admin site.', 'woocommerce-for-japan' ),__( 'Test API Public Key', 'woocommerce-for-japan' )),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'test_api_secret_key' => array(
                'title'       => __( 'Test API Secret Key', 'woocommerce-for-japan' ),
                'type'        => 'password',
                'description' => sprintf(__( 'Please enter %s from Paidy Admin site.', 'woocommerce-for-japan' ),__( 'Test API Secret Key', 'woocommerce-for-japan' )),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'store_name'       => array(
                'title'       => __( 'Store Name', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => __( 'This controls the store name which the user sees during paidy checkout.', 'woocommerce-for-japan' ),
                'default'     => get_bloginfo( 'name' )
            ),
            'logo_image_url' => array(
                'title'       => __( 'Logo Image (168×168 recommend)', 'woocommerce-for-japan' ),
                'type'        => 'image',
                'description' => __( 'URL of a custom logo that can be displayed in the checkout application header. If no value is specified, the Paidy logo will be displayed.', 'woocommerce-for-japan' ),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => __( 'Optional', 'woocommerce-for-japan' ),
            ),
            'debug' => array(
                'title'   => __( 'Debug Mode', 'woocommerce-for-japan' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Debug Mode', 'woocommerce-for-japan' ),
                'default' => 'no',
                'description' => __( 'Save debug data using WooCommerce logging.', 'woocommerce-for-japan' ),
            ),
            'webhook' => array(
                'title'   => __( 'About Webhook', 'woocommerce-for-japan' ),
                'type'    => 'title',
                'description' => __( 'The webhooks set in the Paidy management screen are as follows. <br />', 'woocommerce-for-japan' ) . '<strong>' . site_url() . '/wp-json/paidy/v1/order/' . '</strong>',
            ),
		);
    }

    /**
     * UI - Payment page Description fields for Paidy Payment.
     */
    function payment_fields() {
        // Description of payment method from settings
        ?>
        <br />
        <a href="https://paidy.com/consumer" target="_blank">
            <img src="<?php echo WC_PAIDY_PLUGIN_URL;?>assets/images/checkout_banner_320x100.png" alt="Paidy 翌月まとめてお支払い" style="max-height: none; float: none;"></a>
        <br />
        <p><?php echo $this->description; ?></p>
        <br />
        <ul>
            <li style="list-style: disc !important;">口座振替(支払手数料:無料)</li>
            <li style="list-style: disc !important;">コンビニ(支払手数料:350円税込)</li>
            <li style="list-style: disc !important;">銀行振込(支払手数料:金融機関により異なります)</li>
        </ul>
        Paidyについて詳しくは<a href="https://paidy.com/whatspaidy" target="_blank">こちら</a>。
        <?php
    }

    /**
     * make order data for javascript
     */
    public function make_order_javascript(){
        global $woocommerce;
        //Set public key by environment.
        if( $this->environment == 'live' ){
            $api_public_key = $this->api_public_key;
        }else{
            $api_public_key = $this->test_api_public_key;
        }
        //Set logo image url
        if(isset($this->logo_image_url)){
            $logo_image_url = wp_get_attachment_url($this->logo_image_url);
        }else{
            $logo_image_url = 'http://www.paidy.com/images/logo.png';
        }
        //Set user id
        if(is_user_logged_in()){
            $user_id = get_current_user_id();
        }else{
            $user_id = 'guest-paidy';
        }

        if(version_compare( WC_VERSION, '3.6', '>=' )){
            $jp4wc_countries = new WC_Countries;
            $states = $jp4wc_countries->get_states();
        }else{
            global $states;
        }
        $states_var = '';
        foreach ((array)$states['JP'] as $key => $value) {
            $states_var .= 'states['.substr($key, 2).'] = "'.$value.'"; ';
        }
        $items = '';
        $cart_data = WC()->cart->get_cart();
        $coupons = WC()->cart->get_coupons();
        $items_count = 0;
        $cart_total = 0;
        foreach ( $cart_data as $cart_item_key => $cart_item ) {
            $_product = $cart_item['data'];
            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                $unit_price = $cart_item['line_subtotal'] / $cart_item['quantity'];
                $items .= '{
                    "id":"'.$cart_item['product_id'].'",
                    "quantity":'.$cart_item['quantity'].',
                    "title":"'.$_product->get_name().'",
                    "unit_price":'.$unit_price.',
                    "description":" "';
                if ($cart_item === end($cart_data) and !isset($coupons)) {
                    $items .= '}
';
                }else{
                    $items .= '},
                    ';
                }
                $items_count += $cart_item['quantity'];
                $cart_total += $cart_item['line_subtotal'];
            }
        }
        if(isset( $coupons )) {
            foreach ( $coupons as $coupon ){
                if($coupon->get_discount_type() == 'percent'){
                    $unit_price = round( $coupon->get_amount() * $cart_total / 100 );
                }elseif($coupon->get_discount_type() == 'fixed_cart'){
                    $unit_price = $coupon->get_amount();
                }elseif($coupon->get_discount_type() == 'fixed_product'){
                    $unit_price = $coupon->get_amount() * $items_count;
                }
                $items .= '{
                    "id":"'.$coupon->get_code().'",
                    "quantity":1,
                    "title":"'.$coupon->get_description().'",
                    "unit_price":-'.$unit_price.',
                    "description":" "';
                if ($coupon === end($coupons)) {
                    $items .= '}
';
                }else{
                    $items .= '},
                    ';
                }
            }
        }
        $date = new DateTime();
        $paidy_order_ref = $date->format('YmdHis');
        if(WC()->cart->get_cart_tax()){
            $tax = preg_replace('/[^0-9]/', '', WC()->cart->get_cart_tax());
        }else{
            $tax = 0;
        }
        // Get latest order
        $args = array(
            'customer_id' => $user_id,
            'status' => 'completed',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        $orders = wc_get_orders($args);
        $total_order_amount = 0;
        $order_count = 0;
        foreach($orders as $order){
            if( $order->get_payment_method() != $this->id ){
                $selected_orders[] = $order;
                $total_order_amount += $order->get_total();
                $order_count += 1;
            }
        }
        if(isset($selected_orders[1])) {
            foreach ($selected_orders as $order) {
                if ($order === end($selected_orders)) {
                    $latest_order = $order;
                }
            }
        }elseif(isset($selected_orders)){
            $latest_order = $selected_orders[0];
        }else{
            $latest_order = null;
        }
        if(isset($latest_order)){
            $last_order_amount = $latest_order->get_total();
            $day1 = strtotime($latest_order->get_date_created());
            $day2 = strtotime(date_i18n('Y-m-d H:i:s'));
            $diff_day = floor(($day2 - $day1) / (60 * 60 * 24));
            if($diff_day <=0 ){
                $diff_day = 0;
            }
        }else{
            $last_order_amount = 0;
            $diff_day = 0;
        }
//        print_r($coupons);
        if( $this->enabled =='yes' and isset($api_public_key) and $api_public_key != '' ):
        ?>
        <script type="text/javascript">
            var states = new Array();
            <?php echo $states_var; ?>
            var config = {
                "api_key": "<?php echo $api_public_key;?>",
                "logo_url": "<?php echo $logo_image_url;?>",
                "closed": function(callbackData) {
                    /*
                    Data returned in the callback:
                    callbackData.id,
                    callbackData.amount,
                    callbackData.currency,
                    callbackData.created_at,
                    callbackData.status
                    */
                    document.getElementById( "transaction_id" ).value = callbackData.id;
                    if(callbackData.status === "rejected"){
                        window.location.href = "<?php echo wc_get_checkout_url(); ?>";
                    }else if(callbackData.status === "authorized"){
                        var $form = jQuery('form[name="checkout"]');
                        $form.submit();
                    }else{
                        window.location.href = "<?php echo wc_get_checkout_url(); ?>";
                    }
                }
            };

            var paidyHandler = Paidy.configure(config);
            function paidyPay() {
                var billing_last_name = document.getElementById("billing_last_name").value;
                var billing_first_name = document.getElementById("billing_first_name").value;
<?php if(get_option('wc4jp-yomigana', true) == 1):?>
                var billing_yomigana_last_name = document.getElementById("billing_yomigana_last_name").value;
                var billing_yomigana_first_name = document.getElementById("billing_yomigana_first_name").value;
<?php endif; ?>
                var billing_email = document.getElementById("billing_email").value;
                var billing_phone = document.getElementById("billing_phone").value;

                var shipping_line1 = document.getElementById("billing_address_1").value;
                var shipping_line2 = document.getElementById("billing_address_2").value;
                var shipping_city = document.getElementById("billing_city").value;
                var shipping_state = document.getElementById("billing_state").value;
                var shipping_zip = document.getElementById("billing_postcode").value;

                const validation_address = [
                    {title: '<strong>' + '<?php echo __( 'Billing Last Name', 'woocommerce-for-japan' ); ?>' + '</strong>', data:billing_last_name},
                    {title: '<strong>' + '<?php echo __( 'Billing First Name', 'woocommerce-for-japan' ); ?>' + '</strong>', data:billing_first_name},
<?php if(get_option('wc4jp-yomigana', true) == 1):?>
                    {title: '<strong>' + '<?php echo __( 'Billing First Name Yomigana', 'woocommerce-for-japan' ); ?>' + '</strong>', data:billing_yomigana_last_name},
                    {title: '<strong>' + '<?php echo __( 'Billing Last Name Yomigana', 'woocommerce-for-japan' ); ?>' + '</strong>', data:billing_yomigana_first_name},
<?php endif; ?>
                    {title: '<strong>' + '<?php echo __( 'Billing Email', 'woocommerce-for-japan' ); ?>' + '</strong>', data:billing_email},
                    {title: '<strong>' + '<?php echo __( 'Billing Phone', 'woocommerce-for-japan' ); ?>' + '</strong>', data:billing_phone},
                    {title: '<strong>' + '<?php echo __( 'Shipping Address1', 'woocommerce-for-japan' ); ?>' + '</strong>', data:shipping_line1},
                    {title: '<strong>' + '<?php echo __( 'Shipping city', 'woocommerce-for-japan' ); ?>' + '</strong>', data:shipping_city},
                    {title: '<strong>' + '<?php echo __( 'Shipping State', 'woocommerce-for-japan' ); ?>' + '</strong>', data:shipping_state},
                    {title: '<strong>' + '<?php echo __( 'Shipping Zip', 'woocommerce-for-japan' ); ?>' + '</strong>', data:shipping_zip}
                ];

                var def_text = '<?php echo __( '%s is a required field.', 'woocommerce' ); ?>';
                var message = '';

                for(let i = 0; i < validation_address.length; i++){
                    if(validation_address[i].data == '') {
                        var display_title = def_text.replace('%s', validation_address[i].title);
                        if(i == 0){
                            message += display_title;
                        }else{
                            message += '<br/>' + display_title;
                        }
                    }
                }
                if(message != '') {
                    return false;
                }


                var payload = {
                    "amount": <?php echo preg_replace('/[^0-9]/', '', WC()->cart->get_total());?>,
                    "currency": "JPY",
                    "store_name": "<?php echo wc_clean($this->store_name);?>",
                    "buyer": {
                        "email": billing_email,
                        "name1": billing_last_name + "　" + billing_first_name,
<?php if(get_option('wc4jp-yomigana', true) == 1):?>
                        "name2": billing_yomigana_last_name + "　" + billing_yomigana_first_name,
<?php endif; ?>
                        "phone": billing_phone
                    },
                    "buyer_data": {
                        "user_id": "<?php echo $user_id; ?>",
                        "order_count": <?php echo $order_count; ?>,
                        "ltv": <?php echo $total_order_amount; ?>,
                        "last_order_amount": <?php echo $last_order_amount; ?>,
                        "last_order_at": <?php echo $diff_day?>
                    },
                    "order": {
                        "items": [
                            <?php echo $items;?>

                        ],
                        "order_ref": "<?php echo $paidy_order_ref; ?>",
                        "shipping": <?php echo WC()->cart->get_shipping_total();?>,
                        "tax": <?php echo $tax;?>
                    },
                    "shipping_address": {
                        "line1": shipping_line2,
                        "line2": shipping_line1,
                        "city": shipping_city,
                        "state": states[shipping_state.slice(2)],
                        "zip": shipping_zip
                    },
                    "description": "<?php echo wc_clean($this->store_name);?>"
                };
                paidyHandler.launch(payload);
            };
        </script>
        <input type="hidden" name="transaction_id" id="transaction_id" value="" />
        <input type="hidden" name="paidy_order_ref" id="paidy_order_ref" value="<?php echo wc_clean($paidy_order_ref); ?>" />
    <?php endif;
    }

    /**
     * order button html
     */
    public function order_button_html( $html ){
        if( WC()->session->get('chosen_payment_method' ) == $this->id ) {
            echo '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $this->order_button_text ) . '" data-value="' . esc_attr( $this->order_button_text ) . '" onclick="paidyPay()" >' . wc_clean( $this->order_button_text ) . '</button>';
        }else{
            echo $html;
        }
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array | mixed
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        $transaction_id = $this->get_post('transaction_id');
        if($transaction_id === '' || $transaction_id == 'undefined'){
            $notice['error'][] = '';
            WC()->session->set( 'wc_notices', $notice );
            return array(
                'result' 	=> 'success',
                'redirect'	=> wc_get_checkout_url()
            );
        }

		// Mark as on-hold (we're awaiting[pending] the payment)
        $order->payment_complete( $transaction_id );
        update_post_meta( $order_id, 'paidy_order_ref', $this->get_post('paidy_order_ref'));

        $message = $this->get_post('paidy_order_ref').':paidy_order_ref, '."\n".$this->get_post('transaction_id').':payment_id. '."\n".'This is response data.';
        $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'woocommerce-for-japan');

        // Reduce stock levels
        wc_reduce_stock_levels( $order_id );

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
    }
    public function validate_fields(){
        $transaction_id = $this->get_post('transaction_id');
        if( $transaction_id == '' ){
            $notice['error'][] = '';
            WC()->session->set( 'wc_notices', $notice );
            return false;
        }
    }
    /**
     * Get post data if set
     */
    private function get_post( $name ) {
        if ( isset( $_POST[ $name ] ) ) {
            return sanitize_text_field( $_POST[ $name ] );
        }
        return null;
    }

    /**
     * Read Paidy Token javascript
     */
    public function paidy_token_scripts_method() {
        // Image upload.
        wp_enqueue_media();

        $paygent_token_js_link = 'https://apps.paidy.com/';
        if(is_checkout()){
            wp_enqueue_script(
                'paidy-token',
                $paygent_token_js_link,
                array(),
                '',
                false
            );
            // Paidy Payment for Checkout page
            wp_register_style(
                'jp4wc-paidy',
                WC_PAIDY_PLUGIN_URL .
                '/assets/css/jp4wc-paidy.css',
                false,
                WC_PAIDY_VERSION
            );
            wp_enqueue_style( 'jp4wc-paidy' );
        }
    }

    /**
     * Update Cancel from Auth to Paidy System
     *
     * @param string $order_id
     * @return mixed
     */
    public function jp4wc_order_paidy_status_cancelled( $order_id ){
        if( $this->environment == 'live' ){
            $secret_key = $this->api_secret_key;
        }else{
            $secret_key = $this->test_api_secret_key;
        }
        $order = wc_get_order( $order_id );
        $order_payment_method = $order->get_payment_method();
        if( $order_payment_method == $this->id ) {
            $transaction_id = $order->get_transaction_id();
            $send_url = 'https://api.paidy.com/payments/' . $transaction_id . '/close';
            $args = array(
                'method' => 'POST',
                'body' => '{}',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Paidy-Version' => '2018-04-10',
                    'Authorization' => 'Bearer ' . $secret_key
                )
            );

            $message = 'Send URL is following. : ' . $send_url;
            $this->jp4wc_framework->jp4wc_debug_log($message, $this->debug, 'woocommerce-for-japan');

            $close = wp_remote_post($send_url, $args);
            $close_array = json_decode($close['body'], true);
            if (is_wp_error($close)) {
                $order->add_order_note($close->get_error_message());
                return false;
            } elseif ($close_array['status'] == 'closed') {
                $message = $this->jp4wc_framework->jp4wc_array_to_message($close_array) . 'This is success cancellation data.';
                $this->jp4wc_framework->jp4wc_debug_log($message, $this->debug, 'woocommerce-for-japan');
            } else {
                $message = $this->jp4wc_framework->jp4wc_array_to_message($close_array) . 'This is close data.';
                $this->jp4wc_framework->jp4wc_debug_log($message, $this->debug, 'woocommerce-for-japan');

                $order->add_order_note(__('Cancelled processing has not been completed due to a Paidy error. Please check Paidy admin.', 'woocommerce-for-japan'));
                return false;
            }
        }
    }

    /**
     * Update Sale from Auth to Paidy System
     *
     * @param string $order_id
     * @return mixed
     */
    public function jp4wc_order_paidy_status_completed( $order_id ){
        if( $this->environment == 'live' ){
            $secret_key = $this->api_secret_key;
        }else{
            $secret_key = $this->test_api_secret_key;
        }
        $order = wc_get_order( $order_id );
        $order_payment_method = $order->get_payment_method();
        if( $order_payment_method == $this->id ){
            $transaction_id = $order->get_transaction_id();
            $send_url = 'https://api.paidy.com/payments/'.$transaction_id.'/captures';
            $args = array(
                'method' => 'POST',
                'body' => '{}',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Paidy-Version' => '2018-04-10',
                    'Authorization' => 'Bearer '.$secret_key
                )
            );
            if($this->debug != 'no'){
                $message = 'Send URL is following. : '.$send_url;
                $this->jp4wc_framework->jp4wc_debug_log( $message, true, 'woocommerce-for-japan');
            }
            $capture = wp_remote_post( $send_url, $args );
            $capture_array = json_decode( $capture['body'], true );
            if( is_wp_error( $capture )){
                $order->add_order_note( $capture->get_error_message() );
                return false;
            }elseif( $capture_array['status'] == 'closed' ){
                $message = $this->jp4wc_framework->jp4wc_array_to_message($capture_array). 'This is capture data.';
                $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'woocommerce-for-japan');

                update_post_meta( $order_id, 'paidy_capture_id', $capture_array['captures'][0]['id']);
                if($capture_array['amount'] == $order->get_total() and $transaction_id == $capture_array['id']){
                    $order->add_order_note( __('In the payment completion process, the amount and ID match were confirmed.', 'woocommerce-for-japan') );
                }else{
                    $order->add_order_note( __('In the payment completion process, the amount and ID did not match. Check on the Paidy admin.', 'woocommerce-for-japan') );
                }
            }else{
                $message = $this->jp4wc_framework->jp4wc_array_to_message($capture_array).'This is capture data.';
                $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'woocommerce-for-japan');

                $order->add_order_note( __('Completion processing has not been completed due to a Paidy error.', 'woocommerce-for-japan') );
                return false;
            }
        }
    }
    /**
     * Process a refund if supported
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return  boolean True or false based on success, or a WP_Error object
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        if( $this->environment == 'live' ){
            $secret_key = $this->api_secret_key;
        }else{
            $secret_key = $this->test_api_secret_key;
        }
        $order = wc_get_order( $order_id );
        $order_payment_method = $order->get_payment_method();
        $capture_id = get_post_meta( $order_id, 'paidy_capture_id', true );
        if( $order_payment_method == $this->id ) {
            $transaction_id = $order->get_transaction_id();
            $post_data = '{"capture_id":"' . $capture_id . '","amount":"' . $amount . '"}';
            $send_url = 'https://api.paidy.com/payments/' . $transaction_id . '/refunds';
            $args = array(
                'method' => 'POST',
                'body' => $post_data,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Paidy-Version' => '2018-04-10',
                    'Authorization' => 'Bearer ' . $secret_key
                )
            );
            // Debug message
            $message = 'Send URL is following. : '.$send_url."\n";
            $message .= 'Post data is following. : '.$post_data;
            $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'woocommerce-for-japan');
            if($capture_id != ''){
                $refund = wp_remote_post($send_url, $args);
            }else{
                $order->add_order_note( __('Refund is not possible because Paidy has not completed processing.', 'woocommerce-for-japan') );
                return false;
            }
            $refund_array = json_decode( $refund['body'], true );
            if( is_wp_error( $refund )){
                $order->add_order_note( $refund->get_error_message() );
                return false;
            }elseif( $refund_array['status'] == 'closed' ){
                update_post_meta( $order_id, 'paidy_refund_id', $capture_array['refunds'][0]['id']);
                $order->add_order_note( __('Completion refunding has been completed at Paidy.', 'woocommerce-for-japan') );
                return true;
            }else{
                $message = $this->jp4wc_framework->jp4wc_array_to_message($refund_array).'This is refund data.';
                $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'woocommerce-for-japan');

                $order->add_order_note( __('Completion processing has not been completed due to a Paidy error.', 'woocommerce-for-japan') );
                return false;
            }
        }
    }
    /**
     * Generate Image HTML.
     *
     * @param  mixed $key
     * @param  mixed $data
     * @since  1.5.0
     * @return string
     */
    public function generate_image_html( $key, $data ) {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data  = wp_parse_args( $data, $defaults );
        $value = $this->get_option( $key );

        // Hide show add remove buttons.
        $maybe_hide_add_style    = '';
        $maybe_hide_remove_style = '';

        // For backwards compatibility (customers that already have set a url)
        $value_is_url            = filter_var( $value, FILTER_VALIDATE_URL ) !== false;

        if ( empty( $value ) || $value_is_url ) {
            $maybe_hide_remove_style = 'display: none;';
        } else {
            $maybe_hide_add_style = 'display: none;';
        }

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
            </th>

            <td class="image-component-wrapper">
                <div class="image-preview-wrapper">
                    <?php
                    if ( ! $value_is_url ) {
                        echo wp_get_attachment_image( $value, 'thumbnail' );
                    } else {
                        echo sprintf( __( 'Already using URL as image: %s', 'woocommerce-for-japan' ), $value );
                    }
                    ?>
                </div>

                <button
                        class="button image_upload"
                        data-field-id="<?php echo esc_attr( $field_key ); ?>"
                        data-media-frame-title="<?php echo esc_attr( __( 'Select a image to upload', 'woocommerce-for-japan' ) ); ?>"
                        data-media-frame-button="<?php echo esc_attr( __( 'Use this image', 'woocommerce-for-japan' ) ); ?>"
                        data-add-image-text="<?php echo esc_attr( __( 'Add image', 'woocommerce-for-japan' ) ); ?>"
                        style="<?php echo esc_attr( $maybe_hide_add_style ); ?>"
                >
                    <?php echo esc_html__( 'Add image', 'woocommerce-for-japan' ); ?>
                </button>

                <button
                        class="button image_remove"
                        data-field-id="<?php echo esc_attr( $field_key ); ?>"
                        style="<?php echo esc_attr( $maybe_hide_remove_style ); ?>"
                >
                    <?php echo esc_html__( 'Remove image', 'woocommerce-for-japan' ); ?>
                </button>

                <input type="hidden"
                       name="<?php echo esc_attr( $field_key ); ?>"
                       id="<?php echo esc_attr( $field_key ); ?>"
                       value="<?php echo esc_attr( $value ); ?>"
                />
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    /**
     * delete and make notice
     *
     * @param  array $para
     * @param  string $handle
     * @since  1.5.0
     * @return array
     */
    public function delete_notice($para, $handle){
        if( $handle == 'wc-checkout' ){
            $para['i18n_checkout_error'] = esc_attr__( 'Error processing checkout or Payment processing. Please wait or try again.', 'woocommerce-for-japan' );
        }
        return $para;
    }
}

/**
 * Add the gateway to woocommerce
 *
 * @param array $methods
 * @return array $methods
 */
function add_wc4jp_paidy_gateway( $methods ) {
    $methods[] = 'WC_Gateway_Paidy';
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_wc4jp_paidy_gateway' );

/**
 * The available gateway to woocommerce only Japanese currency
 */
function wc4jp_paidy_available_gateways( $methods ) {
    $currency = get_woocommerce_currency();
    if( $currency !='JPY' ){
        unset( $methods['paidy'] );
    }
    return $methods;
}

add_filter( 'woocommerce_available_payment_gateways', 'wc4jp_paidy_available_gateways' );
