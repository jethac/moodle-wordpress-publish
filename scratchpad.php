<?php

require_once('helper_wordpress_xmlrpc.php');

$assignmenttoexport = array(
    'post_title' => 'My Assignment',
    'post_content' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    'submittedfile' => null
);


?>
<pre>
<?php
print_r($assignmenttoexport);

// instantiate a site interface
$mysiteinterface = new helper_wordpress_xmlrpc("http://temp.jethachan.net/",'jethac', 'e@stc1vl');

?>
</pre>
<textarea style="width:480px; height:640px;">
<?php
// maek post!
$mysiteinterface->makePostNaive($assignmenttoexport);


//echo helper_wordpress_xmlrpc::request_getUserBlogs("jethac", "e@stc1vl");
?>
</textarea>

