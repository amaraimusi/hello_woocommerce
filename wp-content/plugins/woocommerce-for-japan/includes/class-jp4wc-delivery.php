<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.0.0
 * @package 	Admin Screen
 * @author 		ArtisanWorkshop
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class JP4WC_Delivery{
	
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		// Show delivery date and time at checkout page
		add_action( 'woocommerce_before_order_notes', array( $this, 'delivery_date_designation'), 10 );
		// Save delivery date and time values to order
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ) );
		// Show on order detail at thanks page (frontend)
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'frontend_order_timedate' ) );
		// Show on order detail email (frontend)
		add_filter( 'woocommerce_email_order_meta', array( $this, 'email_order_delivery_details' ), 10, 4 );
		// Shop Order functions
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'shop_order_columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_shop_order_columns' ), 2 );
		// display in Order meta box ship date and time (admin)
		add_action( 'add_meta_boxes', array($this, 'add_meta_box' ));
		add_action( 'woocommerce_process_shop_order_meta', array($this, 'save_meta_box'), 0, 2 );
	}

	// Delivery date designation
	public function delivery_date_designation(){
		$setting_methods = array(
		    'delivery-date',
            'start-date',
            'reception-period',
            'unspecified-date',
            'delivery-deadline',
            'no-mon',
            'no-tue',
            'no-wed',
            'no-thu',
            'no-fri',
            'no-sat',
            'no-sun',
            'holiday-start-date',
            'holiday-end-date',
            'delivery-time-zone',
            'unspecified-time'
        );
		foreach($setting_methods as $setting_method){
			$setting[$setting_method] = get_option( 'wc4jp-'.$setting_method );
		}
		if($setting['delivery-date'] or $setting['delivery-time-zone']){
			echo '<h3>'.__('Delivery request date and time', 'woocommerce-for-japan' ).'</h3>';
		}
		$this->delivery_date_display($setting);
		$this->delivery_time_display($setting);
	}

    /**
     * Display Delivery date select
     *
     * @throws
     * @param array $setting
     */
	function delivery_date_display($setting){
		if(get_option( 'wc4jp-delivery-date' )){
			$time = new DateTime();
			$time = $time->format('H:i');
			$now = get_date_from_gmt($time);
			if (strtotime($now) > strtotime($setting['delivery-deadline'])){
				$setting['start-date'] = $setting['start-date'] + 1;
				$today = date_i18n('Y/m/d', strtotime('+1 day'));
			}else{
				$today = date_i18n('Y/m/d');
			}
			//The day of week check
			$weekday_options = array('0'=>'no-sun','1'=>'no-mon','2'=>'no-tue','3'=>'no-wed','4'=>'no-thu','5'=>'no-fri','6'=>'no-sat');
			$no_ship_weekdays = array();
			foreach($weekday_options as $key => $value){
				if(get_option( 'wc4jp-'.$value )){
					$no_ship_weekdays[$value] = $key;
				}
			}
			$datetime = new DateTime();
			$w = $datetime->format('w');
			if($w == 6){
				$tomorrow = 0;				
				$after_tomorrow = 1;
				$after_tomorrow2 = 2;
			}elseif($w == 5){
				$tomorrow = 6;
				$after_tomorrow = 0;
				$after_tomorrow2 = 1;
			}elseif($w == 4){
				$tomorrow = 5;
				$after_tomorrow = 6;
				$after_tomorrow2 = 0;
			}else{
				$tomorrow = $w + 1;
				$after_tomorrow = $w + 2;
				$after_tomorrow2 = $w + 3;
			}
			$no_ship_term = 0;
			if(array_search($tomorrow,$no_ship_weekdays)){
				if(array_search($after_tomorrow,$no_ship_weekdays)){
					if(array_search($after_tomorrow2,$no_ship_weekdays)){
						$no_ship_term = 3;
					}else{
						$no_ship_term = 2;
					}
				}else{
					$no_ship_term = 1;
				}
			}
			echo '<p class="form-row delivery-date" id="order_delivery_date_field">';
			echo '<label for="delivery_date" class="">'.__('Preferred delivery date', 'woocommerce-for-japan' ).'</label>';
			echo '<select name="wc4jp_delivery_date" class="input-select" id="wc4jp_delivery_date">';
			echo '<option value="0">'.$setting['unspecified-date'].'</option>';

			for($i = $setting['start-date']; $i <= $setting['start-date']+$setting['reception-period']; $i++){
				if(isset($setting['holiday-start-date']) and isset($setting['holiday-end-date']) and strtotime($today) >= strtotime($setting['holiday-start-date']) and strtotime($today) <= strtotime($setting['holiday-end-date']) ){
					$set_disp_date = new DateTime();
					$set_disp_date->setDate(substr($setting['holiday-end-date'],0,4), substr($setting['holiday-end-date'],5,2), substr($setting['holiday-end-date'],8,2));
					$set_disp_date->modify('+1 day');
				}else{
					$set_disp_date = new DateTime();
				}
				$add_days = $i + $no_ship_term;
				$set_disp_date->modify('+'.$add_days.' day');
				$set_disp_date = $set_disp_date->format('Y-m-d h:i:s');
				$valuedate[$i] = get_date_from_gmt($set_disp_date, 'Y-m-d');
				$dispdate[$i] = get_date_from_gmt($set_disp_date, __('Y/m/d', 'woocommerce-for-japan' ));
				echo '<option value="'.$valuedate[$i].'">'.$dispdate[$i].'</option>';
				if($i == $setting['start-date']){
					$setting['first-value-date'] = get_date_from_gmt($set_disp_date, 'Y-m-d');
					$setting['first-disp-date'] = get_date_from_gmt($set_disp_date, __('Y/m/d', 'woocommerce-for-japan' ));
				}
			}
			echo '</select>';
			echo '</p>';
		}
		do_action( 'after_wc4jp_delivery_date', $setting );
	}

    /**
     * Display Delivery time select
     *
     * @param array $setting
     */
	function delivery_time_display($setting){
		$time_zone_setting = get_option( 'wc4jp_time_zone_details' );
		if(get_option( 'wc4jp-delivery-time-zone' )){
			echo '<p class="form-row delivery-time" id="order_delivery_time_field">';
			echo '<label for="delivery_time_zone" class="">'.__('Delivery Time Zone', 'woocommerce-for-japan' ).'</label>';
			echo '<select name="wc4jp_delivery_time_zone" class="input-select" id="wc4jp_delivery_time_zone">';
			echo '<option value="0">'.$setting['unspecified-time'].'</option>';
			$count_time_zone = count($time_zone_setting);
			for($i = 0; $i <= $count_time_zone - 1; $i++){
				echo '<option value="'.$time_zone_setting[$i]['start_time'].'-'.$time_zone_setting[$i]['end_time'].'">'.$time_zone_setting[$i]['start_time'].__('-', 'woocommerce-for-japan' ).$time_zone_setting[$i]['end_time'].'</option>';
			}
			echo '</select>';
			echo '</p>';
		}
	}

	/**
	 * Helper: Update order meta on successful checkout submission
	 *
	 * @param string $order_id
	 */
	function update_order_meta( $order_id ) {

		$date = false;
		$time = false;

		if(isset($_POST['wc4jp_delivery_date'])){
			$date = apply_filters('wc4jp_delivery_date', $_POST['wc4jp_delivery_date'], $order_id );
		}
		if( isset($date) && $date != 0 ){
			if(get_option( 'wc4jp-date-format' )){
				$date = strtotime($date);
				$date = date(get_option( 'wc4jp-date-format' ),$date);
			}
			update_post_meta( $order_id, 'wc4jp-delivery-date', esc_attr(htmlspecialchars($date)));
		}

        if(isset($_POST['wc4jp_delivery_time_zone'])){
            $time = apply_filters('wc4jp_time_zone', $_POST['wc4jp_delivery_time_zone'], $order_id );
        }
		if( isset($time) && $time != 0 ){
			update_post_meta( $order_id, 'wc4jp-delivery-time-zone', esc_attr(htmlspecialchars($time)));
		}
	}
	/**
	 * Frontend: Add date and timeslot to frontend order overview
	 *
	 * @param object $order
	 */
	function frontend_order_timedate( $order ){

		if( !$this->has_date_or_time( $order ) )
			return;

		$this->display_date_and_time_zone( $order, true );

	}
	/**
	 * Helper: Display Date and Timeslot
	 *
	 * @param object $order
     * @param bool $show_title
	 * @param bool $plain_text
	 */
	public function display_date_and_time_zone( $order, $show_title = false, $plain_text = false ) {

		$date_time = $this->has_date_or_time( $order );

		if( !$date_time )
			return;
		if($date_time['date'] === 0 ){$date_time['date'] = get_option( 'wc4jp-unspecified-date' );}
		if($date_time['time'] === 0 ){$date_time['time'] = get_option( 'wc4jp-unspecified-time' );}
		$date_time['date'] = apply_filters('wc4jp-unspecified-date', $date_time['date'], $order);
		$date_time['time'] = apply_filters('wc4jp-unspecified-time', $date_time['time'], $order);
		$show_title = apply_filters('wc4jp-show-title', $show_title, $date_time['date'], $date_time['time'], $order);

		if( $plain_text ) {

			echo "\n\n==========\n\n";

			if( $show_title ) {
				printf( "%s \n", strtoupper( apply_filters( 'wc4jp_delivery_details_text', __('Scheduled Delivery date and time', 'woocommerce-for-japan') ) ) );
			}

			if( $date_time['date'] ){
				printf( "\n%s: %s", apply_filters( 'wc4jp_delivery_date_text', __('Scheduled Delivery Date', 'woocommerce-for-japan') ), $date_time['date'] );
			}

			if( $date_time['time'] ){
				printf( "\n%s: %s", apply_filters( 'wc4jp_time_zone_text', __('Scheduled Time Zone', 'woocommerce-for-japan') ), $date_time['time'] );
			}

			echo "\n\n==========\n\n";

		} else {

			if( $show_title ) {
				printf( '<h2>%s</h2>', apply_filters( 'wc4jp_delivery_details_text', __('Scheduled Delivery date and time', 'woocommerce-for-japan') ) );
			}

			if( $date_time['date'] ){
				printf( "<p><strong>%s</strong> <br>%s</p>", apply_filters( 'wc4jp_delivery_date_text', __('Scheduled Delivery Date', 'woocommerce-for-japan') ), $date_time['date'] );
			}

			if( $date_time['time'] ){
				printf( "<p><strong>%s</strong> <br>%s</p>", apply_filters( 'wc4jp_time_zone_text', __('Scheduled Time Zone', 'woocommerce-for-japan') ), $date_time['time'] );
			}
		}
	}
	/**
	 * Frontend: Add date and timeslot to order email
	 *
	 * @param object $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 * @param object $email
	 */
	function email_order_delivery_details( $order, $sent_to_admin, $plain_text, $email ) {

		if( !$this->has_date_or_time( $order ) )
			return;

		if( $plain_text ) {
			$this->display_date_and_time_zone( $order, true, true );
		} else {
			$this->display_date_and_time_zone( $order, true );
		}

	}
	/**
	 * Helper: Check if order has date or time
	 *
	 * @param object $order
	 * @return bool
	 */
	function has_date_or_time( $order ) {

		$meta = array(
			'date' => false,
			'time' => false
		);
		$has_meta = false;
		$order_id = $order->get_id();

		$date = $order->get_meta( 'wc4jp-delivery-date', true );
		$time = $order->get_meta( 'wc4jp-delivery-time-zone', true );

		if( ( $date && $date != "" ) ) {
			$meta['date'] = $date;
			$has_meta = true;
		}

		if( ( $time && $time != "" ) ) {
			$meta['time'] = $time;
			$has_meta = true;
		}

		if( $has_meta ) {
			return $meta;
		}

		return false;

	}
	/**
	 * Admin: Add Columns to orders tab
	 *
	 * @param array $columns
	 * @return array
	 */
	public function shop_order_columns( $columns ) {

		if(get_option( 'wc4jp-delivery-date' ) or get_option( 'wc4jp-delivery-time-zone' )){
			$columns['wc4jp_delivery'] = __( 'Delivery', 'woocommerce-for-japan' );
		}

		return $columns;

	}

	/**
	 * Admin: Output date and timeslot columns on orders tab
	 *
	 * @param string $column
	 */
	public function render_shop_order_columns( $column ) {

		global $post, $woocommerce, $the_order;
		if ( empty( $the_order ) || $the_order->get_id() != $post->ID ) {
			$the_order = wc_get_order( $post->ID );
		}

		switch ( $column ) {
			case 'wc4jp_delivery' :

				$this->display_date_and_time_zone( $the_order );

				break;
		}
	}
	/**
	 * Admin: Display date and timeslot on the admin order page
	 *
	 * @param object $order
	 */
	function display_admin_order_meta( $order ) {

		$this->display_date_and_time_zone( $order );

	}

	/**
	 * Add the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function add_meta_box(){
		if(get_option( 'wc4jp-delivery-date' ) or get_option( 'wc4jp-delivery-time-zone' )){
			add_meta_box('woocommerce-shipping-date-and-time', __('Shipping Detail', 'woocommerce-for-japan'), array(&$this, 'meta_box'), 'shop_order', 'side', 'high');
		}
	}

	/**
	 * Show the meta box for shipment info on the order page
 	 *
	 * @access public
	 */
	public function meta_box(){
		global $post;
		$shipping_fields = $this->shipping_fields($post);
		echo '<div id="aftership_wrapper">';
		foreach($shipping_fields as $key =>$value){
			if( $value['type'] == 'text' ){
				woocommerce_wp_text_input( $value );
			}
		}
		echo '</div>';
	}
	/**
	 * Order Downloads Save
	 *
	 * Function for processing and storing all order downloads.
     *
     * @access public
     * @param string $post_id
     * @param object $post
     */
	 public function save_meta_box($post_id, $post){
		$shipping_fields = $this->shipping_fields($post);
		foreach ($shipping_fields as $field) {
			if(isset($_POST[$field['id']]) && $_POST[$field['id']] != 0){
				update_post_meta($post_id, $field['id'], wc_clean($_POST[$field['id']]));
			}
		}
	}
	/**
	 * Show the meta box for shipment info on the order page
 	 *
	 * @access public
     * @param object $post
     * @return array
	 */
	public function shipping_fields($post){
		$date = get_post_meta( $post->ID, 'wc4jp-delivery-date', true);
		$date_timestamp = strtotime($date);
		$time = get_post_meta( $post->ID, 'wc4jp-delivery-time-zone', true);
		$date_format = get_option( 'wc4jp-date-format', true );
		$shipping_fields = array(
			'wc4jp-delivery-date' => array(
				'type' => 'text',
				'id' => 'wc4jp-delivery-date',
				'label' => __('Delivery Date', 'woocommerce-for-japan'),
				'placeholder' => date($date_format, $date_timestamp),
				'description' => __('Date on which the customer wished delivery.', 'woocommerce-for-japan'),
				'class' => 'wc4jp-delivery-date',
				'value' => ($date) ? $date : ''
			),
			'wc4jp-delivery-time-zone' => array(
				'type' => 'text',
				'id' => 'wc4jp-delivery-time-zone',
				'label' => __('Time Zone', 'woocommerce-for-japan'),
				'description' => __('Time Zone on which the customer wished delivery.', 'woocommerce-for-japan'),
				'class' => 'wc4jp-delivery-time-zone',
				'value' => ($time) ? $time : ''
			),
			'wc4jp_tracking_ship_date' => array(
				'type' => 'text',
				'id' => 'wc4jp_tracking_ship_date',
				'label' => __('Tracking Ship Date', 'woocommerce-for-japan'),
				'placeholder' => date($date_format, $date_timestamp),
				'description' => __('Actually shipped to date', 'woocommerce-for-japan'),
				'class' => 'wc4jp-tracking-ship-date',
				'value' => ($delivery_date = get_post_meta($post->ID, '_' . 'wc4jp_tracking_ship_date', true)) ? date($date_format, $delivery_date) : ''
			),
		);
		return apply_filters( 'wc4jp_shipping_fields', $shipping_fields, $post );
	}
}

new JP4WC_Delivery();
