<?php namespace ProcessWire;


$onas = $page->newSourceFromUrl("/o-nas/r-app_testing_simple-page/q-jedna_eq_1?dva=2");
$sluzby = $page->newSourceFromUrl("/sluzby/r-app_testing_simple-page");

?>

<h2>Inserted simple pages</h2>
Zdroj s voláním <?=$onas->input_url?> má tyto odkazy:
<ul>
    <li>Casted: <a href="<?=$onas->getCastedUrl()?>"><?=$onas->getCastedUrl()?></a></li>
    <li>Live: <a href="<?=$onas->getLiveUrl()?>"><?=$onas->getLiveUrl()?></a></li>
</ul>

<?php

echo $onas->render();
echo $sluzby->include();
?>