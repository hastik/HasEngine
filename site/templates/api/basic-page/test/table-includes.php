<?php namespace ProcessWire; ?>



<?php 

    //$pages = wire($pages)->findMany("template=basic-page");
    //$pages = $page->children("limit=1000",["loadPages=false"]);
    //$pages = wire("pages")->find("template=basic-page,field=title|pocet",["loadPages=false"]); //,["loadPages=true"]
    //$pages = $page->children("field=title|pocet,limit=1000");
    //$pages = $page->children();
    //bd($pages);exit;
    //$pages = wire("pages")->findRaw("template=basic-page","title,pocet");
    //bd($test);
    $hm = $page->_hm;
    $limit = $hm->getQueryData("limit");
    $pages = $page->children("limit=$limit");
   


?>

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
    <a href="<?=$hm->setQueryData("limit",1)->getUrl()?>">1</a>
    <a href="<?=$hm->setQueryData("limit",10)->getUrl()?>">10</a>
    <a href="<?=$hm->setQueryData("limit",100)->getUrl()?>">100</a>
    <a href="<?=$hm->setQueryData("limit",500)->getUrl()?>">500</a>
    <a href="<?=$hm->setQueryData("limit",1000)->getUrl()?>">1000</a>

</div>

<table role="grid">

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

    <?php $output = $hm->get($page->url."/r-test_table-row?selector=published=0,children.count>0&onpage=50&limit=1000&cacshe=60",$page,"include"); 

        echo $output->render();
    
    ?>

<?php endforeach; ?>
</tbody>

</table>