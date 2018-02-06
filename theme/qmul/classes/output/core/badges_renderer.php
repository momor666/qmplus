<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Renderer for use with the badges output
 *
 * @package    core
 * @subpackage badges
 * @copyright  2012 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Yuliya Bozhko <yuliya.bozhko@totaralms.com>
 */

namespace theme_qmul\output\core;
require_once($CFG->dirroot . '/badges/renderer.php');

use \html_writer;
use \moodle_url;
use \stdClass;

class badges_renderer extends \core_badges_renderer {

    protected function render_badge_collection(\badge_collection $badges) {
        $paging = new \paging_bar($badges->totalcount, $badges->page, $badges->perpage, $this->page->url, 'page');
        $htmlpagingbar = $this->render($paging);
        $table = new \html_table();
        $table->attributes['class'] = 'collection';

        $sortbyname = $this->helper_sortable_heading(get_string('name'),
                'name', $badges->sort, $badges->dir);
        $sortbyawarded = $this->helper_sortable_heading(get_string('awardedtoyou', 'badges'),
                'dateissued', $badges->sort, $badges->dir);
        $table->head = array(
                    get_string('badgeimage', 'badges'),
                    $sortbyname,
                    get_string('description', 'badges'),
                    get_string('bcriteria', 'badges'),
                    $sortbyawarded
                );
        $table->colclasses = array('badgeimage', 'name', 'description', 'criteria', 'awards');

        foreach ($badges->badges as $badge) {
            $badgeimage = print_badge_image($badge, $this->page->context, 'large');
            $name = $badge->name;
            $description = $badge->description;
            $criteria = self::print_badge_criteria($badge);
            if ($badge->dateissued) {
                $icon = new \pix_icon('i/valid',
                            get_string('dateearned', 'badges',
                                userdate($badge->dateissued, get_string('strftimedatefullshort', 'core_langconfig'))));
                $badgeurl = new moodle_url('/badges/badge.php', array('hash' => $badge->uniquehash));
                $awarded = $this->output->action_icon($badgeurl, $icon, null, null, true);
            } else {
                $awarded = "";
            }
            $row = array($badgeimage, $name, $description, $criteria, $awarded);
            $table->data[] = $row;
        }

        $htmltable = html_writer::table($table);

        return html_writer::tag('div', $htmlpagingbar . $htmltable . $htmlpagingbar, array('class'=>'no-overflow'));
    }

