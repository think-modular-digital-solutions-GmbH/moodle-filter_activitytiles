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
 * @package    filter_activitytiles
 * @copyright  2023 think-modular 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/renderer.php');

// Returns course cards for all courses that meet the search criteria.

/**
 * Implementation of the Moodle filter API for the Courselist filter.
 * 
 * @copyright  2023 think-modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class filter_activitytiles extends moodle_text_filter {    

    const TOKEN = '{activitytiles:';    

    function filter($text, array $options = array()) {
        global $CFG, $PAGE;        
        
        if (empty($text) or is_numeric($text)) {
            return $text;
        }                        

        if (strpos($text, self::TOKEN) !== false) {                   
            return $this->apply($text);
        } else {
            return $text;
        }        
    }


    /**
     * Does the actual filtering.
     *
     * @param string $text
     * @return string
     */
    protected function apply($text) {        

        // Split text into parts, keeping delimiter.
        $regex = '@(?=' . self::TOKEN . ')@';
        $parts = preg_split($regex, $text);        
        
        foreach ($parts as $key => $part) {                        
            
            if (strpos($part, self::TOKEN) === 0) {                   
                
                $atoms = explode('}', $part);    

                // Check filter integrity.
                if (count($atoms) == 2) {                    
                    $atoms[0] = $this->get_modules($atoms[0]);
                    $parts[$key] = implode($atoms);
                } else {
                    return $this->return_error($text);
                }
            }     
        }    

        return implode($parts);
        
    }


    /**
     * Returns a list of modules in this course according to filter options.
     * 
     * @param string $modtype type of module to look for.
     * @return string
     */
    protected function get_modules($modtype) {  
        global $PAGE, $COURSE, $DB;

        $mods = get_course_mods($COURSE->id);

        $query = "SELECT mdl_filter_activitytiles.*
                  FROM mdl_filter_activitytiles
                  JOIN mdl_course_modules
                  ON mdl_filter_activitytiles.course_module = mdl_course_modules.module
                  WHERE mdl_course_modules.course = :courseid";
        $params = array('courseid' => $COURSE->id);
        $resultSet = $DB->get_records_sql($query, $params);

        $str = "";
        $str.= '<div class="format-tiles">';
        $str.= '<div class="course-content">';
        $str.= '<ul class="tiles" id="multi_section_tiles">';

        foreach ($mods as $mod) {
            foreach($resultSet as $result) {
                if ($mod->module == $result->course_module) {
                    if (!($result->include)) {
                        $str.= '<li class="tile tile-clickable" id="tile-1" data-section="1" data-true-sectionid="344" tabindex="2">
                                    <div class="tile-bg"></div>
                                    <a class="tile-link" href="https://moodle.develop-modular.com/moodle-4.1/mod/attendance/view.php?id=316" data-section="1" id="sectionlink-1">
                                        <div class="tile-content" id="tilecontent-1">
                                            <div class="tile-top" id="tileTop-1">
                                                <div class="tileiconcontainer" id="tileicon_1">
                                                    <span class="tile-icon">
                                                        <i class="icon fa fa-home fa-fw " aria-hidden="true"></i>
                                                    </span>
                                                </div>
                                                <div class="tiletopright pull-right" id="tiletopright-1" aria-hidden="true"></div>
                                            </div>
                                            <div class="tile-text" id="tileText-1">
                                                <div class="tile-textinner" id="tileTextin-1">
                                                    <h3>Attendance 1</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>';
                    }
                }
            }
        }

        $str.= '</ul>
                </div></div>
        </div>';

        return $str;

    

        

        

        /*return '
        <div class="format-tiles">
    <div class="course-content">
        <ul class="tiles" id="multi_section_tiles">
            <li class="tile tile-clickable" id="tile-1" data-section="1" data-true-sectionid="344" tabindex="2">
                <div class="tile-bg"></div>
                <a class="tile-link" href="https://moodle.develop-modular.com/moodle-4.1/mod/attendance/view.php?id=316" data-section="1" id="sectionlink-1">
                    <div class="tile-content" id="tilecontent-1">
                        <div class="tile-top" id="tileTop-1">
                            <div class="tileiconcontainer" id="tileicon_1">
                                <span class="tile-icon">
                                    <i class="icon fa fa-home fa-fw " aria-hidden="true"></i>
                                </span>
                            </div>
                            <div class="tiletopright pull-right" id="tiletopright-1" aria-hidden="true"></div>
                        </div>
                        <div class="tile-text" id="tileText-1">
                            <div class="tile-textinner" id="tileTextin-1">
                                <h3>Attendance 1</h3>
                            </div>
                        </div>
                    </div>
                </a>
            </li>
            <li class="tile tile-clickable" id="tile-2" data-section="2" data-true-sectionid="222" tabindex="2">
                <div class="tile-bg"></div>
                <a class="tile-link" href="https://moodle.develop-modular.com/moodle-4.1/mod/attendance/view.php?id=318" data-section="2" id="sectionlink-2">
                    <div class="tile-content" id="tilecontent-2">
                        <div class="tile-top" id="tileTop-2">
                            <div class="tileiconcontainer" id="tileicon_2">
                                <span class="tile-icon">
                                    <i class="icon fa fa-edit fa-fw " aria-hidden="true"></i>
                                </span>
                            </div>
                            <div class="tiletopright pull-right" id="tiletopright-2" aria-hidden="true"></div>
                        </div>
                        <div class="tile-text" id="tileText-2">
                            <div class="tile-textinner" id="tileTextin-2">
                                <h3>Attendance 2</h3>
                            </div>
                        </div>
                    </div>
                </a>
            </li>
            <li class="tile tile-clickable" id="tile-3" data-section="3" data-true-sectionid="225" tabindex="2">
                <div class="tile-bg"></div>
                <a class="tile-link" href="https://moodle.develop-modular.com/moodle-4.1/mod/attendance/view.php?id=317" data-section="3" id="sectionlink-3">
                    <div class="tile-content" id="tilecontent-3">
                        <div class="tile-top" id="tileTop-3">
                            <div class="tileiconcontainer" id="tileicon_3">
                                <span class="tile-icon">
                                    <img class="icon " alt="" aria-hidden="true" src="https://skills4abroad.de/theme/image.php/sqportal/format_tiles/1686054585/tileicon/thinking-person">
                                </span>
                            </div>
                            <div class="tiletopright pull-right" id="tiletopright-3" aria-hidden="true"></div>
                        </div>
                        <div class="tile-text" id="tileText-3">
                            <div class="tile-textinner" id="tileTextin-3">
                                <h3>Attendance 3</h3>
                            </div>
                        </div>
                    </div>
                </a>
            </li>
            <li class="tile tile-clickable" id="tile-4" data-section="4" data-true-sectionid="223" tabindex="2">
                <div class="tile-bg"></div>
                <a class="tile-link" href="https://moodle.develop-modular.com/moodle-4.1/mod/attendance/view.php?id=319" data-section="4" id="sectionlink-4">
                    <div class="tile-content" id="tilecontent-4">
                        <div class="tile-top" id="tileTop-4">
                            <div class="tileiconcontainer" id="tileicon_4">
                                <span class="tile-icon">
                                    <img class="icon " alt="" aria-hidden="true" src="https://skills4abroad.de/theme/image.php/sqportal/format_tiles/1686054585/tileicon/book">
                                </span>
                            </div>
                            <div class="tiletopright pull-right" id="tiletopright-4" aria-hidden="true"></div>
                        </div>
                        <div class="tile-text" id="tileText-4">
                            <div class="tile-textinner" id="tileTextin-4">
                                <h3>Attendance 4</h3>
                            </div>
                        </div>
                    </div>
                </a>
            </li> 
            <li class="tile spacer" aria-hidden="true"></li>
            <li class="tile spacer" aria-hidden="true"></li>
            <li class="tile spacer" aria-hidden="true"></li>
            <li class="tile spacer" aria-hidden="true"></li>
            <li class="tile spacer" aria-hidden="true"></li>
            <li class="tile spacer" aria-hidden="true" id="lasttile"></li>
        </ul>
        </div></div>
</div>';*/
        
    }


    /**
     * Renders the searchbox.     
     *      
     * @return string
     */
    protected function searchbox() {
        global $OUTPUT;

        // Get parameters.
        $placeholder = get_string('searchcourses');
        $searchvalue = (array_key_exists('courselist_search', $_GET)) ? $_GET['courselist_search'] : null;

        // Render searchbox.
        $data = array('placeholder' => $placeholder, 'searchvalue' => $searchvalue);
        return $OUTPUT->render_from_template('filter_activitytiles/searchbox', $data);           
    }


    /**
     * Returns original text plus error message.
     * 
     * @param string $text 
     * @return string
     */
    protected function return_error($text) {
        $errormsg = get_string('errormsg', 'filter_activitytiles');
        return '<div class="alert alert-danger">' . $errormsg . '</div>' . $text;
    }
     
}