<?php

/**
* MM publications Gateway.
*
* Make an enquiry instead of paying.
*/

function init_custom_gateway_class(){

    class WC_Gateway_Enquiry extends WC_Payment_Gateway {

        public function __construct() {

            $this->id                   = 'enquire';
            $this->icon                 = apply_filters( 'woocommerce_custom_gateway_icon', '' );
            $this->has_fields           = true;
            $this->method_title         = __( 'Enquiry', 'woocommerce' );
            $this->method_description   = __( 'Enquire instead of payment.', 'woocommerce' );
            $this->order_button_text    = $this->get_option( 'button_text' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'mm_instructions' );
            $this->order_status = $this->get_option( 'order_status', 'completed' );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_enquire', array( $this, 'thankyou_page' ) );

            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
        }

        /**
        * Initialise Enquiry Gateway Settings Form Fields.
        */
        
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'woocommerce' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Enquiry', 'woocommerce' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                    'default'     => __( 'Enquire', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', 'woocommerce' ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', 'woocommerce' ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => __( 'Description', 'woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'Enquire description that the customer will see on your checkout.', 'woocommerce' ),
                    'default'     => __( 'Ask us how to buy', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'mm_instructions' => array(
                    'title'       => __( 'Instructions', 'woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
                    'default'     => __( 'You will be notified how to acquire your products', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'button_text' => array(
                    'title'       => __( 'Order button text', 'woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'The text that will appear on the order button. Default is enquire', 'woocommerce' ),
                    'default'     => __( 'Enquire', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
            if ( $this->instructions )
                echo wpautop( wptexturize( $this->instructions ) );
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( $this->instructions && ! $sent_to_admin && 'custom' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );

            $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

            // Set order status
            $order->update_status( $status, __( 'Enquire. ', 'woocommerce' ) );

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }
    }
}
add_action( 'plugins_loaded', 'init_custom_gateway_class' );

function modify_enquire_thank_you_text() {

    $thankyou =  __( 'Thank you. Your enquiry has been received.', 'woocommerce' );

    return $thankyou;
}
add_filter( 'woocommerce_thankyou_order_received_text', 'modify_enquire_thank_you_text' );
