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

//defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/portfolio/plugin.php');
require_once($CFG->libdir.'/filelib.php');


require_once('helper_wordpress_xmlrpc.php');


class portfolio_plugin_wordpress extends portfolio_plugin_push_base
{
    private $xmlrpchelper;

    private $exportsettings = array();
    private $exportpackage = array();

    public static function get_name()
    {
        return get_string('pluginname', 'portfolio_wordpress');
    }

    // PLUGIN FUNCTIONS ###################################################

    /** 
     * Does anything necessary to prepare the package for sending; typically, 
     * read the temporary files and zip them, etc. - but we probably won't be
     * doing a packaged-up file dump just yet, since WordPress out the box 
     * doesn't really deal with that (or at least it doesn't over XML-RPC).
     */
    public function prepare_package()
    {
        $tmproot = make_temp_directory('wordpressupload');
        $files = $this->exporter->get_tempfiles();

        $this->exportpackage = array();

        foreach ($files as $file) {

            // Infer from filename how we should handle things.   
            $filename_info = new SplFileInfo($file->get_filename());
            $filename_extension = $filename_info->getExtension();
            $filename_base = $filename_info->getBasename($filename_extension);

            $field_subject = $filename_base;
            $field_message = "";

            if($filename_extension === 'html')
            {

                // HTML ######################################################
                $domdoc = new DOMDocument();

                // TODO: This probably isn't the best way of doing this.
                $tmpfilepath = $tmproot .'/'.$file->get_contenthash();
                $file->copy_content_to($tmpfilepath);

                $domdoc->loadHTMLFile($tmpfilepath);

                $cells = $domdoc->getElementsByTagName('td');
                $td_header = $cells->item(1);            
                $div_subject = $td_header->getElementsByTagName('div')->item(0);

                // Extract:
                //  - subject
                $field_subject = $div_subject->textContent;            
                //  - message
                $field_message = "";

                if ($filename_base === "post") {

                    // POST
                    // only dump the HTML in the content TD; looks better
                    $td_content = $cells->item($cells->length-1);
                    $field_message = $domdoc->saveHTML(
                        $td_content
                    );

                } else// if($filename_base === "discussion")
                {

                    // DISCUSSION / OTHERWISE
                    // just dump raw HTML.
                    $field_message = $domdoc->saveHTML();

                }

                $domdoc = null;
                unlink($tmpfilepath);

                // Prepare:                
                $postinfo = array(
                        'package_type' => 'html',
                        'post_title' => $field_subject,
                        'post_content' => $field_message,
                        'post_author' => $options['wordpress-user-id']
                    );

                array_push($this->exportpackage, $postinfo);
            } else
            {
                $mimeinfo = & get_mimetypes_array();

                // OTHER #####################################################
                $fileinfo = array(
                    'package_type' => 'raw',
                    'name' => $file->get_filename(),
                    'type' => $mimeinfo[$filename_extension]['type'],
                    'bits' => $file->get_content()
                    );

                array_push($this->exportpackage, $fileinfo);
            }
        }

        return true;
    }

    /**
     * Actually send the package to the remote system.
     *
     * @return bool success
     */
    public function send_package()
    {
        global $DB;

        // Get export options out of the DB.
        $userid = $this->user->id;
        $options = $DB->get_records_menu(
            'portfolio_instance_user',
            array(
                'instance'=> $this->id,
                'userid' => $userid
            ),
            '',
            'name, value'
        );

        // Process "packages" and send them out the door.
        foreach($this->exportpackage as $package)
        {
            if($package['package_type'] == 'html')
            {
                $this->util_makePost(
                    $package,
                    $options['wordpress-blog-id'],
                    $options['wordpress-username'],
                    $options['wordpress-password'],
                    $options['wordpress-url'] . '/xmlrpc.php'
                );
            }
            else if($package['package_type'] == 'raw')
            {
                $this->util_uploadFile(
                    $package,
                    $options['wordpress-blog-id'],
                    $options['wordpress-username'],
                    $options['wordpress-password'],
                    $options['wordpress-url'] . '/xmlrpc.php'
                );
            }
        }
    }

    /**
     * Return a url to present to the user as a 'continue to their portfolio'
     * link.
     */
    public function get_interactive_continue_url()
    {
        // TODO: Replace with actual thing.
        return "http://www.google.com";
    }

    /**
     * Return how long a transfer can reasonably expect to take.
     *
     * @param string    $callertime transfer time estimate from caller, usually
     *                              taking into account filesize
     * @return string   how long we think it'll take
     */
    public function expected_time($callertime) {
        return PORTFOLIO_TIME_LOW;
    }

