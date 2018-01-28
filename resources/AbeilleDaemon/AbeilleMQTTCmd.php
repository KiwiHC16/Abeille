<?php

    require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';

    include("CmdToAbeille.php");  // contient processCmd()
    include("lib/phpMQTT.php");

    function procmsg($topic, $msg)
    {
        global $dest;

        log::add('AbeilleMQTTC', 'debug', 'AbeilleMQTT, Msg Received: Topic: {'.$topic.'} =>\t'.$msg.'\n');

        // list($type, $address, $action) = split('[/.-]', $topic); split ne fonctionne plus avec php 7
        list($type, $address, $action) = explode('/', $topic);
        //
        log::add('AbeilleMQTTC', 'debug', 'AbeilleMQTT, Type: '.$type.'Address: '.$address.'Action: '.$action);

        if ($type == "CmdAbeille") {
            if ($action == "Annonce") {
                $Command = array(
                    "ReadAttributeRequest" => "1",
                    "address" => $address,
                    "clusterId" => "0000",
                    "attributeId" => "0005",
                );
            } elseif ($action == "OnOff") {
                if ($msg == "On") {
                    $actionId = "01";
                }
                if ($msg == "Off") {
                    $actionId = "00";
                }
                if ($msg == "Toggle") {
                    $actionId = "02";
                }
                $Command = array(
                    "onoff" => "1",
                    "address" => $address,
                    "action" => $actionId,
                    "clusterId" => "0006",
                );
            } elseif ($action == "ReadAttributeRequest") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                    "ReadAttributeRequest" => "1",
                    "address" => $address,
                    "clusterId" => $keywords[1],
                    "attributeId" => $keywords[3],
                );
            } elseif ($action == "setLevel") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                    "setLevel" => "1",
                    "address" => $address,
                    "clusterId" => "0008",
                    "Level" => intval($keywords[1] * 255 / 100),
                    "duration" => $keywords[3],
                );
            } elseif ($action == "") {
                $keywords = preg_split("/[=&]+/", $msg);
                $Command = array(
                    "setLevel" => "1",
                    "address" => $address,
                    "clusterId" => "0008",
                    "Level" => intval($keywords[1] * 255 / 100),
                    "duration" => $keywords[3],
                );
            } else {
                log::add('AbeilleMQTTC', 'warning', 'AbeilleMQTT, command unknown: '.$action);
            }


            /*---------------------------------------------------------*/
            if ($address == "Ruche") {
                // msg est une string simple ou  msg de la forme des parametre d un get http parma1=xxx&param2=yyy&param3=zzzz
                $keywords = preg_split("/[=&]+/", $msg);

                // Si une string simple
                if (count($keywords) == 1) {
                    $Command = array($action => $msg);
                } // Si une command type get htt
                else {
                    if (count($keywords) == 4) {
                        log::add('AbeilleMQTTC', 'debug', 'AbeilleMQTT,4 arguments command');
                        $Command = array(
                            $action => $action,
                            $keywords[0] => $keywords[1],
                            $keywords[2] => $keywords[3],
                        );
                    }
                    if (count($keywords) == 6) {
                        log::add('AbeilleMQTTC', 'debug', 'AbeilleMQTT,6 arguments command');
                        $Command = array(
                            $action => $action,
                            $keywords[0] => $keywords[1],
                            $keywords[2] => $keywords[3],
                            $keywords[4] => $keywords[5],
                        );
                    }
                }
            }


            /*---------------------------------------------------------*/

            // print_r( $Command );
            processCmd($dest, $Command);
        } else {
            log::add(
                'Abeille',
                'warning',
                'AbeilleMQTT, Msg Received: Topic: {'.$topic.'} =>\t'.$msg.'mais je ne sais pas quoi en faire, no action.'
            );
        }
    }

    //                      1          2           3       4          5       6
    //$paramDaemon1 = $serialPort.' '.$address.' '.$port.' '.$user.' '.$pass.' '.$qos;

    $dest = $argv[1];
    $server = $argv[2];     // change if necessary
    $port = $argv[3];                     // change if necessary
    $username = $argv[5];                   // set your username
    $password =  $argv[6];                   // set your password
    $client_id = "AbeilleMQTTCmd"; // make sure this is unique for connecting to sever - you could use uniqid()
    $qos=$argv[6];
    $mqtt = new phpMQTT($server, $port, $client_id);


    log::add('AbeilleMQTTCmd', 'debug', 'main: usb='.$dest.' server='.$server.':'.$port.' username='.$username.' pass='.$password.' qos='.$qos);

    if (!$mqtt->connect(true, null, $username, $password)) {
        exit(1);
    }

    $topics['CmdAbeille/#'] = array("qos" => 0, "function" => "procmsg");

    $mqtt->subscribe($topics, $qos);

    while ($mqtt->proc()) {

    }

    $mqtt->close();


?>
