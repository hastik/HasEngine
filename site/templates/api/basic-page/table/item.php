<?php namespace ProcessWire;  ?>

<?php bd($page);  ?>

<tr>
    <td><?=$page->title?></td>
    <td><?=$page->pocet?></td>
    <td><a href="<?=$page->url?>"><?=$page->url?></a></td>
    <td><a href="<?=wire()->input->url?>"><?=wire()->input->httpUrl?></a></td>
</tr>