    /** 
     * The formats this plugin supports.
     *
     * @return array    formats supported by this plugin
     */
    public function supported_formats() {
        return array(PORTFOLIO_FORMAT_FILE, PORTFOLIO_FORMAT_RICHHTML);
    }

    /** 
     * Whether or not this plugin has user-configurable export options.
     *
     * @return array    formats supported by this plugin
     */
    public function has_export_config() {
        return true;
    }

    /**
     * This plugin provides WordPress export settings on a per-user basis; there
     * is very little meaning in allowing multiple instances.
     *
     * @return bool
     */
    public static function allows_multiple_instances() {
        return false;
    }

    /** 
     * Extends the default export configuration form.
     *
     * @param object    moodleform object to have additional elements added to
     *                  it by this function
     */
    public function export_config_form(&$mform)
    {
        // auto-publish
        $mform->addElement(
            'checkbox',
            'wordpress-export-publish',
            get_string('label-wordpress-export-publish', 'portfolio_wordpress')
        );
        $mform->setType(
            'wordpress-export-publish',
            PARAM_BOOL
        );
        $mform->setDefault(
            'wordpress-export-publish',
            false
        );

        // post type
        $select = $mform->addElement(
            'select',
            'wordpress-export-type',
            get_string('label-wordpress-export-type', 'portfolio_wordpress'),
            array(
                'page' => get_string('label-wordpress-export-type-page', 'portfolio_wordpress'),
                'post' => get_string('label-wordpress-export-type-post', 'portfolio_wordpress'),
                'derp' => 'WHAT'
            )
        );
        $mform->setType(
            'wordpress-export-type',
            PARAM_TEXT
        );
        $select->setSelected('wordpress-export-type');
    }

    /**
     * Just like the moodle form validation function.
     * This is passed in the data array from the form and if a non empty array
     * is returned, form processing will stop.
     *
     * @param array $data data from form.
     */
    public function export_config_validation(array $data)
    {
        global $DB;

        // Get export options out of the DB.
        $userid = $this->user->id;
        $options = $DB->get_records_menu(
            'portfolio_instance_user',
            array(
                'instance'=> $this->id,
                'userid' => $userid
            ),
            '',
            'name, value'
        );
        //$options['wordpress-url']
        $errorstrings = array();

        $this->exportsettings['publish'] = array_key_exists('wordpress-export-publish', $data);
        $this->exportsettings['post_type'] = $data['wordpress-export-type'];

        if(!($data['wordpress-export-type'] === 'page' || $data['wordpress-export-type'] === 'post'))
        {
            $errorstrings['wordpress-export-type'] = get_string('label-wordpress-export-type-unsupported', 'portfolio_wordpress');
            $this->exportsettings['post_type'] = 'unsupported';
        }

        $wordpressdisabled =
            !array_key_exists('wordpress-enabled', $options) ||
            $options['wordpress-enabled'] != true;

        if($wordpressdisabled)
            $errorstrings['wordpress-enabled'] = get_string('label-wordpress-error-disabled', 'portfolio_wordpress');

        //var_dump($data);
        //var_dump($errorstrings);

        if(count($errorstrings) > 0)
            return $errorstrings;
    }




    /**
     * Just like the moodle form validation function.
     * This is passed in the data array from the form and if a non empty array
     * is returned, form processing will stop.
     *
     * @param array $data data from form.
     */
    public function user_config_validation(array $data)
    {
        global $USER;
        
        //var_dump($data);
        $errorstrings = array();

        $wordpressenabled = array_key_exists('wordpress-enabled', $data);

        $extra_userconfigopts = array();


        $extra_userconfigopts['wordpress-enabled'] = $wordpressenabled;

        if($wordpressenabled)
        {
            $wp_blog_id = $this->util_useBlog(
                $this->request_getUserBlogs(
                    $data['wordpress-username'],
                    $data['wordpress-password']
                ),
                $data['wordpress-url'] . '/xmlrpc.php'
            );

            $wp_userdata = $this->util_getUserData(
                $wp_blog_id,        
                $data['wordpress-username'],
                $data['wordpress-password'],
                $data['wordpress-url'] . '/xmlrpc.php'
            );

            $extra_userconfigopts['wordpress-blog-id'] = $wp_blog_id;
            $extra_userconfigopts['wordpress-user-id'] = $wp_userdata[0];
            $extra_userconfigopts['wordpress-user-nicename'] = $wp_userdata[1];
        }
        
        //var_dump($extra_userconfigopts);

        $this->set_user_config(
            $extra_userconfigopts,
            $USER->id
        );        

        
        return $errorstrings;
    }


