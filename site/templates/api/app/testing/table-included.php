<?php namespace ProcessWire;

use Processwire\HypermediaResource;

 ?>



<?php
    $page->resource->getVal('limit',25);
    
    $min_count = $page->resource->getVal("count",70);
    $order = $page->resource->getVal("sort","id");

    $query = "pocet>$min_count, sort=$order, limit=$limit";

    $basicPages = $page->children($query);
    
?>

<h4>Počet položek</h4>

<?php 
    $filter= new HypermediaResource($page);
    $filter_count = array(5,10,20,50);
    $filter_var = "limit";
    $filter_target = "#tableincludes";
    $filter_select = "#tableincludes";
    
    $options = array();
    
    foreach($filter_count as $value){
        $live_link = $resource->setQueryVal($name,$value)->getLiveUrl();
        $casted_link = $resource->setQueryVal($name,$value)->getCastedUrl();
        $options[]= wire("hypermedia")->hxLink($value,$live_link,$casted_link,"#tableincludes","#tableincludes");
    }

    $states = new HypermediaObject(new HypermediaResource($page));
    $states->setValues(array(
        0 => "Unpublished",
        1 => "Published",
        2 => "Hidden"
    ));
    $states->setVar("pubstate");
    $states->setTarget("#tableincludes");


?>
<?php unset($resource) ?>
<ul>
    <?php foreach($options as $option) : ?>
        <li><?=$option?></li>
    <?php endforeach; ?>
</ul>


<h4>Minimum</h4>
<ul>
    <?php $resource = clone $page->_hypermedia; 
        $hash = substr(md5($resource->url),0,4);
    ?>
    <li><a href="<?=$resource->setGetVal("count",50)->getUrl()?>">50</a></li>
    <li><a href="<?=$resource->setGetVal("count",80)->getUrl()?>">80</a></li>
    <li><a href="<?=$resource->setGetVal("count",100)->getUrl()?>">100</a></li>
    <li><a href="<?=$resource->setGetVal("count",1100)->getUrl()?>">1100</a></li>
    <?php unset($resource) ?>
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

<?php $i=0; foreach($pages as $page): $i++; ?>

    <?php 
        //$uri = $page->url."/r-basic-page_test_table-row/q-dsdas=dsadas?selector=published=0,children.count>0&onpage=50&limit=1000&cacshe=60";
        //$link = $fragment->getUrl()->setQueryVar("published",0)->setQueryVar("limit",20)->setGetQuery("cache",20);
        $output = wire("hypermedia")->getWiredFromPage($page->url."/r-basic-page_test_table-row/q-jedna=1&r=4",$page); 

        echo $output->include();
    
    ?>

<?php endforeach; ?>
</tbody>

</table>