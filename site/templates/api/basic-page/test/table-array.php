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
   
    $limit = 50;
    $parent = wire("pages")->get("/test");
    $pages = wire("pages")->findRaw("parent=$parent,template=basic-page,limit=$limit","title,pocet,url");;


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

    <?php $output = wire("hypermedia")->getWiredFromArray($page["url"]."/r-basic-page_test_table-row/q-dsdas=dsadas?selector=published=0,children.count>0&onpage=50&limit=1000&cacshe=60",$page); 

        echo $output->include();
    
    ?>

<?php endforeach; ?>
</tbody>

</table>