	public function print_badge_overview($badge, $context) {
        $display = "";

        // Badge details.
        $display .= html_writer::start_tag('div', array('class'=>'row mb-1'));

        $display .= html_writer::start_tag('div', array('class'=>'col-md-8'));
        $display .= html_writer::start_tag('div', array('class'=>'card'));
        $display .= html_writer::start_tag('div', array('class'=>'card-header'));
        $display .= $this->heading(get_string('badgedetails', 'badges'), 3);
        $display .= html_writer::end_tag('div');
        $display .= html_writer::start_tag('div', array('class'=>'card-block'));
        $dl = array();
        $dl[get_string('name')] = $badge->name;
        $dl[get_string('description', 'badges')] = $badge->description;
        $dl[get_string('createdon', 'search')] = userdate($badge->timecreated);
        $dl[get_string('badgeimage', 'badges')] = print_badge_image($badge, $context, 'large');
        $display .= $this->definition_list($dl);
        $display .= html_writer::end_tag('div');
        $display .= html_writer::end_tag('div');
        $display .= html_writer::end_tag('div');

        // Issuer details.
        $display .= html_writer::start_tag('div', array('class'=>'col-md-4'));
        $display .= html_writer::start_tag('div', array('class'=>'card'));
        $display .= html_writer::start_tag('div', array('class'=>'card-header'));
        $display .= $this->heading(get_string('issuerdetails', 'badges'), 3);
        $display .= html_writer::end_tag('div');
        $display .= html_writer::start_tag('div', array('class'=>'card-block'));
        $dl = array();
        $dl[get_string('issuername', 'badges')] = $badge->issuername;
        $dl[get_string('contact', 'badges')] = html_writer::tag('a', $badge->issuercontact, array('href' => 'mailto:' . $badge->issuercontact));
        $display .= $this->definition_list($dl);
        $display .= html_writer::end_tag('div');
        $display .= html_writer::end_tag('div');
        $display .= html_writer::end_tag('div');

        $display .= html_writer::end_tag('div');


        $display .= html_writer::start_tag('div', array('class'=>'row mb-1'));
        // Issuance details if any.
        $display .= html_writer::start_tag('div', array('class'=>'col-md-4'));
        $display .= html_writer::start_tag('div', array('class'=>'card'));
        $display .= html_writer::start_tag('div', array('class'=>'card-header'));
        $display .= $this->heading(get_string('issuancedetails', 'badges'), 3);
        $display .= html_writer::end_tag('div');
        $display .= html_writer::start_tag('div', array('class'=>'card-block'));
        if ($badge->can_expire()) {
            if ($badge->expiredate) {
                $display .= get_string('expiredate', 'badges', userdate($badge->expiredate));
            } else if ($badge->expireperiod) {
                if ($badge->expireperiod < 60) {
                    $display .= get_string('expireperiods', 'badges', round($badge->expireperiod, 2));
                } else if ($badge->expireperiod < 60 * 60) {
                    $display .= get_string('expireperiodm', 'badges', round($badge->expireperiod / 60, 2));
                } else if ($badge->expireperiod < 60 * 60 * 24) {
                    $display .= get_string('expireperiodh', 'badges', round($badge->expireperiod / 60 / 60, 2));
                } else {
                    $display .= get_string('expireperiod', 'badges', round($badge->expireperiod / 60 / 60 / 24, 2));
                }
            }
        } else {
            $display .= get_string('noexpiry', 'badges');
        }
        $display .= html_writer::end_tag('div');
        $display .= html_writer::end_tag('div');
        $display .= html_writer::end_tag('div');

        // Criteria details if any.
        $display .= html_writer::start_tag('div', array('class'=>'col-md-4'));
        $display .= html_writer::start_tag('div', array('class'=>'card'));
        $display .= html_writer::start_tag('div', array('class'=>'card-header'));
        $display .= $this->heading(get_string('bcriteria', 'badges'), 3);
        $display .= html_writer::end_tag('div');
        $display .= html_writer::start_tag('div', array('class'=>'card-block'));
        if ($badge->has_criteria()) {
            $display .= self::print_badge_criteria($badge);
        } else {
            $display .= get_string('nocriteria', 'badges');
            if (has_capability('moodle/badges:configurecriteria', $context)) {
                $display .= $this->output->single_button(
                    new moodle_url('/badges/criteria.php', array('id' => $badge->id)),
                    get_string('addcriteria', 'badges'), 'POST', array('class' => 'activatebadge'));
            }
        }
        $display .= html_writer::end_tag('div');
        $display .= html_writer::end_tag('div');
        $display .= html_writer::end_tag('div');

        // Awards details if any.
        if (has_capability('moodle/badges:viewawarded', $context)) {
        	$display .= html_writer::start_tag('div', array('class'=>'col-md-4'));
	        $display .= html_writer::start_tag('div', array('class'=>'card'));
	        $display .= html_writer::start_tag('div', array('class'=>'card-header'));
            $display .= $this->heading(get_string('awards', 'badges'), 3);
	        $display .= html_writer::end_tag('div');
	        $display .= html_writer::start_tag('div', array('class'=>'card-block'));
            if ($badge->has_awards()) {
                $url = new moodle_url('/badges/recipients.php', array('id' => $badge->id));
                $a = new stdClass();
                $a->link = $url->out();
                $a->count = count($badge->get_awards());
                $display .= get_string('numawards', 'badges', $a);
            } else {
                $display .= get_string('noawards', 'badges');
            }

            if (has_capability('moodle/badges:awardbadge', $context) &&
                $badge->has_manual_award_criteria() &&
                $badge->is_active()) {
                $display .= $this->output->single_button(
                        new moodle_url('/badges/award.php', array('id' => $badge->id)),
                        get_string('award', 'badges'), 'POST', array('class' => 'activatebadge'));
            }
	        $display .= html_writer::end_tag('div');
	        $display .= html_writer::end_tag('div');
	        $display .= html_writer::end_tag('div');
        }

        $display .= html_writer::end_tag('div');

        return html_writer::div($display, null, array('id' => 'badge-overview'));
    }

