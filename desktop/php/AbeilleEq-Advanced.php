<!-- This file displays advanced equipment/zigbee infos.
     Included by 'AbeilleEq.php' -->

<?php
    require_once __DIR__.'/../../core/php/AbeilleZigbeeConst.php';

    function addDocButton($chapter) {
        // $urlUserMan = "https://kiwihc16.github.io/AbeilleDoc"; // Constant defined in Abeille config
        echo '<a class="btn btn-primary btn-xs" target="_blank" href="'.urlUserMan.'/'.$chapter.'"><i class="fas fa-book"></i> ?</a>';
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
?>

<form class="form-horizontal">
    <br>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Nom}}</label>
        <div class="col-sm-5">
            <?php echo '<span>'.$eqName.'</span>'; ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Id Jeedom}}</label>
        <div class="col-sm-5">
            <!-- 'eqLogicAttr' with data-l1key="id" must not be declared twice in same page -->
            <?php echo '<span>'.$eqId.'</span>'; ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Dernière comm.}}</label>
        <?php
        echo '<div class="col-sm-5 cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "Last").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
            echo '<span id="idLastComm">'.getCmdValueByLogicId($eqId, "Time-Time").'</span>';
        ?>
            <script>
                <?php
                    $cmdId = getCmdIdByLogicId($eqId, "Time-Time");
                    echo "jeedom.cmd.update['".$cmdId."'] = function(_options){";
                    echo 'console.log("jeedom.cmd.update['.$cmdId.']");';
                ?>
                    var element = document.getElementById('idLastComm');
                    element.textContent = _options.display_value;
                }
            </script>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Nom logique}}</label>
        <div class="col-sm-5">
            <span class="eqLogicAttr" data-l1key="logicalId"></span>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Adresse (courte/IEEE)}}</label>
        <div class="col-sm-5">
            <span><?php echo $eqAddr; ?></span>
            /
            <span class="eqLogicAttr" data-l1key="configuration" data-l2key="IEEE"></span>
        </div>
    </div>

    <!-- <div class="form-group">
        <label class="col-sm-3 control-label">{{Fichier JSON}}</label>
        <div class="col-sm-5">
            <span class="eqLogicAttr" data-l1key="configuration" data-l2key="modeleJson"></span>
        </div>
    </div> -->

    <div class="form-group">
        <label class="col-sm-3 control-label">{{Documentation}}</label>
        <div class="col-sm-5">
            <?php
                $model = $eqLogic->getConfiguration('modeleJson', '');
                if ($model != '') {
                    echo '<a href="'.urlProducts.'/'.$model.'" target="_blank">Voir ici si présente</a>';
                }
            ?>
        </div>
    </div>

    <?php if ($eqAddr != "0000") { ?>
    <hr>
    <div class="form-group">
        <label class="col-sm-3 control-label">Identifiant JSON</label>
        <div class="col-sm-5">
            <span class="eqLogicAttr" data-l1key="configuration" data-l2key="modeleJson" title="Nom du fichier de config JSON utilisé"></span>
            <?php
                // echo '<a class="btn btn-warning" onclick="updateFromJSON(\''.$eqNet.'\', \''.$eqAddr.'\')" title="Mets à jour les commandes Jeedom">Recharger</a>';
                $jsonLocation = $eqLogic->getConfiguration('ab::jsonLocation', 'Abeille');
                if ($jsonLocation != 'Abeille') {
                    echo '<span style="background-color:red;color:black" title="La configuration vient d\'un fichier local (core/config/devices_local)"> ATTENTION: inclu à partir d\'un fichier local </span>';
                    $jsonId = $eqLogic->getConfiguration('modeleJson', '');
                    if (file_exists(__DIR__."/../../core/config/devices_local/".$jsonId."/".$jsonId.".json"))
                        echo '<a class="btn btn-warning" onclick="removeLocalJSON(\''.$jsonId.'\')" title="Supprime la version locale du fichier de config JSON">Supprimer version locale</a>';
                }
            ?>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label">Configuration</label>
        <div class="col-sm-5">
            <?php
                echo '<a class="btn btn-warning" onclick="reinit(\''.$eqId.'\')" title="Réinitlialise les paramètres par défaut et reconfigure l\'équipement comme s\'il s\'agissait d\'une nouvelle inclusion">Réinitialiser</a>';
                echo ' OU ';
                echo '<a class="btn btn-warning" onclick="updateFromJSON(\''.$eqNet.'\', \''.$eqAddr.'\')" title="Mets à jour les commandes Jeedom">Recharger</a>';
                echo ' ';
                echo '<a class="btn btn-warning" onclick="reconfigure(\''.$eqId.'\')" title="Reconfigure l\'équipement">Reconfigurer</a>';
            ?>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-3 control-label">Assistant de découverte</label>
        <div class="col-sm-5">
            <?php
                echo '<a class="btn btn-warning" onclick="window.location.href=\'index.php?v=d&m=Abeille&p=AbeilleEqAssist&id='.$eqId.'\'">Ouvrir</a>';
            ?>
        </div>
    </div>
    <?php } ?>

    <?php
        // If eq is a zigate (short addr=0000), display special parameters
        if ($eqAddr == "0000") {
    ?>
            <hr>

            <div class="form-group">
                <div class="col-sm-3"></div>
                <h3 class="col-sm-5" style="text-align:left">{{Zigate}}</h3>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Type</label>
                <div class="col-sm-5">
                    <?php
                        echo '<input class="form-control" value="'.$zgType.'" title="{{Type de Zigate}}" />';
                    ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Status réseau</label>
                <div class="col-sm-5">
                    <?php
                        $res = getCmdValueByName($eqId, 'Network Status');
                        echo '<span>'.$res.'</span>';
                        if (isset($dbgDeveloperMode))
                            echo '<a class="btn btn-warning" onclick="sendZigate(\'startNetwork\', \'\')">Démarrer</a>';
                    ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Firmware</label>
                <div class="col-sm-5">
                    <?php
                    echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "SDK").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
                        echo '<span id="idFWVersion">'.getCmdValueByName($eqId, 'SDK').'</span>';
                    ?>
                        <script>
                            <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "SDK")."'] = function(_options){"; ?>
                                console.log("jeedom.cmd.update[SDK]");
                                // console.log(_options);
                                var element = document.getElementById('idFWVersion');
                                element.textContent = _options.display_value;
                            }
                        </script>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">PAN ID</label>
                <div class="col-sm-5">
                    <?php
                    echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "PAN ID").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
                        echo '<span id="idPanId">'.getCmdValueByName($eqId, 'PAN ID').'</span>';
                    ?>
                        <script>
                            <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "PAN ID")."'] = function(_options){"; ?>
                                console.log("jeedom.cmd.update[PAN ID]");
                                var element = document.getElementById('idPanId');
                                element.textContent = _options.display_value;
                            }
                        </script>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">Extended PAN ID</label>
                    <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html"><i class="fas fa-book"></i> ?</a> -->
                </div>
                <div class="col-sm-5">
                    <?php
                    echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "Ext PAN ID").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
                        echo '<span id="idExtPanId">'.getCmdValueByName($eqId, 'Ext PAN ID').'</span>';
                    ?>
                        <script>
                            <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "Ext PAN ID")."'] = function(_options){"; ?>
                                console.log("jeedom.cmd.update[Ext PAN ID]");
                                var element = document.getElementById('idExtPanId');
                                element.textContent = _options.display_value;
                            }
                        </script>
                    </div>
                    <!-- TODO <input type="text" name="extendedPanId" placeholder="XXXXXXXX">
                    <button type="button" onclick="sendZigate('setExtPANId', '')">Modifier</button> -->
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">Canal</label>
                    <?php
                        addDocButton("Radio.html#zigate-channel-selection");
                    ?>
                </div>
                <div class="col-sm-5">
                    <?php
                    echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "Network Channel").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
                        echo '<span id="idChannel" title="{{Canal actuel}}">'.getCmdValueByName($eqId, 'Network Channel').'</span>';
                    ?>
                        <script>
                            <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "Network Channel")."'] = function(_options){"; ?>
                                console.log("jeedom.cmd.update[Network Channel]");
                                var element = document.getElementById('idChannel');
                                element.textContent = _options.display_value;
                            }
                        </script>
                        <input type="text" id="idChannelMask" placeholder="ex: 07FFF800" title="{{Masque des canaux autorisés (en hexa, 1 bit par canal, 800=canal 11, 07FFF800=tous les canaux de 11 à 26)}}" style="margin-left:10px; width:100px">
                        <a class="btn btn-warning" onclick="sendZigate('setChannelMask', '')">Modifier</a>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">Mode inclusion</label>
                </div>
                <div class="col-sm-5">
                    <?php
                    echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "Inclusion Status").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
                        echo '<span id="idInclusionMode">'.getCmdValueByName($eqId, 'Inclusion Status').'</span>';
                        ?>
                        <script>
                            <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "Inclusion Status")."'] = function(_options){"; ?>
                                console.log("jeedom.cmd.update[Inclusion Status]");
                                var element = document.getElementById('idInclusionMode');
                                element.textContent = _options.display_value;
                            }
                        </script>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">Heure zigate</label>
                    <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html#zigate-channel-selection"><i class="fas fa-book"></i> ?</a> -->
                </div>
                <div class="col-sm-7">
                    <?php
                    echo '<div class="cmd" data-type="info" data-subtype="string" data-cmd_id="'.getCmdIdByName($eqId, "ZiGate-Time").'" data-version="dashboard" data-eqlogic_id="'.$eqId.'">';
                    ?>
                        <span id="idZgTime">- ? -</span>
                        <script>
                            <?php echo "jeedom.cmd.update['".getCmdIdByName($eqId, "ZiGate-Time")."'] = function(_options){"; ?>
                                console.log("jeedom.cmd.update[Zigate-Time]");
                                // console.log(_options);
                                var element = document.getElementById('idZgTime');
                                element.textContent = _options.display_value;
                            }
                            // jeedom.cmd.update['233']({display_value:'#state#'});
                        </script>
                        <a class="btn btn-warning" onclick="sendZigate('getTime', '')">Lire</a>
                        <a class="btn btn-warning" onclick="sendZigate('setTime', '')">Mettre à l'heure</a>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-3 control-label">
                    <label class="">TX power</label>
                    <!-- <a class="btn btn-primary btn-xs" target="_blank" href="https://kiwihc16.github.io/AbeilleDoc/Radio.html"><i class="fas fa-book"></i> ?</a> -->
                </div>
                <div class="col-sm-5">
                    <input type="text" name="TxPowerValue" placeholder="XX">
                    <a class="btn btn-warning" onclick="sendZigate('setTXPower', '')">Modifier</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">LED</label>
                <div class="col-sm-5">
                    <a class="btn btn-warning" onclick="sendZigate('setLED', 'ON')">ON</a>
                    <a class="btn btn-warning" onclick="sendZigate('setLED', 'OFF')">OFF</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Certification</label>
                <div class="col-sm-5">
                    <a class="btn btn-warning" onclick="sendZigate('setCertif', 'CE')">CE</a>
                    <a class="btn btn-warning" onclick="sendZigate('setCertif', 'FCC')">FCC</a>
                </div>
            </div>

            <?php if (isset($dbgDeveloperMode)) { ?>
                <div class="form-group">
                    <label class="col-sm-3 control-label">Mode</label>
                    <div class="col-sm-5">
                        <a class="btn btn-warning" onclick="sendZigate(\'setMode\', \'Normal\')">Normal</a>
                        <a class="btn btn-warning" onclick="sendZigate(\'setMode\', \'Raw\')">Raw</a>
                        <a class="btn btn-warning" onclick="sendZigate(\'setMode\', \'Hybride\')">Hybride</a>
                    </div>
                </div>
            <?php } ?>

            <div class="form-group">
                <label class="col-sm-3 control-label">PDM</label>
                <div class="col-sm-5">
                    <a class="btn btn-warning" onclick="sendZigate('erasePersistantDatas', '')" title="{{Efface la PDM. Tous les équipements devront être réinclus}}">Effacer</a>
                </div>
            </div>

            <?php
                if ($zgType == "PI") { ?>
                    <div class="form-group">
                    <label class="col-sm-3 control-label">Reset HW</label>
                    <div class="col-sm-5">
                        <a class="btn btn-warning" onclick="resetPiZigate()" title="{{Reset HW de la PiZigate}}"><i class="fas fa-sync"></i> {{Reset}}</a>
                    </div>
                </div>
            <?php } ?>
            <!-- </fieldset>
        </form> -->
        <hr>

        <?php
            } // End zigate case

            /* If device is a remote control. 'paramType' is defined in device JSON file */
            if ($eqLogic->getConfiguration('paramType', '') == "telecommande") {
                echo '<hr>';

                echo '<div class="form-group">';
                echo '<div class="col-sm-3"></div>';
                echo '<h3 class="col-sm-5" style="text-align:center">{{Télécommande}}</h3>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Adresse groupe}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="Groupe" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Durée (s)}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="onTime" placeholder="{{Durée en secondes}}"/>';
                echo '</div>';
                echo '</div>';
            }

            /* If eq is a Innr Tele. 'paramType' is defined in original JSON file */
            if ($eqLogic->getConfiguration('paramType', '') == "telecommande7groups") {
?>
                <hr>

                <div class="form-group">
                    <div class="col-sm-3">
                    </div>
                    <h3 class="col-sm-5" style="text-align:left">{{Télécommande}}</h3>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe Tous}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP1" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>

                <div class="form-group">
                        <label class="col-sm-3 control-label">{{Adresse groupe 1}}</label>
                        <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP3" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                        </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe 2}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP4" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe 3}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP5" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe 4}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP6" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe 5}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP7" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse groupe 6}}</label>
                    <div class="col-sm-5">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="GroupeEP8" placeholder="{{Adresse courte en hex sur 4 digits (ex: AE12)}}"/>
                    </div>
                </div>
