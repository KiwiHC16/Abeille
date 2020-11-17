<legend><i class="fa fa-cog"></i> {{Gestion}}</legend>

<div class="eqLogicThumbnailContainer">
    <div class="cursor eqLogicAction" data-action="gotoPluginConf"
            style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
        <center>
            <i class="fa fa-wrench" style="font-size : 5em;color:#767676;"></i>
        </center>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
    </div>
<?php
    foreach ( $outils as $key=>$outil ) {
            echo '<div class="cursor" id="'.$outil['bouton'].'" style="background-color: rgb(255, 255, 255); height: 140px; margin-bottom: 10px; padding: 5px; border-top-left-radius: 2px; border-top-right-radius: 2px; border-bottom-right-radius: 2px; border-bottom-left-radius: 2px; width: 160px; margin-left: 10px; position: absolute; left: 170px; top: 0px;">';
            echo '  <center>';
            echo '      <i class="fa '.$outil['icon'].'" style="font-size : 6em;color:#767676;"></i>';
            echo '  </center>';
            echo '  <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>'.$outil['text'].'</center></span>';
            echo '</div>';
    }
?>
</div>
