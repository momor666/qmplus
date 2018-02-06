_<?php
/**
 * Capability definition for the gradebook qmul_sits report
 *
 * @package   gradereport_qmul_sits
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'gradereport/qmul_sits:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
    'gradereport/qmul_sits:bulk' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
    'gradereport/qmul_sits:edit' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    )
);


