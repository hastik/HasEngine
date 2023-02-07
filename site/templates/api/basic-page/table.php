<?php namespace ProcessWire;  ?>


<?php 

    $hasPages = $page->children($page->get("_hasGet")["children"]);


?>

<h2>Tady bude tabulka</h2>
<table>



<table>

    <thead>
        <tr>
            <td>Name</td>
            <td>Count</td>
            <td>Link</td>
        </tr>
    </thead>

</table>

<tr>
    <td><?=$page->title?></td>
    <td><?=$page->pocet?></td>
    <td><a href="<?=$page->url?>"><?=$page->url?></a></td>
</tr>
