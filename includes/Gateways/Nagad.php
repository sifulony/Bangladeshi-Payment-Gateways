<?php
/**
 * Nagad Functionality
 *
 * @package BDPaymentGateways
 * @since   1.0.0
 */

if (! defined('ABSPATH') ) {
    exit;
}

/**
 * Nagad class
 *
 * This class is for enqueuing styles, scripts
 *
 * @package BDPaymentGateways
 * @since   1.0.0
 */
class WC_BD_Nagad_Gateway extends WC_Payment_Gateway
{
    /**
     * Class constructor
     */
    public function __construct()
    {

        $this->id = 'woo_nagad';
        $this->icon = apply_filters('woocommerce_bdpg_Nagad_icon', BD_PAYMENT_GATEWAYS_DIR_URL . '/assets/images/Nagad.png');
        $this->has_fields = true;
        $this->method_description = __('Nagad Payment Gateway Settings.', 'bangladeshi-payment-gateways');
        $this->method_title = __('Nagad', 'bangladeshi-payment-gateways');

        // $this->supports = array(
        //     'products'
        // );

        $this->init_form_fields();

        // Load the Settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->instructions = $this->get_option('instructions');
        $this->nagad_charge = $this->get_option('nagad_charge');
        $this->nagad_fee = $this->get_option('nagad_fee');
        $this->nagad_charge_details = $this->get_option('nagad_charge_details');

        $this->all_account = array(
        array(
        'type' => $this->get_option('type'),
        'number' => $this->get_option('number'),
        'qr_code' => $this->get_option('qr_code'),
        )
        );
        $this->accounts = get_option('bdpg_nagad_accounts', $this->all_account);
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_accounts' ));
        add_action('woocommerce_thankyou_woo_nagad', array( $this, 'woo_nagad_thankyou' ));
        add_action('woocommerce_email_before_order_table', array( $this, 'woo_nagad_customer_email_instructions' ), 10, 3);
    }

