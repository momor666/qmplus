<?php
/**
 * Version details.
 *
 * @package    local
 * @subpackage qmul_dasboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    require_once(__DIR__.'/../../config.php');
    require_once(__DIR__.'/lib.php');

    if(isset($_REQUEST['category']))
    {
        $result = local_qmul_dashboard_removeSetting($_REQUEST['category']);
        echo json_encode($result);
    }

}