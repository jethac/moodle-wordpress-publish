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

class helper_wordpress_xmlrpc
{
    public $m_keys;

    // constructor
    public function __construct($wordpressURL, $username, $password)
    {  
        $this->m_keys['wordpressurl'] = $wordpressURL;
        $this->m_keys['xmlrpcurl'] = $this->m_keys['wordpressurl'] . "xmlrpc.php";
        $this->m_keys['username'] = $username;
        $this->m_keys['password'] = $password;

        // use the first blog we find at that wordpress url
        // @todo: wordpress multi-blog support?
        $this->m_keys['blog_id'] = $this->useBlog();
        // echo 'blog id:' . $this->m_keys['blog_id'] . '<br/>';

        // get the user id        
        $foo = $this->getUserId();
        $this->m_keys['user_id'] = $foo[0];
        $this->m_keys['display_name'] = $foo[1];
        //echo 'user id:' . $this->m_keys['user_id'] . '<br/>';
        //echo 'user name:' . $this->m_keys['display_name'] . '<br/>';
    }


    // WordPress API REQUEST STRING BUILDERS ##################################

    /**
     * Create an XML-RPC request string that retrieves an array of blogs on the
     * WordPress server that have the requesting user as a contributor.
     *
     * @param string    username   the username of the user
     * @param string    password   the password of the user  
     *
     * @return string the resultant XML-RPC request string
     */
    private function request_getUserBlogs($options = null)
    {
        if(!isset($options))
            $options = $this->m_keys;

        // @todo: bulletproof this
        $method = xmlrpc_encode_request(
            'wp.getUsersBlogs', 
            array(
                $options['username'],
                $options['password']
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
    private function request_getProfile($options = null)
    {
        if(!isset($options))
            $options = $this->m_keys;

        // TODO: bulletproof this
        $method = xmlrpc_encode_request(
            'wp.getProfile', 
            array(
                $options['blog_id'],
                $options['username'],
                $options['password']
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
    private function request_newPost_initial($postinfo, $options = null)
    {
        if(!isset($options))
            $options = $this->m_keys;

        $method = xmlrpc_encode_request(
            'wp.newPost',
            array(
                $options['blog_id'],
                $options['username'],
                $options['password'],
                array(
                    // post_type
                    'post_type' => isset($postinfo['post_type'])? $postinfo['post_type'] : 'page',
                    // post_status
                    'post_status' => isset($postinfo['post_status'])? $postinfo['post_status'] : 'publish',
                    // post_title
                    'post_title' => $postinfo['post_title'],
                    // post_author
                    'post_author' => $options['user_id'],
                    // post_excerpt
                    'post_excerpt' => substr($postinfo['post_content'], 0, 55),
                    'post_content' => $postinfo['post_content']
                )
            )
        );

        print_r($method);

        return $method;
    }


    // HELPERS ################################################################

    /**
     * Perform an XML-RPC request, given a well-formed XML-RPC request and a url.
     * 
     * @return mixed    an array, integer, string or boolean according to the
     *                  response received
     */
    private function do_xmlrpc($requeststring, $xmlrpcurl = null)
    {

        if(!isset($xmlrpcurl))
            $xmlrpcurl = $this->m_keys['xmlrpcurl'];

        $context = stream_context_create(array('http' => array(
            'method' => "POST",
            'header' => "Content-Type: text/xml",
            'content' => $requeststring
        )));

        $file = file_get_contents(
            $this->m_keys['xmlrpcurl'],
            false,
            $context
        );

        $response = xmlrpc_decode($file);
        print_r($response);
        if (is_array($response) && xmlrpc_is_fault($response)) {
            // TODO: signal fault
        }

        return $response;
    }


    // PUBLIC FUNCTIONS ###################################################

    // PUBLIC:
    public function useBlog($p_idx = 0)
    {
        $response = $this->do_xmlrpc(
            $this->request_getUserBlogs()
        );
        //print_r($response); 
        if (is_array($response) && xmlrpc_is_fault($response)) {
            //trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
            
            return 0;
        } else {
            return count($response) > 0 ? $response[$p_idx]['blogid'] : 0;
        }
    }

    public function getUserId($options = null)//$blog_id, $username, $password)
    {
        if(!isset($options))
            $options = $this->m_keys;


        $response = $this->do_xmlrpc(
            $this->request_getProfile($options)
        );
        
        //print_r($response); 
        return array($response['user_id'], $response['display_name']);
    }

    public function makePostNaive($postinfo, $options = null)
    {
        if(!isset($postinfo))
            return -1; // signal failure

        if(!isset($options))
            $options = $this->m_keys;

        $response = $this->do_xmlrpc(
            $this->request_newPost_initial($postinfo, $options)
        );
        //print_r($response);

        $this->m_keys['stored_post_id'] = (int)$response;
    }
}
?>