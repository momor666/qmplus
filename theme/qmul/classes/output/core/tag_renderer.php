<?php
// This file is part of The Bootstrap Moodle theme
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

defined('MOODLE_INTERNAL') || die();

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_mars
 * @copyright  2016 Andrew Davidson, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/tag/classes/renderer.php');

class theme_qmul_core_tag_renderer extends core_tag_renderer {
    /**
     * Renders the tag index page
     *
     * @param core_tag_tag $tag
     * @param \core_tag\output\tagindex[] $entities
     * @param int $tagareaid
     * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
     *             are displayed on the page and the per-page limit may be bigger
     * @param int $fromctx context id where the link was displayed, may be used by callbacks
     *            to display items in the same context first
     * @param int $ctx context id where to search for records
     * @param bool $rec search in subcontexts as well
     * @param int $page 0-based number of page being displayed
     * @return string
     */
    public function tag_index_page($tag, $entities, $tagareaid, $exclusivemode, $fromctx, $ctx, $rec, $page) {
        global $CFG, $OUTPUT;
        $this->page->requires->js_call_amd('core/tag', 'initTagindexPage');

        $tagname = $tag->get_display_name();
        $systemcontext = context_system::instance();

        if ($tag->flag > 0 && has_capability('moodle/tag:manage', $systemcontext)) {
            $tagname = '<span class="flagged-tag">' . $tagname . '</span>';
        }

        $rv = '';
        $rv .= $this->output->heading($tagname, 2);

        $rv .= $this->tag_links($tag);

        if ($desciption = $tag->get_formatted_description()) {
            $rv .= $this->output->box($desciption, 'generalbox tag-description');
        }

        $relatedtagslimit = 10;
        $relatedtags = $tag->get_related_tags();
        $taglist = new \core_tag\output\taglist($relatedtags, get_string('relatedtags', 'tag'),
                'tag-relatedtags', $relatedtagslimit);
        $rv .= $OUTPUT->render_from_template('core_tag/taglist', $taglist->export_for_template($OUTPUT));

        // Display quick menu of the item types (if more than one item type found).
        $entitylinks = array();
        foreach ($entities as $entity) {
            if (!empty($entity->hascontent)) {
                $entitylinks[] = '<li><a href="#'.$entity->anchor.'">' .
                        core_tag_area::display_name($entity->component, $entity->itemtype) . '</a></li>';
            }
        }

        if (count($entitylinks) > 1) {
            $rv .= '<div class="tag-index-toc"><ul class="inline-list nav nav-pills">' . join('', $entitylinks) . '</ul></div>';
        } else if (!$entitylinks) {
            $rv .= '<div class="tag-noresults">' . get_string('noresultsfor', 'tag', $tagname) . '</div>';
        }

        // Display entities tagged with the tag.
        $content = '';
        foreach ($entities as $entity) {
            if (!empty($entity->hascontent)) {
                $content .= $this->output->render_from_template('core_tag/index', $entity->export_for_template($this->output));
            }
        }

        if ($exclusivemode) {
            $rv .= $content;
        } else if ($content) {
            $rv .= html_writer::div($content, 'tag-index-items');
        }

        // Display back link if we are browsing one tag area.
        if ($tagareaid) {
            $url = $tag->get_view_url(0, $fromctx, $ctx, $rec);
            $rv .= '<div class="tag-backtoallitems">' .
                    html_writer::link($url, get_string('backtoallitems', 'tag', $tag->get_display_name())) .
                    '</div>';
        }

        return $rv;
    }

    /**
     * Prints the tag search box
     *
     * @param string $query last search string
     * @param int $tagcollid last selected tag collection id
     * @return string
     */
    protected function search_form($query = '', $tagcollid = 0) {
        $searchurl = new moodle_url('/tag/search.php');
        $output = '<form action="' . $searchurl . '">';
        $output .= '<label class="accesshide" for="searchform_query">' . get_string('searchtags', 'tag') . '</label>';
        $output .= html_writer::start_tag('div', array('class'=>'input-group'));
        $output .= '<input id="searchform_query" class="form-control" name="query" type="text" size="40" value="' . s($query) . '" />';
        $tagcolls = core_tag_collection::get_collections_menu(false, true, get_string('inalltagcoll', 'tag'));
        if (count($tagcolls) > 1) {
            $output .= '<label class="accesshide" for="searchform_tc">' . get_string('selectcoll', 'tag') . '</label>';
            $output .= html_writer::select($tagcolls, 'tc', $tagcollid, null, array('id' => 'searchform_tc'));
        }
        $output .= html_writer::start_tag('div', array('class'=>'input-group-btn'));
        $output .= '<input name="go" class="btn btn-info" type="submit" size="40" value="' . s(get_string('search', 'tag')) . '" />';
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= '</form>';

        return $output;
    }

    /**
     * Prints a box that contains the management links of a tag
     *
     * @param core_tag_tag $tag
     * @return string
     */
    protected function tag_links($tag) {
        if ($links = $tag->get_links()) {
            foreach ($links as $key => $value) {
                if (strpos($value, 'addtomyinterests') > -1) {
                    $value = str_replace('class="addtomyinterests"', 'class="btn btn-success addtomyinterests"', $value);
                    $split = explode('>', $value);
                    $split[1] = "<i class='glyphicon glyphicon-plus text-white'></i>".$split[1];
                    $value = implode('>', $split);
                }
                if (strpos($value, 'removefrommyinterests') > -1) {
                    $value = str_replace('class="removefrommyinterests"', 'class="btn btn-danger removefrommyinterests"', $value);
                    $split = explode('>', $value);
                    $split[1] = "<i class='glyphicon glyphicon-remove text-white'></i>".$split[1];
                    $value = implode('>', $split);
                }
                if (strpos($value, 'flagasinappropriate') > -1) {
                    $value = str_replace('class="flagasinappropriate"', 'class="btn btn-danger flagasinappropriate"', $value);
                    $split = explode('>', $value);
                    $split[1] = "<i class='glyphicon glyphicon-flag text-white'></i>".$split[1];
                    $value = implode('>', $split);
                }
                if (strpos($value, 'edittag') > -1) {
                    $value = str_replace('class="edittag"', 'class="btn btn-warning edittag"', $value);
                    $split = explode('>', $value);
                    $split[1] = "<i class='glyphicon glyphicon-edit text-white'></i>".$split[1];
                    $value = implode('>', $split);
                }
                $links[$key] = $value;
            }
            $content = '<div class="btn-group btn-group-sm mb-1">' . implode('', $links) . '</div>';
            return html_writer::div($content, 'tag-management-box');
        }
        return '';
    }

}