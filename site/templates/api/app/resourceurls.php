<?php namespace ProcessWire;



$data = array(
    'limit' => '50',
    'cache' => '60',
    'xdata' => array(
        's7es' => array(
            "limit" => 50,
            "user" => 39,
            "fulltext" => "Nic"
        ),
        'w8r7' => array(

            "limit" => 10,
            "user" => "me",
            "published" => 0
        ),
    )
);

$coded = http_build_query($data);

$ref = "<a href='/app/r-app_resourceurls?$coded'>Odkaz</a>";
echo $ref;
dump($_GET);

echo $coded;

$encoded;
parse_str($coded,$encoded);
dump($encoded);

dump($page->_hypermedia);

?>