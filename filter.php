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

/**
 * Implementation of the Moodle filter API for the Courselist filter.
 *
 * @copyright  2023 think-modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class filter_activitytiles extends moodle_text_filter {

    const TOKEN = '{{ activitytiles ';

    #[\Override]
    public function filter($text, array $options = array()) {
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

                $atoms = explode(' }}', $part);

                // Check filter integrity and get replacement html.
                if (count($atoms) == 2) {
                    $atoms[0] = $this->getHtml($atoms[0]);
                    $parts[$key] = implode($atoms);
                } else {
                    return $this->return_error($text);
                }
            }
        }

        // Return the filtered text.
        return implode($parts);
    }

    /**
     * Returns a list of modules in this course according to filter options.
     *
     * @param string $text text to be replaced
     * @return string
     */
    protected function getHtml($text) {
        global $CFG, $COURSE, $DB, $OUTPUT, $USER;

        // Course params.
        $courseid = $COURSE->id;
        $format = course_get_format($courseid);
        $modinfo = get_fast_modinfo($courseid);
        $completion = new \completion_info($COURSE);

        // Get module type(s).
        if (strpos($text, 'mods=')) {
            $type = explode('mods=', $text)[1];
            $type = explode(' ', $type)[0];
            $type = trim($type);
            if ($type == 'selected') {
                $selected = true;
            } else {
                $modtype = $type;
            }
        }

        // Get section(s).
        if (strpos($text, 'sections=')) {
            $section = explode('sections=', $text)[1];
            $section = explode(' ', $section)[0];
            $section = trim($section);
        }

        // Build SQL across three tables.
        $sql = "SELECT cms.id, mods.name, cms.instance, cs.sequence, cs.section, fat.course_module, fat.include, fat.icon, fat.image, fat.id AS fatid
                  FROM {course_modules} cms
                  LEFT JOIN {filter_activitytiles} fat
                    ON fat.course_module = cms.id
                  JOIN {modules} mods
                    ON cms.module = mods.id
                  JOIN {course_sections} cs
                    ON cms.section = cs.id
                 WHERE cms.course = :courseid";

        // Prepare parameters.
        $params = ['courseid' => $COURSE->id];

        // Add parameter for mod types.
        if (!empty($modtype)) {
            $modtypes = array_map('trim', explode(',', $modtype));
            list($sqlin, $inparams) = $DB->get_in_or_equal($modtypes, SQL_PARAMS_NAMED, 'modname');
            $sql .= " AND mods.name $sqlin";
            $params += $inparams;
        }

        // Add parameter for sections.
        if (!empty($section)) {
            $sections = array_map('trim', explode(',', $section));
            list($sqlin, $inparams) = $DB->get_in_or_equal($sections, SQL_PARAMS_NAMED, 'section');
            $sql .= " AND cs.section $sqlin";
            $params += $inparams;
        }

        // Add addtional WHERE if only selected mods should be shown.
        if (isset($selected)) {
            $sql .= "AND fat.include = 1";
        }

        // We really do not need labels either way.
        $sql .= " AND mods.name != 'label'";

        // Run query.
        if (!$mods = $DB->get_records_sql($sql, $params)) {
            return '';
        }

        // Sort by position in section.
        foreach ($mods as $mod) {
            $order = $mod->section * 1000;
            $order += strpos($mod->sequence, $mod->id);
            $sorted_mods[$order] = $mod;
        }
        ksort($sorted_mods);

        // Prepare data for export to template.
        $data['mods'] = array();

        foreach ($sorted_mods as $mod) {

            // Get module params.
            $type = $mod->name;
            $moduleid = $mod->id;
            $instanceid = $mod->instance;
            $title = $DB->get_record($type, array('id' => $instanceid))->name;
            $context = \context_module::instance($moduleid);
            $cm = $modinfo->get_cm($moduleid);
            $section = $modinfo->get_section_info($mod->section);
            $url = new \moodle_url("/mod/$mod->name/view.php", ['id' => $moduleid]);

            // Check visibility.
            if (!$cm->is_visible_on_course_page()) {
                continue;
            }

            // Check availability.
            $notavailable = false;
            if (!$cm->get_user_visible()) {
                $availability = new \core_availability\info_module($cm);
                $isavailable = $availability->is_available($information, true, $userid);
                if (!$isavailable) {
                    $notavailable = true;
                }
            }

            // Get completion state.
            $completionstate = null;
            $completionelement = null;
            if ($completion->is_enabled($cm)) {
                $completiondata = $completion->get_data($cm, true, $USER->id);
                $completionstate = $completiondata->completionstate;

                // Other completion status might not be supported (yet).
                if ($completionstate < 0 || $completionstate > 3) {
                    $completionstate = 0;
                }
                $completionelement = $OUTPUT->render_from_template("filter_activitytiles/completionstate$completionstate", ['title' => $mod->name]);
            }

            // Get activity type purpose.
            $mod->purpose = '';
            $function = $mod->name . '_supports';
            if (function_exists($function)) {
                $mod->purpose = $function(FEATURE_MOD_PURPOSE);
            }

            // Retrieve image.
            $imgurl = null;
            if ($mod->image) {
                $fs = get_file_storage();
                $files = $fs->get_area_files($context->id,
                                             'filter_activitytiles',
                                             'activitytiles_image',
                                             $mod->fatid,
                );

                // Get the first valid file.
                foreach ($files as $file) {

                    if ($file->get_filename() === '.') {
                        // This is the directory placeholder, skip it.
                        // seriously, this is a thing.
                        continue;
                    }

                    $imgurl = \moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename()
                    )->out();
                }
            }

            // Get section name.
            $sectioninfo = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $mod->section], '*', MUST_EXIST);
            $topic = $format->get_section_name($sectioninfo);

            // Create array for mustache.
            $data['mods'][] = array(
                'categoryid' => $COURSE->category,
                'completion' => $completionelement,
                'courseid' => $courseid,
                'id' => $moduleid,
                'icon' => $mod->icon,
                'image' => $imgurl,
                'iconurl' => $OUTPUT->image_url('icon', "mod_$type"),
                'notavailable' => $notavailable,
                'purpose' => $mod->purpose,
                'title' => $title,
                'topic' => $topic,
                'type' => $type,
                'url' => $url,
            );
        }

        // Get custom template.
        $template = 'activitytiles';
        if (strpos($text, 'template=')) {
            $alttemplate = explode('template=', $text)[1];
            $alttemplate = explode(' ', $alttemplate)[0];
            $alttemplate = trim($alttemplate);
            // Render from template.
            if (str_contains($alttemplate, '/')) {
                return $OUTPUT->render_from_template($alttemplate, $data);
            } else {
            // Check if template exists.
                $template_file_path = $CFG->dirroot . "/filter/activitytiles/templates/$alttemplate" . '.mustache';
                if (file_exists($template_file_path)) {
                    return $OUTPUT->render_from_template('filter_activitytiles/' . $alttemplate, $data);
                } else {
                    return $this->return_error(get_string('errortemplate', 'filter_courselist') . $template_file_path, $text);
                }
            }
        }

        // Render from template.
        return $OUTPUT->render_from_template('filter_activitytiles/' . $template, $data);

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