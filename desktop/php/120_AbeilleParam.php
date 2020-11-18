<div role="tabpanel" class="tab-pane" id="paramtab">
    <form class="form-horizontal">
        <fieldset>
            <hr>
            Pour l'instant cette page consolide l'ensemble des parametres spécifiques à tous les équipments.<br>
            L'idée est de ne faire apparaitre que les paragraphes en relation avec l'objet sélectionné.<br>
            Par exemple si vous avez sélectionné une ampoule le paragraphe "Equipement sur piles" ne devrait pas apparaitre.<br>
            Par defaut si cette partie n est pas definie dans le modele tout est affiché.<br>
            Si cela est defini dans le modele alors que les parameteres necessaires sont affichés.<br>
            <hr>

<?php
            /* Tcharp38: In which case this 'id' is supposed to be set ? <= quand on accede à la page de configuration d un equipement*/
            if (isset($_GET['id']) && ($_GET['id'] > 0)) {
                $eqLogic = eqLogic::byId($_GET['id']);
                if ( ($eqLogic->getConfiguration('paramBatterie', 'notDefined') == "true") || ($eqLogic->getConfiguration('paramBatterie', 'notDefined') == "notDefined") ) {
                    echo '<div class="form-group">';
                    echo '<label class="col-sm-3 control-label">{{Equipements sur piles.}}</label>';
                    echo '</div>';

                    echo '<div class="form-group" >';
                    echo '<label class="col-sm-3 control-label" >{{Type de piles}}</label>';
                    echo '<div class="col-sm-3">';
                    echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="battery_type"  placeholder="{{Doit être indiqué sous la forme : 3xAA}}"/>';
                    echo '</div>';
                    echo '</div>';

                    echo '<hr>';
                }

                if ( ($eqLogic->getConfiguration('paramType', 'notDefined') == "telecommande") || ($eqLogic->getConfiguration('paramType', 'notDefined') == "notDefined") )  {

                    echo '<div class="form-group">';
                    echo '<label class="col-sm-3 control-label">{{Telecommande}}</label>';
                    echo '</div>';

                    echo '<div class="form-group">';
                    echo '<label class="col-sm-3 control-label">{{Groupe}}</label>';
                    echo '<div class="col-sm-3">';
                    echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="Groupe" placeholder="{{Adresse en hex sur 4 digits, ex:ae12}}"/>';
                    echo '</div>';
                    echo '</div>';

                    echo '<div class="form-group">';
                    echo '<label class="col-sm-3 control-label">{{on time (s)}}</label>';
                    echo '<div class="col-sm-3">';
                    echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="onTime" placeholder="{{Durée en secondes}}"/>';
                    echo '</div>';
                    echo '</div>';
                }

                if ( ($eqLogic->getConfiguration('paramType', 'notDefined') == "paramABC") || ($eqLogic->getConfiguration('paramType', 'notDefined') == "notDefined") )  {

                    echo '<div class="form-group">';
                    echo '<label class="col-sm-3 control-label">{{Calibration (y=ax2+bx+c)}}</label>';
                    echo '</div>';

                    echo '<div class="form-group">';
                    echo '<label class="col-sm-3 control-label">{{parametre A}}</label>';
                    echo '<div class="col-sm-3">';
                    echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramA" placeholder="{{nombre}}"/>';
                    echo '</div>';
                    echo '</div>';

                    echo '<div class="form-group">';
                    echo '<label class="col-sm-3 control-label">{{parametre B}}</label>';
                    echo '<div class="col-sm-3">';
                    echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramB" placeholder="{{nombre}}"/>';
                    echo '</div>';
                    echo '</div>';

                    echo '<div class="form-group">';
                    echo '<label class="col-sm-3 control-label">{{parametre C}}</label>';
                    echo '<div class="col-sm-3">';
                    echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramC" placeholder="{{nombre}}"/>';
                    echo '</div>';
                    echo '</div>';
                }
            }
?>

        </fieldset>
    </form>
</div>