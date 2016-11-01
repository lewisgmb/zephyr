<?php
namespace Bookly\Frontend\Modules\Stripe;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\Stripe
 */
class Controller extends Lib\Controller
{
    const SIGNUP = 'https://dashboard.stripe.com/register';
    const HOME   = 'https://stripe.com/';

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    public function executeStripe()
    {
        $response = null;
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $failed_cart_key = $userData->getFailedCartKey();
            if ( $failed_cart_key === null ) {
                include_once AB_PATH . '/lib/payment/Stripe/init.php';
                \Stripe\Stripe::setApiKey( get_option( 'ab_stripe_secret_key' ) );
                \Stripe\Stripe::setApiVersion( '2015-08-19' );

                $cart_info = $userData->getCartInfo();
                try {
                    $charge = \Stripe\Charge::create( array(
                        'amount'      => intval( $cart_info['total_price'] * 100 ), // amount in cents
                        'currency'    => get_option( 'ab_currency' ),
                        'source'      => $this->getParameter( 'card' ), // contain token or card data
                        'description' => 'Charge for ' . $userData->get( 'email' )
                    ) );

                    if ( $charge->paid ) {
                        $payment = Lib\Entities\Payment::query()
                            ->select( 'id' )
                            ->where( 'type', Lib\Entities\Payment::TYPE_STRIPE )
                            ->where( 'transaction_id', $charge->id )
                            ->findOne();
                        if ( empty ( $payment ) ) {
                            $coupon = $userData->getCoupon();
                            if ( $coupon ) {
                                $coupon->claim();
                                $coupon->save();
                            }
                            $payment = new Lib\Entities\Payment();
                            $payment->set( 'transaction_id', $charge->id )
                                ->set( 'type',    Lib\Entities\Payment::TYPE_STRIPE )
                                ->set( 'status',  Lib\Entities\Payment::STATUS_COMPLETED )
                                ->set( 'total',   $cart_info['total_price'] )
                                ->set( 'created', current_time( 'mysql' ) )
                                ->save();
                            $payment_id = $payment->get( 'id' );
                            $ca_list = array();
                            $userData->foreachCartItem( function ( Lib\UserBookingData $userData ) use ( &$ca_list, $payment_id ) {
                                $ca_list[] = $userData->save( $payment_id );
                            } );
                            Lib\NotificationSender::sendFromCart( $ca_list );
                            $payment->setDetails( $ca_list, $coupon )->save();
                        }

                        $response = array( 'success' => true );
                    } else {
                        $response = array( 'success' => false, 'error_code' => 7, 'error' => __( 'Error', 'bookly' ) );
                    }
                } catch ( \Exception $e ) {
                    $response = array( 'success' => false, 'error_code' => 7, 'error' => $e->getMessage() );
                }
            } else {
                $response = array(
                    'success'         => false,
                    'error_code'      => 3,
                    'failed_cart_key' => $failed_cart_key,
                    'error'           => get_option( 'ab_settings_step_cart_enabled' )
                        ? __( 'The highlighted time is not available anymore. Please, choose another time slot.', 'bookly' )
                        : __( 'The selected time is not available anymore. Please, choose another time slot.', 'bookly' )
                );
            }
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        // Output JSON response.
        wp_send_json( $response );
    }

    /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     *
     * @param string $prefix
     */
    protected function registerWpActions( $prefix = '' )
    {
        parent::registerWpActions( 'wp_ajax_ab_' );
        parent::registerWpActions( 'wp_ajax_nopriv_ab_' );
    }

}