    /**
     * Gateway Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
        'enabled' => array(
        'title'       => __('Enable/Disable', 'bangladeshi-payment-gateways'),
        'label'       => __('Enable Nagad Gateway', 'bangladeshi-payment-gateways'),
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no'
        ),
        'title' => array(
        'title'       => __('Title', 'bangladeshi-payment-gateways'),
        'type'        => 'text',
        'default'     => 'Nagad',
        'description' => __('Title', 'bangladeshi-payment-gateways'),
        'desc_tip'    => true,
        ),
        'description' => array(
        'title'       => __('Description', 'bangladeshi-payment-gateways'),
        'default'     => 'Description here. ',
        'type'        => 'textarea',
        ),
        'nagad_charge' => array(
        'title'       => __('Nagad Charge?', 'bangladeshi-payment-gateways'),
        'type'        => 'checkbox',
        'description' => __('Add Nagad <b>Send Money</b> charge.', 'bangladeshi-payment-gateways'),
        'default' => 'no'
        ),

        'nagad_fee' => array(
        'title'       => __('Nagad Fee? (in %)', 'bangladeshi-payment-gateways'),
        'type'        => 'text',
        'default'     => '1.45',
        'description' => __('Don\'t add %.', 'bangladeshi-payment-gateways'),
        ),

        'nagad_charge_details' => array(
        'title'       => __('Nagad Charge Details', 'bangladeshi-payment-gateways'),
        'type'        => 'textarea',
        'default' => __('Nagad "Send Money" fee will be added with net price.'),
        ),

        'instructions' => array(
        'title'       => __('Instructions', 'bangladeshi-payment-gateways'),
        'type'        => 'textarea',
        'description' => __('Instructions', 'bangladeshi-payment-gateways'),
        'default'     => 'Instructions',
        ),
        'accounts' => array(
        'type' => 'accounts'
        )
        );

    }

    /**
     * Payment Fields
     */
    public function payment_fields()
    {
        global $woocommerce;

        $nagad_charge_details = ( 'yes' == $this->nagad_charge ) ? $this->nagad_charge_details : '';
        echo wpautop(wptexturize(esc_html__($this->description, 'bangladeshi-payment-gateways')) . ' ' . $nagad_charge_details);

        $total_amount = 'You need to send us <b>' . get_woocommerce_currency_symbol() . $woocommerce->cart->total . '</b>';
        echo '<div class="bdpg-total-amount">' .  $total_amount . '</div>';
        ?>
        <div class="bdpg-available-accounts">
        <?php
        foreach ( $this->accounts as $account ) {
            ?>
                    <div class="bdpg-s__acc">
            <?php
            if ('' !== $account['qr_code'] ) {
                ?>
                        <div class="bdpg-acc__qr-code">
                            <img src="<?php echo $account['qr_code'];?>" alt="QR Code">
                        </div>
                <?php
            }
            ?>
                        <div class="bdpg-acc_d">
                            <p>Account Type: <b><?php echo $account['type']; ?></b></p>
                            <p>Account Number: <b><?php echo $account['number'];?></b> </p>
                        </div>
                    </div>
            <?php
        }
        ?>
            <div class="bdpg-user__acc">
                <div class="bdpg-user__field">
                    <label for="nagad_acc_no">
                        Your Nagad Account Number
                    </label>
                    <input type="text" class="widefat" name="nagad_acc_no" placeholder="01XXXXXXXXX">
                </div>
                <div class="bdpg-user__field">
                    <label for="nagad_trans_id">
                        Nagad Transaction ID
                    </label>
                    <input type="text" class="widefat" name="nagad_trans_id" placeholder="2M7A5">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Accounts Fields
     */
    public function generate_accounts_html()
    {
        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php _e('Account Details', 'woocommerce'); ?>:</th>
            <td class="forminp" id="nagad_accounts">
                <table class="widefat wc_input_table sortable" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="sort">&nbsp;</th>
                            <th><?php _e('Account Type', 'woocommerce'); ?></th>
                            <th><?php _e('Account Number', 'woocommerce'); ?></th>
                            <th><?php _e('QR Code', 'woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th colspan="7"><a href="#" class="add button"><?php _e('+ Add Account', 'woocommerce'); ?></a> <a href="#" class="remove_rows button"><?php _e('Remove selected account(s)', 'woocommerce'); ?></a></th>
                        </tr>
                    </tfoot>
                    <tbody class="accounts ui-sortable">
        <?php
         $i = -1;
        if ($this->accounts ) {
            foreach ( $this->accounts as $account ) {
                $i++;
                echo '<tr class="account">
								<td class="sort"></td>
								<td><input type="text" value="' . esc_attr($account['type']) . '" name="nagad_account_type[' . $i . ']" /></td>
								<td><input type="text" value="' . esc_attr($account['number']) . '" name="nagad_account_number[' . $i . ']" /></td><td><input type="hidden" value="' . esc_attr($account['qr_code']) . '" name="nagad_account_qr_code[' . $i . ']" id="bdpg_qr_code-' . $i . '" />
								<input type="button" class="button button-primary add_qr_c_img" value="Edit Image" data-target="#bdpg_qr_code-' . $i . '"  data-qr="#bdpg_qr_img-' . $i . '"><div  id="bdpg_qr_img-' . $i . '"><img src="' . esc_attr($account['qr_code']) . '" alt="QR Code" id="qr_code" /></div>
								</td>
								</tr>';
            }
        }
        ?>
                    </tbody>
                </table>
                <script>
                    
                    jQuery(function($) {
                        $('#nagad_accounts').on( 'click', 'a.add', function(){

                            var size = $('#nagad_accounts').find('tbody .account').length;

                            $('<tr class="account">\
                                    <td class="sort"></td>\
                                    <td><input type="text" name="nagad_account_type[' + size + ']" /></td>\
                                    <td><input type="text" name="nagad_account_number[' + size + ']" /></td>\
                                    <td><input type="hidden" id="bdpg_qr_code-' + size + '" name="nagad_account_qr_code[' + size + ']" /><input type="button" class="button button-primary add_qr_c_img" value="Add Image" data-target="#bdpg_qr_code-' + size + '" data-qr="#bdpg_qr_img-' + size + '"><div id="bdpg_qr_img-' + size + '"></div>\
                                    </td>\
                                </tr>').appendTo('#nagad_accounts table tbody');

                            return false;
                        });
                        
                    });
                
                </script>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Save Accounts
     */
    public function save_accounts()
    {
        
        if (isset($_POST['nagad_account_type' ]) ) {
            $accounts = array();

            $type = array_map('wc_clean', $_POST['nagad_account_type']);
            $number = array_map('wc_clean', $_POST['nagad_account_number']);
            $qr_code = array_map('wc_clean', $_POST['nagad_account_qr_code']);

            foreach ( $type as $key => $value ) {
                if (!isset($type[$key]) ) { continue;
                }

                $accounts[] = array(
                 'type' => $type[$key],
                 'number' => $number[$key],
                 'qr_code' => $qr_code[$key],
                );
            }
            update_option('bdpg_nagad_accounts', $accounts);
        }
    
    }

    /**
     * Process Payment
     * 
     * @param int $order_id Order ID
     */
    public function process_payment( $order_id )
    {
        global $woocommerce;

        $order = new WC_Order($order_id);

        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status('on-hold', __('Awaiting Nagad payment', 'woocommerce'));

        // Reduce stock levels.
        $order->reduce_order_stock();
        
        // Remove cart
        $woocommerce->cart->empty_cart();

        // Return thankyou redirect
        return array(
        'result' => 'success',
        'redirect' => $this->get_return_url($order)
        );
    }

    /**
     * Thank You Page
     */
    public function woo_nagad_thankyou($order_id )
    {

        $order = new WC_Order($order_id);
        
        if ('woo_nagad' == $order->get_payment_method() ) {
            echo $this->instructions;
        } else {
            echo esc_html__('Thank you. Your order has been received.', "woocommerce");
        }
    }

    /**
     * Customer Email
     */
    public function woo_nagad_customer_email_instructions( $order, $sent_to_admin, $plain_text = false )
    {
        if ($this->id !== $order->get_payment_method() || $sent_to_admin ) { return;
        }

        if ($this->instructions ) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }
}

/**
 * Field Validation
 */
add_action('woocommerce_checkout_process', 'woo_nagad_payment_process');
if (! function_exists('woo_nagad_payment_process') ) {
    function woo_nagad_payment_process()
    {
        if ('woo_nagad' !== $_POST['payment_method'] ) { return;
        }
        $number = sanitize_text_field($_POST['nagad_acc_no']);
        $trans_id = sanitize_text_field($_POST['nagad_trans_id']);

        if ('' == $number ) {
            wc_add_notice(__('Please enter your Nagad number.', 'bangladeshi-payment-gateways'), 'error');
        }

        if ('' == $trans_id ) {
            wc_add_notice(__('Please enter your Nagad transaction ID.', 'bangladeshi-payment-gateways'), 'error');
        }
    }
}

/**
 * Nagad Field Update
 */
add_action('woocommerce_checkout_update_order_meta', 'woo_Nagad_fields_update');
if (! function_exists('woo_Nagad_fields_update') ) {
    function woo_Nagad_fields_update( $order_id)
    {

        if ('woo_nagad' !== $_POST['payment_method'] ) { return;
        }
        $number = sanitize_text_field($_POST['nagad_acc_no']);
        $trans_id = sanitize_text_field($_POST['nagad_trans_id']);

        update_post_meta($order_id, 'woo_nagad_number', $number);
        update_post_meta($order_id, 'woo_nagad_trans_id', $trans_id);
    }
}

/**
 * Display Nagad data in admin page
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'woo_nagad_admin_order_data');

if (! function_exists('woo_nagad_admin_order_data') ) {
    function woo_nagad_admin_order_data( $order)
    {
        if ('woo_nagad' !== $order->get_payment_method() ) { return;
        }

        $number = ( get_post_meta($_GET['post'], 'woo_nagad_number', true) ) ? get_post_meta($_GET['post'], 'woo_nagad_number', true) : '';
        $trans_id = ( get_post_meta($_GET['post'], 'woo_nagad_trans_id', true) ) ? get_post_meta($_GET['post'], 'woo_nagad_trans_id', true) : '';
        ?>
        <div class="form-field form-field-wide bdpg-admin-data">
            <img src="<?php echo BD_PAYMENT_GATEWAYS_DIR_URL . '/assets/images/Nagad.png';?> " alt="Nagad">
            <table class="wp-list-table widefat striped posts">
                <tbody>
                    <tr>
                        <th>
                            <strong>
                                <?php echo __('Nagad Number', 'bangladeshi-payment-gateways');?>
                            </strong>
                        </th>
                        <td>
        <?php echo esc_attr($number);?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <strong>
                                <?php echo __('Transaction ID', 'bangladeshi-payment-gateways');?>
                            </strong>
                        </th>
                        <td>
        <?php echo esc_attr($trans_id);?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}

$nagad_settings = get_option('woocommerce_woo_nagad_settings');
if ('yes' == $nagad_settings['nagad_charge'] ) {
    add_action('woocommerce_cart_calculate_fees', 'bdpg_Nagad_charge_settings', 20, 1);
}

/**
 * Check if Nagad charge status.
 * 
 * @param Object $cart Cart
 */

if (! function_exists('bdpg_Nagad_charge_settings') ) {
    function bdpg_Nagad_charge_settings( $cart )
    {
        global $woocommerce;
        $nagad_settings = get_option('woocommerce_woo_nagad_settings');

        $av_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
        if (!empty($av_gateways) ) {

            $payment_method = WC()->session->get('chosen_payment_method');

            if (is_admin() && ! defined('DOING_AJAX') ) { return;
            }

            if($payment_method == 'woo_nagad' ) {
                $label = __('Nagad Charge', 'bangladeshi-payment-gateways');
                $amount = round( $cart->cart_contents_total * ( $nagad_settings['nagad_fee'] / 100 ) );
                $cart->add_fee($label, $amount, true, 'standard');
            }
        }
    }
}

/**
 * Display Nagad data in order review page
 */
add_action('woocommerce_order_details_after_customer_details', 'woo_nagad_data_order_review_page');

if (! function_exists('woo_nagad_data_order_review_page') ) {
    function woo_nagad_data_order_review_page( $order)
    {
        if ('woo_nagad' !== $order->get_payment_method() ) { return;
        }
        global $wp;

        if ($wp->query_vars['order-received'] ) {
            $order_id = (int) $wp->query_vars['order-received'];
        } else {
            $order_id = (int) $wp->query_vars['view-order'];
        }

        $number = ( get_post_meta($order_id, 'woo_nagad_number', true) ) ? get_post_meta($order_id, 'woo_nagad_number', true) : '';
        $trans_id = ( get_post_meta($order_id, 'woo_nagad_trans_id', true) ) ? get_post_meta($order_id, 'woo_nagad_trans_id', true) : '';
        ?>
        <div class="bdpg-g-details">
            <img src="<?php echo BD_PAYMENT_GATEWAYS_DIR_URL . '/assets/images/Nagad.png';?> " alt="Nagad">
            <table class="wp-list-table widefat striped posts">
                <tbody>
                    <tr>
                        <th>
                            <strong>
                                <?php echo __('Nagad Number', 'bangladeshi-payment-gateways');?>
                            </strong>
                        </th>
                        <td>
        <?php echo esc_attr($number);?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <strong>
                                <?php echo __('Transaction ID', 'bangladeshi-payment-gateways');?>
                            </strong>
                        </th>
                        <td>
        <?php echo esc_attr($trans_id);?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}


/**
 * Register New Column For Payment Info
 */
add_filter('manage_edit-shop_order_columns', 'bdpg_nagad_admin_register_column');
function bdpg_nagad_admin_register_column( $columns )
{

    $columns = ( is_array($columns) ) ? $columns : array();

    $columns['payment_no']   = esc_html__('Payment No', 'bangladeshi-payment-gateways');
    $columns['tran_id']     = esc_html__('Tran. ID', 'bangladeshi-payment-gateways');

    $columns['order_actions'] = $columns['order_actions'];

    return $columns;

}

/**
 * Load Payment Data in New Column
 */
add_action('manage_shop_order_posts_custom_column', 'bdpg_nagad_admin_column_value', 2);
function bdpg_nagad_admin_column_value( $column )
{

    global $post;

    $payment_no = ( get_post_meta($post->ID, 'woo_nagad_number', true) ) ? get_post_meta($post->ID, 'woo_nagad_number', true) : '';
    $tran_id  = ( get_post_meta($post->ID, 'woo_nagad_trans_id', true) ) ? get_post_meta($post->ID, 'woo_nagad_trans_id', true) : '';

    if ($column == 'payment_no' ) {
        echo esc_attr($payment_no);
    }
    
    if ($column == 'tran_id' ) {
        echo esc_attr($tran_id);
    }
}