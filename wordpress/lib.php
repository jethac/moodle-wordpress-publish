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

require_once('helper_wordpress_xmlrpc.php');

class portfolio_plugin_wordpress extends portfolio_plugin_push_base
{
    private $xmlrpchelper;

    public static function get_name()
    {
        return get_string('pluginname', 'portfolio_wordpress');
    }

    // PLUGIN FUNCTIONS ###################################################

    /** 
     * Does anything necessary to prepare the package for sending.
     */
    public function prepare_package()
    {
        $files = $this->exporter->get_tempfiles();

        //print_r($this->exporter);

        return true;
    }

    /**
     * Actually send the package to the remote system.
     */
    public function send_package()
    {
        global $DB;

        $userid = $this->user->id;//USER->id;
        $options = $DB->get_records_menu(
            'portfolio_instance_user',
            array(
                'instance'=> $this->id,
                'userid' => $userid
            ),
            '',
            'name, value'

        );
        //print_r($options);

        $tmproot = make_temp_directory('wordpressupload');

        $files = $this->exporter->get_tempfiles();
/*
        ?>
        <pre><?php print_r($files); ?></pre> 
        <?php
        */
        foreach ($files as $file) {

            // TODO: This probably isn't the best way of doing this.
            $tmpfilepath = $tmproot .'/'.$file->get_contenthash();
            $file->copy_content_to($tmpfilepath);    
            $domdoc = new DOMDocument();
            $domdoc->loadHTMLFile($tmpfilepath);


            $cells = $domdoc->getElementsByTagName('td');
            $td_header = $cells->item(1);
            $td_content = $cells->item($cells->length-1);
        
            $div_subject = $td_header->getElementsByTagName('div')->item(0);

            // Extract:
            //  - subject
            $field_subject = $div_subject->textContent;            
            //  - message
            $field_message = $domdoc->saveHTML($td_content);//$td_content->textContent;

            // Prepare:                
            $postinfo = array(
                    'post_title' => $field_subject,
                    'post_content' => $field_message,
                    'post_author' => $options['wordpress-user-id']
                );

            // Post:
            $this->util_makePostNaive(
                $postinfo,
                $options['wordpress-blog-id'],//$blog_id,
                $options['wordpress-username'],//$username,
                $options['wordpress-password'],//$password,
                $options['wordpress-url'] . '/xmlrpc.php'//$xmlrpcurl
            );
            //echo $file->get_filepath();
            /*
        ?>
        <pre><?php print_r($div_subject); ?></pre>
        <h3><?php echo $field_subject; ?></h3>
        <div><?php echo $field_message; ?></div>
        <?php
        */

        }

        /*
        ?>
        <pre><?php print_r(); ?></pre>
        <?php
        
        $post = $this->exporter->caller->post;
        */

        /*
        $postinfo = array(
                'post_title' => $post['subject'],
                'post_content' => $post['message'],
                'post_author' => $options['wordpress-user-id']
            );
            */


        //print_r($this);//->userconfig);
        /*
        $this->util_makePostNaive(
            array(
                'post_title' => $this->exporter['subject'],
                'post_content' => $this->exporter['message'],
                'post_author' => $options['wordpress-user-id']
            ),
            $options['wordpress-blog-id'],//$blog_id,
            $options['wordpress-username'],//$username,
            $options['wordpress-password'],//$password,
            $options['wordpress-url'] . '/xmlrpc.php'//$xmlrpcurl
        );*/
        

        //print_r($this->exporter);
        //$this->util_makePostNaive(
        //);
    }

    /**
     * Return a url to present to the user as a 'continue to their portfolio'
     * link.
     */
    public function get_interactive_continue_url()
    {
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
            get_string('defaulttext-wordpress-url', 'portfolio_wordpress')
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
            get_string('defaulttext-wordpress-username', 'portfolio_wordpress')
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
            get_string('defaulttext-wordpress-password', 'portfolio_wordpress')
        );

        // rules
        $strrequired = get_string('required');
        $mform->addRule('wordpress-url', $strrequired, 'required', null, 'client');
        $mform->addRule('wordpress-username', $strrequired, 'required', null, 'client');
        $mform->addRule('wordpress-password', $strrequired, 'required', null, 'client');

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
            'wordpress-user-nicename'
        );
    }

    public function user_config_validation($data)
    {
        global $USER;
        
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
        


        $this->set_user_config(
            array(
                'wordpress-blog-id' => $wp_blog_id,
                'wordpress-user-id' => $wp_userdata[0],
                'wordpress-user-nicename' => $wp_userdata[1]                
            ),
            $USER->id
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


    // WORDPRESS XML-RPC API UTILITIES ####################################

    /**
     * Perform an XML-RPC request, given a well-formed XML-RPC request and a url.
     * 
     * @return mixed    an array, integer, string or boolean according to the
     *                  response received
     */
    private function util_doxmlrpc($requeststring, $xmlrpcurl = null)
    {

        if(!isset($xmlrpcurl))
            $xmlrpcurl = $this->m_keys['xmlrpcurl'];

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

        $response = xmlrpc_decode($file);
        //print_r($file);
        //print_r($response);
        if (is_array($response) && xmlrpc_is_fault($response)) {
            // TODO: signal fault
        }

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

    public function util_makePostNaive($postinfo, $blog_id, $username, $password, $xmlrpcurl)
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

        print_r($response);

        if (is_array($response) && xmlrpc_is_fault($response)) {
            throw new portfolio_plugin_exception('xmlrpcfault', 'portfolio_wordpress');
        }
        
        return (int)$response;
    }
}

?>