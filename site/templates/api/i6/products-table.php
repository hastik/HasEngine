<?php namespace ProcessWire; ?>



<?php

    $limit = $page->resource->getVal('limit',10);
    $query = "template='basic-page',limit=$limit";
    $products = wire()->pages->findOne("/produkty")->children($query);

    dump(wire()->pages->findOne("/produkty"));
    dump($products);
?>

    <div class="card">
            <div class="card-inner">
               

               
            </div>
    </div>


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

    <?php $i=0; foreach($products as $product): $i++; ?>
            <?php 
                //$source = $page->newSourceFromUrlAndPage($thread->url."/r-ai_openai_threads_table-row",$thread);
                $source = $page->newSourceFromRouterAndPage("i6/products_table-row",$product);

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


<?php //Templater::Include(__DIR__."/./../corpus.template.php","fragment");

                   