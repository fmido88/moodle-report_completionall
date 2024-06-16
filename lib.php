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
 * lib.
 *
 * most of the code copied from report_completion and report_progress
 * @package    report_completionall
 * @copyright  2023 Mohammad Farouk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_completionall_extend_navigation_course($navigation, $course, $context) {
    global $CFG;

    require_once($CFG->libdir.'/completionlib.php');

    if (has_capability('report/completionall:view', $context)) {
        $completion = new report_completionall\completion_info($course);
        if ($completion->is_enabled() && $completion->has_criteria()) {
            $url = new moodle_url('/report/completionall/index.php', ['course' => $course->id]);
            $navigation->add(get_string('pluginname', 'report_completionall'), $url,
                            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        }

        $showonnavigation = has_capability('report/progress:view', $context);
        $group = groups_get_course_group($course, true); // Supposed to verify group.
        if ($group === 0 && $course->groupmode == SEPARATEGROUPS) {
            $showonnavigation = ($showonnavigation && has_capability('moodle/site:accessallgroups', $context));
        }

        $showonnavigation = ($showonnavigation && $completion->is_enabled() && $completion->has_activities());
        if ($showonnavigation) {
            $url = new moodle_url('/report/completionall/progress.php', ['course' => $course->id]);
            $navigation->add(get_string('progress_report', 'report_completionall'), $url,
                            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        }
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function report_completionall_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $array = [
        '*'                       => get_string('page-x', 'pagetype'),
        'report-*'                => get_string('page-report-x', 'pagetype'),
        'report-completionall-*'     => get_string('page-report-completionall-x',  'report_completionall'),
        'report-completionall-index' => get_string('page-report-completionall-index',  'report_completionall'),
        'report-completionall-user'  => get_string('page-report-completionall-user',  'report_completionall'),
        'report-completionall-progress'   => get_string('page-report-progress-index',  'report_progress'),
    ];
    return $array;
}
