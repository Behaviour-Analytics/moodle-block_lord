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
 * This file contains the scheduled task.
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_lord\task;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/blocks/lord/locallib.php");

/**
 * Parses text content from modules and compares the module similarity.
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compare_learning_objects extends \core\task\scheduled_task {

    /**
     * Debugging flag.
     * @var boolean $dodebug
     */
    private static $dodebug = true;

    /**
     * Function to print out a debugging message or other variable.
     *
     * @param object $msg The string or object to print
     */
    private static function dbug($msg) {
        if (self::$dodebug) {
            if (is_string($msg)) {
                echo $msg, "<br>";
            } else {
                var_dump($msg);
            }
        }
    }

    /**
     * Number of words to restrict a sentence length to.
     * @var int $maxlength
     */
    private $maxlength;

    /**
     * Number of sentences to restrict a paragraph to.
     * @var int $maxcentence
     */
    private $maxsentence;

    /**
     * Number of paragraphs to restrict content to.
     * @var int $maxparagraph
     */
    private $maxparagraph;

    /**
     * A dictionary of words and their status in Wordnet.
     * @var int $dictionary
     */
    private $dictionary;

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('compare', 'block_lord');
    }

    /**
     * Execute the task. Called automatically and uses other functions to
     * accomplish the necessary tasks.
     */
    public function execute() {
        global $DB;

        // Do not compare modules unless set up to do so.
        if (!get_config('block_lord', 'start')) {
            self::dbug('Learning object relation discovery process is turned off globally. Nothing to do.');
            return;
        }

        // Build the Wordnet dictionary from DB records.
        $this->dictionary = [];
        $words = $DB->get_records('block_lord_dictionary');

        if (count($words) == 0) {
            $this->add_stop_words_to_dictionary();

        } else {
            foreach ($words as $word) {
                $this->dictionary[$word->word] = $word->status;
            }
        }

        // Process each course where this block is installed.
        $courses = $this->get_courses();
        foreach ($courses as $course) {

            self::dbug('COURSE: '.$course->id);

            // Get custom parameters or set defaults.
            $record = $DB->get_record('block_lord_max_words', ['courseid' => $course->id]);

            if ($record) {
                if ($record->dodiscovery == 0) {
                    self::dbug('Learning object relation discovery process is turned off for this course.');
                    continue;
                }
                $this->maxlength = $record->maxlength;
                $this->maxsentence = $record->maxsentence;
                $this->maxparagraph = $record->maxparas;
            } else {
                self::dbug('Learning object relation discovery process is turned off for this course.');
                continue;
            }

            // Get learning activity module records.
            $records = $DB->get_records('block_lord_modules', ['courseid' => $course->id]);

            // If this is the first time seeing this course, initialize DB tables.
            if (count($records) == 0) {
                self::dbug('INITIALIZING DB TABLES: '.$course->id);
                $data = $this->get_course_data($course);
                $this->initialize_tables($course, $data);
            }

            // Get learning activity comparison records.
            $records = $DB->get_records('block_lord_comparisons', ['courseid' => $course->id]);

            // If the table has been reset, then reinitialize.
            if (count($records) == 0) {
                self::dbug('INITIALIZING COMPARISON TABLE: '.$course->id);
                $this->initialize_comparison_table($course);
            }

            // Compare a single set of modules that have not been compared yet.
            self::dbug('COMPARING RECORDS...');
            unset($record);
            $compared = 0;
            $possible = count($records);

            foreach ($records as $record) {

                if (!$record->value) {
                    self::dbug($record->module1.' '.$record->module2.' '.$record->value);
                    $this->get_similarity($record);
                    break;
                }
                $compared++;
            }

            // All modules have been compared, but if max setences or paragraphs have
            // changed, then there may be more comparisons for a given module pair.
            if ($compared > 0 && $compared == $possible) {
                $this->recheck_comparisons($course->id);
            }

            $this->check_for_new_modules($course);
        }
    }

    /**
     * Called to check for new modules added to the course.
     *
     * @param stdClass $course The course object.
     */
    private function check_for_new_modules(&$course) {
        global $DB;

        // Get the current stored modules.
        $modules = $DB->get_records('block_lord_modules', ['courseid' => $course->id]);
        $current = [];
        foreach ($modules as $module) {
            $current[$module->module] = $module->module;
        }

        // Get course module information and process.
        $modinfo = get_fast_modinfo($course);

        $new = [];
        $all = [];
        foreach ($modinfo->sections as $section) {
            foreach ($section as $cmid) {

                $cm = $modinfo->cms[$cmid];

                // Only want clickable modules.
                if (!$cm->has_view() || !$cm->uservisible) {
                    continue;
                }

                // Any modules not in current dataset are new.
                if (!isset($current[$cmid])) {
                    $new[$cmid] = $cm;
                }

                $all[$cmid] = $cmid;
            }
        }

        // Remove current course modules from those stored in DB.
        foreach ($all as $al) {
            unset($current[$al]);
        }

        // ... Anything left has been removed from the course and
        // can be deleted from the DB as well.
        $params = ['courseid' => $course->id];
        foreach ($current as $cur) {
            self::dbug('Deleting records for module '.$cur);

            $params['module'] = $cur;
            $DB->delete_records('block_lord_modules', $params);
            $DB->delete_records('block_lord_paragraphs', $params);

            unset($params['module']);

            $params['m1'] = $cur;
            $params['m2'] = $cur;
            $DB->delete_records_select('block_lord_comparisons',
                'courseid = :courseid AND (module1 = :m1 OR module2 = :m2)', $params);

            unset($params['m1']);
            unset($params['m2']);
        }

        $paragraphs = [];
        $contents = [];
        $comparisons = [];
        reset($modules);

        // Get the data for the new modules and add to DB tables.
        foreach ($new as $id => $mod) {
            $data = $this->get_module_data($mod, $course);

            // Gather the module paragraph contents.
            if (isset($data['paragraphs'])) {
                $n = 0;

                foreach ($data['paragraphs'] as $para) {
                    $paragraphs[] = (object) array(
                        'courseid' => $course->id,
                        'module'   => $id,
                        'paragraph' => $n++,
                        'content' => $para
                    );
                }
            }

            // ... And the module's name and intro.
            $contents[] = (object) array(
                'courseid' => $course->id,
                'module'   => $id,
                'name'     => $data['name'],
                'intro'    => $data['intro']
            );

            // Determine which modules need to be compared.
            unset($module);
            foreach ($modules as $module) {
                $k1 = $module->module;
                $k2 = $id;

                // Keep modules ordered, easier to avoid duplicates.
                if ($k2 < $k1) {
                    $temp = $k1;
                    $k1 = $k2;
                    $k2 = $temp;
                }

                self::dbug('Adding new comparisons '.$k1.' '.$k2);

                // The comparison data, less the actual comparison value.
                $comparisons[] = (object) array(
                    'courseid' => $course->id,
                    'module1'  => $k1,
                    'module2'  => $k2
                );
            }
        }

        // Populate the tables.
        if (count($paragraphs) > 0) {
            $DB->insert_records('block_lord_paragraphs', $paragraphs);
        }
        if (count($contents) > 0) {
            $DB->insert_records('block_lord_modules', $contents);
        }
        if (count($comparisons) > 0) {
            $DB->insert_records('block_lord_comparisons', $comparisons);
        }
    }

    /**
     * Called when all modules have been compared. If the maximum paragraph or
     * sentence values have changed since then, some more comarpisons may need
     * to be made.
     *
     * @param int $courseid The course id.
     */
    private function recheck_comparisons($courseid) {
        global $DB;

        // Build an array of all previous comparisons.
        $records = $DB->get_records('block_lord_comparisons', ['courseid' => $courseid], 'module1, module2, compared');
        $compared = [];

        foreach ($records as $record) {

            if (!isset($compared[$record->module1])) {
                $compared[$record->module1] = [];
            }
            if (!isset($compared[$record->module1][$record->module2])) {
                $compared[$record->module1][$record->module2] = [];
            }

            $compared[$record->module1][$record->module2][$record->compared] = 1;
        }

        // Recheck the comparisons.
        $params = array(
            'courseid' => $courseid,
            'compared' => 'name'
        );
        $records = $DB->get_records('block_lord_comparisons', $params, 'module1');

        $n = 0;
        foreach ($records as $record) {
            $didsome = $this->recheck_similarity($record, $compared);

            if ($didsome) {
                $n++;
            }
            if ($n > 2) {
                self::dbug('Did 3 rechecks, stopping for now.');
                break;
            }
        }
    }

    /**
     * Called to recheck the similarity values between modules.
     *
     * @param stdClass $record The comparison table record for the modules.
     * @param array $compared An array of module sentence values that have been compared.
     * @return boolean
     */
    private function recheck_similarity(&$record, &$compared) {
        global $DB;

        // Get the key text from the first module.
        $params = array(
            'courseid' => $record->courseid,
            'module'   => $record->module1
        );
        $key = $DB->get_record('block_lord_modules', $params);
        $kparas = $DB->get_records('block_lord_paragraphs', $params);

        $keyparas = [];
        foreach ($kparas as $para) {
            $keyparas[] = $para->content;
        }
        unset($para);

        // Get the target text from the second module.
        $params['module'] = $record->module2;
        $target = $DB->get_record('block_lord_modules', $params);
        $tparas = $DB->get_records('block_lord_paragraphs', $params);

        $targetparas = [];
        foreach ($tparas as $para) {
            $targetparas[] = $para->content;
        }

        // Compare the introductions of the modules.
        $keyintrosent = block_lord_split_paragraph($key->intro);
        $targetintrosent = block_lord_split_paragraph($target->intro);
        $params = [];
        $didcomparison = false;

        for ($ks = 0; $ks < $this->maxsentence; $ks++) {
            if (isset($keyintrosent[$ks])) {
                $keysent = $this->clean_sentence($keyintrosent[$ks]);
            } else {
                break;
            }

            for ($ts = 0; $ts < $this->maxsentence; $ts++) {
                if (isset($targetintrosent[$ts])) {
                    $targetsent = $this->clean_sentence($targetintrosent[$ts]);
                } else {
                    break;
                }

                // Only check the similarity if it has not been checked already.
                if (!isset($compared[$record->module1][$record->module2]['intro'.$ks.'x'.$ts])) {
                    self::dbug('Comparing intros: '.$ks.' x '.$ts);

                    list($similarity, $matrix) = $this->call_bridge($keysent, $targetsent);

                    $params[] = (object) array(
                        'courseid' => $record->courseid,
                        'module1'  => $record->module1,
                        'module2'  => $record->module2,
                        'compared' => 'intro'.$ks.'x'.$ts,
                        'value'    => $similarity,
                        'matrix'   => $matrix
                    );

                    $didcomparison = true;
                }
            }
        }
        if (count($params) > 0) {
            $DB->insert_records('block_lord_comparisons', $params);
        }

        // Compare the paragraphs and sentences.
        $params = [];
        for ($p0 = 0; $p0 < $this->maxparagraph; $p0++) {
            if (isset($keyparas[$p0])) {
                $keyss = block_lord_split_paragraph($keyparas[$p0]);
            } else {
                break;
            }

            for ($p1 = 0; $p1 < $this->maxparagraph; $p1++) {
                if (isset($targetparas[$p1])) {
                    $targetss = block_lord_split_paragraph($targetparas[$p1]);
                } else {
                    break;
                }

                for ($s0 = 0; $s0 < $this->maxsentence; $s0++) {
                    if (isset($keyss[$s0])) {
                        $sentence1 = $this->clean_sentence($keyss[$s0]);
                    } else {
                        break;
                    }

                    for ($s1 = 0; $s1 < $this->maxsentence; $s1++) {
                        if (isset($targetss[$s1])) {
                            $sentence2 = $this->clean_sentence($targetss[$s1]);
                        } else {
                            break;
                        }

                        // Only check the similarity if it has not been checked already.
                        if (!isset($compared[$record->module1][$record->module2]['P'.$p0.'S'.$s0.'P'.$p1.'S'.$s1])) {
                            self::dbug('Comparing: P'.$p0.' S'.$s0.' x P'.$p1.' S'.$s1);

                            list($similarity, $matrix) = $this->call_bridge($sentence1, $sentence2);

                            $params[] = (object) array(
                                'courseid' => $record->courseid,
                                'module1'  => $record->module1,
                                'module2'  => $record->module2,
                                'compared' => 'P'.$p0.'S'.$s0.'P'.$p1.'S'.$s1,
                                'value'    => $similarity,
                                'matrix'   => $matrix
                            );

                            $didcomparison = true;
                        }
                    }
                }
            }
        }
        if (count($params) > 0) {
            $DB->insert_records('block_lord_comparisons', $params);
        }

        return $didcomparison;
    }

    /**
     * Called to get the courses where this block is installed.
     *
     * @return stdClass
     */
    private function get_courses() {
        global $DB;

        $sql = "SELECT c.id FROM {course} c
                  JOIN {context} ctx
                    ON c.id = ctx.instanceid
                   AND ctx.contextlevel = :contextcourse
                 WHERE ctx.id in (SELECT distinct parentcontextid FROM {block_instances}
                                   WHERE blockname = 'lord')
              ORDER BY c.sortorder";

        $records = $DB->get_records_sql($sql, array('contextcourse' => CONTEXT_COURSE));
        return $records;
    }

    /**
     * Called to get the data for a single course.
     *
     * @param stdClass $course The course object from the DB.
     * @return array
     */
    private function get_course_data(&$course) {

        // Get course module information and process.
        $modinfo = get_fast_modinfo($course);

        $data = [];
        foreach ($modinfo->sections as $section) {
            foreach ($section as $cmid) {

                $cm = $modinfo->cms[$cmid];

                // Only want clickable modules.
                if (!$cm->has_view() || !$cm->uservisible) {
                    continue;
                }

                $data[$cmid] = $this->get_module_data($cm, $course);
            }
            sleep(0.1); // Seconds. Play nice and don't bombard any servers.
        }

        return $data;
    }

    /**
     * Called to get the data for the given course module.
     *
     * @param stdClass $cm The course module.
     * @param stdClass $course The course objecct.
     * @return array
     */
    private function get_module_data(&$cm, &$course) {

        // Get data for a given module type.
        $data = [];
        switch ($cm->modname) {
            case 'page':
                $data = $this->do_page($cm);
                break;
            case 'url':
                $data = $this->do_url($cm, $course);
                break;
            case 'resource':
                $data = $this->do_file($cm);
                break;
            case 'folder':
                $data = $this->do_folder($cm);
                break;
            case 'forum':
                $data = $this->do_forum($cm, $course);
                break;
            case 'chat':
                $data = $this->do_chat($cm, $course);
                break;
            case 'choice':
                $data = $this->do_choice($cm);
                break;
            case 'data':
                $data = $this->do_database($cm);
                break;
            case 'feedback':
                $data = $this->do_feedback($cm);
                break;
            case 'glossary':
                $data = $this->do_glossary($cm);
                break;
            case 'lesson':
                $data = $this->do_lesson($cm);
                break;
            case 'survey':
                $data = $this->do_survey($cm);
                break;
            case 'wiki':
                $data = $this->do_wiki($cm);
                break;
            case 'workshop':
                $data = $this->do_workshop($cm);
                break;
            case 'label':
                $data = $this->do_label($cm);
                break;
            case 'book':
                $data = $this->do_book($cm);
                break;
            case 'quiz':
                $data = $this->do_quiz($cm);
                break;
            case 'assign':
                $data = $this->do_assignment($cm);
                break;
            case 'lti':
                $data = $this->do_external_tool($cm);
                break;
            case 'scorm':
                $data = $this->do_scorm_package($cm);
                break;
            case 'imscp':
                $data = $this->do_ims_package($cm);
                break;
            default:
                $data = $this->do_unknown($cm);
                break;
        }
        return $data;
    }

    /**
     * Called to initialize the module and comparison DB tables.
     *
     * @param stdClass $course The course object.
     * @param array $modules The course module data.
     */
    private function initialize_tables(&$course, &$modules) {
        global $DB;

        $contents = [];
        $comparisons = [];
        $paragraphs = [];
        $seen = [];

        foreach ($modules as $key1 => $mod1) {

            // Gather the module paragraph contents.
            if (isset($mod1['paragraphs'])) {
                $n = 0;

                foreach ($mod1['paragraphs'] as $para) {
                    $paragraphs[] = (object) array(
                        'courseid' => $course->id,
                        'module'   => $key1,
                        'paragraph' => $n++,
                        'content' => $para
                    );
                }
            }

            // ... And the module's name and intro.
            $contents[] = (object) array(
                'courseid' => $course->id,
                'module'   => $key1,
                'name'     => $mod1['name'],
                'intro'    => $mod1['intro']
            );

            // Determine which modules need to be compared.
            foreach ($modules as $key2 => $mod2) {

                $k1 = $key1;
                $k2 = $key2;

                // Don't compare a module to itself.
                if ($k1 == $k2) {
                    continue;
                }
                // Keep modules ordered, easier to avoid duplicates.
                if ($k2 < $k1) {
                    $temp = $k1;
                    $k1 = $k2;
                    $k2 = $temp;
                }

                // Ensure this module combination has not been compared already.
                $key = $k1.'_'.$k2;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = 1;

                // The comparison data, less the actual comparison value.
                $comparisons[] = (object) array(
                    'courseid' => $course->id,
                    'module1'  => $k1,
                    'module2'  => $k2
                );
            }
            unset($key2);
            unset($mod2);
        }

        // Populate the tables.
        if (count($paragraphs) > 0) {
            $DB->insert_records('block_lord_paragraphs', $paragraphs);
        }
        if (count($contents) > 0) {
            $DB->insert_records('block_lord_modules', $contents);
        }
        if (count($comparisons) > 0) {
            $DB->insert_records('block_lord_comparisons', $comparisons);
        }
    }

    /**
     * Called to populate the comparison table.
     *
     * @param stdClass $course The course object from the DB.
     * @return array
     */
    private function initialize_comparison_table(&$course) {
        global $DB;

        // Get course module information and process.
        $modinfo = get_fast_modinfo($course);

        $modules = [];
        foreach ($modinfo->sections as $section) {
            foreach ($section as $cmid) {

                $cm = $modinfo->cms[$cmid];

                // Only want clickable modules.
                if (!$cm->has_view() || !$cm->uservisible) {
                    continue;
                }

                $modules[$cmid] = 1;
            }
        }

        $comparisons = [];
        $seen = [];

        // Determine which modules need to be compared.
        foreach ($modules as $key1 => $mod1) {
            foreach ($modules as $key2 => $mod2) {

                $k1 = $key1;
                $k2 = $key2;

                // Don't compare a module to itself.
                if ($k1 == $k2) {
                    continue;
                }
                // Keep modules ordered, easier to avoid duplicates.
                if ($k2 < $k1) {
                    $temp = $k1;
                    $k1 = $k2;
                    $k2 = $temp;
                }

                // Ensure this module combination has not been compared already.
                $key = $k1.'_'.$k2;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = 1;

                // The comparison data, less the actual comparison value.
                $comparisons[] = (object) array(
                    'courseid' => $course->id,
                    'module1'  => $k1,
                    'module2'  => $k2
                );
            }
        }

        // Populate the table.
        if (count($comparisons) > 0) {
            $DB->insert_records('block_lord_comparisons', $comparisons);
        }
    }

    /**
     * Called to get the similarity value between modules.
     *
     * @param stdClass $record The comparison table record for the modules.
     */
    private function get_similarity(&$record) {
        global $DB;

        // Get the key text from the first module.
        $params = array(
            'courseid' => $record->courseid,
            'module'   => $record->module1
        );
        $key = $DB->get_record('block_lord_modules', $params);
        $kparas = $DB->get_records('block_lord_paragraphs', $params);

        $keyparas = [];
        foreach ($kparas as $para) {
            $keyparas[] = $para->content;
        }
        unset($para);

        // Get the target text from the second module.
        $params['module'] = $record->module2;
        $target = $DB->get_record('block_lord_modules', $params);
        $tparas = $DB->get_records('block_lord_paragraphs', $params);

        $targetparas = [];
        foreach ($tparas as $para) {
            $targetparas[] = $para->content;
        }

        // Compare the names of the modules.
        $keyname = $this->clean_sentence($key->name);
        $targetname = $this->clean_sentence($target->name);

        list($similarity, $matrix) = $this->call_bridge($keyname, $targetname);

        $params = array(
            'id'       => $record->id,
            'courseid' => $record->courseid,
            'module1'  => $record->module1,
            'module2'  => $record->module2,
            'compared' => 'name',
            'value'    => $similarity,
            'matrix'   => $matrix
        );
        $DB->update_record('block_lord_comparisons', $params);

        // Compare the introductions of the modules.
        $keyintrosent = block_lord_split_paragraph($key->intro);
        $targetintrosent = block_lord_split_paragraph($target->intro);
        $params = [];

        for ($ks = 0; $ks < $this->maxsentence; $ks++) {
            if (isset($keyintrosent[$ks])) {
                $keysent = $this->clean_sentence($keyintrosent[$ks]);
            } else {
                break;
            }

            for ($ts = 0; $ts < $this->maxsentence; $ts++) {
                if (isset($targetintrosent[$ts])) {
                    $targetsent = $this->clean_sentence($targetintrosent[$ts]);
                } else {
                    break;
                }
                self::dbug('Comparing intros: '.$ks.' x '.$ts);

                list($similarity, $matrix) = $this->call_bridge($keysent, $targetsent);

                $params[] = (object) array(
                    'courseid' => $record->courseid,
                    'module1'  => $record->module1,
                    'module2'  => $record->module2,
                    'compared' => 'intro'.$ks.'x'.$ts,
                    'value'    => $similarity,
                    'matrix'   => $matrix
                );
            }
        }
        if (count($params) > 0) {
            $DB->insert_records('block_lord_comparisons', $params);
        }

        // Compare the paragraphs and sentences.
        $params = [];
        for ($p1 = 0; $p1 < $this->maxparagraph; $p1++) {
            if (isset($keyparas[$p1])) {
                $keyss = block_lord_split_paragraph($keyparas[$p1]);
            } else {
                break;
            }

            for ($p2 = 0; $p2 < $this->maxparagraph; $p2++) {
                if (isset($targetparas[$p2])) {
                    $targetss = block_lord_split_paragraph($targetparas[$p2]);
                } else {
                    break;
                }

                for ($s1 = 0; $s1 < $this->maxsentence; $s1++) {
                    if (isset($keyss[$s1])) {
                        $sentence1 = $this->clean_sentence($keyss[$s1]);
                    } else {
                        break;
                    }

                    for ($s2 = 0; $s2 < $this->maxsentence; $s2++) {
                        if (isset($targetss[$s2])) {
                            $sentence2 = $this->clean_sentence($targetss[$s2]);
                        } else {
                            break;
                        }
                        self::dbug('Comparing: P'.$p1.' S'.$s1.' x P'.$p2.' S'.$s2);
                        self::dbug($sentence1);
                        self::dbug($sentence2);

                        list($similarity, $matrix) = $this->call_bridge($sentence1, $sentence2);

                        $params[] = (object) array(
                            'courseid' => $record->courseid,
                            'module1'  => $record->module1,
                            'module2'  => $record->module2,
                            'compared' => 'P'.$p1.'S'.$s1.'P'.$p2.'S'.$s2,
                            'value'    => $similarity,
                            'matrix'   => $matrix
                        );
                    }
                }
            }
        }
        if (count($params) > 0) {
            $DB->insert_records('block_lord_comparisons', $params);
        }
    }

    /**
     * Function to call the bridge and get a similarity.
     *
     * @param string $key The key text.
     * @param string $target The target text.
     * @return array
     */
    private function call_bridge(&$key, &$target) {

        // Sanity check.
        if (strlen($key) == 0 || strlen($target) == 0) {
            return [0.0, ''];
        }

        // The outgoing JSON data.
        $json = array(
            'value'  => 1,
            'key'    => $this->restrict_sentence_length($key),
            'target' => $this->restrict_sentence_length($target)
        );
        $json = json_encode($json);

        // Create stream context.
        $context = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/json',
                'content' => $json,
                'timeout' => 125 // Bridge times out at 120.
            ));

        self::dbug('SENT TO BRIDGE SERVICE:');
        self::dbug($json);
        self::dbug('RECEIVED FROM BRIDGE SERVICE:');

        // Call the bridge service and parse similarity from the result.
        $context = stream_context_create($context);
        $contents = file_get_contents('https://ws-nlp.vipresearch.ca/bridge/', false, $context);

        // Handle connection timeout.
        if ($contents === false) {
            self::dbug('ERROR: Connection timed out.');
            return [0.0, ''];

        } else {

            self::dbug($contents);
            $decoded = json_decode($contents);

            // Handle successful calculation.
            if (isset($decoded->similarity)) {
                self::dbug("SIMILARITY: " . $decoded->similarity);
                return [$decoded->similarity, json_encode($decoded->matrix)];

            } else {
                self::dbug('ERROR: Similarity calculation timed out.');
                return [0.0, ''];
            }
        }
    }

    /**
     * Function to get the module name and introduction from the DB.
     *
     * @param stdClass $record The module DB record.
     * @return array
     */
    private function get_name_and_intro(&$record) {

        $name = strip_tags($record->name);
        $intro = strip_tags($record->intro);

        return array('name' => $name, 'intro' => $intro);
    }

    /**
     * Function to do an unknown type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_unknown(&$cm) {
        global $DB;

        $unknown = $DB->get_record($cm->modname, array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($unknown);

        return $intro;
    }

    /**
     * Function to do a page type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_page(&$cm) {
        global $DB;

        $page = $DB->get_record('page', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($page);

        $html = $this->parse_html($page->content, false);

        return array_merge($intro, $html);
    }

    /**
     * Function to do a url type learning activity.
     *
     * @param stdClass $cm The course module.
     * @param stdClass $course The course object.
     * @return array
     */
    private function do_url(&$cm, &$course) {
        global $DB, $CFG;

        $url = $DB->get_record('url', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($url);

        // Get filename and path from URL.
        $urlparts = parse_url($url->externalurl);
        $fname = explode('/', $urlparts['path']);
        $path = '/';

        for ($i = 4; $i < count($fname) - 1; $i++) {
            $path .= $fname[$i] . '/';
        }
        $fname = $fname[count($fname) - 1];

        $fs = get_file_storage();
        $str = [];

        // Convert the local URL file to text.
        if (strpos($url->externalurl, $CFG->wwwroot) !== false) {
            $context = \context_course::instance($course->id);
            $file = $fs->get_file($context->id, 'course', 'legacy', 0, $path, $fname);
            $str[] = $this->convert_file($file);

        } else {
            // Convert the remote URL file to text.
            $contents = file_get_contents($url->externalurl);
            $context = \context_module::instance($cm->id);

            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'mod_url',
                'filearea'  => 'lord',
                'itemid'    => 0,
                'filepath'  => '/',
                'filename'  => $fname == '' ? 'file.ext' : $fname);

            if ($fs->file_exists($context->id, 'mod_url', 'lord', 0, '/', $fileinfo['filename'])) {
                $file = $fs->get_file($context->id, 'mod_url', 'lord', 0, '/', $fileinfo['filename']);

            } else {
                $file = $fs->create_file_from_string($fileinfo, $contents);
            }

            $str[] = $this->convert_file($file);
            $file->delete();
        }

        $urldata = array('url' => $url->externalurl, 'filename' => $fname, 'filepath' => $path);

        return array_merge($intro, $urldata, [ 'paragraphs' => $str ]);
    }

    /**
     * Function to do a file type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_file(&$cm) {
        global $DB;

        $resource = $DB->get_record('resource', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($resource);

        // Retrieve the file from storage and convert to text.
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        if (count($files) < 1) {
            return $intro;

        } else {
            $file = reset($files);
            unset($files);
        }

        $file = $fs->get_file($context->id, 'mod_resource', 'content', 0, $file->get_filepath(), $file->get_filename());
        $para = [];
        $para[] = $this->convert_file($file);

        $filedata = array('filename' => $file->get_filename(), 'filepath' => $file->get_filepath());

        return array_merge($intro, $filedata, array('paragraphs' => $para));
    }

    /**
     * Function to do a forum type learning activity.
     *
     * @param stdClass $cm The course module.
     * @param stdClass $course The course object.
     * @return array
     */
    private function do_forum(&$cm, &$course) {
        global $DB;

        $forum = $DB->get_record('forum', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($forum);

        // Gather all this forum's posts into an array.
        $query = "SELECT p.*
                    FROM {forum_posts} p
                    JOIN {forum_discussions} d ON d.id = p.discussion
                    JOIN {forum} f             ON f.id = d.forum
                   WHERE f.id = ? AND f.course = ? AND p.deleted <> 1
                ORDER BY p.id ASC";

        $posts = $DB->get_records_sql($query, array($cm->instance, $course->id));

        $data = [];
        foreach ($posts as $post) {
            $data[] = strip_tags($post->subject.' '.$post->message);
        }

        return array_merge($intro, array('paragraphs' => $data));
    }

    /**
     * Function to do a chat type learning activity.
     *
     * @param stdClass $cm The course module.
     * @param stdClass $course The course object.
     * @return array
     */
    private function do_chat(&$cm, &$course) {
        global $DB;

        $chat = $DB->get_record('chat', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($chat);

        // Gather all this chat's messages into an array.
        $query = "SELECT cms.*
                    FROM {chat_messages} cms
                    JOIN {chat} c ON c.id = cms.chatid
                   WHERE c.id = ? AND c.course = ?
                ORDER BY cms.id ASC";

        $messages = $DB->get_records_sql($query, array($cm->instance, $course->id));

        $msgs = [];
        foreach ($messages as $msg) {
            if ($msg->message == 'enter' || $msg->message == 'exit') {
                continue;
            }
            $msgs[] = strip_tags($msg->message);
        }

        return array_merge($intro, array('paragraphs' => $msgs));
    }

    /**
     * Function to do a choice type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_choice(&$cm) {
        global $DB;

        $choice = $DB->get_record('choice', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($choice);

        // Gather all this choice's options into an array.
        $options = $DB->get_records('choice_options', array('choiceid' => $cm->instance), 'id');
        $opts = [];
        foreach ($options as $option) {
            $opts[] = $option->text;
        }

        return array_merge($intro, array('paragraphs' => $opts));
    }

    /**
     * Function to do a database type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_database(&$cm) {
        global $DB;

        $data = $DB->get_record('data', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($data);

        // Gather the table fields into an array.
        $fields = $DB->get_records('data_fields', array('dataid' => $cm->instance), 'id');
        $flds = [];
        foreach ($fields as $field) {
            $flds[] = $field->name.' '.$field->description;
        }

        return array_merge($intro, array('paragraphs' => $flds));
    }

    /**
     * Function to do a feedback type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_feedback(&$cm) {
        global $DB;

        $feedback = $DB->get_record('feedback', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($feedback);

        // Gather feedback items into an array. The items are stored in the following formats:
        // '2' OR '30|255' OR 'r>>>>>one\r|two\r|three' OR 'r>>>>>100####one|222111####two|0####three'
        // and just want to extract the 'one', 'two', and 'three'.
        $data = [];
        $items = $DB->get_records('feedback_item', array('feedback' => $cm->instance), 'id');
        foreach ($items as $item) {

            // Multiple choice questions have options, get them.
            $options = '';
            // Basic multiple choice.
            if ($pos1 = strpos($item->presentation, '>>>>>') !== false) {
                $options = substr($item->presentation, $pos1 + 5);

                // Rated multiple choice.
                if (strpos($options, '####') !== false) {
                    $options = preg_replace('/(\d+)####/', ' ', $options);
                }

                $options = str_replace('|', '', $options);
            }

            $data[] = $item->label.' '.$item->name.' '.$options;
        }

        return array_merge($intro, array('paragraphs' => $data));
    }

    /**
     * Function to do a glossary type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_glossary(&$cm) {
        global $DB;

        $glossary = $DB->get_record("glossary", array("id" => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($glossary);

        // Gather glossary categories into an array.
        $cats = '';
        $categories = $DB->get_records('glossary_categories', array('glossaryid' => $cm->instance));
        foreach ($categories as $ctg) {
            $cats .= strip_tags($ctg->name) . '. ';
        }

        // Gather terms and definitions into an array.
        $terms = [$cats];
        $entries = $DB->get_records('glossary_entries', array('glossaryid' => $cm->instance));
        foreach ($entries as $entry) {
            $terms[] = strip_tags($entry->concept.' '.$entry->definition);
        }

        return array_merge($intro, array('paragraphs' => $terms));
    }

    /**
     * Function to do a lesson type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_lesson(&$cm) {
        global $DB;

        $lesson = $DB->get_record('lesson', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($lesson);

        // Gather the pages into an array.
        $paras = [];
        $pages = $DB->get_records('lesson_pages', array('lessonid' => $cm->instance));

        foreach ($pages as $page) {
            if ($page->title != get_string('endofbranch', 'mod_lesson') &&
                $page->title != get_string('cluster', 'mod_lesson') &&
                $page->title != get_string('endofcluster', 'mod_lesson')) {

                $paras[] = strip_tags($page->title) . '. ' . strip_tags($page->contents);
            }
        }

        // Gather other possibly related text into the headings array.
        $answers = $DB->get_records('lesson_answers', array('lessonid' => $cm->instance));
        $contents = '';
        foreach ($answers as $answer) {
            $contents .= strip_tags($answer->answer.', '.$answer->response) . '. ';
        }
        $paras[] = $contents;

        return array_merge($intro, array('paragraphs' => $paras));
    }

    /**
     * Function to do a survey type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_survey(&$cm) {
        global $DB;

        $survey = $DB->get_record('survey', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($survey);

        // Gather the survey data into an array.
        $data = [];
        $analysiss = $DB->get_records('survey_analysis', array('survey' => $cm->instance));
        foreach ($analysiss as $analysis) {
            $data[] = strip_tags($analysis->notes);
        }

        return array_merge($intro, array('paragraphs' => $data));
    }

    /**
     * Function to do a wiki type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_wiki(&$cm) {
        global $DB;

        $wiki = $DB->get_record('wiki', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($wiki);

        // Get the wiki content if the page is collaborative.
        $paras = [];
        if ($wiki->wikimode == 'collaborative') {
            $page = $DB->get_record('wiki_pages', array('subwikiid' => $cm->instance));
            $paras[] = strip_tags($page->title) . '. ' . strip_tags($page->cachedcontent);
        }

        return array_merge($intro, array('paragraphs' => $paras));
    }

    /**
     * Function to do a workshop type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_workshop(&$cm) {
        global $DB;

        $workshop = $DB->get_record('workshop', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($workshop);

        return $intro;
    }

    /**
     * Function to do a folder type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_folder(&$cm) {
        global $DB;

        $folder = $DB->get_record('folder', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($folder);

        // Get the root of the directory tree to parse its files.
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $root = $fs->get_area_tree($context->id, 'mod_folder', 'content', 0);

        $paras = [];
        $data = $this->parse_tree($root, $paras);

        return array_merge($intro, $data);
    }

    /**
     * Function to do a book type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_book(&$cm) {
        global $DB;

        $book = $DB->get_record('book', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($book);

        // Gather the chapters into a string.
        $chs = '';
        $chapters = $DB->get_records('book_chapters', array('bookid' => $cm->instance));
        foreach ($chapters as $chapter) {
            $chs .= ' '.$chapter->content;
        }

        // Parse the content from all the chapters.
        $data = $this->parse_html($chs, false);

        return array_merge($intro, $data);
    }

    /**
     * Function to do a quiz type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_quiz(&$cm) {
        global $DB;

        $quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($quiz);

        // Gather the section headings into an array.
        $heads = '';
        $sections = $DB->get_records('quiz_sections', array('quizid' => $quiz->id));
        foreach ($sections as $section) {
            $heads .= strip_tags($section->heading) . '. ';
        }
        $paras = $heads == '. ' ? [] : [$heads];

        // Gather the questions, answers, and hints into an array.
        $slots = $DB->get_records('quiz_slots', array('quizid' => $quiz->id));
        foreach ($slots as $slot) {

            $contents = '';
            $questions = $DB->get_records('question', array('id' => $slot->questionid));
            foreach ($questions as $question) {
                $contents .= strip_tags($question->name.' '.$question->questiontext) . ' ';

                $answers = $DB->get_records('question_answers', array('question' => $slot->questionid));
                foreach ($answers as $answer) {
                    $contents .= strip_tags($answer->answer.' '.$answer->feedback) . ' ';
                }

                $hints = $DB->get_records('question_hints', array('questionid' => $slot->questionid));
                foreach ($hints as $hint) {
                    $contents .= strip_tags($hint->hint) . ' ';
                }
            }
            $paras[] = $contents;
        }

        return array_merge($intro, array('paragraphs' => $paras));
    }

    /**
     * Function to do a assignment type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_assignment(&$cm) {
        global $DB;

        $assignment = $DB->get_record('assign', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($assignment);

        return $intro;
    }

    /**
     * Function to do an external tool type learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_external_tool(&$cm) {
        global $DB;

        $tool = $DB->get_record('lti', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($tool);

        return $intro;
    }

    /**
     * Function to do a SCORM package learning activity.
     *
     * @param stdClass $cm The course module.
     * @return array
     */
    private function do_scorm_package(&$cm) {
        global $DB;

        // There seems to be much extraneous data, don't need? And not sure how to
        // parse the real scorm content.
        $scorm = $DB->get_record('scorm', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($scorm);

        $heads = [];
        $scoes = $DB->get_records('scorm_scoes', array('scorm' => $scorm->id));
        foreach ($scoes as $scoe) {
            $heads[] = $scoe->title;
        }

        return array_merge($intro, array('paragraphs' => $heads));
    }

    /**
     * Function to do an IMS content package learning activity.
     *
     * @param stdClass $cm The course module.
     * @return string
     */
    private function do_ims_package(&$cm) {
        global $DB;

        $ims = $DB->get_record('imscp', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = $this->get_name_and_intro($ims);

        return $intro;
    }

    /**
     * Function to parse a folder tree for folder activity type.
     *
     * @param stdClass $dir The folder root.
     * @param array $paras The array of paragraph texts.
     * @return array
     */
    private function parse_tree($dir, &$paras) {

        foreach ($dir as $key => $value) {

            if (gettype($value) == 'object') {
                $filename = $value->get_filename();

                if ($filename != '.') {
                    $paras[] = $this->convert_file($value);
                }
            } else if (gettype($value) == 'array') {
                $this->parse_tree($value, $paras);
            }
        }

        return array('paragraphs' => $paras);
    }

    /**
     * Function to extract heading and paragraph elements from an HTML document.
     *
     * @param string $text The document as a string.
     * @param boolean $asstring Return a single string or an array of strings?
     * @return array|string
     */
    private function parse_html(&$text, $asstring) {

        $dom = new \DOMDocument();
        @$dom->loadHTML($text);

        $str = '';
        $headings = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
        foreach ($headings as $heading) {
            foreach ($dom->getElementsByTagName($heading) as $head) {
                $str .= $head->textContent.'. ';
            }
        }

        $paras = $str == '' ? [] : [$str];
        foreach ($dom->getElementsByTagName('p') as $para) {
            $paras[] = $para->textContent;
            $str .= $para->textContent.' ';
        }

        if ($asstring) {
            return $str;
        } else {
            return array('paragraphs' => $paras);
        }
    }

    /**
     * Function to parse a file name from a file object.
     *
     * @param stored_file $file The file object to parse.
     * @return string
     */
    private function get_filename(&$file) {
        global $CFG;

        $dir1 = substr($file->get_contenthash(), 0, 2);
        $dir2 = substr($file->get_contenthash(), 2, 2);
        $fn = $CFG->dataroot.'/filedir/'.$dir1.'/'.$dir2.'/'.$file->get_contenthash();

        return $fn;
    }

    /**
     * Function to extract text data from another file type.
     *
     * @param stored_file $file The file object to convert.
     * @return string
     */
    private function convert_file(&$file) {

        if (!$file) {
            return '';
        }

        // Get file contents, file name, and MIME type.
        $contents = $file->get_content();
        $fn = $this->get_filename($file);
        $mimetype = $file->get_mimetype();

        // HTML files are parsed separately, text files need no conversion.
        if ($mimetype == 'text/html') {
            return $this->parse_html($contents, true);

        } else if ($mimetype == 'text/plain') {
            // Test for URL that returns HTML, but has .php or something in the filename,
            // which produces the text/plain mime type, but isn't.
            $dom = new \DOMDocument();
            @$dom->loadHTML($contents);
            if ($dom->doctype->name == 'html') {
                return $this->parse_html($contents, true);
            }

            return $contents;

        } else {
            // Use AbiWord to convert the file into text.
            $text = shell_exec('abiword --to=txt --to-name=fd://1 "' . $fn . '"');
            if (strlen($text) == 0) {
                return $file->get_filename();
            }
            return $text;
        }
    }

    /**
     * Function to restrict a sentence string to a set number of words.
     *
     * @param string $sentence The sentence to restrict.
     * @return string
     */
    private function restrict_sentence_length(&$sentence) {

        $maxlength = $this->maxlength;
        $out = '';
        $expld = explode(' ', $sentence);

        if (count($expld) > $maxlength) {
            self::dbug('RESTRICTING SENTENCE LENGTH');
            for ($i = 0; $i < $maxlength; $i++) {
                $out .= $expld[$i] . ' ';
            }
            return $out;
        }
        return $sentence;
    }

    /**
     * Function to initialize the dictionary table.
     */
    private function add_stop_words_to_dictionary() {
        global $DB;

        $words = get_string('stopwords', 'block_lord');
        $words = explode(' ', $words);

        $params = [];
        foreach ($words as $word) {
            $params[] = (object) array(
                'word' => $word,
                'status' => 2
            );
            $this->dictionary[$word] = 2;
        }

        $DB->insert_records('block_lord_dictionary', $params);
    }

    /**
     * Function to remove stop words, numbers, words not in Wordnet, and
     * duplicate words from a sentence.
     *
     * @param string $sentence The sentence to clean.
     * @return string
     */
    private function clean_sentence(&$sentence) {
        global $DB;

        $regex = block_lord_get_cleaning_regex();
        $nopunctuation = preg_replace($regex, ' ', $sentence);
        $lowercase = strtolower($nopunctuation);
        $words = explode(' ', $lowercase);
        $cleaned = '';
        $params = [];
        $seen = [];

        foreach ($words as $word) {

            if (isset($seen[$word])) { // Ignore duplicate words.
                continue;
            }
            $seen[$word] = $word;

            if (strlen($word) == 0 || is_numeric($word)) { // Ignore numbers.
                continue;

            } else if (isset($this->dictionary[$word])) { // Have seen this word before.
                if ($this->dictionary[$word] == 0) { // ... And it is in Wordnet.
                    $cleaned .= $word . ' ';
                }

            } else { // Have not seen this word before.
                $sim = $this->call_bridge($word, $word);

                if ($sim[0] == 1) { // Word is in Wordnet.
                    $this->dictionary[$word] = 0;
                    $cleaned .= $word . ' ';
                    $params[] = (object) array(
                        'word' => $word,
                        'status' => 0
                    );

                } else { // Not in Wordnet.
                    $this->dictionary[$word] = 1;
                    $params[] = (object) array(
                        'word' => $word,
                        'status' => 1
                    );
                }
            }
        }

        if (count($params) > 0) {
            $DB->insert_records('block_lord_dictionary', $params);
        }

        return trim($cleaned);
    }
}