    public function print_badge_status_box(\badge $badge) {
        if (has_capability('moodle/badges:configurecriteria', $badge->get_context())) {

            if (!$badge->has_criteria()) {
                $criteriaurl = new moodle_url('/badges/criteria.php', array('id' => $badge->id));
                $status = get_string('nocriteria', 'badges');
                if ($this->page->url != $criteriaurl) {
                    $action = $this->output->single_button(
                        $criteriaurl,
                        get_string('addcriteria', 'badges'), 'POST', array('class' => 'activatebadge'));
                } else {
                    $action = '';
                }

                $message = $status . $action;
            } else {
                $status = get_string('statusmessage_' . $badge->status, 'badges');
                if ($badge->is_active()) {
                    $action = $this->output->single_button(new moodle_url('/badges/action.php',
                                array('id' => $badge->id, 'lock' => 1, 'sesskey' => sesskey(),
                                      'return' => $this->page->url->out_as_local_url(false))),
                            get_string('deactivate', 'badges'), 'POST', array('class' => 'activatebadge'));
                } else {
                    $action = $this->output->single_button(new moodle_url('/badges/action.php',
                                array('id' => $badge->id, 'activate' => 1, 'sesskey' => sesskey(),
                                      'return' => $this->page->url->out_as_local_url(false))),
                            get_string('activate', 'badges'), 'POST', array('class' => 'activatebadge'));
                }

                $message = $status . $this->output->help_icon('status', 'badges') . $action;

            }

            $style = $badge->is_active() ? 'generalbox statusbox active alert alert-success' : 'generalbox statusbox inactive alert alert-danger';
            return $this->output->box($message, $style);
        }

        return null;
    }

