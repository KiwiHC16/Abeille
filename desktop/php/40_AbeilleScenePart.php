<label>Scenes</label>
<a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/Scenes.html"><i class="fas fa-book"></i>Documentation</a>

<div id="the whole thing" style="height:100%; width:100%; overflow: hidden;">
    <div id="leftMargin" style="float: left; width:10%;">.
    </div>
    <div id="leftThing" style="float: left; width:40%;">
        <table>
            <thead>
                <tr>
                    <th>{{Module}}</th>
                    <th>{{Telecommande}}</th>
                    <th>{{Membre}}</th>
                </tr>
            </thead>
            <tbody>
<?php
                $abeille = new Abeille();
                $commandIEEE = new AbeilleCmd();

                foreach ($eqLogics as $key => $eqLogic) {

                    $name= "";
                    $sceneMember = "";
                    $sceneTele = "";
                    $print=0;

                    $abeilleId = $abeille->byLogicalId($eqLogic->getLogicalId(), 'Abeille')->getId();

                    $name = $eqLogic->getHumanName(true);

                    if ( $commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Scene-Membership') ) {
                        if ( strlen($commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Scene-Membership')->execCmd())>2 ) {

                            $sceneMember = str_replace('-',' ',$commandIEEE->byEqLogicIdAndLogicalId($abeilleId, 'Scene-Membership')->execCmd());
                            $print = 1;
                        }
                    }

                    if ( strlen($eqLogic->getConfiguration('Scene'))>3 ) {
                        $sceneTele = $eqLogic->getConfiguration('Scene');
                        $print = 1;
                    }
                    
                    if ( $print ) echo '<tr><td class="one">'.$name.'</td><td align="center" class="one">'.$sceneTele.'</td><td align="center" class="one">'.$sceneMember.'</td></tr>';
                }
?>
            </tbody>
        </table>
    </div>
    <div id="rightThing" style="float: left; width:40%;">
        <table>
            <tr>
                <td>
                    <label control-label data-toggle="tooltip" title="en hex de 0000 a ffff, probablement celui que vous avez récuperé de votre télécommande.">Group Id</label>
                    <input type="text" name="groupIdScene1" placeholder="XXXX">
                    <br>
                    <input type="submit" name="submitButton" value="Get Scene Membership">
                    <input type="submit" name="submitButton" value="Remove All Scene">
                </td>
            </tr>
            <tr>
                <td>
                    <label>Group Id</label><input type="text" name="groupIdScene2" placeholder="XXXX">
                    <label>Scene Id</label><input type="text" name="sceneID" placeholder="YY">
                    <br>
                    <input type="submit" name="submitButton" value="View Scene">
                    <input type="submit" name="submitButton" value="Add Scene">
                    <input type="submit" name="submitButton" value="Remove Scene">
                    <input type="submit" name="submitButton" value="Store Scene">
                    <input type="submit" name="submitButton" value="Recall Scene">
                    <input type="submit" name="submitButton" value="scene Group Recall">
                </td>
            </tr>
        </table>
    </div>
</div>

<br>