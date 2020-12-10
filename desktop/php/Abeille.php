<?php
if(0){
    if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
    }
}
?>

<?php include '005_AbeilleFunctionPart.php'; ?>

<?php
sendVarToJS('eqType', 'Abeille');
$eqLogics = eqLogic::byType('Abeille');

$zigateNb = config::byKey('zigateNb', 'Abeille', '1');

$parametersAbeille = Abeille::getParameters();

$outils = array(
    'health'    => array( 'bouton'=>'bt_healthAbeille',         'icon'=>'fa-medkit',        'text'=>'{{Santé}}' ),
    'netList'   => array( 'bouton'=>'bt_networkAbeilleList',    'icon'=>'fa-sitemap',       'text'=>'{{Network List}}' ),
    'net'       => array( 'bouton'=>'bt_networkAbeille',        'icon'=>'fa-map',           'text'=>'{{Network Graph}}' ),
    'graph'     => array( 'bouton'=>'bt_graph',                 'icon'=>'fa-flask',         'text'=>'{{Graph}}' ),
    'compat'    => array( 'bouton'=>'bt_listeCompatibilite',    'icon'=>'fa-align-left',    'text'=>'{{Compatibilite}}' ),
    'inconnu'   => array( 'bouton'=>'bt_Inconnu',               'icon'=>'fa-paperclip',     'text'=>'{{Inconnu}}' ),
    'support'   => array( 'bouton'=>'bt_supportPage',           'icon'=>'fa-medkit',        'text'=>'{{Support}}' ),
    'modele'    => array( 'bouton'=>'bt_template',              'icon'=>'fa-money-check',   'text'=>'{{Modeles}}' ),
    );

/* Developers debug features */
$dbgFile = __DIR__."/../../tmp/debug.php";
if (file_exists($dbgFile)) {
    // include_once $dbgFile;
    include $dbgFile;
    $dbgDeveloperMode = TRUE;
    echo '<script>var js_dbgDeveloperMode = '.$dbgDeveloperMode.';</script>'; // PHP to JS
}
?>

<!-- For all modals on 'Abeilles' page. -->
<div class="row row-overflow" id="abeilleModal">

	<form action="plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post">

		<!-- Barre d outils horizontale  -->
		<div class="col-xs-12 eqLogicThumbnailDisplay">

			<!-- Icones de toutes les modales  -->
			<?php include '010_AbeilleGestionPart.php'; ?>

			<!-- Icones de toutes les abeilles  -->
			<?php include '020_AbeilleMesAbeillesPart.php'; ?>
				
			<legend><i class="fa fa-cogs"></i> {{Appliquer les commandes sur la selection}}</legend>
			<div class="form-group" style="background-color: rgba(var(--defaultBkg-color), var(--opacity)) !important; padding-left: 10px">
				
				<!-- Gestion des groupes et des scenes  -->
				<?php include '030_AbeilleGroupPart.php'; ?>
				
				<!-- Gestion des groupes et des scenes  -->
				<?php include '040_AbeilleScenePart.php'; ?>
			
			</div>

			<!-- Gestion des ghosts / remplacement d equipements  -->
			<?php include '050_AbeilleRemplacementPart.php'; ?>

			<!-- Gestion des parametres Zigate -->
			<?php include '060_AbeilleZigatePart.php'; ?>
			
			<!-- Affichage des details zigate  -->
			<?php include '070_AbeilleDetailsPart.php'; ?>

			<!-- Affichage de la zone developpeur  -->
			<?php include '080_AbeilleZoneDevPart.php'; ?>

		</div> <!-- Fin - Barre d outils horizontale  -->
	
	</form>

    <!-- Affichage des informations d un equipement - tab : Equipement / Param / Commandes  -->
    <div class="col-xs-12 eqLogic" style="display: none;">
	
        <!-- Affichage de la zone developpeur  -->
        <?php include '100_AbeilleEntete.php'; ?>

        <!-- Affichage des informations specifiques d un equipement  -->
        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">

            <!-- Affichage des informations specifiques a cet equipement: Equipement tab  -->
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
            <?php include '110_AbeilleCoreParam.php'; ?>
            </div>

            <!-- Affichage des informations specifiques a cet equipement: Param tab -->
            <?php include '120_AbeilleParam.php'; ?>

            <!-- Affichage des informations specifiques a cet equipement: Command tab -->
            <?php include '130_AbeilleCommandes.php'; ?>

        </div>

    </div>

</div>

<?php include_file('desktop', 'Abeille', 'js', 'Abeille'); ?>
<?php include_file('desktop', 'AbeilleDev', 'js', 'Abeille'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

<!-- Scripts -->
<?php include '200_AbeilleScript.php'; ?>
