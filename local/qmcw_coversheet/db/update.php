<?php

function xmldb_local_coversheet_upgrade($oldversion)
{


    if ($oldversion < 2017030711) {

        // Define field id to be added to local_coversheet.
        $table = new xmldb_table('local_coversheet');
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Qmul_coursework_coversheet savepoint reached.
        upgrade_plugin_savepoint(true, 2017030711, 'local', 'qmcw_coversheet');
    }

}