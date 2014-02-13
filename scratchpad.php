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
    }

    // PRIVATE:

    /**
     * Create an XML-RPC request string that retrieves an array of blogs on the
     * WordPress server that have the requesting user as a contributor.
     *
     * @param string    $username   the username of the user in question
     * @param string    $password   the password of the user in question     
     *
     * @return string the resultant XML-RPC request string
     */
    private function request_getUserBlogs($username = null, $password = null)
    {
        if(!isset($username))
            $username = $this->m_keys['username'];
        if(!isset($password))
            $password = $this->m_keys['password'];

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
     * @param int       $blog_id   the username of the user in question
     * @param string    $username   the username of the user in question
     * @param string    $password   the password of the user in question    
     *
     * @return string   the resultant XML-RPC request string
     */
    private function request_getProfile($blog_id = null, $username = null, $password = null)
    {
        if(!isset($blog_id))
            $blog_id = $this->m_keys['blog_id'];
        if(!isset($username))
            $username = $this->m_keys['username'];
        if(!isset($password))
            $password = $this->m_keys['password'];

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



    private function request_newPost_initial($blog_id = null, $username = null, $password = null, $post_type, $post_status)
    {
        if(!isset($blog_id))
            $blog_id = $this->m_keys['blog_id'];
        if(!isset($username))
            $username = $this->m_keys['username'];
        if(!isset($password))
            $password = $this->m_keys['password'];

        $method = xmlrpc_encode_request(
            'wp.newPost',
            array(
                $this->m_keys['blog_id'],
                $this->m_keys['username'],
                $this->m_keys['password'],
                array(

                )
            )
        );

    }

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

        if ($response && xmlrpc_is_fault($response)) {
            // TODO: signal fault
        }


        return xmlrpc_decode($file);
    }

    // PUBLIC:
    public function useBlog($p_idx = 0)
    {
        $response = $this->do_xmlrpc(
            $this->request_getUserBlogs()
        );
        /*
        $request = $this->request_getUserBlogs();

        $context = stream_context_create(array('http' => array(
            'method' => "POST",
            'header' => "Content-Type: text/xml",
            'content' => $request
        )));

        //echo $request;
        //echo $this->m_keys['wordpressurl'] . "xmlrpc.php";

        $file = file_get_contents(
            $this->m_keys['xmlrpcurl'],
            false,
            $context
        );

        //print_r ($file);
        $response = xmlrpc_decode($file);
            */
        print_r($response); 
        if ($response && xmlrpc_is_fault($response)) {
            //trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
            
            return 0;
        } else {
            return count($response) > 0 ? $response[$p_idx]['blogid'] : 0;
        }
    }

    public function getUserId($blog_id, $username, $password)
    {

        if(!isset($blog_id))
            $blog_id = $this->m_keys['blog_id'];
        if(!isset($username))
            $username = $this->m_keys['username'];
        if(!isset($password))
            $password = $this->m_keys['password'];


        $request = $this->request_getProfile($blog_id, $username, $password);

        $context = stream_context_create(array('http' => array(
            'method' => "POST",
            'header' => "Content-Type: text/xml",
            'content' => $request
        )));


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

