<?php namespace ProcessWire; ?>



<?php 

    //$pages = wire($pages)->findMany("template=basic-page");
    //$limit = wire($page->_hypermedia)->getQueryData("limit");
    $limit = 50;
    $pages = $page->children("limit=$limit");
    //$pages = wire("pages")->findRaw("template=basic-page","title,pocet");

    //dump($limit);

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




<?php $i=0; foreach($pages as $page): $i++; ?>

    <tr>
        <td><?=$i?></td>
        <td><?=$page->title?></td>
        <td><?=$page->pocet?></td>
        <td><a href="<?=$page->url?>/r-basic-page_test_table-row-edit" hx-get="<?=$page->url?>/r-basic-page_test_table-row-edit" hx-target="closest tr" hx-swap="outerHTML"  href="x">Editovat</a></td>
    </tr>

<?php endforeach; ?>


</table>