<?php namespace ProcessWire; ?>



<?php 

    //$pages = wire($pages)->findMany("template=basic-page");
    $pages = $page->children("limit=100");

    $hypermedia = new Hypermedia; 

?>


<table role="grid">

    <thead>
        <tr>
            <td>Name</td>
            <td>Count</td>
            <td>Edit</td>
        </tr>
    </thead>



    <tbody id="tbody">
<?php $i=0; foreach($pages as $page): $i++; ?>

    <?php $output = $hypermedia->get("/test/table-row?selector=published=0,children.count>0&onpage=50&page=1&cacshe=20",$page,"include")->fetch(); 

        echo $output;
    
    ?>

<?php endforeach; ?>
</tbody>

</table>