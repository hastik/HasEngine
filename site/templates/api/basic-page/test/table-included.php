<?php namespace ProcessWire; ?>



<?php 

    $limit = $page->_hypermedia->getVal("limit",50);
    
    $min_count = $page->_hypermedia->getVal("count",70);
    $order = $page->_hypermedia->getVal("sort","id");
    $query = "pocet>$min_count, sort=$order, limit=$limit";
    $pages = $page->children($query);
    

?>

<h4>Počet položek</h4>

<?php

    $filter_count = array();
    $filter_count = array(5,10,20,40);
    $name = "limit";
    $target = "#tableincludes";
    $select = "#tableincludes";

    $options = array();
    foreach($filter_count as $value){
        $link=$page->_hypermedia->setQueryVal($name,$value)->getUrl();
        $options[]= wire("hypermedia")->hxLink($value,$link,"#tableincludes","#tableincludes");
    }
    bd($options);

?>
<ul>
    <?php foreach($options as $option) : ?>
        <li><?=$option?></li>
    <?php endforeach; ?>
</ul>


<h4>Minimum</h4>
<ul>
    <li><a href="<?=$page->_hypermedia->setQueryVal("count",50)->getUrl()?>">50</a></li>
    <li><a href="<?=$page->_hypermedia->setQueryVal("count",80)->getUrl()?>">80</a></li>
    <li><a href="<?=$page->_hypermedia->setQueryVal("count",100)->getUrl()?>">100</a></li>
    <li><a href="<?=$page->_hypermedia->setQueryVal("count",1000)->getUrl()?>">1000</a></li>
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
        $uri = $page->url."/r-basic-page_test_table-row/q-dsdas=dsadas?selector=published=0,children.count>0&onpage=50&limit=1000&cacshe=60";

        //$link = $fragment->getUrl()->setQueryVar("published",0)->setQueryVar("limit",20)->setGetQuery("cache",20);

    $output = wire("hypermedia")->getWiredFromPage($page->url."/r-basic-page_test_table-row/q-dsdas=dsadas?selector=published=0,children.count>0&onpage=50&limit=1000&cacshe=60",$page); 

        echo $output->include();
    
    ?>

<?php endforeach; ?>
</tbody>

</table>