    /**
     * Returns information to be displayed to the user after export config 
     * submission.
     *
     * @return array    a table of nice strings => values summarising user
     *                  configuration choices.
     */
    public function get_export_summary()
    {
        return array(                
            get_string('label-wordpress-export-publish', 'portfolio_wordpress') =>
                $this->exportsettings['publish']? get_string('yes') : get_string('no'),

            get_string('label-wordpress-export-type', 'portfolio_wordpress') =>
                get_string('label-wordpress-export-type-' . $this->exportsettings['post_type'], 'portfolio_wordpress')
            );
    }

    /** 
     * Whether or not this plugin is user-configurable.
     *
     * @return array    formats supported by this plugin
     */
    public function has_user_config() {
        return true;
    }

    /** 
     * Extends the default user configuration form.
     *
     * @param object    moodleform object to have additional elements added to
     *                  it by this function
     */
    public function user_config_form(&$mform) {
 
        // enabled
        $mform->addElement(
            'checkbox',
            'wordpress-enabled',
            get_string('label-wordpress-enabled', 'portfolio_wordpress')
        );
        $mform->setType(
            'wordpress-enabled',
            PARAM_BOOL
        );
        $mform->setDefault(
            'wordpress-enabled',
            false//get_string('defaulttext-wordpress-url', 'portfolio_wordpress')
        );

        // WordPress URL
        $mform->addElement(
            'text',
            'wordpress-url',
            get_string('label-wordpress-url', 'portfolio_wordpress')
        );
        $mform->setType(
            'wordpress-url',
            PARAM_TEXT
        );
        $mform->setDefault(
            'wordpress-url',
            ''//get_string('defaulttext-wordpress-url', 'portfolio_wordpress')
        );

        // WordPress auth
        // TODO: OAuth using Jetpack instead of (in addition to?) this
        $mform->addElement(
            'text',
            'wordpress-username',
            get_string('label-wordpress-username', 'portfolio_wordpress')
        );
        $mform->setType(
            'wordpress-username',
            PARAM_TEXT
        );
        $mform->setDefault(
            'wordpress-username',
            ''//get_string('defaulttext-wordpress-username', 'portfolio_wordpress')
        );
        $mform->addElement(
            'passwordunmask',
            'wordpress-password',
            get_string('label-wordpress-password', 'portfolio_wordpress')
        );
        $mform->setType(
            'wordpress-password',
            PARAM_TEXT
        );
        $mform->setDefault(
            'wordpress-password',
            ''//get_string('defaulttext-wordpress-password', 'portfolio_wordpress')
        );

        // rules
        $strrequired = get_string('required');
        $mform->disabledIf('wordpress-url', 'wordpress-enabled');
        $mform->disabledIf('wordpress-username', 'wordpress-enabled');
        $mform->disabledIf('wordpress-password', 'wordpress-enabled');

    }

    public function get_allowed_user_config(){
        return array(
            'config',
            'submitbutton',
            'wordpress-url',
            'wordpress-username',
            'wordpress-password',
            'wordpress-blog-id',
            'wordpress-user-id',
            'wordpress-user-nicename',
            'wordpress-enabled'
        );
    }




    // WORDPRESS XML-RPC API REQUEST STRINGBUILDERS #######################

    /**
     * Create an XML-RPC request string that retrieves an array of blogs on the
     * WordPress server that have the requesting user as a contributor.
     *
     * @param string    username   the username of the user
     * @param string    password   the password of the user  
     *
     * @return string the resultant XML-RPC request string
     */
    private function request_getUserBlogs($username, $password)
    {
        $userid = $this->user->id;

        // @todo: bulletproof this
        $method = xmlrpc_encode_request(
            'wp.getUsersBlogs', 
            array(
                $username,
                $password
            )
        );
        //var_dump($method);

        return $method;
    }

    /**
     * Create an XML-RPC request string that retrieves the profile of the 
     * specified user.
     *
     * @param int       $blog_id   the blog_id of the WordPress blog
     * @param string    $username   the username of the user
     * @param string    $password   the password of the user
     *
     * @return string   the resultant XML-RPC request string
     */
    private function request_getProfile($blog_id, $username, $password)
    {
        $userid = $this->user->id;       


        // TODO: bulletproof this
        $method = xmlrpc_encode_request(
            'wp.getProfile', 
            array(
                $blog_id,
                $username,
                $password
            )
        );

        return $method;
    }

