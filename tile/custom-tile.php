<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Oneten_Cities_Tile
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields' ], 1, 2 );
    }

    /**
     * @param array $fields
     * @param string $post_type
     * @return array
     */
    public function dt_custom_fields( array $fields, string $post_type = '' ) {
        if ( $post_type === 'campaigns' || $post_type === 'starter_post_type' ){
            $fields['app_auth_code'] = [
                'name'        => __( 'App Auth Code', 'oneten-cities' ),
                'type'        => 'text',
                'default'     => '',
                'tile'        => 'campaign_setup',
                'font-icon' => 'mdi mdi-key',
                'enabled' => true,
            ];
            //android app link
            $fields['android_app_link'] = [
                'name'        => __( 'Android App Link', 'oneten-cities' ),
                'type'        => 'text',
                'default'     => '',
                'tile'        => 'campaign_setup',
                'font-icon' => 'mdi mdi-android',
                'enabled' => true,
            ];
            //ios app link
            $fields['ios_app_link'] = [
                'name'        => __( 'iOS App Link', 'oneten-cities' ),
                'type'        => 'text',
                'default'     => '',
                'tile'        => 'campaign_setup',
                'font-icon' => 'mdi mdi-apple',
                'enabled' => true,
            ];
            $fields['city_link'] = [
                'name'        => __( 'City Link', 'oneten-cities' ),
                'type'        => 'text',
                'default'     => '',
                'tile'        => 'campaign_setup',
                'font-icon' => 'mdi mdi-link',
                'enabled' => true,
            ];
        }
        return $fields;
    }
}
Oneten_Cities_Tile::instance();
