<?php

class One_Ten_Cities_Porch_Simple_Loader extends DT_Generic_Porch_Loader {

    public $id = 'oneten-cities-porch-simple';
    public static $porch_url = null;

    public function __construct() {
        parent::__construct( __DIR__ );
        self::$porch_url = plugin_dir_url( __FILE__ ) . 'site/';

        $this->label = __( '110 Cities Landing Page Simple', 'disciple-tools-prayer-campaigns' );
        add_filter( 'dt_campaigns_wizard_types', array( $this, 'wizard_types' ) );

//        require_once( __DIR__ . '/settings.php' );
    }

    public function wizard_types( $wizard_types ) {
        $wizard_types[$this->id] = [
            'porch' => $this->id,
            'label' => '24/7 110 Cities Simple',
        ];

        return $wizard_types;
    }
}
( new One_Ten_Cities_Porch_Simple_Loader() )->register_porch();