    /**
     * Create an XML-RPC request string that requests a new post of specified
     * type and status be made.
     *
     * @param int       $blog_id   the blog_id of the WordPress blog
     * @param string    $username   the username of the user
     * @param string    $password   the password of the user
     *
     * @return string   the resultant XML-RPC request string
     */
    private function request_newPost_initial($postinfo, $blog_id, $username, $password)
    {

        $method = xmlrpc_encode_request(
            'wp.newPost',
            array(
                $blog_id,
                $username,
                $password,
                array(
                    // post_type
                    'post_type' => isset($postinfo['post_type'])? $postinfo['post_type'] : 'page',
                    // post_status
                    'post_status' => isset($postinfo['post_status'])? $postinfo['post_status'] : 'publish',
                    // post_title
                    'post_title' => $postinfo['post_title'],
                    // post_author
                    'post_author' => $postinfo['post_author'],
                    // post_excerpt
                    'post_excerpt' => substr($postinfo['post_content'], 0, 55),
                    'post_content' => $postinfo['post_content']
                )
            )
        );

        //print_r($method);

        return $method;
    }

    /**
     * Create an XML-RPC request string for uploading a file.
     *
     * @param array     $fileinfo   information about the file, as well as its bytes
     * @param int       $blog_id    the blog_id of the WordPress blog
     * @param string    $username   the username of the user
     * @param string    $password   the password of the user
     *
     * @return string   the resultant XML-RPC request string
     */
    private function request_uploadFile(array $fileinfo, $blog_id, $username, $password)
    {

        $method = xmlrpc_encode_request(
            'wp.uploadFile',
            array(
                $blog_id,
                $username,
                $password,
                $fileinfo
            )
        );

        //print_r($method);

        return $method;
    }


    // WORDPRESS XML-RPC API UTILITIES ####################################

    public static function util_xmlrpc_errorhandler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return;
        }

        switch ($errno) {
            case E_WARNING:
                restore_error_handler();
                throw new portfolio_plugin_exception(
                    'xmlrpcfault',
                    'portfolio_wordpress'
                );
                break;
        }
    }

    /**
     * Perform an XML-RPC request, given a well-formed XML-RPC request and a url.
     * 
     * @return mixed    an array, integer, string or boolean according to the
     *                  response received
     */
    private function util_doxmlrpc($requeststring, $xmlrpcurl = null)
    {
 

        //set_error_handler("portfolio_plugin_wordpress::util_xmlrpc_errorhandler");

            $context = stream_context_create(array('http' => array(
                'method' => "POST",
                'header' => "Content-Type: text/xml",
                'content' => $requeststring
            )));


            $file = file_get_contents(
                $xmlrpcurl,
                false,
                $context
            );            
            //var_dump($file);

            $response = xmlrpc_decode($file);

        //print_r($file);
        //print_r($response);
        if (is_array($response) && xmlrpc_is_fault($response)) {
            return false;
        }

        //restore_error_handler();

        return $response;
    }

    private function util_useBlog($requeststring = null, $xmlrpcurl = null, $p_idx = 0)
    {
        if(is_null($requeststring))
            $requeststring = $this->request_getUserBlogs();

        $response = $this->util_doxmlrpc(
            $requeststring,
            $xmlrpcurl
        );

        //var_dump($response);
        if (is_array($response) && xmlrpc_is_fault($response)) {
            throw new portfolio_plugin_exception('xmlrpcfault', 'portfolio_wordpress');
        } else {
            return count($response) > 0 ? $response[$p_idx]['blogid'] : 0;
        }
    }

    private function util_getUserData($blog_id, $username, $password, $xmlrpcurl)
    {
        $response = $this->util_doxmlrpc(
            $this->request_getProfile($blog_id, $username, $password),
            $xmlrpcurl
        );
        /*
        ?>
        <textarea><?php print_r ($response); ?></textarea>
        <?php
        */
        
        //print_r($response); 
        return array($response['user_id'], $response['display_name']);
    }

    public function util_makePost(array $postinfo, $blog_id, $username, $password, $xmlrpcurl)
    {
        if(!isset($postinfo))
            return -1; // signal failure

        $reqstring = $this->request_newPost_initial($postinfo, $blog_id, $username, $password);
        /*
        ?>
        <textarea><?php print_r ($reqstring); ?></textarea>
        <?php
        */
        
        $response = $this->util_doxmlrpc(
            $reqstring,
            $xmlrpcurl
        );

        //print_r($response);

        if (is_array($response) && xmlrpc_is_fault($response)) {
            throw new portfolio_plugin_exception('xmlrpcfault', 'portfolio_wordpress');
        }
        
        return (int)$response;
    }

    public function util_uploadFile(array $fileinfo, $blog_id, $username, $password, $xmlrpcurl)
    {
        if(!isset($fileinfo))
            return -1; // signal failure

        $reqstring = $this->request_uploadFile($fileinfo, $blog_id, $username, $password);

        $response = $this->util_doxmlrpc(
            $reqstring,
            $xmlrpcurl
        );

        //print_r($response);

        if (is_array($response) && xmlrpc_is_fault($response)) {
            throw new portfolio_plugin_exception('xmlrpcfault', 'portfolio_wordpress');
        }
        
        return (int)$response;
    }


}

?>