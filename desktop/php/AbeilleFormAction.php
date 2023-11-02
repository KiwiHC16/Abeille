<?php
    require_once __DIR__.'/../../../../core/php/core.inc.php';
    require_once __DIR__.'/../../core/class/Abeille.class.php';
    require_once __DIR__.'/../../core/config/Abeille.config.php';
    require_once __DIR__.'/../../core/php/AbeilleLog.php'; // logDebug()

    $zigateIds = array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10' );

    function sendMessageFromFormToCmd($topic, $payload) {
        global $abQueues;
        $queueId = $abQueues['xToCmd']['id']; // Previously formToCmd queue
        $queue = msg_get_queue($queueId);

        $msg = array();
        $msg['topic']   = $topic;
        $msg['payload'] = $payload;
        $msgJson = json_encode($msg);

        if (msg_send($queue, 1, $msgJson, false, false)) {
            echo "added to queue ID ".$queueId.": ".$msgJson."<br>\n";
        } else {
            echo "could not add message to queue ID ".$queueId."<br>\n";
        }
    }

    try {

        // echo "_POST: ".json_encode( $_POST )."<br>\n";
        // echo "Group: ".$_POST['groupID'].$_POST['groupIdScene1'].$_POST['groupIdScene2']."<br>\n";
        // echo "Action: ".$_POST['submitButton']."<br>\n";

        switch ($_POST['submitButton']) {

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
        }

        // TX Power
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'TxPower Z'.$zigateId ) {
                echo "TxPower request processing";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/TxPower', $_POST['TxPowerValue'] );
            }
        }

        // Set Extended PANID
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'Set Extended PANID Z'.$zigateId ) {
                echo "Set Extended PANID request processing";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/0000/setExtendedPANID', $_POST['extendedPanId'] );
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
    } catch (Exception $e) {
        echo '<br>error: '.$e->getMessage();
    }
    echo "<br>Fin";
    sleep(1);
    header ("location:../../../../index.php?v=d&m=Abeille&p=Abeille");
?>