<?php
            }

            /* If eq is ?. 'paramType' is defined in original JSON file */
            if ($eqLogic->getConfiguration('paramType', '') == "paramABC") {
                echo '<hr>';

                echo '<div class="form-group">';
                echo '<div class="col-sm-3"></div>';
                echo '<h3 class="col-sm-5" style="text-align:left">{{Calibration (y=ax2+bx+c)}}</h3>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Paramètre A}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramA" placeholder="{{nombre}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Paramètre B}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramB" placeholder="{{nombre}}"/>';
                echo '</div>';
                echo '</div>';

                echo '<div class="form-group">';
                echo '<label class="col-sm-3 control-label">{{Paramètre C}}</label>';
                echo '<div class="col-sm-5">';
                echo '<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="paramC" placeholder="{{nombre}}"/>';
                echo '</div>';
                echo '</div>';
            }
        ?>

    <?php if ($eqAddr != "0000") {
        function addEpButton($id, $defEp) {
            echo '<input id="'.$id.'" title="{{End Point, format hexa (ex: 01)}}" value="'.$defEp.'"  style="width:30px; margin-left: 8px" />';
        }
        // $GLOBALS['zbClusters'] = $zbClusters;
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
        <hr>
        <div class="form-group">
            <div class="col-sm-3">
            </div>
            <h3 class="col-sm-5" style="text-align:left">{{Interrogation de l'équipement. Sortie dans 'AbeilleParser.log'}}</h3>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">ZCL: Lecture attribut</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-warning" onclick="interrogate(\'readAttribute\', \''.$eqId.'\')">{{Lire}}</a>';
                    // echo '<input id="idEpA" title="{{End Point (ex: 01)}}" value="'.$mainEP.'"/>';
                    addEpButton("idEpA", $mainEP);
                    addClusterButton("idClustIdA");
                    addAttrInput("idAttrIdA");
                ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">ZCL: Ecriture attribut</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-danger" onclick="interrogate(\'writeAttribute\', \''.$eqId.'\')">{{Ecrire}}</a>';
                    addEpButton("idEpWA", $mainEP);
                    addClusterButton("idClustIdWA");
                    addAttrInput("idAttrIdWA");
                ?>
                <input id="idAttrTypeWA" title="{{Type attribut. Format hex string 2 car (ex: 21)}}" placeholder="{{Type (ex: 21)}}" />
                <input id="idValueWA" title="{{Valeur à écrire. Format hex string}}"  placeholder="{{Data}}" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="readReportingConfig">ZCL: Lecture configuration de reporting</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-warning" onclick="interrogate(\'readReportingConfig\', \''.$eqId.'\')">{{Interroger}}</a>';
                    addEpButton("idEp", $mainEP);
                    addClusterButton("idClustId");
                    addAttrInput("idAttrId");
                ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="discoverCommandsReceived">ZCL: Découverte des commandes RX</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-warning" onclick="interrogate(\'discoverCommandsReceived\', \''.$eqId.'\')">{{Interroger}}</a>';
                    addEpButton("idEpB", $mainEP);
                    addClusterButton("idClustIdB");
                ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="discoverAttributes">ZCL: Découverte des attributs</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-warning" onclick="interrogate(\'discoverAttributes\', \''.$eqId.'\')">{{Interroger}}</a>';
                    addEpButton("idEpD", $mainEP);
                    addClusterButton("idClustIdD");
                ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="discoverAttributesExt">ZCL: Découverte des attributs (extended)</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-warning" onclick="interrogate(\'discoverAttributesExt\', \''.$eqId.'\')">{{Interroger}}</a>';
                    addEpButton("idEpC", $mainEP);
                    addClusterButton("idClustIdC");
                ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="Cluster 0000, commande ResetToFactory">ZCL: Reset aux valeurs d'usine</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-danger" onclick="interrogate(\'resetToFactory\', \''.$eqId.'\')">{{Reset}}</a>';
                    addEpButton("idEpG", $mainEP);
                ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="Configure le reporting d'un attribut">ZCL: Configurer le reporting</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-danger" onclick="interrogate(\'configureReporting\', \''.$eqId.'\')">{{Configurer}}</a>';
                    addEpButton("idEpCR", $mainEP);
                    addClusterButton("idClustIdCR");
                    addAttrInput("idAttrIdCR");
                ?>
            </div>
        </div>

        <br>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="getRoutingTable (Mgmt_Rtg_req)">Table de routage</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-warning" onclick="interrogate(\'getRoutingTable\', \''.$eqId.'\')">{{Interroger}}</a>';
                ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="getBindingTable (Mgmt_Bind_req)">Table de binding</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-warning" onclick="interrogate(\'getBindingTable\', \''.$eqId.'\')">{{Interroger}}</a>';
                ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="getNeighborTable (Mgmt_Lqi_req)">Table de voisinage</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-warning" onclick="interrogate(\'getNeighborTable\', \''.$eqId.'\')">{{Interroger}}</a>';
                ?>
                <input id="idStartIdx" title="{{Start index (ex: 00)}}" value="00" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="getActiveEndPoints">Liste des 'end points'</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-warning" onclick="interrogate(\'getActiveEndPoints\', \''.$eqId.'\')">{{Interroger}}</a>';
                ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="Bind cet équipement vers un autre (Bind_req)">Bind to device</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-danger" onclick="interrogate(\'bindToDevice\', \''.$eqId.'\')">{{Bind}}</a>';
                    addEpButton("idEpE", $mainEP);
                    addClusterButton("idClustIdE");
                    // <input id="idIeeeE" title="{{Adresse IEEE de destination (ex: 5C0272FFFE2857A3)}}" />
                    addIeeeListButton("idIeeeE");
                ?>
                <?php addEpButton("idEpE2", $mainEP); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label" title="Bind cet équipement vers un groupe (Bind_req)">Bind to group</label>
            <div class="col-sm-5">
                <?php
                    echo '<a class="btn btn-danger" onclick="interrogate(\'bindToGroup\', \''.$eqId.'\')">{{Bind}}</a>';
                    addEpButton("idEpF", $mainEP);
                    addClusterButton("idClustIdF");
                ?>
                <input id="idGroupF" title="{{Adresse du groupe de destination (ex: 0001)}}" />
            </div>
        </div>
    <?php } ?>

</form>
