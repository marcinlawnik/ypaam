<?php

$startUrl = 'https://www.reddit.com/user/Your_Post_As_A_Movie.json';

// Array containing data extracted from posts
$posts = [];

// String of next identifier to process
$nextPageId = '';
//get json file
function get_web_page( $url )
{
    $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

    $options = array(

        CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
        CURLOPT_POST           =>false,        //set to GET
        CURLOPT_USERAGENT      => $user_agent, //set user agent
        CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
        CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        CURLOPT_SSL_VERIFYHOST => 0,        //dirty fix
        CURLOPT_SSL_VERIFYPEER => 0         //dirty fix
    );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;
    return $header;
}

$content = get_web_page($startUrl);

$array = json_decode($content['content'], true);

function convert_imgur_link($link) {
    //check if has extension
    $last5chars = substr($link, -5);
    $strpos = strpos($last5chars, '.');
    if ($strpos === false) {
        //didnt find dot
        return $link . '.png';
    }
    return $link;

}
function process ($comment) {
    $out = $comment['body'];

    $startsAt = strpos($out, "[") + strlen("[");
    $endsAt = strpos($out, "]", $startsAt);

    $photoshopText = substr($out, $startsAt, $endsAt - $startsAt);
    $startsAt = strpos($out, "(") + strlen("(");
    $endsAt = strpos($out, ")", $startsAt);
    $photoshopLink = substr($out, $startsAt, $endsAt - $startsAt);
    $linkUrl = convert_imgur_link($comment['link_url']);

    $posts[] = [
        'link_author' => $comment['link_author'],
        'link_url' => $comment['link_url'],
        'link_url_converted' => $linkUrl,
        'link_title' => $comment['link_title'],
        'comment_body' => $comment['body'],
        'photoshop_link' => $photoshopLink,
        'photoshop_text' => $photoshopText
    ];
    $html = '<tr>';

    $html .= '<div id="left_col"><td>Oryginalny obraz:'. $comment['link_title'] .'<br> <img src="'. $linkUrl .'" width="200px"></td></div>';


    $html .= '<div id="right_col"><td>Zmieniony obraz:'.$photoshopText.'<br> <img src="'. $photoshopLink .'" width="200px"></td></div></div></tr>';

    return $html;
}
echo '<html><head>
<style type="text/css">
#wrap {
   width:600px;
   margin:0 auto;
}
#left_col {
   float:left;
   width:300px;
}
#right_col {
   float:right;
   width:300px;
}
</style>
</head><body><table>
';

// Save next page identifier

$nextPageId = $array['data']['after'];
foreach ($array['data']['children'] as $comment) {
     echo process($comment['data']);
}
echo '</body></html>';