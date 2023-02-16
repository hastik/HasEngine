<?php namespace ProcessWire;


$simplepages = $page->newSourceFromUrl("/o-nas/r-app_testing_inserted-simple-pages/q-jedna_eq_1?dva=2");

?>

<h2>Dashboard of all</h2>
Zdroj s voláním <?=$simplepages->input_url?> má tyto odkazy:
<ul>
    <li>Casted: <a href="<?=$simplepages->getCastedUrl()?>"><?=$simplepages->getCastedUrl()?></a></li>
    <li>Live: <a href="<?=$simplepages->getLiveUrl()?>"><?=$simplepages->getLiveUrl()?></a></li>
</ul>

<?=$simplepages->render();?>


<?php
    
    $table = $page->newSourceFromUrl("/test/r-app_testing_table-included/q-id_eq_1?dva=2");
    echo $table->render();
    dump($table->hash);
    

    $table2 = $page->newSourceFromUrl("/test/r-app_testing_table-included/q-id_eq_2?tri=3");
    echo $table2->render();
    dump($table2->hash);


?>
