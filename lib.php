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

/**
 * Get additional elements for forms.
 *
 * @param MoodleQuickForm $mform The actual form object (required to modify the form).
 */
function filter_activitytiles_get_additional_form_elements($mform) {

    global $COURSE, $DB, $PAGE;

    // Check if the filter is enabled in the course.
    $context = $PAGE->context;
    $course_context = $context->get_course_context();
    if (!filter_is_enabled('activitytiles', $context)) {
        return;
    }

    // New section in settings.
    $mform->addElement('header', 'activitytiles', get_string('filtername', 'filter_activitytiles'));

    // Include checkbox.
    $mform->addElement('checkbox', 'activitytiles_include', get_string('include', 'filter_activitytiles'));

    // Fontawesome icon.
    $mform->addElement('text', 'activitytiles_icon', get_string('icon', 'filter_activitytiles'));
    $mform->setType('activitytiles_icon', PARAM_TEXT);

    // Image.
    $maxbytes = get_real_size('300K');
    $imagetypes = '.jpg, .jpeg, .gif, .svg, .png';
    $mform->addElement('filemanager', 'activitytiles_image', get_string('image', 'filter_activitytiles'),
        null, array('subdirs' => 0,
                    'maxbytes' => $maxbytes,
                    'areamaxbytes' => $maxbytes,
                    'accepted_types' => $imagetypes,
                    'maxfiles' => 1,
                    'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
                    ));

    // Load our defaults.
    $course_module_id = $PAGE->context->__get('instanceid');

    if ($at_settings = $DB->get_record('filter_activitytiles', array('course_module' => $course_module_id))) {
        $mform->setDefault('activitytiles_include', $at_settings->include);
        $mform->setDefault('activitytiles_icon', $at_settings->icon);

        // Load image.
        $draftitemid = file_get_submitted_draft_itemid('activitytiles_image');
        file_prepare_draft_area($draftitemid, $PAGE->context->id, 'filter_activitytiles', 'activitytiles_image',
            $at_settings->id, array('subdirs' => 0,
                                    'maxbytes' => $maxbytes,
                                    'maxfiles' => 1,
            ));

        $mform->setDefault('activitytiles_image', $draftitemid);
    }

}

/**
 * Inject elements into all moodle module settings forms.
 *
 * @param moodleform $formwrapper The moodle quickforms wrapper object.
 * @param MoodleQuickForm $mform The actual form object (required to modify the form).
 */
function filter_activitytiles_coursemodule_standard_elements($formwrapper, $mform) {
    filter_activitytiles_get_additional_form_elements($mform);
}


/**
 * Inject elements into all moodle section settings forms.
 * This hook does not exist yet, but we will try to have it implemented in 4.5.
 * See https://tracker.moodle.org/browse/MDL-83280
 *
 * @param moodleform $formwrapper The moodle quickforms wrapper object.
 * @param MoodleQuickForm $mform The actual form object (required to modify the form).
 */
function filter_activitytiles_coursesection_standard_elements($formwrapper, $mform) {
    filter_activitytiles_get_additional_form_elements($mform);
}


/**
 * Hook the add/edit of the course module to insert our option into our DB table.
 *
 * @param stdClass $data Data from the form submission.
 * @param stdClass $course The course.
 * @return object
 */
function filter_activitytiles_coursemodule_edit_post_actions($data, $course) {
    global $COURSE, $DB, $PAGE;
    // TODO: check if filter is enabled in course.

    // Get settings from form.
    $at_settings = new stdClass;
    $at_settings->courseid = $COURSE->id;
    $at_settings->course_module = $data->coursemodule;
    $at_settings->include = property_exists($data, 'activitytiles_include');
    $at_settings->icon = $data->activitytiles_icon;

    // Update existing record or insert new one.
    if ($at_settings_id = $DB->get_record('filter_activitytiles', array('course_module' => $data->coursemodule), 'id')) {
        $at_settings->id = $at_settings_id->id;
        $DB->update_record('filter_activitytiles', $at_settings);
    } else {
        $at_settings->id = $DB->insert_record('filter_activitytiles', $at_settings);
    }

    // Save image.
    file_save_draft_area_files($data->activitytiles_image, $PAGE->context->id, 'filter_activitytiles', 'activitytiles_image',
        $at_settings->id, array('subdirs' => 0,
                               'maxbytes' => get_real_size('300K'),
                               'maxfiles' => 1,
                            ));

    return $data;
}


/**
 * Serves the image file.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return mixed
 */
function filter_activitytiles_pluginfile($course, $cm, $context, string $filearea, array $args,bool $forcedownload, array $options = []) :bool {

    // Check the contextlevel.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'activitytiles_image') {
        return false;
    }

    // We only need one item.
    $itemid = 0;

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'filter_activitytiles', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}