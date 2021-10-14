<?php
    require_once __DIR__.'/../../../../core/php/core.inc.php';
    require_once __DIR__.'/../../core/class/Abeille.class.php';

    $zigateIds = array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10' );

    function sendMessageFromFormToCmd( $topic, $payload ) {

        $queueKeyFormToCmd   = msg_get_queue(queueKeyFormToCmd);
        $msgAbeille = new MsgAbeille;

        $msgAbeille->message['topic']   = $topic;
        $msgAbeille->message['payload'] = $payload;

        if (msg_send($queueKeyFormToCmd, 1, $msgAbeille, true, false)) {
            echo "added to queue (".queueKeyFormToCmd."): ".json_encode($msgAbeille)."<br>\n";
        } else {
            echo "could not add message to queue id: ".$queueKeyId."<br>\n";
        }
    }

    function ApplySettingsToNE($item, $value) {
        $deviceId = substr( $item, strpos($item,"-")+1 );
        log::add('Abeille', 'debug', "deviceId: ".substr( $item, strpos($item,"-")+1 ) );
        $device = Abeille::byId(substr( $item, strpos($item,"-")+1 ));
        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
        log::add('Abeille', 'debug', "NE: ".$device->getName()." - dest: ".$dest." - address: ".$address );
        // $EP = $device->getConfiguration('mainEP');
        // log::add('Abeille', 'debug', 'EP: '.$EP );

        foreach ( $device->searchCmdByConfiguration('execAtCreation','action') as $cmd ) {
            log::add('Abeille', 'debug', 'Cmd: '.$cmd->getName() );
            if ($cmd->getConfiguration('execAtCreation','')=='Yes') {
                if ($cmd->getConfiguration('execAtCreationDelay','0')>0) {
                    log::add('Abeille', 'debug', '     Send Cmd: '.$cmd->getName() . ' - ' . $cmd->getConfiguration('topic','') . ' - ' . $cmd->getConfiguration('request','') );
                    $topic = 'Tempo'.'Cmd'.$device->getLogicalId().'/'.$cmd->getConfiguration('topic','').'&time='.(time()+$cmd->getConfiguration('execAtCreationDelay','0'));
                    $topic = AbeilleCmd::updateField($dest, $cmd, $topic);
                    log::add('Abeille', 'debug', '     Send Cmd: topic: '.$topic );
                    $request = $cmd->getConfiguration('request','');
                    $request = AbeilleCmd::updateField($dest, $cmd, $request);
                    log::add('Abeille', 'debug', '     Send Cmd: request: '.$request );
                    sendMessageFromFormToCmd( $topic, $request );
                    // $cmd->execute();
                }
            }
        }
    }

    function getInfosFromNe($item, $value) {
        $deviceId = substr( $item, strpos($item,"-")+1 );
        log::add('Abeille', 'debug', "deviceId: ".substr( $item, strpos($item,"-")+1 ) );
        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
        log::add('Abeille', 'debug', "dest: ".$dest." address: ".$address);
        $EP = $device->getConfiguration('mainEP');
        log::add('Abeille', 'debug', "mainEP: ".$EP);

        // Get Name
        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/ActiveEndPoint',           'address='.$address                             );
        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/SimpleDescriptorRequest',  'address='.$address.'&endPoint='.$EP            );
        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/IEEE_Address_request',     'address='.$address                             );
        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/getName',                  'address='.$address.'&destinationEndPoint='.$EP );
        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/getLocation',              'address='.$address.'&destinationEndPoint='.$EP );
        sendMessageFromFormToCmd('Cmd'.$dest.'/'.$address.'/getGroupMembership', 'ep='.$EP );
        // sendMessageFromFormToCmd('CmdAbeille/0000/getSceneMembership',   'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$grouID, 0);
    }

    try {

        // echo "_POST: ".json_encode( $_POST )."<br>\n";
        // echo "Group: ".$_POST['groupID'].$_POST['groupIdScene1'].$_POST['groupIdScene2']."<br>\n";
        // echo "Action: ".$_POST['submitButton']."<br>\n";

        switch ($_POST['submitButton']) {

            // Group
            case 'Add Group':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/addGroup', 'address='.$address.'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        sleep(1);
                        sendMessageFromFormToCmd('Cmd'.$dest.'/'.$address.'/getGroupMembership', 'ep='.$EP );
                        sleep(1);
                    }
                }
                break;

            case 'Set Group Remote':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/commissioningGroupAPS', 'address='.$address.'&groupId='.$_POST['group'] );
                    }
                }
                break;

            case 'Set Group Remote Legrand':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/commissioningGroupAPSLegrand', 'address='.$address.'&groupId='.$_POST['group'] );
                    }
                }
                break;

            case 'Remove Group':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/removeGroup',        'address='.$address.'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        sleep(1);
                        sendMessageFromFormToCmd('Cmd'.$dest.'/'.$address.'/getGroupMembership', 'ep='.$EP );
                        sleep(1);
                    }
                }
                break;

            case 'Get Group':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos( $item, "eqSelected") === 0 ) {
                        echo "Id: ->".substr( $item, strpos($item,"-")+1 )."<-<br>\n";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        echo "Dest: ".$dest."<br>";
                        echo "Address: ".$address."<br>";
                        $EP = $device->getConfiguration('mainEP');
                        echo "Id: ".$EP."<br>";
                        sendMessageFromFormToCmd('Cmd'.$dest.'/'.$address.'/getGroupMembership', 'ep='.$EP );
                    }
                }
                break;

            // Scene
            case 'Get Scene Info':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd( 'Cmd'.$device->getLogicalId().'/readAttribute', 'ep=01&clustId=0005&attrId=0000' );
                        sendMessageFromFormToCmd( 'Cmd'.$device->getLogicalId().'/readAttribute', 'ep=01&clustId=0005&attrId=0001' );
                        sendMessageFromFormToCmd( 'Cmd'.$device->getLogicalId().'/readAttribute', 'ep=01&clustId=0005&attrId=0002' );
                        sendMessageFromFormToCmd( 'Cmd'.$device->getLogicalId().'/readAttribute', 'ep=01&clustId=0005&attrId=0003' );
                    }
                }
                break;

            case 'View Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/viewScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                    }
                }
                break;

            case 'Store Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/storeScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                    }
                }
                break;

            case 'Recall Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/recallScene',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                    }
                }
                break;

            case 'scene Group Recall':
                if (0) {
                    foreach ( $_POST as $item=>$Value ) {
                        if ( strpos("-".$item, "eqSelected") == 1 ) {
                            echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                            $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                            list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                            $EP = $device->getConfiguration('mainEP');
                            sendMessageFromFormToCmd('Cmd'.$dest.'/0000/sceneGroupRecall',       'groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                        }
                    }
                }
                else {
                    sendMessageFromFormToCmd('CmdAbeille1/0000/sceneGroupRecall',           'groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                }
                break;

            case 'Add Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/addScene',               'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'].'&sceneName=aa' );
                    }
                }
                break;

            case 'Remove Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/removeScene',            'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                    }
                }
                break;

            case 'Get Scene Membership':
                echo "Get Scene Membership<br>";
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/getSceneMembership',      'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene1'] );
                    }
                }
                break;

            case 'Remove All Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('Cmd'.$dest.'/0000/removeSceneAll',           'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene1'] );
                    }
                }
                break;

            // Template
            case 'Apply Template':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        $deviceId = substr( $item, strpos($item,"-")+1 );
                        // echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        // $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        // $address = substr($device->getLogicalId(),8);
                        // $EP = $device->getConfiguration('mainEP');
                        // sendMessageFromFormToCmd('CmdAbeille/0000/addGroup', 'address='.(substr( $item, strpos($item,"-")+1 )).'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        abeille::updateConfigAbeille( $deviceId );
                        // abeille::updateConfigAbeille( );
                    }
                }
                break;

            case 'Get Infos from NE':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        // $deviceId = substr( $item, strpos($item,"-")+1 );
                        // echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        // $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        // $address = substr($device->getLogicalId(),8);
                        // $EP = $device->getConfiguration('mainEP');
                        // sendMessageFromFormToCmd('CmdAbeille/0000/addGroup', 'address='.(substr( $item, strpos($item,"-")+1 )).'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        getInfosFromNe($item, $Value);
                        // abeille::updateConfigAbeille( );
                    }
                }
                break;

            case 'Apply Settings to NE':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        // $deviceId = substr( $item, strpos($item,"-")+1 );
                        // echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        // $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        // $address = substr($device->getLogicalId(),8);
                        // $EP = $device->getConfiguration('mainEP');
                        // sendMessageFromFormToCmd('CmdAbeille/0000/addGroup', 'address='.(substr( $item, strpos($item,"-")+1 )).'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        ApplySettingsToNE($item, $Value);
                        // abeille::updateConfigAbeille( );
                    }
                }
                break;

            case "Remplace":
                log::add('Abeille', 'debug', 'Replace: '.$_POST['ghost'] . ' - ' . $_POST['real']);
                Abeille::replaceGhost($_POST['ghost'], $_POST['real']);
                break;

            case "ReHome":
                log::add('Abeille', 'debug', 'ReHome: '.$_POST['beeId'] . ' - ' . $_POST['zigateY']);
                Abeille::migrateBetweenZigates($_POST['beeId'], $_POST['zigateY']);
                break;

            case "ReplaceZigate":
                log::add('Abeille', 'debug', 'Removing all data of previous zigate: '.$_POST['zigateZ']);
                config::remove( "AbeilleIEEE_Ok".$_POST['zigateZ'], 'Abeille');
                config::remove( "AbeilleIEEE".$_POST['zigateZ'], 'Abeille');
                break;
        }

        // TX Power
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'TxPower Z'.$zigateId ) {
                echo "TxPower request processing";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/TxPower', $_POST['TxPowerValue'] );
            }
        }

        // Set Channel Mask
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'Set Channel Mask Z'.$zigateId ) {
                echo "Set Channel Mask processing: Zigate Id: ".$zigateId." Channel mask: ".$_POST['channelMask'];
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/setChannelMask', $_POST['channelMask'] );
            }
        }

        // Set Extended PANID
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'Set Extended PANID Z'.$zigateId ) {
                echo "Set Extended PANID request processing";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/setExtendedPANID', $_POST['extendedPanId'] );
            }
        }

        // Set Time
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'SetTime Z'.$zigateId ) {
                echo "SetTime request processing";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/setTimeServer', "time=".time() );
            }
        }

        // Get Time
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'getTime Z'.$zigateId ) {
                echo "getTime request processing";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/getTimeServer', "");
            }
        }

        // setOnZigateLed
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'SetLedOn Z'.$zigateId ) {
                echo "SetTime request processing";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/setOnZigateLed', "");
            }
        }

        // setOffZigateLed
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'SetLedOff Z'.$zigateId ) {
                echo "getTime request processing";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/setOffZigateLed', "");
            }
        }

        // Set Certification CE
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'Set Certification CE Z'.$zigateId ) {
                echo "Set Certification CE";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/setCertificationCE', "");
            }
        }

        // Set Certification FCC
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'Set Certification FCC Z'.$zigateId ) {
                echo "Set Certification FCC";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/setCertificationFCC', "");
            }
        }

        // startNetwork
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'Start Network Z'.$zigateId ) {
                echo "Start Network";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/zgStartNetwork', "");
            }
        }

        // Mode: normal, raw, hybride
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'Set Normal Mode Z'.$zigateId ) {
                echo "Set Hybride Mode";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/zgSetMode', "mode=normal");
            }
        }
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'Set Raw Mode Z'.$zigateId ) {
                echo "Set Hybride Mode";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/zgSetMode', "mode=raw");
            }
        }
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'Set Hybride Mode Z'.$zigateId ) {
                echo "Set Hybride Mode";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/zgSetMode', "mode=hybrid");
            }
        }

    } catch (Exception $e) {
        echo '<br>error: '.$e->getMessage();
    }
    echo "<br>Fin";
    sleep(1);
    header ("location:../../../../index.php?v=d&m=Abeille&p=Abeille");
?>
