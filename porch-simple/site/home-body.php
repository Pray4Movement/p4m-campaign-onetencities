<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$lang = dt_campaign_get_current_lang();

$porch_fields = DT_Porch_Settings::settings();
$campaign_fields = DT_Campaign_Landing_Settings::get_campaign();
$root_url = DT_Campaign_Landing_Settings::get_landing_root_url();

global $allowedtags;

if ( empty( $campaign_fields ) ): ?>
    <div class="container">
        <p style="margin:auto">Choose a campaign in settings <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns' ) );?>">here</a></p>
    </div>
    <?php die();
endif;

$campaign_has_end_date = !empty( $campaign_fields['end_date']['timestamp'] );

$campaign_root = 'campaign_app';
$campaign_type = 'ongoing';
$key_name = 'public_key';
$key = '';
if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
    $key_name = DT_Magic_URL::get_public_key_meta_key( $campaign_root, $campaign_type );
}
if ( isset( $campaign_fields[$key_name] ) ){
    $key = $campaign_fields[$key_name];
}
$atts = [
    'root' => $campaign_root,
    'type' => $campaign_type,
    'public_key' => $key,
    'meta_key' => $key_name,
    'post_id' => (int) $campaign_fields['ID'],
    'rest_url' => rest_url(),
    'lang' => $lang
];
$dt_campaign_selected_campaign_magic_link_settings = $atts;
$dt_campaign_selected_campaign_magic_link_settings['color'] = CAMPAIGN_LANDING_COLOR_SCHEME_HEX;
if ( $dt_campaign_selected_campaign_magic_link_settings['color'] === 'preset' ){
    $dt_campaign_selected_campaign_magic_link_settings['color'] = '#4676fa';
}

?>

<!-- MODALS -->
<div id="edit_modal_div"></div>
<!-- MODALS -->


<!-- SIGN UP TO PRAY -->
<section id="features" class="section" data-stellar-background-ratio="0.2">
    <div id="sign-up" name="sign-up" class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Sign Up to Pray', 'disciple-tools-prayer-campaigns' ); ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
        </div>
        <div class="row">
            <?php echo dt_generic_signup_shortcode( $dt_campaign_selected_campaign_magic_link_settings ); //phpcs:ignore ?>
        </div>
    </div>
</section>
<!-- Features Section End -->


<!-- COUNTER ROW -->
<div class="counters section" data-stellar-background-ratio="0.5" >
    <div class="overlay"></div>
    <div class="container">
        <div class="row">
            <?php
            $days_in_campaign = DT_Campaign_Fuel::total_days_in_campaign();
            $minutes_committed = DT_Campaigns_Base::get_minutes_prayed_and_scheduled( $campaign_fields['ID'] );
            $time_committed = DT_Time_Utilities::display_minutes_in_time( $minutes_committed );

            $size = $campaign_has_end_date ? 'col-sm-6 col-md-4 col-lg-4' : 'col-sm-6 col-md-6 col-lg-6';
            ?>
            <div class="<?php echo esc_html( $size ); ?>">
                <div class="wow fadeInUp" data-wow-delay=".2s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-calendar-full"></i>
                        </div>
                        <div class="fact-count">

                            <h3><?php echo esc_html( $time_committed ); ?></h3>
                            <h4><?php esc_html_e( 'Time Committed', 'disciple-tools-prayer-campaigns' ) ?></h4>

                        </div>
                    </div>
                </div>
            </div>
            <?php $subscribers_count = DT_Subscriptions::get_subscribers_count( $campaign_fields['ID'] ); ?>

            <div class="<?php echo esc_html( $size ); ?>">
                <div class="wow fadeInUp" data-wow-delay=".6s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-user"></i>
                        </div>
                        <div class="fact-count">
                            <h3><?php echo esc_html( $subscribers_count ?? 0 ) ?></h3>
                            <h4><?php esc_html_e( 'Prayer Warriors', 'disciple-tools-prayer-campaigns' ); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ( $campaign_has_end_date ) : ?>
                <div class="<?php echo esc_html( $size ); ?>">
                    <div class="wow fadeInUp" data-wow-delay=".8s">
                        <div class="facts-item">
                            <div class="icon">
                                <i class="lnr lnr-heart"></i>
                            </div>
                            <div class="fact-count">

                                <h3><?php echo $days_in_campaign !== -1 ? esc_html( $days_in_campaign * 24 * 4 ) : '2880' ?></h3>
                                <h4><?php esc_html_e( 'Prayer Commitments Needed', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Counter Section End -->

<!-- Blog Section -->
<section id="blog" class="section">
    <!-- Container Starts -->
    <div class="container">
        <div class="section-header">
            <h2 class="section-title split-color wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'prayer_fuel_title', 'btn-common', true ); ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'prayer_fuel_description' ); ?></p>
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
                <a href="<?php echo esc_html( $root_url . '/list' ); ?>" class="btn btn-common btn-rm"><?php esc_html_e( 'View All', 'disciple-tools-prayer-campaigns' ); ?></a>
            </p>
        </div>
    </div>
</section>
<!-- blog Section End -->
