<?php namespace ProcessWire;

use Processwire\HypermediaResource;

 ?>
<script src="https://unpkg.com/htmx.org@1.8.5"></script>
<?php
    
   
    $limit = $page->resource->getVal('limit',10);
    $min_count = $page->resource->getVal("count",70);
    $order = $page->resource->getVal("sort","id");

    $query = "pocet>$min_count, sort=$order, limit=$limit";
 
    $basicPages = $page->children($query);
    //dump($basicPages);
?>

<h3>Tabulka </h3>



<h4>Počet položek</h4>

<?php $resource = $page->cloneResource() ?>
<?php

    $filter_count = array();
    $filter_count = array(5,10,20,50);
    $name = "limit";
    $target = "#tableincludes";
    $select = "#tableincludes";

    $options = array();
 
    foreach($filter_count as $value){
        
        $resource->setGetVal($name,$value);
        
        $live_link = $resource->update()->getLiveUrl();
        
        $casted_link = $resource->getCastedUrl();
        $options[]= $page->hxLink($value,$live_link,$casted_link,"#tableincludes","#tableincludes");
    }


?>
<?php unset($resource) ?>
<ul>
    <?php foreach($options as $option) : ?>
        <li><?=$option?></li>
    <?php endforeach; ?>
</ul>



<style>

    .pills{
        margin-bottom: 1rem;
    }
    .pills > * {
        font-size: .6em;
        display: inline-block;
        padding: 0.5em 1em;
        border: 1px solid grey;
        margin-right:0.5em;
        border-radius:20%;
    }
    .pills > *:hover {
        text-decoration: none;
        background: #f6f6f6;
    }

</style>
<div class="pills">
    
</div>

<table role="grid" id="tableincludes">

    <thead>
        <tr>
            <td>No.</td>
            <td>Name</td>
            <td>Count</td>
            <td>Edit</td>
        </tr>
    </thead>



    <tbody id="tbody">

<?php $i=0; foreach($basicPages as $input_page): $i++; ?>

    <?php 
        $source = $page->newSourceFromUrlAndPage($page->url."/r-app_testing_table-row/q-id=4",$input_page);

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