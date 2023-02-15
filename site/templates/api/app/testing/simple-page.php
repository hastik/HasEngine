<?php namespace ProcessWire;

dump($page);

echo "tada";

$newResource = $page->newSourceFromUrl("/o-nas/r-app_testing_static");
dump($newResource);

echo $newResource->include();