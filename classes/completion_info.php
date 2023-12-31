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
 * Contains class completion_info used during the tracking of activity completion for users.
 *
 * Completion top-level options (admin setting enablecompletion)
 *
 * Copied from lib/completionlib.php to be overridden.
 * @package report_completionall
 * @copyright 2023 Mohammad Farouk
 * @copyright 1999 onwards Martin Dougiamas   {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_completionall;

/**
 * Most of the code and function are copied from the original class.
 *
 * This is just to avoid the private properties.
 *
 * @package report_completionall
 * @copyright 2023 Mohammad Farouk
 * @copyright 1999 onwards Martin Dougiamas   {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_info extends \completion_info {

    /** @var \stdClass Course object passed during construction */
    public $course;
    /** @var array */
    public $criteria;
    /**
     * Constructs with course details.
     *
     * When instantiating a new completion info object you must provide a course
     * object with at least id, and enablecompletion properties. Property
     * cacherev is needed if you check completion of the current user since
     * it is used for cache validation.
     *
     * @param \stdClass $course Moodle course object.
     */
    public function __construct($course) {
        $this->course = $course;
        $this->course_id = $course->id;
    }

    /**
     * Obtains a list of activities for which completion is enabled on the
     * course. The list is ordered by the section order of those activities.
     *
     * @return \cm_info[] Array from $cmid => $cm of all activities with completion enabled,
     *   empty array if none
     */
    public function get_activities() {
        $modinfo = get_fast_modinfo($this->course);
        $result = [];
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->completion != COMPLETION_TRACKING_NONE && !$cm->deletioninprogress) {
                $result[$cm->id] = $cm;
            }
        }
        return $result;
    }
    /**
     * Checks to see if the userid supplied has a tracked role in
     * this course
     *
     * @param int $userid User id
     * @return bool
     */
    public function is_tracked_user($userid) {
        switch(get_user_preferences('report_completion_all_enrolstat', 'all')) {
            case 'all':
            case 'suspended':
            case 'notcurrent':
            case 'notsuspended':
            case 'notactive':
                $onlyactive = false;
                break;
            case 'active':
                $onlyactive = true;
                break;
            default:
                $onlyactive = false;
        }
        return is_enrolled(\context_course::instance($this->course->id), $userid,
                                    'moodle/course:isincompletionreports', $onlyactive);
    }

    /**
     * Returns the number of users whose progress is tracked in this course.
     *
     * Optionally supply a search's where clause, or a group id.
     *
     * @param string $where Where clause sql (use 'u.whatever' for user table fields)
     * @param array $whereparams Where clause params
     * @param int $groupid Group id
     * @return int Number of tracked users
     */
    public function get_num_tracked_users($where = '', $whereparams = [], $groupid = 0) {
        global $DB;
        switch(get_user_preferences('report_completion_all_enrolstat', 'all')) {
            case 'all':
                $onlyactive = false;
                $onlysuspended = false;
                break;
            case 'suspended':
            case 'notcurrent':
            case 'notactive':
                $onlyactive = false;
                $onlysuspended = true;
                break;
            case 'notsuspended':
                $onlyactive = false;
                $onlysuspended = false;
                break;
            case 'active':
                $onlyactive = true;
                $onlysuspended = false;
                break;
            default:
                $onlyactive = false;
                $onlysuspended = false;
        }
        list($enrolledsql, $enrolledparams) = get_enrolled_sql(
                                                    \context_course::instance($this->course->id),
                                                    'moodle/course:isincompletionreports',
                                                    $groupid, $onlyactive, $onlysuspended
                                                );
        $sql  = 'SELECT COUNT(eu.id) FROM (' . $enrolledsql . ') eu JOIN {user} u ON u.id = eu.id';
        if ($where) {
            $sql .= " WHERE $where";
        }

        $params = array_merge($enrolledparams, $whereparams);
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Return array of users whose progress is tracked in this course.
     *
     * Optionally supply a search's where clause, group id, sorting, paging.
     *
     * @param string $where Where clause sql, referring to 'u.' fields (optional)
     * @param array $whereparams Where clause params (optional)
     * @param int $groupid Group ID to restrict to (optional)
     * @param string $sort Order by clause (optional)
     * @param int $limitfrom Result start (optional)
     * @param int $limitnum Result max size (optional)
     * @param \context $extracontext If set, includes extra user information fields
     *   as appropriate to display for current user in this context
     * @return array Array of user objects with user fields (including all identity fields)
     */
    public function get_tracked_users($where = '', $whereparams = [], $groupid = 0,
             $sort = '', $limitfrom = '', $limitnum = '', \context $extracontext = null) {
        switch(get_user_preferences('report_completion_all_enrolstat', 'all')) {
            case 'all':
                $includenotcurrent = true;
                $includesuspended = true;
                $includeactive = true;
                break;
            case 'suspended':
                $includenotcurrent = false;
                $includesuspended = true;
                $includeactive = false;
                break;
            case 'notcurrent':
                $includenotcurrent = true;
                $includesuspended = false;
                $includeactive = false;
                break;
            case 'notsuspended':
                $includenotcurrent = true;
                $includesuspended = false;
                $includeactive = true;
                break;
            case 'notactive':
                $includenotcurrent = true;
                $includesuspended = true;
                $includeactive = false;
                break;
            case 'active':
                $includenotcurrent = false;
                $includesuspended = false;
                $includeactive = true;
                break;
            default:
                $includenotcurrent = true;
                $includesuspended = true;
                $includeactive = true;
        }
        $onlyactive = $includeactive && !$includenotcurrent && !$includesuspended;
        $onlysuspended = !$includeactive;
        global $DB;

        $context = \context_course::instance($this->course->id);
        list($enrolledsql, $params) = get_enrolled_sql(
                $context,
                'moodle/course:isincompletionreports', $groupid, $onlyactive, $onlysuspended);

        $userfieldsapi = \core_user\fields::for_identity($extracontext)->with_name()->excluding('id', 'idnumber');
        $fieldssql = $userfieldsapi->get_sql('u', true);
        $sql = 'SELECT u.id, u.idnumber ' . $fieldssql->selects;
        $sql .= ' FROM (' . $enrolledsql . ') eu JOIN {user} u ON u.id = eu.id';

        if ($where) {
            $sql .= " AND $where";
            $params = array_merge($params, $whereparams);
        }

        $sql .= $fieldssql->joins;
        $params = array_merge($params, $fieldssql->params);

        if ($sort) {
            $sql .= " ORDER BY $sort";
        }
        $records = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $now = time();
        foreach ($records as $key => $user) {
            $sql = "SELECT ue.id, ue.status, ue.timestart, ue.timeend
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
            JOIN (SELECT DISTINCT userid, roleid
                                FROM {role_assignments}
                               WHERE contextid = :contextid
                             ) ra ON ra.userid = ue.userid AND ra.roleid = e.roleid
            WHERE ue.userid = :userid";
            $params = ['courseid' => $this->course->id, 'contextid' => $context->id, 'userid' => $user->id];
            $user->enrolments = $DB->get_records_sql($sql, $params);
            foreach ($user->enrolments as $ue) {
                if (
                    $ue->status == ENROL_USER_ACTIVE
                    && (empty($ue->timestart) || $ue->timestart < $now)
                    && (empty($ue->timeend) || $ue->timeend > $now)
                    ) {
                    if (!$includeactive) {
                        continue;
                    }
                    $user->enrolstatus = 'active'; // Active override all.
                    break;
                }
                if (!isset($user->enrolstatus) && $ue->status == ENROL_USER_SUSPENDED) {
                    if (!$includesuspended) {
                        continue;
                    }
                    $user->enrolstatus = 'suspended';
                    continue;
                }
                if (!$includenotcurrent) {
                    continue;
                }
                $user->enrolstatus = 'notcurrent'; // Not current override suspended.
            }
            if (!isset($user->enrolstatus)) {
                unset($records[$key]);
            }
        }

        return $records;
    }
    /**
     * Get course completion criteria
     *
     * @param int $criteriatype Specific criteria type to return (optional)
     */
    public function get_criteria($criteriatype = null) {

        // Fill cache if empty.
        if (!is_array($this->criteria)) {
            global $DB;

            $params = [
                'course' => $this->course->id,
            ];

            // Load criteria from database.
            $records = (array)$DB->get_records('course_completion_criteria', $params);

            // Order records so activities are in the same order as they appear on the course view page.
            if ($records) {
                $activitiesorder = array_keys(get_fast_modinfo($this->course)->get_cms());
                usort($records, function ($a, $b) use ($activitiesorder) {
                    $aidx = ($a->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) ?
                        array_search($a->moduleinstance, $activitiesorder) : false;
                    $bidx = ($b->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) ?
                        array_search($b->moduleinstance, $activitiesorder) : false;
                    if ($aidx === false || $bidx === false || $aidx == $bidx) {
                        return 0;
                    }
                    return ($aidx < $bidx) ? -1 : 1;
                });
            }

            // Build array of criteria objects.
            $this->criteria = [];
            foreach ($records as $record) {
                $this->criteria[$record->id] = \completion_criteria::factory((array)$record);
            }
        }

        // If after all criteria.
        if ($criteriatype === null) {
            return $this->criteria;
        }

        // If we are only after a specific criteria type.
        $criteria = [];
        foreach ($this->criteria as $criterion) {

            if ($criterion->criteriatype != $criteriatype) {
                continue;
            }

            $criteria[$criterion->id] = $criterion;
        }

        return $criteria;
    }

    /**
     * Obtains progress information across a course for all users on that course, or
     * for all users in a specific group. Intended for use when displaying progress.
     *
     * This includes only users who, in course context, have one of the roles for
     * which progress is tracked (the gradebookroles admin option) and are enrolled in course.
     *
     * Users are included (in the first array) even if they do not have
     * completion progress for any course-module.
     *
     * @param string $where Where clause sql (optional)
     * @param array $whereparams Where clause params (optional)
     * @param int $groupid Group ID or 0 (default)/false for all groups
     * @param string $sort Sort by
     * @param int $pagesize Number of users to actually return (optional)
     * @param int $start User to start at if paging (optional)
     * @param \context $extracontext If set, includes extra user information fields as appropriate
     *               to display for current user in this context
     * @return array[\stdClass] with ->total and ->start (same as $start) and ->users;
     *   an array of user objects (like mdl_user id, firstname, lastname)
     *   containing an additional ->progress array of coursemoduleid => completionstate
     */
    public function get_progress_all($where = '', $whereparams = [], $groupid = 0,
            $sort = '', $pagesize = '', $start = '', \context $extracontext = null) {
        global $CFG, $DB;

        // Get list of applicable users.
        $users = $this->get_tracked_users($where, $whereparams, $groupid, $sort,
                $start, $pagesize, $extracontext);

        // Get progress information for these users in groups of 1, 000 (if needed).
        // to avoid making the SQL IN too long.
        $results = [];
        $userids = [];
        foreach ($users as $user) {
            $userids[] = $user->id;
            $results[$user->id] = $user;
            $results[$user->id]->progress = [];
        }

        for ($i = 0; $i < count($userids); $i += 1000) {
            $blocksize = count($userids) - $i < 1000 ? count($userids) - $i : 1000;

            list($insql, $params) = $DB->get_in_or_equal(array_slice($userids, $i, $blocksize));
            array_splice($params, 0, 0, [$this->course->id]);
            $rs = $DB->get_recordset_sql("
                SELECT
                    cmc.*
                FROM
                    {course_modules} cm
                    INNER JOIN {course_modules_completion} cmc ON cm.id=cmc.coursemoduleid
                WHERE
                    cm.course=? AND cmc.userid $insql", $params);
            foreach ($rs as $progress) {
                $progress = (object)$progress;
                $results[$progress->userid]->progress[$progress->coursemoduleid] = $progress;
            }
            $rs->close();
        }

        return $results;
    }

    /**
     * Try to override the magic get method to obtain the course object.
     * @param string $name the name of the property.
     */
    public function __get($name) {
        if ($name == 'course' && is_null($this->course)) {
            $this->course = get_course($this->course_id);
            return $this->course;
        }
        return $this->$name;
    }
}
