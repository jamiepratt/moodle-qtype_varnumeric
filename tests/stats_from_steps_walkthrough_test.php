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
 * Quiz attempt walk through using data from csv file.
 *
 * @package    quiz_statistics
 * @category   phpunit
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/report/statistics/tests/stats_from_steps_walkthrough_test.php');
require_once($CFG->dirroot . '/mod/quiz/report/default.php');
require_once($CFG->dirroot . '/mod/quiz/report/statistics/report.php');
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');

/**
 * Quiz attempt walk through using data from csv file.
 *
 * @package    quiz_statistics
 * @category   phpunit
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_statistics_from_steps_testcase extends quiz_report_statistics_from_steps_testcase {

    /**
     * @var quiz_statistics_report object to do stats calculations.
     */
    protected $report;

    protected function get_full_path_of_csv_file($setname, $test) {
        // Overridden here so that __DIR__ points to the path of this file.
        return  __DIR__."/fixtures/{$setname}{$test}.csv";
    }

    protected $files = array('questions', 'steps', 'responsecounts');

    /**
     * Create a quiz add questions to it, walk through quiz attempts and then check results.
     *
     * @param PHPUnit_Extensions_Database_DataSet_ITable[] of data read from csv file "questionsXX.csv" and "stepsXX.csv"
     * @dataProvider get_data_for_walkthrough
     */
    public function test_walkthrough_from_csv($quizsettings, $csvdata) {

        $this->create_quiz_simulate_attempts_and_check_results($quizsettings, $csvdata);

        $whichattempts = QUIZ_GRADEAVERAGE; // All attempts.
        if ($quizsettings['preferredbehaviour'] === 'deferredfeedback') {
            $whichtries = question_attempt::LAST_TRY;
        } else {
            $whichtries = question_attempt::ALL_TRIES;
        }
        $groupstudents = array();
        list($questions, $quizstats, $questionstats, $qubaids) =
            $this->check_stats_calculations_and_response_analysis($csvdata, $whichattempts, $whichtries, $groupstudents);

        if ($quizsettings['testnumber'] === '00') {
            $responesstats = new \core_question\statistics\responses\analyser($questions[1]);
            $this->assertTimeCurrent($responesstats->get_last_analysed_time($qubaids, $whichtries));
            $analysis = $responesstats->load_cached($qubaids, $whichtries);
            $variantsnos = $analysis->get_variant_nos();

            $this->assertEquals(array(1), $variantsnos);
            $total = 0;
            $subpartids = $analysis->get_subpart_ids(1);
            $subpartid = current($subpartids);

            $subpartanalysis = $analysis->get_analysis_for_subpart(1, $subpartid);
            $classids = $subpartanalysis->get_response_class_ids();
            foreach ($classids as $classid) {
                $classanalysis = $subpartanalysis->get_response_class($classid);
                $actualresponsecounts = $classanalysis->data_for_question_response_table('', '');
                foreach ($actualresponsecounts as $actualresponsecount) {
                    $total += $actualresponsecount->totalcount;
                }
            }
            $this->assertEquals(25, $total);

            $this->assertEquals(25, $questionstats->for_slot(1)->s);
            $this->assertEquals(null, $questionstats->for_slot(1, 1));
            $this->assertEquals(null, $questionstats->for_slot(1, 2));
            $this->assertEquals(null, $questionstats->for_slot(1, 3));
            $this->assertEquals(null, $questionstats->for_slot(1, 4));
            $this->assertEquals(null, $questionstats->for_slot(1, 5));
        }
    }
}
