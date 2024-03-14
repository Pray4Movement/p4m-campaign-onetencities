<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Oneten_Sync_Fuel_Menu
 */
class Oneten_Sync_Fuel_Menu {

    public $token = 'ramadan_2024';
    public $page_title = 'Ramadan 2024';

    private static $_instance = null;

    /**
     * Ramadan_2024_Menu Instance
     *
     * Ensures only one instance of Ramadan_2024_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return Ramadan_2024_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
        add_action( 'dt_prayer_campaigns_admin_install_fuel', [ $this, 'content' ] );
    } // End __construct()

    public static function content(){
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php self::main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php self::right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }
    public static function main_column() {

        $sync_started = false;

        if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'oneten_sync' ) ){
            $campaign_id = DT_Campaign_Landing_Settings::get_campaign_id();
            global $wpdb;
            $wpdb->query( $wpdb->prepare("
                DELETE p FROM $wpdb->posts p
                INNER JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id AND pm.meta_key = 'linked_campaign' AND pm.meta_value = %s )
                WHERE p.post_type = 'landing'
           ", $campaign_id ) );
            $wpdb->query( $wpdb->prepare("
                DELETE pm FROM $wpdb->postmeta p
                INNER JOIN $wpdb->postmeta pm ON ( p.post_id = pm.post_id AND pm.meta_key = 'linked_campaign' AND pm.meta_value = %s )
           ", $campaign_id ) );

            $start = new DateTime( '2024-01-01' );
            $end = new DateTime();

            $wpdb->query( $wpdb->prepare("
                DELETE FROM {$wpdb->prefix}queue_jobs
                WHERE category = %s
            ", 'one_ten_sync_fuel_' . $campaign_id ) );

            $interval = DateInterval::createFromDateString( '1 day' );
            $period = new DatePeriod( $start, $interval, $end );


            foreach ( $period as $dt ){
                $format = $dt->format( 'Y-m-d' );
                $job = new DT_Get_Fuel_For_Campaign_Job( $campaign_id, $format, $format );
                wp_queue()->push( $job, 0, 'one_ten_sync_fuel_' . $campaign_id );
            }
            ?>
                <div style="padding: 10px; margin-bottom: 10px; background-color: indianred; color: white">
                    Sync Started. See <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_utilities&tab=background_jobs' ) ) ?>">Background Jobs</a> for progress.
                </div>
            <?php

            $sync_started = true;
        }


        ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th>110 Cities - App Prayer Fuel Sync</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Delete all prayer fuel and resync for start
                </td>
                <td>
                    <form method="post">
                        <?php wp_nonce_field( 'oneten_sync' ) ?>
                        <button type="submit" class="button button-primary">Delete and Sync</button>
                    </form>
                </td>
            </tr>
            </tbody>
        </table>


        <br>
        <!-- End Box -->
        <?php
    }

    public static function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Information</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}
Oneten_Sync_Fuel_Menu::instance();

