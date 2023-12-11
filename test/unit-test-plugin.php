<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'oneten-cities/oneten-cities.php' );

        $this->assertContains(
            'oneten-cities/oneten-cities.php',
            get_option( 'active_plugins' )
        );
    }
}
