<?php namespace ProcessWire; ?>



<?php 

    //$pages = wire($pages)->findMany("template=basic-page");
    $hm = $page->_hm;
    $limit = $hm->getQueryData("limit");
    $pages = $page->children("limit=$limit");
    //$pages = wire("pages")->findRaw("template=basic-page","title,pocet");

    dump($limit);

?>


<ul>
    <li><a href="<?=$hm->setQueryData("limit",5)->getUrl()?>">1</a></li>
    <li><a href="<?=$hm->setQueryData("limit",10)->getUrl()?>">10</a></li>
    <li><a href="<?=$hm->setQueryData("limit",100)->getUrl()?>">100</a></li>
    <li><a href="<?=$hm->setQueryData("limit",500)->getUrl()?>">500</a></li>
    <li><a href="<?=$hm->setQueryData("limit",1000)->getUrl()?>">1000</a></li>

</ul>

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
        <td><a href="<?=$page->url?>/r-test_table-row-edit" hx-get="<?=$page->url?>/test/table-row-edit" hx-target="closest tr" hx-swap="outerHTML"  href="x">Editovat</a></td>
    </tr>

<?php endforeach; ?>


</table>