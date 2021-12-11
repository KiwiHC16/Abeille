<!-- This file displays equipment commands.
     Included by 'AbeilleEq-Advanced.php' -->

<?php
    function addDocButton($chapter) {
        // $urlUserMan = "https://kiwihc16.github.io/AbeilleDoc"; // Constant defined in Abeille config
        echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.$urlUserMan.'/'.$chapter.'"><i class="fas fa-book"></i> ?</a>';
    }

    /* Returns current cmd value identified by its Jeedom name */
    function getCmdValueByName($eqId, $cmdName) {
        $cmd = AbeilleCmd::byEqLogicIdCmdName($eqId, $cmdName);
        if (!is_object($cmd))
            return "";
        return $cmd->execCmd();
    }

    /* Returns current cmd value identified by its Jeedom logical ID name */
    function getCmdValueByLogicId($eqId, $logicId) {
        $cmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, $logicId);
        if (!is_object($cmd))
            return "";
        return $cmd->execCmd();
    }

    /* Returns cmd ID identified by its Jeedom name */
    function getCmdIdByName($eqId, $cmdName) {
        $cmd = AbeilleCmd::byEqLogicIdCmdName($eqId, $cmdName);
        if (!is_object($cmd))
            return "";
        return $cmd->getId();
    }

    /* Returns cmd ID identified by its Jeedom logical ID name */
    function getCmdIdByLogicId($eqId, $logicId) {
        $cmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, $logicId);
        if (!is_object($cmd))
            return "";
        return $cmd->getId();
    }

    function addEpButton($id, $defEp) {
        echo '<input id="'.$id.'" title="{{End Point, format hexa (ex: 01)}}" value="'.$defEp.'"  style="width:30px; margin-left: 8px" />';
    }

    function addClusterButton($id) {
        global $zbClusters;
        echo '<select id="'.$id.'" title="{{Cluster}}" style="width:140px; margin-left: 8px">';
        foreach ($zbClusters as $clustId => $clust) {
            echo '<option value="'.$clustId.'">'.$clustId.' / '.$clust['name'].'</option>';
        }
        echo '</select>';
    }

    function addIeeeListButton($id) {
        echo '<select id="'.$id.'" title="{{Equipements}}" style="width:140px; margin-left: 8px">';
        $eqLogics = eqLogic::byType('Abeille');
        foreach ($eqLogics as $eqLogic) {
            $ieee = $eqLogic->getConfiguration('IEEE', '');
            if ($ieee == '')
                continue;
            $eqName = $eqLogic->getName();
            echo '<option value="'.$ieee.'">'.$ieee.' / '.$eqName.'</option>';
        }
        echo '</select>';
    }

    function addAttrInput($id) {
        echo '<input id="'.$id.'" style="width:120px; margin-left: 8px" placeholder="{{Attrib (ex: 0021)}}" title="Attribut, format hex 4 caracteres (ex: 0508)"/>';
    }
?>