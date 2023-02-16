<?php namespace ProcessWire;


?>

<h4>From resource</h4>


<?php


    $fromresource = $page->newResource("fromsource")
        ->setPageUrl("/reference")
        ->setRouter("app/testing/static")
        ->setQueryVal("limit",10)
        ->setQueryVal("count",100)
        ->setGetVal("cache",60)
        ->update();
    

    dump($fromresource);
    dump($fromresource->data);
    dump($fromresource->master_data);


    $cloned = $page->cloneResource("cloned")
        ->setQueryVal("page",2)
        ->update();

?>

<?=$fromresource->getCastedUrl();?><br>
<?=$fromresource->getLiveUrl();?>


<?=$page->newSourceFromResource($fromresource)->include();?>

<hr>

<?=$cloned->getLiveUrl();?>