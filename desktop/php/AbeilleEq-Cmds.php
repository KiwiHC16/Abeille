<!-- This file displays equipment commands.
     Included by 'AbeilleEq.php' -->

<form class="form-horizontal">
    <fieldset>
        <!-- <div class="form-actions">
            <a class="btn btn-success btn-sm cmdAction pull-right" data-action="add"><i class="fa fa-plus-circle"></i>  {{Ajouter une commande}}</a>
        </div> -->
        <div class="form-actions">
            <a class="btn btn-success btn-sm pull-right" id="bt_addAbeilleAction"><i class="fa fa-plus-circle"></i>  {{Ajouter une commande action}}</a>
            <a class="btn btn-success btn-sm pull-right" id="bt_addAbeilleInfo">  <i class="fa fa-plus-circle"></i>  {{Ajouter une commande info}}</a>
        </div>
    </fieldset>
</form>
<br/>
<table id="table_cmd" class="table table-bordered table-condensed">
    <thead>
    <tr>
        <?php
            if (isset($dbgDeveloperMode) && ($dbgDeveloperMode == TRUE)) {
        ?>
                <th style="width:  80px;">{{#}}</th>
                <th style="width: 150px;">{{Commande Jeedom}}</th>
                <th style="width: 120px;">{{Sous-Type}}</th>
                <th style="width: 300px;">{{Commande Abeille}}</th>
                <th style="width: 600px;">{{Paramètres commande Abeille}}</th>
                <th style="width: 150px;">{{Unité/Cron}}</th>
                <th style="width: 130px;">{{Options}}</th>
                <th style="width:  20px;"></th>
                <th style="width: 80px;">{{Supprimer}}</th>
        <?php
            }
            else {
        ?>
                <th style="width:  80px;">{{#}}</th>
                <th style="width: 150px;">{{Commande Jeedom}}</th>
                <th style="width: 120px;">{{Sous-Type}}</th>
                <th style="width: 150px;">{{Unité/Cron}}</th>
                <th style="width: 130px;">{{Options}}</th>
                <th style="width:  20px;"></th>
                <th style="width: 80px;">{{Supprimer}}</th>
        <?php
             }
        ?>

    </tr>
    </thead>
    <tbody>

    </tbody>
</table>
