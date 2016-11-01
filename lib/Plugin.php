<?php
namespace Bookly\Lib;

/**
 * Class Plugin
 * @package Bookly\Lib
 */
abstract class Plugin
{
    protected static $version;
    const PURCHASE_CODE = 'ab_envato_purchase_code';
    
    public static function activate( $register_hook = true )
    {
        $installer = new \Bookly\Installer();
        $installer->install();
        $register_hook ? do_action( 'bookly_plugin_activate', 'bookly' ) : null;
    }

    public static function deactivate( $register_hook = true )
    {
        unload_textdomain( 'bookly' );
        $register_hook ? do_action( 'bookly_plugin_deactivate', 'bookly' ) : null;
    }

    public static function uninstall( $register_hook = true )
    {
        $installer = new \Bookly\Installer();
        $installer->uninstall();
        if ( $register_hook !== false ) {
            add_filter( 'bookly_plugins', function ( array $plugins ) {
                $plugins['bookly'] = array( 'title' => 'Bookly', 'path' => 'appointment-booking/main.php', 'purchase_code' => Plugin::PURCHASE_CODE, 'Plugin' => '\Bookly\Lib\Plugin' );

                return $plugins;
            } );
            do_action( 'bookly_plugin_uninstall', 'bookly' );
        }
    }

    public static function version()
    {
        if ( self::$version == null ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_data = get_plugin_data( AB_PATH . '/main.php' );
            self::$version = $plugin_data['Version'];
        }

        return self::$version;
    }

    public static function registerHooks()
    {
        if ( is_admin() ) {
            register_activation_hook( AB_PATH . '/main.php',   array( __CLASS__, 'activate' ) );
            register_deactivation_hook( AB_PATH . '/main.php', array( __CLASS__, 'deactivate' ) );
            register_uninstall_hook( AB_PATH . '/main.php',    array( __CLASS__, 'uninstall' ) );
            if ( get_option( self::PURCHASE_CODE ) ) {
                include_once AB_PATH . '/lib/utils/plugin-update-checker.php';
                add_filter( 'puc_manual_check_link-' . basename( AB_PATH ), function () { return __( 'Check for updates', 'bookly' ); } );
                add_filter( 'puc_manual_check_message-' . basename( AB_PATH ), function ( $message, $status ) {
                    switch ( $status ) {
                        case 'no_update':           return __( 'This plugin is up to date.', 'bookly' );
                        case 'update_available':    return __( 'A new version of this plugin is available.', 'bookly' );
                        default:                    return sprintf( __( 'Unknown update checker status "%s"', 'bookly' ), htmlentities( $status ) );
                    }
                }, 10, 2 );
                add_filter( 'puc_request_info_query_args-' . basename( AB_PATH ), function( $queryArgs ) {
                    global $wp_version;

                    $queryArgs['api']      = '1.0';
                    $queryArgs['action']   = 'update';
                    $queryArgs['plugin']   = 'bookly';
                    $queryArgs['site']     = parse_url( site_url(), PHP_URL_HOST );
                    $queryArgs['versions'] = array( \Bookly\Lib\Plugin::version(), 'wp' => $wp_version, 'ab' => get_option( 'ab_db_version' ) );
                    $queryArgs['purchase_code'] = get_option( \Bookly\Lib\Plugin::PURCHASE_CODE );
                    unset( $queryArgs['checking_for_updates'] );
                    return $queryArgs;
                } );
                \PucFactory::buildUpdateChecker(
                    'http://booking-wp-plugin.com/index.php',
                    AB_PATH . '/main.php',
                    basename( AB_PATH ),
                    24
                );
            } else {
                add_filter( 'plugin_row_meta', function ( $links, $plugin ) {
                    if ( $plugin == 'appointment-booking/main.php' )
                        return array_merge( $links, array( 0 => '<span class="dashicons dashicons-info"></span> ' . sprintf( __( 'To update - enter the <a href="%s">Purchase Code</a>', 'bookly' ), Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Settings\Controller::page_slug, array( 'tab' => 'purchase_code' ) ) ) ) );
                    return $links;
                }, 10, 2 );
            }
            add_action( 'bookly_render_purchase_code', function ( $blog_id = null ) {
                $purchase_code = $blog_id ? get_blog_option( $blog_id, Plugin::PURCHASE_CODE ) : get_option( Plugin::PURCHASE_CODE );
                printf( '<div class="form-group"><h4>Bookly</h4><label for="%1$s">%2$s</label><input id="%1$s" class="purchase-code form-control" type="text" name="purchase_code[%1$s]" value="%3$s" /></div>', Plugin::PURCHASE_CODE, __( 'Purchase Code', 'bookly' ), $purchase_code );
            }, 1, 1 );
            add_filter( 'bookly_save_purchase_codes', function ( $errors, $purchase_codes, $blog_id ) {
                if ( array_key_exists( Plugin::PURCHASE_CODE, (array) $purchase_codes ) ) {
                    $purchase_code = $purchase_codes[ Plugin::PURCHASE_CODE ];
                    if ( $purchase_code == '' || \Bookly\Lib\Utils\Common::verifyPurchaseCode( $purchase_code, 'bookly' ) ) {
                        if ( $blog_id ) {
                            update_blog_option( $blog_id, Plugin::PURCHASE_CODE, $purchase_code );
                        } else {
                            update_option( Plugin::PURCHASE_CODE, $purchase_code );
                        }
                    } else {
                        $errors[] = sprintf( __( '<strong>%s</strong> is not a valid purchase code for %s.', 'bookly' ), $purchase_code, 'Bookly' );
                    }
                }

                return $errors;
            } , 10, 3 );
        }
        add_filter( 'bookly_plugins', function( array $plugins ) {
            $plugins['bookly'] = array( 'title' => 'Bookly', 'path' => 'appointment-booking/main.php', 'purchase_code' => Plugin::PURCHASE_CODE, 'Plugin' => '\Bookly\Lib\Plugin' );

            return $plugins;
        } );
    }

}