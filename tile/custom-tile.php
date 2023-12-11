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
                'icon' => get_template_directory_uri() . '/dt-assets/images/edit.svg',
                'enabled' => true,
            ];
        }
        return $fields;
    }
}
Oneten_Cities_Tile::instance();
