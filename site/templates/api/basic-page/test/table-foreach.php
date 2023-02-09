<?php namespace ProcessWire; ?>



<?php 

    //$pages = wire($pages)->findMany("template=basic-page");
    $pages = $page->children("limit=1000");

    foreach($pages as $page){
        
    } 

?>


<table role="grid">

    <thead>
        <tr>
            <td>No.</td>
            <td>Name</td>
            <td>Count</td>
            <td>Edit</td>
        </tr>
    </thead>




<?php $i=0; foreach($pages as $page): $i++; ?>

    <tr>
        <td><?=$i?></td>
        <td><?=$page->title?></td>
        <td><?=$page->pocet?></td>
        <td><a hx-get="<?=$page->url?>/test/table-row-edit" hx-target="closest tr" hx-swap="outerHTML"  href="x">Editovat</a></td>
    </tr>

<?php endforeach; ?>


</table>