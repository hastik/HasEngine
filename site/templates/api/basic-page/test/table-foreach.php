<?php namespace ProcessWire; ?>



<?php 

    //$pages = wire($pages)->findMany("template=basic-page");
    $pages = $page->children("limit=100");

    foreach($pages as $page){
        
    } 

?>


<table role="grid">

    <thead>
        <tr>
            <td>Name</td>
            <td>Count</td>
            <td>Edit</td>
        </tr>
    </thead>




<?php foreach($pages as $page): ?>

    <tr>
        <td><?=$page->title?></td>
        <td><?=$page->pocet?></td>
        <td><a hx-get="<?=$page->url?>/test/table-row-edit" hx-target="closest tr" hx-swap="outerHTML"  href="x">Editovat</a></td>
    </tr>

<?php endforeach; ?>


</table>