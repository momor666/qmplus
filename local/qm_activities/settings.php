<?php
/**
 * Created by PhpStorm.
 * User: vasileios
 * Date: 26/06/2017
 * Time: 09:30
 * QM+ Activities reporting plugin
 */
// Ensure the configurations for this site are set
defined('MOODLE_INTERNAL') || die();
if ( $hassiteconfig ) {
    $settings = new admin_settingpage( 'local_qm_activities', 'QM+ Calendar Activities Settings' );

    // Create
    $ADMIN->add( 'localplugins', $settings );
    // Add a setting field to the settings for this page
    $settings->add( new admin_setting_configtext(

    // This is the reference you will use to your configuration
        'local_qm_activities/qm_activities_cache_minutes',

        // This is the friendly title for the config, which will be displayed
        'Cache Time in Minutes',

        // This is helper text for this config field
        'Set how many minutes the reports will cache data for',

        // This is the default value
        5,

        // This is the type of Parameter this config is
        PARAM_INT

    ) );
    $settings->add( new admin_setting_configtext(

    // This is the reference you will use to your configuration
        'local_qm_activities/qm_activities_heatmap_method',

        // This is the friendly title for the config, which will be displayed
        'Heatmap Method',

        // This is helper text for this config field
        'Heatmap colour scale method: can be cyan-red (default) or blue-red',

        // This is the default value
        'cyan-red',

        // This is the type of Parameter this config is
        PARAM_TEXT

    ) );

}