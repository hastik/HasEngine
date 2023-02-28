<?php namespace ProcessWirel;

    $limit = $page->resource->getVal('limit',10);
    $query = "template='chat',limit=$limit";
    $threats = $page->children($query);

    $target = "table-".$page->resource->hash;
?>



<table role="grid" id="<?=$target?>" class="table table-striped">
    <thead>
        <tr>
            <td>Id</td>
            <td>Název</td>
            <td>Vytvořeno</td>
            <td>Poslední změna</td>
            <td>Poznámka</td>
            <td>Akce</td>
            
        </tr>
    </thead>

    <tbody id="tbody">

    <?php $i=0; foreach($threats as $thread): $i++; ?>
            <?php 
                //$source = $page->newSourceFromUrlAndPage($thread->url."/r-ai_openai_threads_table-row",$thread);
                $source = $page->newSourceFromRouterAndPage("ai/openai/threads/table-row",$thread);

        //dump($source);
        echo $source->include();
        //$uri = $page->url."/r-basic-page_test_table-row/q-dsdas=dsadas?selector=published=0,children.count>0&onpage=50&limit=1000&cacshe=60";
        //$link = $fragment->getUrl()->setQueryVar("published",0)->setQueryVar("limit",20)->setGetQuery("cache",20);
        //$output = wire("hypermedia")->getWiredFromPage($page->url."/r-basic-page_test_table-row/q-jedna=1&r=4",$page); 

        //echo $output->include();
    
    ?>

        <?php endforeach; ?>
</tbody>

</table>