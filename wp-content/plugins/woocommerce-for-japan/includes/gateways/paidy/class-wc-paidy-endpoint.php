<?php
use \ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_2 as Framework;

add_action( 'rest_api_init', function () {
    register_rest_route( 'paidy/v1', '/order/', array(
        'methods' => 'POST',
        'callback' => 'paidy_check_webhook',
    ) );
} );

/**
 * Paidy Webhook response.
 *
 * @param object $data post data.
 * @return WP_REST_Response | WP_Error endpoint Paidy webhook response
 */
function paidy_check_webhook( $data ){
    $jp4wc_framework =new Framework\JP4WC_Plugin();
    $paidy = new WC_Gateway_Paidy();
    $debug = $paidy->debug;
    if ( empty( $data ) ) {
        $message = "no_data";
        $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'woocommerce-for-japan');

        return new WP_Error( 'no_data', 'Invalid author', array( 'status' => 404 ) );
    }elseif( isset( $data["payment_id"] ) ){
        $order_id = $jp4wc_framework->get_order_id_by_transaction_id( $data["payment_id"] );
        if(is_numeric($order_id)){
        $order = wc_get_order( $order_id );
            $status = $order->get_status();
            $body_data = (array)$data->get_body();

            // Debug
            $jp4wc_framework->jp4wc_debug_log( $body_data[0], $debug, 'woocommerce-for-japan');
            if( $data["status"] == 'authorize_success' && $status == 'processing' ){
                $order->add_order_note( sprintf( __( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), __( 'authorization', 'woocommerce-for-japan' ) ) );
                return new WP_REST_Response( $data, 200 );
            }elseif( $data["status"] == 'close_success' && $status == 'cancelled' ){
                $order->add_order_note( sprintf( __( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), __( 'cancelled', 'woocommerce-for-japan' ) ) );
                return new WP_REST_Response( $data, 200 );
            }elseif( $data["status"] == 'close_success' && $status == 'completed' ){
                $order->add_order_note( sprintf( __( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), __( 'close', 'woocommerce-for-japan' ) ) );
                return new WP_REST_Response( $data, 200 );
            }elseif( $data["status"] == 'capture_success' && $status == 'completed' ){
                $order->add_order_note( sprintf( __( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), __( 'completed', 'woocommerce-for-japan' ) ) );
                return new WP_REST_Response( $data, 200 );
            }elseif( $data["status"] == 'refund_success' && $status == 'refunded' ){
                $order->add_order_note( sprintf( __( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), __( 'refunded', 'woocommerce-for-japan' ) ) );
                return new WP_REST_Response( $data, 200 );
            }else{
                $order->add_order_note( sprintf( __( 'It failed to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), $data["status"] ) );
                return new WP_REST_Response( $data, 200 );
            }
        }else{
            // Debug
            $message = 'Payment_id exist but order_id. Payment_id : '.$data["payment_id"] . '; Status : ' . $data["status"];
            $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'woocommerce-for-japan');
            return new WP_Error( 'no_order_id', $message , array( 'status' => 404 ) );
        }
    }else{
        // Debug
        $message = 'no_payment_id';
        $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'woocommerce-for-japan');
        return new WP_Error( 'no_payment_id', 'Invalid author', array( 'status' => 404 ) );
    }
}

