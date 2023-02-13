<?php namespace ProcessWire;  ?>

<?php 
  
?>


<tr class="page" >
    <td></td>
    <td><?=$page->title?></td>
    <td><?=$page->pocet?></td>
    <td>
        <?php if (wire("user")->isLoggedin()): ?>    
            <a hx-get="<?=$page->url?>/r-basic-page_test_table-row-edit/q-e_eq_3?d=s" hx-target="closest tr" hx-swap="outerHTML"  href="x">Editovat</a></td>
        <?php endif; ?>
</tr>