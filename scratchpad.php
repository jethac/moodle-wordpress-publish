derp

<?php

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
        echo 'blog id:' . $this->m_keys['blog_id'];

        // get the user id        
        $foo = $this->getUserId();
        $this->m_keys['user_id'] = $foo[0];
        $this->m_keys['display_name'] = $foo[1];
        echo 'user id:' . $this->m_keys['user_id'] . '<br/>';
        echo 'user name:' . $this->m_keys['display_name'] . '<br/>';
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
    private function request_getUserBlogs(&$options = null)//$username = null, $password = null)
    {
        if(!isset($options))
            $options = &$this->m_keys;

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
    private function request_getProfile(&$options = null)//$blog_id = null, $username = null, $password = null)
    {
        if(!isset($options))
            $options = &$this->m_keys;

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
    private function request_newPost_initial(&$postinfo, &$options = null)//$blog_id = null, $username = null, $password = null, $post_type, $post_status)
    {
        if(!isset($options))
            $options = &$this->m_keys;

        $method = xmlrpc_encode_request(
            'wp.newPost',
            array(
                $options['blog_id'],
                $options['username'],
                $options['password'],
                array(

                )
            )
        );

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
        if ($response && xmlrpc_is_fault($response)) {
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
        print_r($response); 
        if ($response && xmlrpc_is_fault($response)) {
            //trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
            
            return 0;
        } else {
            return count($response) > 0 ? $response[$p_idx]['blogid'] : 0;
        }
    }

    public function getUserId(&$options = null)//$blog_id, $username, $password)
    {
        if(!isset($options))
            $options = &$this->m_keys;


        $response = $this->do_xmlrpc(
            $this->request_getProfile($options)
        );
        
        //print_r($response); 
        return array($response['user_id'], $response['display_name']);
    }
}





$assignmenttoexport = array(
    'title' => 'My Assignment',
    'body' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    'submittedfile' => null
);


?>
<pre>
<?php
print_r($assignmenttoexport);
?>
</pre>
<textarea style="width:480px; height:640px;"><?php

// instantiate a site interface
$mysiteinterface = new helper_wordpress_xmlrpc("http://temp.jethachan.net/",'jethac', 'e@stc1vl');

// get the id of the blog (usually 1, but let's not be messy.)




//echo helper_wordpress_xmlrpc::request_getUserBlogs("jethac", "e@stc1vl");
?>
</textarea>

