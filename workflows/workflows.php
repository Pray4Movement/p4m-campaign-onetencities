<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Oneten_Cities_Workflows
 *
 * @since  1.11.0
 */
class Oneten_Cities_Workflows {
    /**
     * Oneten_Cities_Workflows constructor.
     */
    public function __construct() {
        if ( !wp_next_scheduled( 'oneten_cities_sync_app_prayer_fuel' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'oneten_cities_sync_app_prayer_fuel' );
        }
        add_action( 'oneten_cities_sync_app_prayer_fuel', [ $this, 'sync_app_prayer_fuel' ] );
    }


    public static function sync_app_prayer_fuel(){
        $campaigns = DT_Posts::list_posts( 'campaigns', [
            'app_auth_code' => [ '*' ]
        ] );

        foreach ( $campaigns['posts'] as $campaign ){
            $auth_key = $campaign['app_auth_code'];

//            $start_date = gmdate( 'Y-m-d', $campaign['start_date']['timestamp'] );
            $start_date = gmdate( 'Y-m-d', strtotime( '-5 day' ) );
            $end_date = gmdate( 'Y-m-d', strtotime( '+15 day' ) );
            $language_id = '2'; // '2' = 'English

            $url = 'https://prod.connect.prayerforus.com/api/v2/prayerpoints?source=DT';
            if ( !empty( $start_date ) ) {
                $url .= '&start_date=' . $start_date;
            }
            if ( !empty( $end_date ) ) {
                $url .= '&end_date=' . $end_date;
            }
            if ( !empty( $language_id ) ) {
                $url .= '&language_id=' . $language_id;
            }

            $call = wp_remote_get(
                $url,
                [
                    'headers' => [
                        'Authorization' => $auth_key,
                    ]
                ]
            );
            $body = wp_remote_retrieve_body( $call );
            $data = json_decode( $body, true );

            $prayer_points = $data['data'];

            global $wpdb;
            $existing_ids = $wpdb->get_col( "
                SELECT meta_value
                FROM $wpdb->postmeta
                WHERE meta_key = 'app_prayer_point_id'
            " );

            foreach ( $prayer_points as $prayer_point ){
                if ( !in_array( $prayer_point['id'], $existing_ids ) ){
                    self::create_post_day( $campaign['ID'], $prayer_point );
                }
            }
        }
    }


    public static function create_post_day( $campaign_id, $prayer_point ){
        global $wpdb;

        //get day related to campaign start
        $day = DT_Campaign_Fuel::what_day_in_campaign( $prayer_point['forday'], $campaign_id );

        $post_content = '';
        $image = '';
        if ( !empty( $prayer_point['picturesmall'] ) ){
            $image = '<figure class="wp-block-image"><img src="' . $prayer_point['picturesmall'] . '" alt="prayer point image"  /></figure >';
        }
        $prayer_point_content = [
            '<!-- wp:heading {"level":3} -->',
            '<h3><strong>' . $prayer_point['title'] . '</strong></h3>',
            '<!-- /wp:heading -->',

            '<!-- wp:paragraph -->',
            wp_kses_post( $prayer_point['introduction'] ),
            '<!-- /wp:paragraph -->',

            '<!-- wp:paragraph -->',
            wp_kses_post( $prayer_point['remainder'] ),
            '<!-- /wp:paragraph -->',

            '<!-- wp:image -->',
            $image,
            '<!-- /wp:image -->',

        ];
        $post_content .= implode( '', wp_unslash( $prayer_point_content ) );

        $args = [
            'post_title'    => $prayer_point['title'],

            'post_content'  => $post_content,
            'post_excerpt'  => wp_strip_all_tags( $prayer_point['introduction'] ),
            'post_type'  => PORCH_LANDING_POST_TYPE,
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
            'meta_input' => [
                'app_prayer_point_id' => $prayer_point['id'],
                PORCH_LANDING_META_KEY => $prayer_point['forday'],
                'post_language' => 'en_US',
                'day' => $day,
                'linked_campaign' => $campaign_id,
            ]
        ];

        $installed[] = wp_insert_post( $args );

    }

}
