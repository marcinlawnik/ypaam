<?php

$startUrl = 'https://www.reddit.com/user/Your_Post_As_A_Movie.json';

// Process next page

if(isset($_GET['after'])) {
    $startUrl .= '?after=' . $_GET['after'];
}

$storageDir = __DIR__ . '/storage/';

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

// Save content to file



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

function processJSON($comment) {
    $out = $comment['body'];

    $startsAt = strpos($out, "[") + strlen("[");
    $endsAt = strpos($out, "]", $startsAt);

    $photoshopText = substr($out, $startsAt, $endsAt - $startsAt);
    $startsAt = strpos($out, "(") + strlen("(");
    $endsAt = strpos($out, ")", $startsAt);
    $photoshopLink = substr($out, $startsAt, $endsAt - $startsAt);
    $linkUrl = convert_imgur_link($comment['link_url']);

    $post = [
        'link_author' => $comment['link_author'],
        'link_url' => $comment['link_url'],
        'link_url_converted' => $linkUrl,
        'link_title' => $comment['link_title'],
        'comment_body' => $comment['body'],
        'photoshop_link' => $photoshopLink,
        'photoshop_text' => $photoshopText
    ];
    return $post;

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

echo '<html><head></head><body><table>';

// Save next page identifier

$nextPageId = $array['data']['after'];
foreach ($array['data']['children'] as $comment) {
     echo process($comment['data']);
     $posts[] = processJson($comment['data']);
}
//Add link to next page

echo '<a href="list.php?after=' . $nextPageId . '">Next page </a>';

echo '</body></html>';


// Save processed data to file
$filename = $storageDir . 'ypaam_scrape_' . date('Y_m_d_H_i_s_') . $nextPageId . '.json';

$output = [
    'posts' => $posts,
    'after' => $nextPageId
];

file_put_contents($filename, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));