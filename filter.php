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

    const TOKEN = '{activitytiles';    

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

        // Split text into parts.
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
     * @param string $filtertext text to be replaced
     * @return string
     */
    protected function get_modules($filtertext) {  
        global $COURSE, $DB, $OUTPUT;

        // See if a module type was specified.        
        if (strpos($filtertext, ':')) {
            $modtype = trim(explode(':', $filtertext)[1]);
        }

        // Build SQL across three tables.
        $sql = "SELECT fat.*, mods.name, cms.instance, cs.sequence, cs.section
                  FROM {filter_activitytiles} fat
                  JOIN {course_modules} cms
                    ON fat.course_module = cms.id
                  JOIN {modules} mods
                    ON cms.module = mods.id
                  JOIN {course_sections} cs
                    ON cms.section = cs.id
                 WHERE cms.course = :courseid";
        
        // Add additional WHERE if modtype is specified.
        if (isset($modtype)) {
            $sql .= "AND mods.name = :name"; 
        }
                   
        // Run query.
        $params = array('courseid' => $COURSE->id, 'name' => $modtype);
        $mods = $DB->get_records_sql($sql, $params);
        
        // Sort by position in section.
        foreach ($mods as $mod) {
            $order = $mod->section * 1000;
            $order += strpos($mod->sequence, $mod->course_module);
            $sorted_mods[$order] = $mod;
        }
        ksort($sorted_mods);

        // TODO: get activity type purpose.
        
        // Prepare data for export to template.
        $data['mods'] = array();
        foreach ($sorted_mods as $mod) {
            $data['mods'][] = array(                
                'id' => $mod->course_module,
                'icon' => $mod->icon,
                'image' => $mod->image,
                'title' => $DB->get_record($mod->name, array('id' => $mod->instance))->name,
                'type' => $mod->name, 
                'url' => "/mod/$mod->name/view.php?id=$mod->course_module",
            );
        }

        $template = 'activitytiles';
        return $OUTPUT->render_from_template('filter_activitytiles/' . $template, $data);   ;
        
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