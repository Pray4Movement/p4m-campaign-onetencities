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
        ], false );

        foreach ( $campaigns['posts'] as $campaign ){
            $auth_key = $campaign['app_auth_code'];
            $enabled_languages = $campaign['enabled_languages'];
            if ( empty( $enabled_languages ) ){
                $enabled_languages = [ 'en_US' ];
            }

//            $start_date = gmdate( 'Y-m-d', $campaign['start_date']['timestamp'] );
            $start_date = gmdate( 'Y-m-d', strtotime( '-5 day' ) );
            $end_date = gmdate( 'Y-m-d', strtotime( '+2 day' ) );

//            $language_id = $lang['language_id'];

            $url = 'https://prod.connect.prayerforus.com/api/v2/prayerpoints?source=DT';
            if ( !empty( $start_date ) ){
                $url .= '&start_date=' . $start_date;
            }
            if ( !empty( $end_date ) ){
                $url .= '&end_date=' . $end_date;
            }
//            if ( !empty( $language_id ) ){
//                $url .= '&language_id=' . $language_id;
//            }
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
                    self::create_post_day( $campaign['ID'], $prayer_point, 'en_US' );

                    if ( !isset( $prayer_point['sub_contents'] ) ){
                        continue;
                    }

                    foreach ( $prayer_point['sub_contents'] as $sub_content ){
                        if ( in_array( $sub_content['id'], $existing_ids ) ){
                            continue;
                        }
                        $lang = self::get_lang_by_language_id( $sub_content['language_id'] );
                        if ( !empty( $lang ) && !in_array( $lang['language_locale'], $enabled_languages ) ){
                            continue;
                        }
                        $sub_content['picturesmall'] = $prayer_point['picturesmall'];

                        self::create_post_day( $campaign['ID'], $sub_content, $lang['language_locale'] );
                    }
                }
            }
        }
    }


    public static function create_post_day( $campaign_id, $prayer_point, $language_code = 'en_US'  ){
        //get day related to campaign start
        $day = DT_Campaign_Fuel::what_day_in_campaign( $prayer_point['forday'], $campaign_id );

        $post_content = '';
        $image = '';
        if ( !empty( $prayer_point['picturesmall'] ) && $prayer_point['picturesmall'] !== 'https://prod.connect.prayerforus.com/uploads/small/' ){
            $image = '<figure class="wp-block-image"><img src="' . $prayer_point['picturesmall'] . '" alt="prayer point image"  /></figure >';
        }

        //if does not contain html, add <p> tags
        if ( !strpos( $prayer_point['introduction'], '<' ) ){
            $prayer_point['introduction'] = '<p>' . $prayer_point['introduction'] . '</p>';
        }
        if ( !strpos( $prayer_point['remainder'], '<' ) ){
            $prayer_point['remainder'] = '<p>' . $prayer_point['remainder'] . '</p>';
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
            'post_type'  => 'landing',
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
            'meta_input' => [
                'app_prayer_point_id' => $prayer_point['id'],
                'prayer_fuel_magic_key' => $prayer_point['forday'],
                'post_language' => $language_code,
                'day' => $day,
                'linked_campaign' => $campaign_id,
            ]
        ];

        $installed[] = wp_insert_post( $args );

    }


    public static function get_lang_by_language_id( $lang_id ){
        $langs = self::get_app_languages();
        foreach ( $langs as $lang ){
            if ( $lang['language_id'] == $lang_id ){
                return $lang;
            }
        }
        return null;
    }

    public static function get_lang( $lang_code ){
        $langs = self::get_app_languages();
        foreach ( $langs as $lang ){
            if ( $lang['language_locale'] == $lang_code || $lang['language_code'] == $lang_code ){
                return $lang;
            }
        }
        return null;
    }
    public static function get_app_languages(){
        $language_data = [
            [
                'language_id' => 1,
                'language_name' => 'Chinese',
                'language_code' => 'zh',
                'language_locale' => 'zh_CN',
                'language_direction' => 'ltr',
                'language_date_format' => 'MM?dd?',
                'language_status' => 1,
            ],
            [
                'language_id' => 2,
                'language_name' => 'English',
                'language_code' => 'en',
                'language_locale' => 'en_US',
                'language_direction' => 'ltr',
                'language_date_format' => 'MMMM dd',
                'language_status' => 1,
            ],
            [
                'language_id' => 3,
                'language_name' => 'Spanish',
                'language_code' => 'es',
                'language_locale' => 'es_ES',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 4,
                'language_name' => 'Hindi',
                'language_code' => 'hi',
                'language_locale' => 'hi_IN',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 5,
                'language_name' => 'Arabic',
                'language_code' => 'ar',
                'language_locale' => 'ar',
                'language_direction' => 'rtl',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 6,
                'language_name' => 'German',
                'language_code' => 'de',
                'language_locale' => 'de_DE',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd. MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 7,
                'language_name' => 'Japanese',
                'language_code' => 'ja',
                'language_locale' => 'ja',
                'language_direction' => 'ltr',
                'language_date_format' => 'MMMM dd',
                'language_status' => 1,
            ],
            [
                'language_id' => 8,
                'language_name' => 'Russian',
                'language_code' => 'ru',
                'language_locale' => 'ru_RU',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 9,
                'language_name' => 'Portuguese',
                'language_code' => 'pt',
                'language_locale' => 'pt',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 10,
                'language_name' => 'Korean',
                'language_code' => 'ko',
                'language_locale' => 'ko_KR',
                'language_direction' => 'ltr',
                'language_date_format' => 'MMMM dd',
                'language_status' => 1,
            ],
            [
                'language_id' => 11,
                'language_name' => 'French',
                'language_code' => 'fr',
                'language_locale' => 'fr_FR',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 12,
                'language_name' => 'Urdu',
                'language_code' => 'ur',
                'language_locale' => 'ur',
                'language_direction' => 'rtl',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 13,
                'language_name' => 'Dutch',
                'language_code' => 'nl',
                'language_locale' => 'nl_NL',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 14,
                'language_name' => 'Malay',
                'language_code' => 'ms',
                'language_locale' => 'ms',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 15,
                'language_name' => 'Italian',
                'language_code' => 'it',
                'language_locale' => 'it_IT',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 16,
                'language_name' => 'Greek',
                'language_code' => 'el',
                'language_locale' => 'el',
                'language_direction' => 'ltr',
                'language_date_format' => 'MMMM dd',
                'language_status' => 1,
            ],
            [
                'language_id' => 17,
                'language_name' => 'Turkish',
                'language_code' => 'tr',
                'language_locale' => 'tr_TR',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 18,
                'language_name' => 'Afrikaans',
                'language_code' => 'af',
                'language_locale' => 'af',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 19,
                'language_name' => 'Swahili',
                'language_code' => 'sw',
                'language_locale' => 'sw',
                'language_direction' => 'ltr',
                'language_date_format' => 'MMMM dd',
                'language_status' => 1,
            ],
            [
                'language_id' => 20,
                'language_name' => 'Amharic',
                'language_code' => 'am',
                'language_locale' => 'am_ET',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 21,
                'language_name' => 'Danish',
                'language_code' => 'da',
                'language_locale' => 'da',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 22,
                'language_name' => 'Finnish',
                'language_code' => 'fi',
                'language_locale' => 'fi',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd. MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 23,
                'language_name' => 'Swedish',
                'language_code' => 'sv',
                'language_locale' => 'sv',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 24,
                'language_name' => 'Thai',
                'language_code' => 'th',
                'language_locale' => 'th',
                'language_direction' => 'ltr',
                'language_date_format' => 'MMMM dd',
                'language_status' => 1,
            ],
            [
                'language_id' => 25,
                'language_name' => 'Faroese',
                'language_code' => 'fo',
                'language_locale' => 'fo_FO',
                'language_direction' => 'ltr',
                'language_date_format' => 'd. MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 26,
                'language_name' => 'Albanian',
                'language_code' => 'sq',
                'language_locale' => 'sq_AL',
                'language_direction' => 'ltr',
                'language_date_format' => 'd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 27,
                'language_name' => 'Czech',
                'language_code' => 'cs',
                'language_locale' => 'cs',
                'language_direction' => 'ltr',
                'language_date_format' => 'd. MMMM yyyy',
                'language_status' => 1,
            ],
            [
                'language_id' => 28,
                'language_name' => 'Nepali',
                'language_code' => 'ne',
                'language_locale' => 'ne_NP',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 29,
                'language_name' => 'Romanian',
                'language_code' => 'ro',
                'language_locale' => 'ro_RO',
                'language_direction' => 'ltr',
                'language_date_format' => 'd MMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 30,
                'language_name' => 'Ukrainian',
                'language_code' => 'uk',
                'language_locale' => 'uk_UA',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 31,
                'language_name' => 'Estonian',
                'language_code' => 'et',
                'language_locale' => 'et_EE',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 32,
                'language_name' => 'Hungarian',
                'language_code' => 'hu',
                'language_locale' => 'hu_HU',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 33,
                'language_name' => 'Indonesian',
                'language_code' => 'id',
                'language_locale' => 'id_ID',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 34,
                'language_name' => 'Latvian',
                'language_code' => 'lv',
                'language_locale' => 'lv_LV',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 35,
                'language_name' => 'Lithuanian',
                'language_code' => 'lt',
                'language_locale' => 'lt_LT',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 36,
                'language_name' => 'Polish',
                'language_code' => 'pl',
                'language_locale' => 'pl_PL',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 37,
                'language_name' => 'Portuguese (Brazilian)',
                'language_code' => 'pt',
                'language_locale' => 'pt_BR',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 39,
                'language_name' => 'Slovak',
                'language_code' => 'sk',
                'language_locale' => 'sk_SK',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 40,
                'language_name' => 'Slovenian',
                'language_code' => 'sl',
                'language_locale' => 'sl_SI',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 41,
                'language_name' => 'Norwegian',
                'language_code' => 'no',
                'language_locale' => 'nb_NO',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd. MMMM',
                'language_status' => 1,
            ],
            [
                'language_id' => 42,
                'language_name' => 'Bulgarian',
                'language_code' => 'bg',
                'language_locale' => 'bg_BG',
                'language_direction' => 'ltr',
                'language_date_format' => 'dd MMMM',
                'language_status' => 1,
            ],
        ];
        return $language_data;
    }

}
