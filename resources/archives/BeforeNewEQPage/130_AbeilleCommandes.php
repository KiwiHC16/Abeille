<div role="tabpanel" class="tab-pane" id="commandtab">

    <form class="form-horizontal">
        <fieldset>
            <div class="form-actions">
                <a class="btn btn-success btn-sm cmdAction" id="bt_addAbeilleAction">   <i class="fa fa-plus-circle"></i>  {{Ajouter une commande action}}</a>
                <a class="btn btn-success btn-sm cmdAction" id="bt_addAbeilleInfo">     <i class="fa fa-plus-circle"></i>  {{Ajouter une commande info (dev en cours) }}</a>
            </div>
        </fieldset>
    </form>
    <br/>
    <table id="table_cmd" class="table table-bordered table-condensed">
        <thead>
        <tr>
            <th style="width: 50px;">#</th>
            <th style="width: 300px;">{{Nom}}</th>
            <th style="width: 120px;">{{Sous-Type}}</th>
            <th style="width: 400px;">{{Topic}}</th>
            <th style="width: 600px;">{{Payload}}</th>
            <th style="width: 150px;">{{Paramètres}}</th>
            <th style="width: 80px;"></th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

</div>