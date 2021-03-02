<!-- This file displays equipment commands.
     Included by 'AbeilleEq.php' -->

<form class="form-horizontal">
    <fieldset>
        <?php if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) { ?>
            <div class="form-actions">
                <a class="btn btn-success btn-sm pull-right" id="bt_addAbeilleAction"><i class="fa fa-plus-circle"></i>  {{Ajouter une commande action}}</a>
                <a class="btn btn-success btn-sm pull-right" id="bt_addAbeilleInfo">  <i class="fa fa-plus-circle"></i>  {{Ajouter une commande info}}</a>
            </div>
        <?php } ?>
    </fieldset>
</form>
<br/>
<table id="table_cmd" class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th style="width:  80px;">{{#}}</th>
            <th style="width: 150px;">{{Commande Jeedom}}</th>
            <th style="width: 120px;">{{Type}}</th>
            <?php if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) { ?>
                <th style="width: 300px;">{{Commande Abeille}}</th>
                <th style="width: 600px;">{{Paramètres commande Abeille}}</th>
            <?php } ?>
            <th style="width: 150px;">{{Unité/Cron}}</th>
            <th style="width: 130px;">{{Options}}</th>
            <th style="width:  20px;"></th>
            <th style="width: 80px;">{{Supprimer}}</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
