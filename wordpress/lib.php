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
 * WordPress Portfolio Plugin
 *
 * @package    portfolio
 * @subpackage wordpress
 * @copyright 2014 Jetha Chan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/portfolio/plugin.php');

class portfolio_plugin_wordpress extends portfolio_plugin_push_base
{
    public static function get_name()
    {
        return get_string('pluginname', 'portfolio_wordpress');
    }


    /** 
     * Does anything necessary to prepare the package for sending.
     */
    public function prepare_package()
    {

    }

    /**
     * Actually send the package to the remote system.
     */
    public function send_package()
    {    	
    }

    /**
     * Return a url to present to the user as a 'continue to their portfolio'
     * link.
     */
    public function get_interactive_continue_url()
    {    	
    }


    /**
     * Return how long a transfer can reasonably expect to take.
     *
     * @param string 	$callertime	transfer time estimate from caller, usually
     *								taking into account filesize
     * @return string 	how long we think it'll take
     */
    public function expected_time($callertime) {
        return PORTFOLIO_TIME_LOW;
    }

    /** 
     * The formats this plugin supports.
     *
     * @return array 	formats supported by this plugin
     */
    public function supported_formats() {
        return array(PORTFOLIO_FORMAT_FILE, PORTFOLIO_FORMAT_RICHHTML);
    }

    // User configuration -----------------------------------------------------

    /** 
     * Whether or not this plugin is user-configurable.
     *
     * @return array 	formats supported by this plugin
     */
    public function has_user_config() {
    	return true;
    }

    /** 
     * Extends the default user configuration form.
     *
     * @param object 	moodleform object to have additional elements added to
     *					it by this function
     */
    public function user_config_form(&$moodleform_extant) {
    	
    }
}

?>