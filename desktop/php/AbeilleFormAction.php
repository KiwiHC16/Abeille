<?php
    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__).'/../../core/class/Abeille.class.php';
        
    $zigateIds = array( '', '2', '3', '4', '5' );
    
    function sendMessageFromFormToCmd( $topic, $payload ) {
        
        $queueKeyFormToCmd   = msg_get_queue(queueKeyFormToCmd);
        $msgAbeille = new MsgAbeille;
        
        $msgAbeille->message['topic']   = $topic;
        $msgAbeille->message['payload'] = $payload;
        
        if (msg_send($queueKeyFormToCmd, 1, $msgAbeille, true, false)) {
            echo "added to queue (".queueKeyFormToCmd."): ".json_encode($msgAbeille)."<br>\n";
        }
        else {
            echo "could not add message to queue id: ".$queueKeyId."<br>\n";
        }
    }
    
    function getInfosFromNe( $item, $value, $client ) {
      $deviceId = substr( $item, strpos($item,"-")+1 );
      echo "deviceId: ".substr( $item, strpos($item,"-")+1 )."<br>";
      $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
      list( $dest, $address ) = explode( "/", $device->getLogicalId() );
      echo "address: ".$address."<br>\n";
      $EP = $device->getConfiguration('mainEP');
      echo "EP: ".$EP."<br>\n";

      // Get Name
      sendMessageFromFormToCmd('CmdAbeille/Ruche/ActiveEndPoint',           'dest='.$dest.'&address='.$address                             );
      sendMessageFromFormToCmd('CmdAbeille/Ruche/SimpleDescriptorRequest',  'dest='.$dest.'&address='.$address.'&endPoint='.$EP            );
      sendMessageFromFormToCmd('CmdAbeille/Ruche/IEEE_Address_request',     'dest='.$dest.'&address='.$address                             );
      sendMessageFromFormToCmd('CmdAbeille/Ruche/getName',                  'dest='.$dest.'&address='.$address.'&destinationEndPoint='.$EP );
      sendMessageFromFormToCmd('CmdAbeille/Ruche/getLocation',              'dest='.$dest.'&address='.$address.'&destinationEndPoint='.$EP );
      sendMessageFromFormToCmd('CmdAbeille/Ruche/getGroupMembership',       'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP );
      // sendMessageFromFormToCmd('CmdAbeille/Ruche/getSceneMembership',   'address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$grouID, 0);
      // sendMessageFromFormToCmd('CmdAbeille/Ruche/ReadAttributeRequest', 'address='.$address.'&DestinationEndPoint='.$EP'.'&ClusterId='.$clusterId'.'&attributId='.$attributId'.'&Proprio='.$proprio', 0);

    }


    try {

        echo "_POST: ".json_encode( $_POST )."<br>\n";
        echo "Group: ".$_POST['groupID'].$_POST['groupIdScene1'].$_POST['groupIdScene2']."<br>\n";
        echo "Action: ".$_POST['submitButton']."<br>\n";

        switch ($_POST['submitButton']) {

            // Group
            case 'Add Group':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/addGroup',           'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        sleep(1);
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/getGroupMembership', 'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP );
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
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/commissioningGroupAPS',           'dest='.$dest.'&address='.$address.'&groupId='.$_POST['group'] );
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
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/removeGroup',        'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        sleep(1);
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/getGroupMembership', 'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP );
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
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/getGroupMembership', 'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP );
                    }
                }
                
                break;

            // Scene
            case 'View Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/viewScene',           'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );

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
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/storeScene',           'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );

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
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/recallScene',           'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
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
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/sceneGroupRecall',       'dest='.$dest.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                    }
                }
                }
                else {
                    sendMessageFromFormToCmd('CmdAbeille/Ruche/sceneGroupRecall',       'dest='.$dest.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
                }
                break;

            case 'Add Scene':
                foreach ( $_POST as $item=>$Value ) {
                    if ( strpos("-".$item, "eqSelected") == 1 ) {
                        echo "Id: ".substr( $item, strpos($item,"-")+1 )."<br>";
                        $device = eqLogic::byId(substr( $item, strpos($item,"-")+1 ));
                        list( $dest, $address ) = explode( "/", $device->getLogicalId() );
                        $EP = $device->getConfiguration('mainEP');
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/addScene',           'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'].'&sceneName=aa' );
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
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/removeScene',           'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene2'].'&sceneID='.$_POST['sceneID'] );
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
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/getSceneMembership',     'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene1'] );
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
                        sendMessageFromFormToCmd('CmdAbeille/Ruche/removeSceneAll',         'dest='.$dest.'&address='.$address.'&DestinationEndPoint='.$EP.'&groupID='.$_POST['groupIdScene1'] );
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
                        // sendMessageFromFormToCmd('CmdAbeille/Ruche/addGroup', 'address='.(substr( $item, strpos($item,"-")+1 )).'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
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
                        // sendMessageFromFormToCmd('CmdAbeille/Ruche/addGroup', 'address='.(substr( $item, strpos($item,"-")+1 )).'&DestinationEndPoint='.$EP.'&groupAddress='.$_POST['group'] );
                        getInfosFromNe( $item, $Value, $client );
                        // abeille::updateConfigAbeille( );
                    }
                }
                break;
        }
        
        // TX Power
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'TxPower Z'.$zigateId ) {
                echo "TxPower request processing";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/Ruche/TxPower', $_POST['TxPowerValue'] );
            }
        }
                
        // Set Channel Mask
        foreach ( $zigateIds as $zigateId ) {
            if ( $_POST['submitButton'] == 'Set Channel Mask Z'.$zigateId ) {
                echo "TxPower request processing";
                sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/Ruche/setChannelMask', $_POST['channelMask'] );
            }
        }
                
        // Set Extended PANID
        foreach ( $zigateIds as $zigateId ) {
          if ( $_POST['submitButton'] == 'Set Extended PANID Z'.$zigateId ) {
                  echo "TxPower request processing";
              sendMessageFromFormToCmd('CmdAbeille'.$zigateId.'/Ruche/setExtendedPANID', $_POST['extendedPanId'] );
          }
        }

    } catch (Exception $e) {
        echo '<br>error: '.$e->getMessage();
    }
    echo "<br>Fin";
    sleep(1);
    header ("location:/index.php?v=d&m=Abeille&p=Abeille");

    ?>
