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

namespace mod_board;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . "/mod/board/external.php");
require_once($CFG->libdir . "/formslib.php");

/**
 * The main board class functions.
 * @package     mod_board
 * @author      Eric Merrill <eric.a.merrill@gmail.com>
 * @copyright   2021 Brickfield Education Labs <https://www.brickfield.ie/>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class note_form extends \moodleform {

    /**
     * Definition of the form elements.
     */
    public function definition() {
        // TODO media select style - media_selection.
        $config = get_config('mod_board');

        $mform = $this->_form;

        $mform->addElement('hidden', 'noteid');
        $mform->setType('noteid', PARAM_INT);
        $mform->addElement('hidden', 'columnid');
        $mform->setType('columnid', PARAM_INT);
        $mform->addElement('hidden', 'ownerid');
        $mform->setType('ownerid', PARAM_INT);

        $maxlenheading = board::LENGTH_HEADING;
        $mform->addElement('text', 'heading', get_string('form_title', 'mod_board'),
            ['maxlength' => $maxlenheading]);
        $mform->setType('heading', PARAM_TEXT);
        $mform->addRule('heading', get_string('maximumchars', '', $maxlenheading), 'maxlength',
            $maxlenheading, 'client');

        $maxlen = $config->post_max_length;
        $options = ['maxlength' => $maxlen, 'cols' => 30, 'rows' => 3];
        $mform->addElement('textarea', 'content', get_string('form_body', 'mod_board'), $options);
        $mform->setType('content', PARAM_TEXT);
        $mform->addRule('content', get_string('maximumchars', '', $maxlen), 'maxlength', $maxlen, 'client');

        $options = [
            0 => get_string('option_empty', 'mod_board'),
            2 => get_string('option_image', 'mod_board'),
            3 => get_string('option_link', 'mod_board'),
        ];

        if ($config->allowyoutube) {
            $options[1] = get_string('option_youtube', 'mod_board');
        }
        if ($config->allowpeertube) {
            $options[7] = get_string('option_peertube', 'mod_board');
        }
        if ($config->allowpod) {
            $options[8] = get_string('option_pod', 'mod_board');
        }

        $attr = ['class' => 'mod_board_type'];
        $mform->addElement('select', 'mediatype', get_string('form_mediatype', 'mod_board'), $options, $attr);

        $html = '<div class="mod_board_note_buttons">
                    <div class="mod_board_attachment_button link_button fa fa-link" role="button" tabindex="0"></div>
                    <div class="mod_board_attachment_button image_button fa fa-picture-o" role="button" tabindex="0"></div>';
        if ($config->allowyoutube) {
            $html .= '<div class="mod_board_attachment_button youtube_button fa fa-youtube" role="button" tabindex="0"></div>';
        }
        if ($config->allowpeertube) {
            $html .= '<div class="mod_board_attachment_button peertube_button" role="button" tabindex="0"></div>';
        }
        if ($config->allowpod) {
            $html .= '<div class="mod_board_attachment_button pod_button" role="button" tabindex="0"></div>';
        }
        $html .= '</div>';

        $mform->addElement('static', 'mediabuttons', get_string('form_mediatype', 'mod_board'), $html);

        // Link.
        $maxleninfo = board::LENGTH_INFO;
        $options = ['maxlength' => $maxleninfo, 'placeholder' => get_string('option_link_info', 'mod_board')];
        $mform->addElement('text', 'linktitle', get_string('option_link_info', 'mod_board'), $options);
        $mform->setType('linktitle', PARAM_TEXT);
        $mform->hideIf('linktitle', 'mediatype', 'neq', 3);
        $mform->addRule('linktitle', get_string('maximumchars', '', $maxleninfo), 'maxlength', $maxleninfo, 'client');

        // URL.
        $maxlenurl = board::LENGTH_URL;
        $attr = ['maxlength' => $maxlenurl, 'placeholder' => get_string('option_link_url', 'mod_board')];
        $mform->addElement('url', 'linkurl', get_string('option_link_url', 'mod_board'), $attr, ['usefilepicker' => false]);
        $mform->setType('linkurl', PARAM_URL);
        $mform->hideIf('linkurl', 'mediatype', 'neq', 3);
        $mform->addRule('linkurl', get_string('maximumchars', '', $maxlenurl), 'maxlength', $maxlenurl, 'client');

        if ($config->allowyoutube) {
            // YouTube video.
            $options = ['maxlength' => $maxleninfo, 'placeholder' => get_string('option_youtube_info', 'mod_board')];
            $mform->addElement('text', 'youtubetitle', get_string('option_youtube_info', 'mod_board'), $options);
            $mform->setType('youtubetitle', PARAM_TEXT);
            $mform->hideIf('youtubetitle', 'mediatype', 'neq', 1);
            $mform->addRule('youtubetitle', get_string('maximumchars', '', $maxleninfo), 'maxlength', $maxleninfo, 'client');

            $options = ['maxlength' => $maxlenurl, 'placeholder' => get_string('option_youtube_url', 'mod_board')];
            $mform->addElement('text', 'youtubeurl', get_string('option_youtube_url', 'mod_board'), $options);
            $mform->setType('youtubeurl', PARAM_URL);
            $mform->hideIf('youtubeurl', 'mediatype', 'neq', 1);
            $mform->addRule('youtubeurl', get_string('maximumchars', '', $maxlenurl), 'maxlength', $maxlenurl, 'client');
        }
        
        if ($config->allowpeertube) {
            // peerTube video.
            $options = ['maxlength' => $maxleninfo, 'placeholder' => get_string('option_peertube_info', 'mod_board')];
            $mform->addElement('text', 'peertubetitle', get_string('option_peertube_info', 'mod_board'), $options);
            $mform->setType('peertubetitle', PARAM_TEXT);
            $mform->hideIf('peertubetitle', 'mediatype', 'neq', 7);
            $mform->addRule('peertubetitle', get_string('maximumchars', '', $maxleninfo), 'maxlength', $maxleninfo, 'client');

            $options = ['maxlength' => $maxlenurl, 'placeholder' => get_string('option_peertube_url', 'mod_board')];
            $mform->addElement('text', 'peertubeurl', get_string('option_peertube_url', 'mod_board'), $options);
            $mform->setType('peertubeurl', PARAM_URL);
            $mform->hideIf('peertubeurl', 'mediatype', 'neq', 7);
            $mform->addRule('peertubeurl', get_string('maximumchars', '', $maxlenurl), 'maxlength', $maxlenurl, 'client');
        }

        if ($config->allowpod) {
            // pod video.
            $options = ['maxlength' => $maxleninfo, 'placeholder' => get_string('option_pod_info', 'mod_board')];
            $mform->addElement('text', 'podtitle', get_string('option_pod_info', 'mod_board'), $options);
            $mform->setType('podtitle', PARAM_TEXT);
            $mform->hideIf('podtitle', 'mediatype', 'neq', 8);
            $mform->addRule('podtitle', get_string('maximumchars', '', $maxleninfo), 'maxlength', $maxleninfo, 'client');

            $options = ['maxlength' => $maxlenurl, 'placeholder' => get_string('option_pod_url', 'mod_board')];
            $mform->addElement('text', 'podurl', get_string('option_pod_url', 'mod_board'), $options);
            $mform->setType('podurl', PARAM_URL);
            $mform->hideIf('podurl', 'mediatype', 'neq', 8);
            $mform->addRule('podurl', get_string('maximumchars', '', $maxlenurl), 'maxlength', $maxlenurl, 'client');
        }

        // Image file.
        $options = ['maxlength' => $maxleninfo, 'placeholder' => get_string('option_image_info', 'mod_board')];
        $mform->addElement('text', 'imagetitle', get_string('option_image_info', 'mod_board'), $options);
        $mform->setType('imagetitle', PARAM_TEXT);
        $mform->hideIf('imagetitle', 'mediatype', 'neq', 2);
        $mform->addRule('imagetitle', get_string('maximumchars', '', $maxleninfo), 'maxlength', $maxleninfo, 'client');

        $pickerparams = board::get_image_picker_options();
        $mform->addElement('filemanager', 'imagefile', get_string('form_image_file', 'mod_board'), null, $pickerparams);
        $mform->hideIf('imagefile', 'mediatype', 'neq', 2);
    }

}
