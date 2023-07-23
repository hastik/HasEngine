<?php namespace ProcessWire;  ?>


<?php 
    
  
?>




<?php 

?>


<tr hx-ext="preload" class="page tablerow" >
    <td><?=$page->id?></td>
    <td><?=$page->title?></td>
    <td><?=hmDateTime($page->created)?></td>
    <td><?=$page->updated ? hmDateTime($page->updated) : "";?></td>
</tr>
