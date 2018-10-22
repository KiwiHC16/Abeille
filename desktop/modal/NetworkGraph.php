
<?php

    require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
    
?>

<p id="demo">Coucou</p>

<svg id="idDessin" xmlns="http://www.w3.org/2000/svg" width="1100px" height="1100px" onload="makeDraggable(evt)">
    <image x="0" y="0" width="1100px" height="1100px" xlink:href="/plugins/Abeille/Network/TestSVG/images/AbeilleLQI_MapData.png" ></image>
    <g id="legend"></g>
    <g id="lesVoisines"></g>
    <g id="lesTextes"></g>
    <g id="lesAbeillesText"></g>
    <g id="lesAbeilles"></g>
</svg></br>

<p id="idFiltre">idFiltre</p>

<div id="idFeatures"><p>idFeatures</p></div>

<div id="idRefresh"><p>idRefresh</p></div>

<div id="idUpload"><p>idUpload</p></div>


<?php include_file('desktop', 'networkGraph', 'js', 'Abeille'); ?>
