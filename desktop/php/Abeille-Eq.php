<!-- This is equipement page opened when clicking on it.
     Displays main infos + specific params + commands. -->

<?php
    /* Returns cmd ID identified by its Jeedom logical ID name */
    function getCmdIdByLogicId($eqId, $cmdLogicId) {
        $cmd = cmd::byEqLogicIdAndLogicalId($eqId, $cmdLogicId);
        if (!is_object($cmd))
            return "";
        return $cmd->getId();
    }

    /* Returns current cmd value identified by its Jeedom logical ID name */
    function getCmdValueByLogicId($eqId, $logicId) {
        $cmd = AbeilleCmd::byEqLogicIdAndLogicalId($eqId, $logicId);
        if (!is_object($cmd))
            return "";
        return $cmd->execCmd();
    }
?>

<!-- For all modals on 'Abeille' page. -->
<div class="row row-overflow" id="abeilleModal">
</div>

<div class="col-xs-12 eqLogic" style="display: none;">
    <div class="input-group pull-right" style="display:inline-flex">
		<span class="input-group-btn">
			<a class="btn btn-success eqLogicAction btn-sm roundedLeft" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a><a class="btn btn-default eqLogicAction btn-sm roundedRight" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
		</span>
	</div>

    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
        <li role="presentation" class="active"><a href="#idMain" aria-controls="home" role="tab" data-toggle="tab">                                                <i class="fas fa-home">             </i> {{Equipement}} </a></li>
        <li role="presentation"               ><a href="#idAdvanced" aria-controls="home" role="tab" data-toggle="tab">                                            <i class="fas fa-list-alt">         </i> {{Avancé}}     </a></li>
        <li role="presentation"               ><a href="#idCommands" aria-controls="home" role="tab" data-toggle="tab">                                            <i class="fas fa-align-left">       </i> {{Commandes}}  </a></li>
    </ul>

    <!-- <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;"> -->
    <div class="tab-content">
        <!-- Displays Jeedom specifics  -->
        <div role="tabpanel" class="tab-pane active" id="idMain">
            <?php
                include 'Abeille-Eq-Main.php';
            ?>
        </div>

        <!-- Displays advanced & Zigbee specifics  -->
        <div role="tabpanel" class="tab-pane" id="idAdvanced">
            <?php include 'Abeille-Eq-Advanced.php'; ?>
        </div>

        <!-- Displays Jeedom commands  -->
        <div role="tabpanel" class="tab-pane" id="idCommands">
            <?php include 'Abeille-Eq-Cmds.php'; ?>
        </div>
    </div>
</div>
