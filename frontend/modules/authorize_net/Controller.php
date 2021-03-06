<?php
namespace Bookly\Frontend\Modules\AuthorizeNet;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\AuthorizeNet
 */
class Controller extends Lib\Controller
{
    const SIGNUP = 'https://www.authorize.net/solutions/merchantsolutions/pricing/';
    const HOME   = 'https://www.authorize.net/';

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    /**
     * Do AIM payment.
     */
    public function executeAuthorizeNetAIM()
    {
        $response = null;
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $failed_cart_key = $userData->getFailedCartKey();
            if ( $failed_cart_key === null ) {
                $cart_info = $userData->getCartInfo();
                $total = $cart_info['total_price'];
                $card  = $this->getParameter( 'card' );
                // Authorize.Net AIM Payment.
                $authorize = new Lib\Payment\AuthorizeNet( get_option( 'ab_authorizenet_api_login_id' ), get_option( 'ab_authorizenet_transaction_key' ), (bool) get_option( 'ab_authorizenet_sandbox' ) );
                $authorize->setField( 'amount',     $total );
                $authorize->setField( 'card_num',   $card['number'] );
                $authorize->setField( 'card_code',  $card['cvc'] );
                $authorize->setField( 'exp_date',   $card['exp_month'] . '/' . $card['exp_year'] );
                $authorize->setField( 'first_name', $userData->get( 'name' ) );
                $authorize->setField( 'email',      $userData->get( 'email' ) );
                $authorize->setField( 'phone',      $userData->get( 'phone' ) );

                $aim_response = $authorize->authorizeAndCapture();
                if ( $aim_response->approved ) {
                    $payment = Lib\Entities\Payment::query()
                        ->select( 'id' )
                        ->where( 'type', Lib\Entities\Payment::TYPE_AUTHORIZENET )
                        ->where( 'transaction_id', $aim_response->transaction_id )
                        ->findOne();
                    if ( empty ( $payment ) ) {
                        $coupon = $userData->getCoupon();
                        if ( $coupon ) {
                            $coupon->claim();
                            $coupon->save();
                        }
                        $payment = new Lib\Entities\Payment();
                        $payment->set( 'transaction_id', $aim_response->transaction_id )
                            ->set( 'type',    Lib\Entities\Payment::TYPE_AUTHORIZENET )
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
                    $response = array ( 'success' => true );
                } else {
                    $response = array ( 'success' => false, 'error_code' => 7, 'error' => $aim_response->response_reason_text );
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