    protected function definition_list(array $items, array $attributes = array()) {
        $output = html_writer::start_tag('dl', $attributes);
        foreach ($items as $label => $value) {
        	$output .= html_writer::start_tag('div', array('class'=>'row'));
            $output .= html_writer::tag('dt', $label, array('class'=>'col-md-3'));
            $output .= html_writer::tag('dd', $value, array('class'=>'col-md-9'));
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('dl');
        return $output;
    }

    // Displays the user badges.
    protected function render_badge_user_collection(\badge_user_collection $badges) {
        global $CFG, $USER, $SITE;
        $backpack = $badges->backpack;
        $mybackpack = new moodle_url('/badges/mybackpack.php');

        $paging = new \paging_bar($badges->totalcount, $badges->page, $badges->perpage, $this->page->url, 'page');
        $htmlpagingbar = $this->render($paging);

        // Set backpack connection string.
        $backpackconnect = '';
        if (!empty($CFG->badges_allowexternalbackpack) && is_null($backpack)) {
            $backpackconnect = $this->output->box(get_string('localconnectto', 'badges', $mybackpack->out()), 'noticebox');
        }
        // Search box.
        $searchform = $this->output->box($this->helper_search_form($badges->search), 'col-md-12 boxwidthwide boxaligncenter');

        // Download all button.
        $downloadall = $this->output->single_button(
                    new moodle_url('/badges/mybadges.php', array('downloadall' => true, 'sesskey' => sesskey())),
                    get_string('downloadall'), 'POST', array('class' => 'activatebadge'));

        // Local badges.
        $localhtml = html_writer::start_tag('div', array('id' => 'issued-badge-table', 'class' => 'card mb-1'));
        $heading = get_string('localbadges', 'badges', format_string($SITE->fullname, true, array('context' => \context_system::instance())));
        $localhtml .= $this->output->heading_with_help($heading, 'localbadgesh', 'badges', '', '', 2, 'card-header');
        $localhtml .= html_writer::start_tag('div', array('class'=>'card-block'));
        if ($badges->badges) {
            $downloadbutton = $this->output->heading(get_string('badgesearned', 'badges', $badges->totalcount), 4, 'activatebadge');
            $downloadbutton .= $downloadall;

            $htmllist = $this->print_badges_list($badges->badges, $USER->id);
            $localhtml .= $backpackconnect . $downloadbutton . $searchform . $htmlpagingbar . $htmllist . $htmlpagingbar;
        } else {
            $localhtml .= $searchform . $this->output->notification(get_string('nobadges', 'badges'));
        }
        $localhtml .= html_writer::end_tag('div');
        $localhtml .= html_writer::end_tag('div');

        // External badges.
        $externalhtml = "";
        if (!empty($CFG->badges_allowexternalbackpack)) {
            $externalhtml .= html_writer::start_tag('div', array('class' => 'card'));
            $externalhtml .= $this->output->heading_with_help(get_string('externalbadges', 'badges'), 'externalbadges', 'badges', '', '', 2, array('class'=>'card-header'));
            $externalhtml .= html_writer::start_tag('div', array('class'=>'card-block'));
            if (!is_null($backpack)) {
                if ($backpack->totalcollections == 0) {
                    $externalhtml .= get_string('nobackpackcollections', 'badges', $backpack);
                } else {
                    if ($backpack->totalbadges == 0) {
                        $externalhtml .= get_string('nobackpackbadges', 'badges', $backpack);
                    } else {
                        $externalhtml .= get_string('backpackbadges', 'badges', $backpack);
                        $externalhtml .= '<br/><br/>' . $this->print_badges_list($backpack->badges, $USER->id, true, true);
                    }
                }
            } else {
                $externalhtml .= get_string('externalconnectto', 'badges', $mybackpack->out());
            }

            $externalhtml .= html_writer::end_tag('div');
            $externalhtml .= html_writer::end_tag('div');
        }

        return $localhtml . $externalhtml;
    }

    public function print_badges_list($badges, $userid, $profile = false, $external = false) {
        global $USER, $CFG;
        foreach ($badges as $badge) {
            if (!$external) {
                $context = ($badge->type == BADGE_TYPE_SITE) ? \context_system::instance() : \context_course::instance($badge->courseid);
                $bname = $badge->name;
                $imageurl = moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/', 'f1', false);
            } else {
                $bname = s($badge->assertion->badge->name);
                $imageurl = $badge->imageUrl;
            }

            $name = html_writer::tag('h6', $bname, array('class' => 'badge-name card-header'));

            $image = html_writer::empty_tag('img', array('src' => $imageurl, 'class' => 'badge-image'));
            if (!empty($badge->dateexpire) && $badge->dateexpire < time()) {
                $image .= $this->output->pix_icon('i/expired',
                        get_string('expireddate', 'badges', userdate($badge->dateexpire)),
                        'moodle',
                        array('class' => 'expireimage'));
                $name .= '(' . get_string('expired', 'badges') . ')';
            }

            $download = $status = $push = '';
            if (($userid == $USER->id) && !$profile) {
                $url = new moodle_url('mybadges.php', array('download' => $badge->id, 'hash' => $badge->uniquehash, 'sesskey' => sesskey()));
                $notexpiredbadge = (empty($badge->dateexpire) || $badge->dateexpire > time());
                $backpackexists = badges_user_has_backpack($USER->id);
                if (!empty($CFG->badges_allowexternalbackpack) && $notexpiredbadge && $backpackexists) {
                    $assertion = new moodle_url('/badges/assertion.php', array('b' => $badge->uniquehash));
                    $action = new component_action('click', 'addtobackpack', array('assertion' => $assertion->out(false)));
                    $push = $this->output->action_icon(new moodle_url('#'), new \pix_icon('t/backpack', get_string('addtobackpack', 'badges')), $action);
                }

                $download = $this->output->action_icon($url, new \pix_icon('t/download', get_string('download')));
                if ($badge->visible) {
                    $url = new moodle_url('mybadges.php', array('hide' => $badge->issuedid, 'sesskey' => sesskey()));
                    $status = $this->output->action_icon($url, new \pix_icon('t/hide', get_string('makeprivate', 'badges')));
                } else {
                    $url = new moodle_url('mybadges.php', array('show' => $badge->issuedid, 'sesskey' => sesskey()));
                    $status = $this->output->action_icon($url, new \pix_icon('t/show', get_string('makepublic', 'badges')));
                }
            }

            if (!$profile) {
                $url = new moodle_url('badge.php', array('hash' => $badge->uniquehash));
            } else {
                if (!$external) {
                    $url = new moodle_url('/badges/badge.php', array('hash' => $badge->uniquehash));
                } else {
                    $hash = hash('md5', $badge->hostedUrl);
                    $url = new moodle_url('/badges/external.php', array('hash' => $hash, 'user' => $userid));
                }
            }

            $link = html_writer::link($url, $image, array('title' => $bname));
            $output = $name . html_writer::tag('div', $link, array('class'=>'card-block'));
            if (!empty($push . $download . $status)) {
                $actions = html_writer::tag('div', $push . $download . $status, array('class' => 'badge-actions'));
                $output .= html_writer::tag('div', $actions , array('class'=>'card-footer'));
            }

            $output = html_writer::tag('div', $output, array('class'=>'card'));
            $items[] = $output;
        }

        return $this->alist($items, array('class' => 'badges row card-columns'));
    }

    public function alist(array $items, array $attributes = null, $tag = 'ul') {
        $output = html_writer::start_tag($tag, $attributes)."\n";
        foreach ($items as $item) {
            $output .= html_writer::tag('li', $item, array('class'=>'text-center'))."\n";
        }
        $output .= html_writer::end_tag($tag);
        return $output;
    }

}