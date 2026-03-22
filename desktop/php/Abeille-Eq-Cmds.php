<!-- This file displays equipment commands.
     Included by 'Abeille-Eq.php' -->

<form class="form-horizontal">
    <?php if (isset($dbgDeveloperMode)) { ?>
        <div class="form-actions">
            <a class="btn btn-default btn-sm pull-right" id="bt_loadCmdFromJson" style="margin-top:5px;"><i class="fas fa-plus-circle"></i>  {{Ajouter à partir d'un modèle}}</a>
            <a class="btn btn-default btn-sm pull-right" id="bt_addAbeilleInfo" style="margin-top:5px;">  <i class="fas fa-plus-circle"></i>  {{Ajouter une commande info}}</a>
            <a class="btn btn-default btn-sm pull-right" id="bt_addAbeilleAction" style="margin-top:5px;"><i class="fas fa-plus-circle"></i>  {{Ajouter une commande action}}</a>
        </div>
        <br/>
        <br/>
    <?php } ?>
</form>
<!-- <a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
<br><br> -->

<div class="table-responsive">
    <table id="table_cmd" class="table table-bordered table-condensed ui-sortable">
        <thead>
            <tr>
                <th style="width:  70px;">{{ID}}</th>
                <th style="width: 160px;">{{Nom}}</th>
                <th style="width: 50px;">{{Type}}</th>
                <th style="width: 150px;">{{ID logique}}</th>
                <th style="width: 130px;">{{Options}}</th>
                <?php if (isset($dbgDeveloperMode)) { ?>
                    <!-- Tcharp38: logicalId & topic to be revisited. Currently logicalId seems to not be used. -->
                    <th style="width: 150px;">{{Cmde Abeille/topic}}</th>
                    <th style="width: 400px;">{{Paramètres cmde Abeille}}</th>
                <?php } ?>
                <th style="width: 150px;">{{Unité/Cron}}</th>
                <th style="width:  20px;"></th>
                <th style="width: 80px;">{{Supprimer}}</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div> <!-- class="table-responsive" -->
