<?php
    include_once __DIR__.'/AbeilleDebug.class.php';
    include_once __DIR__.'/../php/AbeilleZigbeeConst.php'; // Attribute type

    class AbeilleCmdProcess extends AbeilleDebug {

        // // Inverse l ordre des des octets.
        // function reverse_hex( $a ) {
        //     $reverse = "";

        //     for ($i = strlen($a)-2; $i >= 0; $i-=2) {
        //         // echo $i . " -> " . $a[$i] . $a[$i+1] . "\n";
        //         $reverse .= $a[$i].$a[$i+1];
        //     }
        //     return $reverse;
        // }

        /* Check if required param are set in 'Command'.
           Returns: true if ok, else false */
        function checkRequiredParams($required, $Command) {
            $missingParam = false;
            foreach ($required as $idx => $param) {
                if (isset($Command[$param]))
                    continue;
                $this->deamonlog('debug', "    ERROR: Missing '".$param."'");
                $missingParam = true;
            }
            if ($missingParam)
                return false;
            return true;
        }

        // Ne semble pas fonctionner et me fait planté la ZiGate, idem ques etParam()
        // Tcharp38: See https://github.com/KiwiHC16/Abeille/issues/2143#
        function ReportParamXiaomi($dest,$Command) {
            // Write Attribute request
            // Msg Type = 0x0110

            // <address mode: uint8_t>
            // <target short address: uint16_t>
            // <source endpoint: uint8_t>
            // <destination endpoint: uint8_t>
            // <Cluster id: uint16_t>
            // <direction: uint8_t>
            // <manufacturer specific: uint8_t>
            // <manufacturer id: uint16_t>
            // <number of attributes: uint8_t>
            // <attributes list: data list of uint16_t  each>
            //      Direction:
            //          0 - from server to client
            //          1 - from client to server
            //      Manufacturer specific :
            //          1 – Yes
            //          0 – No

            $priority = $Command['priority'];

            $cmd                    = "0110";

            $addressMode            = "02"; // Short Address -> 2
            $address                = $Command['address'];
            $sourceEndpoint         = "01";
            $destinationEndpoint    = "01";
            $clusterId              = $Command['clusterId'];
            $direction              = "00";
            $manufacturerSpecific   = "01";
            $proprio                = $Command['Proprio'];
            $numberOfAttributes     = "01";
            $attributeId            = $Command['attributeId'];
            $attributeType          = $Command['attributeType'];
            $value                  = $Command['value'];

            $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$clusterId.$direction.$manufacturerSpecific.$proprio.$numberOfAttributes.$attributeId.$attributeType.$value;

            $length = sprintf("%04s", dechex(strlen($data) / 2));

            $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);

            if (isset($Command['repeat'])) {
                if ($Command['repeat']>1 ) {
                    for ($x = 2; $x <= $Command['repeat']; $x++) {
                        sleep(5);
                        $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                    }
                }
            }
        }

        // J'ai un probleme avec la command 0110, je ne parviens pas à l utiliser. Prendre setParam2 en atttendant.
        function setParam($dest,$address,$clusterId,$attributeId,$destinationEndPoint,$Param) {
            /*
                <address mode: uint8_t>
                <target short address: uint16_t>
                <source endpoint: uint8_t>
                <destination endpoint: uint8_t>
                <Cluster id: uint16_t>
                <direction: uint8_t>
                <manufacturer specific: uint8_t>
                <manufacturer id: uint16_t>
                <number of attributes: uint8_t>
                <attributes list: data list of uint16_t  each>
                Direction:
                0 - from server to client
                1 - from client to server
                */

            $priority               = $Command['priority'];

            $cmd                    = "0110";
            $length                  = "000E";

            $addressMode            = "02";
            // $address             = $Command['address'];
            $sourceEndpoint         = "01";
            // $destinationEndpoint = "01";
            //$ClusterId            = "0006";
            $Direction              = "01";
            $manufacturerSpecific   = "00";
            $manufacturerId         = "0000";
            $numberOfAttributes     = "01";
            $attributesList         = $attributeId;
            $attributesList         = "Salon1         ";

            $data = $addressMode.$address.$sourceEndpoint.$destinationEndPoint.$clusterId.$Direction.$manufacturerSpecific.$manufacturerId.$numberOfAttributes.$attributesList;

            $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
        }

        /**
         * setParam2: send the commande to zigate to send a write attribute on zigbee network
         *
         * @param dest
         * @param address
         * @param clusterId
         * @param attributeId
         * @param destinationEndPoint
         * @param Param
         * @param dataType
         *
         * @return none
         */
        function setParam2( $dest, $address, $clusterId, $attributeId, $destinationEndPoint, $Param, $dataType) {
            $this->deamonlog('debug', "  command setParam2", $this->debug['processCmd']);

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)
            // <data: auint8_t>
            // APS Part <= data
            // dummy 00 to align mesages                                            -> 1
            // <target extended address: uint64_t>                                  -> 8
            // <target endpoint: uint8_t>                                           -> 1
            // <cluster ID: uint16_t>                                               -> 2
            // <destination address mode: uint8_t>                                  -> 1
            // <destination address:uint16_t or uint64_t>                           -> 8
            // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            // => 34 -> 0x22

            $addressMode            = "02";
            $targetShortAddress     = $address;
            $sourceEndpoint         = "01";
            $destinationEndpoint    = $destinationEndPoint;
            $profileID              = "0104";
            $clusterID              = $clusterId;
            $securityMode           = "02"; // ???
            $radius                 = "30";
            // $dataLength <- calculated later

            $frameControl               = "00";
            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute      = "02";

            $attributeId = $attributeId[2].$attributeId[3].$attributeId[0].$attributeId[1]; // $attributeId;

            $lengthAttribut = sprintf("%02s",dechex(strlen( $Param ))); // "0F";
            $attributValue = ""; for ($i=0; $i < strlen($Param); $i++) { $attributValue .= sprintf("%02s",dechex(ord($Param[$i]))); }

            $data2 = $frameControl.$transqactionSequenceNumber.$commandWriteAttribute.$attributeId.$dataType.$lengthAttribut.$attributValue;

            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;

            $data = $data1.$data2;

            $length = sprintf("%04s", dechex(strlen($data) / 2));

            $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
        }

        /**
         * setParam3: send the commande to zigate to send a write attribute on zigbee network with a proprio field
         *
         * @param dest
         * @param Command with following info: Proprio=115f&clusterId=0000&attributeId=ff0d&attributeType=20&value=15
         *
         * @return none
         */
        function setParam3($dest,$Command) {
            $this->deamonlog('debug', "command setParam3", $this->debug['processCmd']);

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)

            // <data: auint8_t>
            // APS Part <= data
            // dummy 00 to align mesages                                            -> 1
            // <target extended address: uint64_t>                                  -> 8
            // <target endpoint: uint8_t>                                           -> 1
            // <cluster ID: uint16_t>                                               -> 2
            // <destination address mode: uint8_t>                                  -> 1
            // <destination address:uint16_t or uint64_t>                           -> 8
            // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            // => 34 -> 0x22

            $addressMode                = "02";
            $targetShortAddress         = $Command['address'];
            $sourceEndpoint             = "01";

            if (isset($Command['destinationEndpoint'])) {
                if ($Command['destinationEndpoint']>1 ) {
                    $destinationEndpoint = $Command['destinationEndpoint'];
                }
                else {
                    $destinationEndpoint = "01";
                }
            }
            else {
                $destinationEndpoint = "01";
            }

            $profileID                  = "0104";
            $clusterID                  = $Command['clusterId'];

            $securityMode               = "02"; // ???
            $radius                     = "30";
            // $dataLength <- define later

            $frameControlAPS            = "40";   // APS Control Field
            // If Ack Request 0x40 If no Ack then 0x00
            // Avec 0x40 j'ai un default response

            $frameControlZCL            = "14";   // ZCL Control Field
            // Disable Default Response + Manufacturer Specific

            $frameControl = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $Proprio                    = $Command['Proprio'][2].$Command['Proprio'][3].$Command['Proprio'][0].$Command['Proprio'][1];

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute      = "02";

            $attributeId                = $Command['attributeId'][2].$Command['attributeId'][3].$Command['attributeId'][0].$Command['attributeId'][1];

            $dataType                   = $Command['attributeType'];

            $attributValue              = $Command['value'];

            $data2                      = $frameControl.$Proprio.$transqactionSequenceNumber.$commandWriteAttribute.$attributeId.$dataType.$attributValue;

            $dataLength                 = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1                      = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;

            $data                       = $data1.$data2;

            $length                      = sprintf("%04s",dechex(strlen($data) / 2));

            $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
        }

        /**
         * setParam4: send the commande to zigate to send a write attribute on zigbee network without a proprio field
         *
         * @param dest
         * @param Command with following info: clusterId=fc01&attributeId=0000&attributeType=09&value=0101
         *
         * @return none
         */
        function setParam4($dest,$Command) {

            $this->deamonlog('debug', "command setParam4", $this->debug['processCmd']);

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)

            // <data: auint8_t>
            // APS Part <= data
            // dummy 00 to align mesages                                            -> 1
            // <target extended address: uint64_t>                                  -> 8
            // <target endpoint: uint8_t>                                           -> 1
            // <cluster ID: uint16_t>                                               -> 2
            // <destination address mode: uint8_t>                                  -> 1
            // <destination address:uint16_t or uint64_t>                           -> 8
            // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            // => 34 -> 0x22

            $addressMode                = "02";
            $targetShortAddress = $Command['address'];
            $sourceEndpoint             = "01";
            if ($Command['destinationEndpoint']>1 ) { $destinationEndpoint = $Command['destinationEndpoint']; } else { $destinationEndpoint = "01"; } // $destinationEndPoint; // "01";

            $profileID                  = "0104";
            $clusterID                  = $Command['clusterId'];

            $securityMode               = "02"; // ???
            $radius                     = "30";
            // $dataLength <- calculated later

            $frameControlAPS            = "40";   // APS Control Field - If Ack Request 0x40 If no Ack then 0x00 - Avec 0x40 j'ai un default response

            $frameControlZCL            = "10";   // ZCL Control Field - Disable Default Response + Not Manufacturer Specific

            $frameControl               = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute      = "02";

            $attributeId                = $Command['attributeId'][2].$Command['attributeId'][3].$Command['attributeId'][0].$Command['attributeId'][1];

            $dataType                   = $Command['attributeType'];
            $attributValue              = $Command['value'];

            $data2                      = $frameControl.$transqactionSequenceNumber.$commandWriteAttribute.$attributeId.$dataType.$attributValue;

            $dataLength                 = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1                      = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;

            $data                       = $data1.$data2;

            $length                      = sprintf("%04s",dechex(strlen($data) / 2));

            $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
        }

        /**
         * setParamGeneric: send the commande to zigate to send a write attribute on zigbee network
         * This setParam try to be generic because there are too many setParam function above
         * Target: On long term would replace all of them.
         *
         * @param dest
         * @param Command with following info: clusterId=fc01&attributeId=0000&attributeType=09&value=0101
         *
         * @return none
         */
        function setParamGeneric($dest,$Command) {

            $this->deamonlog('debug', "command setParam4", $this->debug['processCmd']);

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)

            // <data: auint8_t>
            // APS Part <= data
            // dummy 00 to align mesages                                            -> 1
            // <target extended address: uint64_t>                                  -> 8
            // <target endpoint: uint8_t>                                           -> 1
            // <cluster ID: uint16_t>                                               -> 2
            // <destination address mode: uint8_t>                                  -> 1
            // <destination address:uint16_t or uint64_t>                           -> 8
            // <destination endpoint (value ignored for group address): uint8_t>    -> 1
            // => 34 -> 0x22

            $addressMode        = "02";
            $targetShortAddress = $Command['address'];
            $sourceEndpoint     = "01";
            if ($Command['destinationEndpoint']>1 ) { $destinationEndpoint = $Command['destinationEndpoint']; } else { $destinationEndpoint = "01"; } // $destinationEndPoint; // "01";

            $profileID          = "0104";
            $clusterID          = $Command['clusterId'];

            $securityMode       = "02"; // ???
            $radius             = "30";
            // $dataLength <- calculated later

            $frameControlAPS    = "40";   // APS Control Field - If Ack Request 0x40 If no Ack then 0x00 - Avec 0x40 j'ai un default response

            if ($Command['Proprio'] == '' ) {
                $frameControlZCL    = "10";   // ZCL Control Field - Disable Default Response + Not Manufacturer Specific
            }
            else {
                $frameControlZCL    = "14";   // ZCL Control Field - Disable Default Response + Manufacturer Specific
            }
            $Proprio = $Command['Proprio'];

            $frameControl       = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

            $transqactionSequenceNumber = "1A"; // to be reviewed
            $commandWriteAttribute      = "02";

            $attributeId        = $Command['attributeId'][2].$Command['attributeId'][3].$Command['attributeId'][0].$Command['attributeId'][1];

            $dataType           = $Command['attributeType'];
            $attributValue      = $Command['value'];

            $data2 = $frameControl.$Proprio.$transqactionSequenceNumber.$commandWriteAttribute.$attributeId.$dataType.$attributValue;

            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;

            $data = $data1.$data2;

            $length = sprintf("%04s", dechex(strlen($data) / 2));

            $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
        }

        // Needed for fc41 of Legrand Contacteur
        function commandLegrand($dest,$Command) {

            $priority = $Command['priority'];

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1  (22 -> 0x16)

            // <data: auint8_t>


            $addressMode = "02";
            $targetShortAddress = $Command['address'];
            $sourceEndpoint = "01";
            if ($Command['destinationEndpoint']>1 ) { $destinationEndpoint = $Command['destinationEndpoint']; } else { $destinationEndpoint = "01"; } // $destinationEndPoint; // "01";

            $profileID = "0104";
            $clusterID = "FC41";

            $securityMode = "02"; // ???
            $radius = "30";
            // $dataLength = define later

            // ---------------------------

            $frameControlAPS = "15";   // APS Control Field, see doc for details

            $manufacturerCode = "2110";
            $transqactionSequenceNumber = "1A"; // to be reviewed
            $command = "00";

            // $data = "00"; // 00 = Off, 02 = Auto, 03 = On.
            $data = $Command['Mode'];

            $data2 = $frameControlAPS.$manufacturerCode.$transqactionSequenceNumber.$command.$data;

            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;

            $data = $data1.$data2;

            $length = sprintf("%04s", dechex(strlen($data) / 2));

            $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
        }

        // Generate a 'Read attribute request'
        function readAttribute($priority, $dest, $addr, $destEp, $clustId, $attribId, $manufId = '') {
            /*
                <address mode: uint8_t>
                <target short address: uint16_t>
                <source endpoint: uint8_t>
                <destination endpoint: uint8_t>
                <Cluster id: uint16_t>
                <direction: uint8_t>
                    0 - from server to client
                    1 - from client to server
                <manufacturer specific: uint8_t>
                    0 – No
                    1 – Yes
                <manufacturer id: uint16_t>
                <number of attributes: uint8_t>
                <attributes list: data list of uint16_t  each>
            */

            $cmd = "0100";

            $addrMode = "02";
            // TODO: Check 'addr' size
            $srcEp = "01";
            // TODO: Check 'destEp' size
            // TODO: Check 'clustId' size
            $dir = "00";

            if (($manufId == "") || ($manufId == "0000")) {
                $manufSpecific = "00";
                $manufId = "0000";
            } else {
                $manufSpecific = "01";
                // TODO: check manufId size
            }

            /* Supporting multi-attributes (ex: '0505,0508,050B')/
            Tcharp38 note: This way is not recommended as Zigate team is unable to tell the max number
                of attributs for the request to be "functional". Seems ok up to 4. */
            $list = explode(',', $attribId);
            $attribList = "";
            $nbAttr = 0;
            foreach ($list as $attrId) {
                // TODO: Check 'attrId' size
                $attribList .=  $attrId;
                $nbAttr++;
            }
            $nbOfAttrib = sprintf("%02X", $nbAttr);

            $data = $addrMode.$addr.$srcEp.$destEp.$clustId.$dir.$manufSpecific.$manufId.$nbOfAttrib.$attribList;
            $len = sprintf("%04s", dechex(strlen($data) / 2));
            $this->addCmdToQueue($priority, $dest, $cmd, $len, $data, $addr);
        }

        /**
         * getParamMulti
         *
         * Read Attribut from a specific cluster. Can read multiple attribut in one message.
         * Doesn't ask for APS ACk, for default Response ZCL to reduce traffic on net
         *
         * @param Command including priority, dest, address, EP, clusterId, Proprio, attributId
         * proprio not implemented yet
         *
         *
         * @return none,
         */
        function getParamMulti($Command) {
            $this->deamonlog('debug', " command getParamMulti", $this->debug['processCmd']);

            $cmd = "0530";

            // <address mode: uint8_t>              -> 1
            // <target short address: uint16_t>     -> 2
            // <source endpoint: uint8_t>           -> 1
            // <destination endpoint: uint8_t>      -> 1

            // <profile ID: uint16_t>               -> 2
            // <cluster ID: uint16_t>               -> 2

            // <security mode: uint8_t>             -> 1
            // <radius: uint8_t>                    -> 1
            // <data length: uint8_t>               -> 1
            //                                                                                12 -> 0x0C
            // <data: auint8_t>
            // ZCL Control Field
            // ZCL SQN
            // Commad Id
            // ....

            $priority               = $Command['priority'];
            $dest                   = $Command['dest'];

            $addressMode            = "02";
            $targetShortAddress     = $Command['address'];
            $sourceEndpoint         = "01";
            $destinationEndpoint    = $Command['EP'];
            $clusterID              = $Command['clusterId'];
            $profileID              = "0104";
            $securityMode           = "02";
            $radius                 = "1E";

            $zclControlField        = "10";
            $transactionSequence    = "01";
            $cmdId                  = "00";

            $attributs = "";
            $attributList           = explode(',',$Command['attributeId']);

            foreach ($attributList as $attribut) {
                $attributs .= AbeilleTools::reverseHex(str_pad( $attribut, 4, "0", STR_PAD_LEFT));
            }

            $sep = "";
            $data2 = $zclControlField.$sep.$transactionSequence.$sep.  $cmdId.$sep.$attributs;
            $sep = "-";
            $data2Txt = $zclControlField.$sep.$transactionSequence.$sep.  $cmdId.$sep.$attributs;

            $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2));

            $sep = "";
            $data1 = $addressMode.$sep.$targetShortAddress.$sep.$sourceEndpoint.$sep.$destinationEndpoint.$sep.$clusterID.$sep.$profileID.$sep.$securityMode.$sep.$radius.$sep.$dataLength;
            $sep = "-";
            $data1Txt = $addressMode.$sep.$targetShortAddress.$sep.$sourceEndpoint.$sep.$destinationEndpoint.$sep.$clusterID.$sep.$profileID.$sep.$securityMode.$sep.$radius.$sep.$dataLength;

            $data = $data1.$data2;
            $length = sprintf("%04s", dechex(strlen($data) / 2));

            $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
        }

        // getParamHue: based on getParam for testing purposes. If works then perhaps merge with get param and manage the diff by parameters like destination endpoint
        function getParamHue($priority,$dest,$address,$clusterId,$attributeId) {
            $this->deamonlog('debug','getParamHue', $this->debug['processCmd']);

            $priority = $Command['priority'];

            $cmd = "0100";
            $length = "000E";
            $addressMode = "02";
            // $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = "0B";
            //$ClusterId = "0006";
            $ClusterId = $clusterId;
            $Direction = "00";
            $manufacturerSpecific = "00";
            $manufacturerId = "0000";
            $numberOfAttributes = "01";
            // $attributesList = "0000";
            $attributesList = $attributeId;

            $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$ClusterId.$Direction.$manufacturerSpecific.$manufacturerId.$numberOfAttributes.$attributesList;

            $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
        }

        // getParamOSRAM: based on getParam for testing purposes. If works then perhaps merge with get param and manage the diff by parameters like destination endpoint
        function getParamOSRAM($priority,$dest,$address,$clusterId,$attributeId) {
            $this->deamonlog('debug','getParamOSRAM', $this->debug['processCmd']);

            $priority = $Command['priority'];

            $cmd = "0100";
            $length = "000E";
            $addressMode = "02";
            // $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = "03";
            //$ClusterId = "0006";
            $ClusterId = $clusterId;
            $Direction = "00";
            $manufacturerSpecific = "00";
            $manufacturerId = "0000";
            $numberOfAttributes = "01";
            // $attributesList = "0000";
            $attributesList = $attributeId;

            $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$ClusterId.$Direction.$manufacturerSpecific.$manufacturerId.$numberOfAttributes.$attributesList;

            $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
        }

        /**
         * reviewPriority()
         *
         * See if we need to change the priority reaquested for a message
         *
         * @param Command
         * @return priority re-evaluated
         *
         */
        function reviewPriority($Command) {
            if (isset($Command['priority'])) {
                // TODO: Eq Address and Group Address can't be distingueshed here. Probability to have a group address = eq address is low but exist.
                if (isset($Command['address'])) {
                    if ($NE = Abeille::byLogicalId($Command['dest'].'/'.$Command['address'], 'Abeille')) {
                        if ($NE->getIsEnable()) {
                            if (( time() - strtotime($NE->getStatus('lastCommunication'))) < (60*$NE->getTimeout()) ) {
                                if ($NE->getStatus('APS_ACK', '1') == '1') {
                                    return $Command['priority'];
                                }
                                else {
                                    $this->deamonlog('debug', "    NE n a pas repondu lors de precedente commande alors je mets la priorite au minimum.");
                                    return priorityLostNE;
                                }
                            }
                            else {
                                $this->deamonlog('debug', "    NE en Time Out alors je mets la priorite au minimum.");
                                return priorityLostNE;
                            }
                        }
                        else {
                            /* Tcharp38: Preventing cmd to be sent if EQ is disabled is not good here.
                            If EQ was disabled but now under pairing process (dev announce)
                            this prevents interrogation of EQ and therefore reinclusion.
                            This check should be done at source not here. At least don't filter
                            requests from parser. */
                            // $this->deamonlog('debug', "    NE desactive, je n envoie pas de commande.");
                            // return -1;
                            return $Command['priority'];
                        }
                    }
                    else {
                        $this->deamonlog('debug', "    NE n existe pas dans Abeille, une annonce/une commande de groupe, je ne touche pas à la priorite.");
                        return $Command['priority'];
                    }
                }
                else {
                    return $Command['priority'];
                }
            }
            else {
                $this->deamonlog('debug', "    priority not defined !!!");
                return priorityInterrogation;
            }
        }

        /**
         * processCmd()
         *
         * Convert an Abeille internal command to proper zigate format
         *
         * @param Command
         * @return None
         */
        function processCmd($Command) {

            $this->deamonlog("debug", "    L1 - processCmd(".json_encode($Command).")", $this->debug['processCmd']);

            if (!isset($Command)) {
                $this->deamonlog('debug', "    L1 - Command not set", $this->debug['processCmd']);
                return;
            }

            $priority   = $this->reviewPriority($Command);
            if ($priority==-1) {
                $this->deamonlog("debug", "    L1 - processCmd - can t define priority, stop here");
                return;
            }
            if (isset($Command['dest']))
                $dest       = $Command['dest'];
            else {
                $this->deamonlog("debug", "    L1 - No dest defined, stop here");
                return;
            }


            //---- PDM ------------------------------------------------------------------
            if (isset($Command['PDM'])) {

                if (isset($Command['req']) && $Command['req'] == "E_SL_MSG_PDM_HOST_AVAILABLE_RESPONSE") {
                    $cmd = "8300";

                    $PDM_E_STATUS_OK = "00";
                    $data = $PDM_E_STATUS_OK;

                    $length = sprintf("%04s", dechex(strlen($data) / 2));
                    $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                }

                if (isset($Command['req']) && $Command['req'] == "E_SL_MSG_PDM_EXISTENCE_RESPONSE") {
                    $cmd = "8208";

                    $recordId = $Command['recordId'];

                    $recordExist = "00";

                    if ($recordExist == "00" ) {
                        $size = '0000';
                        $persistedData = "";
                    }

                    $data = $recordId.$recordExist.$size;

                    $length = sprintf("%04s", dechex(strlen($data) / 2));
                    $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                }
                return;
            }

            //----------------------------------------------------------------------

            // abeilleList abeilleListAll
            if (isset($Command['abeilleList'])) {
                $this->deamonlog('debug', "    Get Abeilles List", $this->debug['processCmd']);
                $this->addCmdToQueue($priority,$dest,"0015","0000","");
                return;
            }


            //----------------------------------------------------------------------
            if (isset($Command['setOnZigateLed'])) {
                $this->deamonlog('debug', "    setOnZigateLed", $this->debug['processCmd']);
                $cmd = "0018";
                $data = "01";

                $length = sprintf("%04s", dechex(strlen($data) / 2));
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            if (isset($Command['setOffZigateLed'])) {
                $this->deamonlog('debug', "    setOffZigateLed", $this->debug['processCmd']);
                $cmd = "0018";
                $data = "00";

                $length = sprintf("%04s", dechex(strlen($data) / 2));
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            //----------------------------------------------------------------------
            if (isset($Command['setCertificationCE'])) {
                $this->deamonlog('debug', "    setCertificationCE", $this->debug['processCmd']);
                $cmd = "0019";
                $data = "01";

                $length = sprintf("%04s", dechex(strlen($data) / 2));
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            if (isset($Command['setCertificationFCC'])) {
                $this->deamonlog('debug', "    setCertificationFCC", $this->debug['processCmd']);
                $cmd = "0019";
                $data = "02";

                $length = sprintf("%04s", dechex(strlen($data) / 2));
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            //----------------------------------------------------------------------
            // https://github.com/fairecasoimeme/ZiGate/issues/145
            // PHY_PIB_TX_POWER_DEF (default - 0x80)
            // PHY_PIB_TX_POWER_MIN (minimum - 0)
            // PHY_PIB_TX_POWER_MAX (maximum - 0xbf)
            if (isset($Command['TxPower'])  ) {
                $this->deamonlog('debug', "    TxPower", $this->debug['processCmd']);
                $cmd = "0806";
                $data = $Command['TxPower'];
                if ($data < 10 ) $data = '0'.$data;

                $length = sprintf("%04s", dechex(strlen($data) / 2));
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            //----------------------------------------------------------------------
                // https://github.com/fairecasoimeme/ZiGate/issues/145
                // Added cmd 0807 Get Tx Power #175
                // PHY_PIB_TX_POWER_DEF (default - 0x80)
                // PHY_PIB_TX_POWER_MIN (minimum - 0)
                // PHY_PIB_TX_POWER_MAX (maximum - 0xbf)
                if (isset($Command['GetTxPower'])) {
                    $this->deamonlog('debug', "    GetTxPower", $this->debug['processCmd']);
                    $cmd = "0807";
                    $data = "";

                    $length = sprintf("%04s", dechex(strlen($data) / 2));
                    $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                    return;
                }

            //----------------------------------------------------------------------
            if (isset($Command['setChannelMask'])) {
                $this->deamonlog('debug', "    setChannelMask", $this->debug['processCmd']);
                $cmd = "0021";
                $data = $Command['setChannelMask'];

                $length = sprintf("%04s", dechex(strlen($data) / 2));
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            //----------------------------------------------------------------------
            if (isset($Command['setExtendedPANID'])) {
                $this->deamonlog('debug', "    setExtendedPANID", $this->debug['processCmd']);
                $cmd = "0020";
                $data = $Command['setExtendedPANID'];

                $length = sprintf("%04s", dechex(strlen($data) / 2));
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            //----------------------------------------------------------------------
            if (isset($Command["getNetworkStatus"])) {
                $this->addCmdToQueue($priority, $dest, "0009", "0000", "");
                return;
            }

            //----------------------------------------------------------------------
            // Management Network Update request
            // ZPS_eAplZdpMgmtNwkUpdateRequest - APP_eZdpMgmtNetworkUpdateReq - E_SL_MSG_MANAGEMENT_NETWORK_UPDATE_REQUEST
            if (isset($Command['managementNetworkUpdateRequest']) && isset($Command['address'])) {
                // Msg Type =  0x004A

                // <target short address: uint16_t>
                // <channel mask: uint32_t>
                // <scan duration: uint8_t>
                // <scan count: uint8_t> -> Si valeur a 5 alors l ampoule envoit 5 messages avec les mesures
                // <network update ID: uint8_t>
                // <network manager short address: uint16_t>
                //
                // Channel Mask: Mask of channels to scan
                // Scan Duration: 0 – 0xFF Multiple of superframe duration.
                // Scan count: Scan repeats 0 – 5
                // Network Update ID: 0 – 0xFF Transaction ID for scan

                $cmd = "004A";

                $shortAddress               = $Command['address'];
                $channelMask                = "07FFF800";
                $scanDuration               = "01";
                $scanCount                  = "01";
                $networkUpdateId            = "01";
                $networkManagerShortAddress = "0000";

                $data = $shortAddress.$channelMask.$scanDuration.$scanCount.$networkUpdateId .$networkManagerShortAddress;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $shortAddress);
                return;
            }

            //----------------------------------------------------------------------
            // 2.4.3.3.3   Mgmt_Rtg_req
            // Request routing table. To Zigbee router or coordinator.
            if (isset($Command['getRoutingTable'])) {
                if (!isset($Command['address'])) {
                    $this->deamonlog('debug', "    ERROR: command getRoutingTable: Missing address");
                    return;
                }
                $this->deamonlog('debug', "    command getRoutingTable", $this->debug['processCmd']);

                // Msg Type = 0x0530
                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (22 -> 0x16)
                // <data: auint8_t>
                // APS Part <= data

                $addressMode            = "02";
                $targetShortAddress     = $Command['address'];
                $sourceEndpoint         = "00";
                $destinationEndpoint    = "00";
                $profileID              = "0000";
                $clusterID              = "0032";
                $securityMode           = "28";
                $radius                 = "30";
                // $dataLength             = "16";

                $SQN = "00";  // I don't know why I need this but if I don't put it then I'm missing some data: C'est ls SQN que je met à 00 car de toute facon je ne sais pas comment le calculer.
                $startIndex = "00";

                $data2 = $SQN.$startIndex;
                $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));
                $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;

                $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength, $this->debug['processCmd'] );
                $this->deamonlog('debug', "  Data2: ".$SQN."-".$startIndex, $this->debug['processCmd'] );

                $data = $data1.$data2;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
                return;
            }

            // Get binding table: Mgmt_Bind_req
            if (isset($Command['getBindingTable'])) {
                if (!isset($Command['address'])) {
                    $this->deamonlog('debug', "    ERROR: command getBindingTable: Missing address");
                    return;
                }
                $this->deamonlog('debug', "    command getBindingTable", $this->debug['processCmd']);
                // Msg Type = 0x0530
                $cmd = "0530";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <profile ID: uint16_t>
                // <cluster ID: uint16_t>
                // <security mode: uint8_t>
                // <radius: uint8_t>

                $addressMode            = "02"; // Short addr mode
                $targetShortAddress     = $Command['address'];
                $sourceEndpoint         = "00";
                $destinationEndpoint    = "00";
                $profileID              = "0000";
                $clusterID              = "0033"; // Mgmt_Bind_req
                $securityMode           = "28";
                $radius                 = "30";

                $SQN = "12";
                $startIndex = "00";

                $data2 = $SQN.$startIndex;
                $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));
                $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;

                $data = $data1.$data2;
                $len = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $len, $data, $targetShortAddress);
                return;
            }

            //----------------------------------------------------------------------
            // Bind
            // Title => 000B57fffe3025ad (IEEE de l ampoule)
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006
            if (isset($Command['bind'])) // Tcharp38: OBSOLETE !! Use "bind0030" instead
            {
                $this->deamonlog('debug', "    command bind", $this->debug['processCmd']);
                // Msg Type = 0x0030
                $cmd = "0030";

                // <target extended address: uint64_t>                                  -> 16
                // <target endpoint: uint8_t>                                           -> 2
                // <cluster ID: uint16_t>                                               -> 4
                // <destination address mode: uint8_t>                                  -> 2
                // <destination address:uint16_t or uint64_t>                           -> 4 / 16 => 0000 for Zigate
                // <destination endpoint (value ignored for group address): uint8_t>    -> 2

                // $targetExtendedAddress  = "000B57fffe3025ad";
                $targetExtendedAddress  = $Command['address'];
                //
                if (isset($Command['targetEndpoint'])) {
                    $targetEndpoint         = $Command['targetEndpoint'];
                }
                else {
                    $targetEndpoint         = "01";
                }

                // $clusterID              = "0006";
                $clusterID              = $Command['ClusterId'];
                // $destinationAddressMode = "02";
                $destinationAddressMode = "03";

                // $destinationAddress     = "0000";
                // $destinationAddress     = "00158D0001B22E24";
                $destinationAddress     = $Command['reportToAddress'];

                $destinationEndpoint    = "01";
                //  16 + 2 + 4 + 2 + 4 + 2 = 30/2 => 15 => F
                // $length = "000F";
                //  16 + 2 + 4 + 2 + 16 + 2 = 42/2 => 21 => 15
                $length = "0015";

                $data = $targetExtendedAddress.$targetEndpoint.$clusterID.$destinationAddressMode.$destinationAddress.$destinationEndpoint;
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            // Bind, thru command 0030 => generates 'Bind_req' / cluster 0021
            if (isset($Command['bind0030']))
            {
                /* Mandatory infos: addr, clustId, attrType, attrId */
                $required = ['addr', 'ep', 'clustId', 'destAddr'];
                $missingParam = false;
                foreach ($required as $idx => $param) {
                    if (isset($Command[$param]))
                        continue;
                    $this->deamonlog('debug', "    command bind0030 ERROR: Missing '".$param."'");
                    $missingParam = true;
                }
                if ($missingParam)
                    return;
                // If 'destAddr' == IEEE then need 'destEp' too.
                if ((strlen($Command['destAddr']) == 16) && !isset($Command['destEp'])) {
                    $this->deamonlog('debug', "    command bind0030 ERROR: Missing 'destEp'");
                    return;
                }

                $this->deamonlog('debug', "    command bind0030", $this->debug['processCmd']);

                $cmd = "0030";

                // <target extended address: uint64_t>
                // <target endpoint: uint8_t>
                // <cluster ID: uint16_t>
                // <destination address mode: uint8_t>
                // <destination address:uint16_t or uint64_t>
                // <destination endpoint (value ignored for group address): uint8_t>

                // Source
                $addr = $Command['addr'];
                if (strlen($addr) != 16) {
                    $this->deamonlog('debug', "    command bind0030 ERROR: Invalid addr length");
                    return;
                }
                $ep = $Command['ep'];
                $clustId = $Command['clustId'];

                // Dest
                // $destAddr: 01=16bit group addr, destEp ignored
                // $destAddr: 03=64bit ext addr, destEp required
                $destAddr = $Command['destAddr'];
                if (strlen($destAddr) == 4)
                    $destAddrMode = "01";
                else if (strlen($destAddr) == 16)
                    $destAddrMode = "03";
                else {
                    $this->deamonlog('debug', "    command bind0030 ERROR: Invalid dest addr length");
                    return;
                }
                $destEp = isset($Command['destEp']) ? $Command['destEp'] : "00"; // destEp ignored if group address

                $data = $addr.$ep.$clustId.$destAddrMode.$destAddr.$destEp;
                $length = sprintf( "%04x", strlen($data) / 2);
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            // BindToGroup
            // Pour Telecommande RC110
            // Tcharp38: Why the need of a 0530 based function ?
            if (isset($Command['BindToGroup']))
            {
                $this->deamonlog('debug', "    command BindToGroup", $this->debug['processCmd']);

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (22 -> 0x16)
                // <data: auint8_t>
                // APS Part <= data
                // dummy 00 to align mesages                                            -> 1
                // <target extended address: uint64_t>                                  -> 8
                // <target endpoint: uint8_t>                                           -> 1
                // <cluster ID: uint16_t>                                               -> 2
                // <destination address mode: uint8_t>                                  -> 1
                // <destination address:uint16_t or uint64_t>                           -> 8
                // <destination endpoint (value ignored for group address): uint8_t>    -> 1
                // => 34 -> 0x22

                $addressMode                = "02";
                $targetShortAddress         = $Command['address'];
                $sourceEndpointBind         = "00";
                $destinationEndpointBind    = "00";
                $profileIDBind              = "0000";
                $clusterIDBind              = "0021";
                $securityMode               = "02";
                $radius                     = "30";
                // $dataLength                 = "16";

                $dummy = "00";  // I don't know why I need this but if I don't put it then I'm missing some data: C'est ls SQN que je met à 00 car de toute facon je ne sais pas comment le calculer.

                $targetExtendedAddress      = AbeilleTools::reverseHex($Command['targetExtendedAddress']);
                $targetEndpoint             = $Command['targetEndpoint'];
                $clusterID                  = AbeilleTools::reverseHex($Command['clusterID']);
                $destinationAddressMode     = "01";
                $reportToGroup              = AbeilleTools::reverseHex($Command['reportToGroup']);

                $data2 = $dummy.$targetExtendedAddress.$targetEndpoint.$clusterID .$destinationAddressMode.$reportToGroup;
                $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2));

                $data1 = $addressMode.$targetShortAddress.$sourceEndpointBind.$destinationEndpointBind.$clusterIDBind.$profileIDBind.$securityMode.$radius.$dataLength;

                $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpointBind."-".$destinationEndpointBind."-".$clusterIDBind."-".$profileIDBind."-".$securityMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2), $this->debug['processCmd'] );
                $this->deamonlog('debug', "  Data2: ".$dummy."-".$targetExtendedAddress."-".$targetEndpoint."-".$clusterID."-".$destinationAddressMode."-".$reportToGroup." len: ".(strlen($data2)/2), $this->debug['processCmd'] );

                $data = $data1.$data2;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
                return;
            }

            // Bind Short
            // Title => 000B57fffe3025ad (IEEE de l ampoule) <= to be reviewed
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006 <= to be reviewed
            // Tcharp38: Why the need of a 0530 based function ?
            if (isset($Command['bindShort']))
            {
                $this->deamonlog('debug', "    command bind short", $this->debug['processCmd']);
                // Msg Type = 0x0530
                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (22 -> 0x16)
                // <data: auint8_t>
                // APS Part <= data
                // dummy 00 to align mesages                                            -> 1
                // <target extended address: uint64_t>                                  -> 8
                // <target endpoint: uint8_t>                                           -> 1
                // <cluster ID: uint16_t>                                               -> 2
                // <destination address mode: uint8_t>                                  -> 1
                // <destination address:uint16_t or uint64_t>                           -> 8
                // <destination endpoint (value ignored for group address): uint8_t>    -> 1
                // => 34 -> 0x22

                $addressMode                = "02";
                $targetShortAddress         = $Command['address'];
                $sourceEndpointBind         = "00";
                $destinationEndpointBind    = "00";
                $profileIDBind              = "0000";
                $clusterIDBind              = "0021";
                $securityMode               = "02";
                $radius                     = "30";
                $dataLength                 = "16";

                $dummy = "00";  // I don't know why I need this but if I don't put it then I'm missing some data: C'est ls SQN que je met à 00 car de toute facon je ne sais pas comment le calculer.

                if (strlen($Command['targetExtendedAddress']) < 2 ) {
                    $this->deamonlog('debug', "  command bind short: param targetExtendedAddress is empty. Can t do. So return", $this->debug['processCmd']);
                    return;
                }
                $targetExtendedAddress = AbeilleTools::reverseHex($Command['targetExtendedAddress']);

                $targetEndpoint = $Command['targetEndpoint'];

                $clusterID = AbeilleTools::reverseHex($Command['clusterID']);

                $destinationAddressMode = "03";
                if (strlen($Command['destinationAddress']) < 2 ) {
                    $this->deamonlog('debug', "  command bind short: param destinationAddress is empty. Can t do. So return", $this->debug['processCmd']);
                    return;
                }
                $destinationAddress = AbeilleTools::reverseHex($Command['destinationAddress']);

                $destinationEndpoint = $Command['destinationEndpoint'];

                $length = "0022";

                $data1 = $addressMode.$targetShortAddress.$sourceEndpointBind.$destinationEndpointBind.$clusterIDBind.$profileIDBind.$securityMode.$radius.$dataLength;
                $data2 = $dummy.$targetExtendedAddress.$targetEndpoint.$clusterID .$destinationAddressMode.$destinationAddress.$destinationEndpoint;

                $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpointBind."-".$destinationEndpointBind."-".$clusterIDBind."-".$profileIDBind."-".$securityMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2), $this->debug['processCmd'] );
                $this->deamonlog('debug', "  Data2: ".$dummy."-".$targetExtendedAddress."-".$targetEndpoint."-".$clusterID."-".$destinationAddressMode."-".$destinationAddress."-".$destinationEndpoint." len: ".(strlen($data2)/2), $this->debug['processCmd'] );

                $data = $data1.$data2;

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
                return;
            }

            // setReport
            // Title => setReport
            // message => address=d45e&ClusterId=0006&AttributeId=0000&AttributeType=10
            if (isset($Command['setReport'])) // Tcharp38: OBSOLETE. Use 'configureReporting' instead.
            {
                $this->deamonlog('debug', "    command setReport", $this->debug['processCmd']);
                // Configure Reporting request
                // Msg Type = 0x0120

                $cmd = "0120";

                // <address mode: uint8_t>              -> 2
                // <target short address: uint16_t>     -> 4
                // <source endpoint: uint8_t>           -> 2
                // <destination endpoint: uint8_t>      -> 2
                // <Cluster id: uint16_t>               -> 4
                // <direction: uint8_t>                 -> 2
                // <manufacturer specific: uint8_t>     -> 2
                // <manufacturer id: uint16_t>          -> 4
                // <number of attributes: uint8_t>      -> 2
                // <attributes list: data list of uint16_t  each>
                //      Attribute direction : uint8_t   -> 2
                //      Attribute type : uint8_t        -> 2
                //      Attribute id : uint16_t         -> 4
                //      Min interval : uint16_t         -> 4
                //      Max interval : uint16_t         -> 4
                //      Timeout : uint16_t              -> 4
                //      Change : uint8_t                -> 2

                $addressMode            = "02";
                $targetShortAddress     = $Command['address'];
                if (isset( $Command['sourceEndpoint'] )) { $sourceEndpoint = $Command['sourceEndpoint']; } else { $sourceEndpoint = "01"; }
                if (isset( $Command['targetEndpoint'] )) { $targetEndpoint = $Command['targetEndpoint']; } else { $targetEndpoint = "01"; }
                $ClusterId              = $Command['ClusterId'];
                $direction              = "00";                     //
                $manufacturerSpecific   = "00";                     //
                $manufacturerId         = "0000";                   //
                $numberOfAttributes     = "01";                     // One element at a time

                if (isset( $Command['AttributeDirection'] )) { $AttributeDirection = $Command['AttributeDirection']; } else { $AttributeDirection = "00"; } // See if below

                $AttributeId            = $Command['AttributeId'];    // "0000"

                if ($AttributeDirection == "00" ) {
                    if (isset($Command['AttributeType'])) {$AttributeType = $Command['AttributeType']; }
                    else {
                        $this->deamonlog('error', "set Report with an AttributeType not defines for equipment: ". $targetShortAddress." attribut: ".$AttributeId." can t process" , $this->debug['processCmd']);
                        return;
                    }
                    if (isset($Command['MinInterval']))   { $MinInterval  = $Command['MinInterval']; } else { $MinInterval    = "0000"; }
                    if (isset($Command['MaxInterval']))   { $MaxInterval  = $Command['MaxInterval']; } else { $MaxInterval    = "0000"; }
                    if (isset($Command['Change']))        { $Change       = $Command['Change']; }      else { $Change         = "00"; }
                    $Timeout = "ABCD";
                }
                else if ($AttributeDirection == "01" ) {
                    $AttributeType          = "12";     // Put crappy info to see if they are passed to zigbee by zigate but it's not.
                    $MinInterval            = "3456";   // Need it to respect cmd 0120 format.
                    $MaxInterval            = "7890";
                    $Change                 = "12";
                    if (isset($Command['Timeout']))   { $Timeout      = $Command['Timeout']; }     else { $Timeout        = "0000"; }
                }
                else {
                    $this->deamonlog('error', "set Report with an AttributeDirection (".$AttributeDirection.") not valid for equipment: ". $targetShortAddress." attribut: ".$AttributeId." can t process", $this->debug['processCmd']);
                    return;
                }

                $data =  $addressMode.$targetShortAddress.$sourceEndpoint.$targetEndpoint.$ClusterId.$direction.$manufacturerSpecific.$manufacturerId.$numberOfAttributes.$AttributeDirection.$AttributeType.$AttributeId.$MinInterval.$MaxInterval.$Timeout.$Change ;
                $len = sprintf("%04s",dechex(strlen($data) / 2));
                $this->deamonlog('debug', "Data: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$targetEndpoint."-".$ClusterId."-".$direction."-".$manufacturerSpecific."-".$manufacturerId."-".$numberOfAttributes."-".$AttributeDirection."-".$AttributeType."-".$AttributeId."-".$MinInterval."-".$MaxInterval."-".$Timeout."-".$Change, $this->debug['processCmd']);
                $this->addCmdToQueue($priority, $dest, $cmd, $len, $data, $targetShortAddress);
                return;
            }

            // setReportRaw
            // Title => setReportRaw
            // message => address=d45e&ClusterId=0006&AttributeId=0000&AttributeType=10
            // For the time being hard coded to run tests but should replace setReport due to a bug on Timeout of command 0120. See my notes.
            if (isset($Command['setReportRaw'])) { // Tcharp38: OBSOLETE. Use 'configureReporting' instead.
                $this->deamonlog('debug', "   command setReportRaw", $this->debug['processCmd']);

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1
                //                                                                                12 -> 0x0C
                // <data: auint8_t>
                // ZCL Control Field
                // ZCL SQN
                // Commad Id
                // ....

                $addressMode            = "02";
                $targetShortAddress     = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = "01";
                $profileID              = "0104";
                $clusterID              = "0B04";
                $securityMode           = "02";
                $radius                 = "1E";

                $zclControlField        = "10";
                $transactionSequence    = "01";
                $cmdId                  = "06";
                $directionReport        = "00";

                // $attributeType          = "29";      // Puissance
                $attributeType          = "21";         // A

                // $attribut               = "0B05";    // Puissance
                $attribut               = "0805";       // A

                $MinInterval            = "0500";
                $MaxInterval            = "2C01";
                $change                 = "0100";

                $data2 = $zclControlField.$transactionSequence.$cmdId.$directionReport.$attribut.$attributeType.$MinInterval.$MaxInterval.$change;

                $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2));

                $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;

                $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) , $this->debug['processCmd']);
                $this->deamonlog('debug', "  Data2: ".$zclControlField."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) , $this->debug['processCmd']);

                $data = $data1.$data2;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
                return;
            }

            // Tcharp38: Generic configure reporting command.
            // Why don't we use 0120 zigate command instead of 0530 ??
            if (isset($Command['configureReporting'])) {
                /* Mandatory infos: addr, clustId, attrId. 'attrType' can be auto-detected */
                $required = ['addr', 'clustId', 'attrId'];
                $missingParam = false;
                foreach ($required as $idx => $param) {
                    if (isset($Command[$param]))
                        continue;
                    $this->deamonlog('debug', "    command configureReporting ERROR: Missing '".$param."'");
                    $missingParam = true;
                }
                if ($missingParam)
                    return;
                if (!isset($Command['attrType'])) {
                    /* Attempting to find attribute type according to its id */
                    $attr = zbGetZCLAttribute($Command['clustId'], $Command['attrId']);
                    if (($attr === false) || !isset($attr['dataType'])) {
                        $this->deamonlog('debug', "    command configureReporting ERROR: Missing 'attrType'");
                        return;
                    }
                    $Command['attrType'] = sprintf("%02X", $attr['dataType']);
                    $this->deamonlog('debug', "    Using attrType ".$Command['attrType'], $this->debug['processCmd']);
                }

                $this->deamonlog('debug', "    command configureReporting", $this->debug['processCmd']);
                $cmd = "0530";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <profile ID: uint16_t>
                // <cluster ID: uint16_t>
                // <security mode: uint8_t>
                // <radius: uint8_t>
                // <data length: uint8_t>

                // <data: auint8_t>
                //  ZCL Control Field
                //  ZCL SQN
                //  Command Id
                //  ....

                $addrMode       = "02";
                $addr           = $Command['addr'];
                $srcEp          = "01";
                $destEp         = $Command['ep'];
                $profId         = "0104";
                $clustId        = $Command['clustId'];
                $securityMode   = "02";
                $radius         = "1E";

                /* ZCL header */
                $fcf            = "10"; // Frame Control Field
                $sqn            = "01";
                $cmdId          = "06";

                /* Attribute Reporting Configuration Record */
                $dir                    = "00";
                $attrId                 = AbeilleTools::reverseHex($Command['attrId']);
                $attrType               = $Command['attrType'];
                $minInterval            = isset($Command['minInterval']) ? AbeilleTools::reverseHex($Command['minInterval']) : "0000";
                $maxInterval            = isset($Command['maxInterval']) ? AbeilleTools::reverseHex($Command['maxInterval']) : "0000";
                switch ($attrType) {
                case "21": // Uint16
                    $changeVal = "0001";
                    break;
                case "10": // Boolean
                case "20": // Uint8
                    $changeVal = "01";
                    break;

                // Tcharp38: TO BE COMPLETED ! changeVal size depends on attribute type
                default:
                    $this->deamonlog('debug', "    ERROR: Unsupported attrType ".$attrType, $this->debug['processCmd']);
                    $changeVal = "01";
                }
                $change                 = AbeilleTools::reverseHex($changeVal); // Reportable change.
                // $timeout                = "0000";

                $data2 = $fcf.$sqn.$cmdId.$dir.$attrId.$attrType.$minInterval.$maxInterval.$change;
                $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));

                $data1 = $addrMode.$addr.$srcEp.$destEp.$clustId.$profId.$securityMode.$radius.$dataLen2;
                $data = $data1.$data2;
                $len = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $len, $data, $addr);
                return;
            }

            // Read Reporting Request
            if (isset($Command['readReportingConfig'])) {
                if (!isset($Command['addr'])) {
                    $this->deamonlog('debug', "    command readReportingConfig ERROR: Missing 'addr'");
                    return;
                }
                if (!isset($Command['ep']))
                    $Command['ep'] = "01";
                if (!isset($Command['clustId'])) {
                    $this->deamonlog('debug', "    command readReportingConfig ERROR: Missing 'clustId'");
                    return;
                }
                if (!isset($Command['attrId'])) {
                    $this->deamonlog('debug', "    command readReportingConfig ERROR: Missing 'attrId'");
                    return;
                }

                $this->deamonlog('debug', "    command readReportingConfig", $this->debug['processCmd']);
                $cmd = "0122";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <Cluster id: uint16_t>
                // <direction: uint8_t>
                // <number of attributes: uint8_t>
                // <manufacturer specific: uint8_t>
                // <manufacturer id: uint16_t>
                // Attribute direction : uint8_t
                // Attribute id : uint16_t

                $addrMode = "02";
                $addr = $Command['addr'];
                $srcEp = "01";
                $destEp = $Command['ep'];
                $clustId = $Command['clustId'];
                $dir = "00"; // 00=attribute is reported, 01=attribute is received
                $nbOfAttr = "01";
                $manufSpecific = "00";
                $manufId = "0000";
                $attrDir = "00";
                $attrId = $Command['attrId'];

                $data =  $addrMode.$addr.$srcEp.$destEp.$clustId.$dir.$nbOfAttr.$manufSpecific.$manufId.$attrDir.$attrId;
                $len = sprintf("%04s", dechex(strlen($data) / 2));
                $this->addCmdToQueue($priority, $dest, $cmd, $len, $data, $addr);
                return;
            }

            // CmdAbeille/0000/commissioningGroupAPS -> address=a048&groupId=AA00
            // Commission group for Ikea Telecommande On/Off still interrupteur
            if (isset($Command['commissioningGroupAPS']))
            {
                $this->deamonlog('debug', "   commissioningGroupAPS", $this->debug['processCmd']);

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1
                //                                                                                12 -> 0x0C
                // <data: auint8_t>
                // 19 ZCL Control Field
                // 01 ZCL SQN
                // 41 Commad Id: Get Group Id Response
                // 01 Total
                // 00 Start Index
                // 01 Count
                // 00 Group Type
                // 001B Group Id

                $addressMode            = "02";
                $targetShortAddress     = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = "01";
                $profileID              = "0104";
                $clusterID              = "1000";
                $securityMode           = "02";
                $radius                 = "1E";

                $zclControlField        = "19";
                $transactionSequence    = "01";
                $cmdId                  = "41";
                $total                  = "01";
                $startIndex             = "00";
                $count                  = "01";
                $groupId                = AbeilleTools::reverseHex($Command['groupId']);
                $groupType              = "00";

                $data2 = $zclControlField.$transactionSequence.$cmdId.$total.$startIndex.$count.$groupId.$groupType;
                $dataLength = sprintf( "%02s", dechex(strlen($data2) / 2));
                $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;
                $data = $data1.$data2;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
                return;
            }

            // CmdAbeille/0000/commissioningGroupAPSLegrand -> address=a048&groupId=AA00
            // Commission group for Legrand Telecommande On/Off still interrupteur Issue #1290
            if (isset($Command['commissioningGroupAPSLegrand']))
            {
                $this->deamonlog('debug', "    commissioningGroupAPSLegrand", $this->debug['processCmd']);

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1
                //                                                                                12 -> 0x0C
                // <data: auint8_t>
                // 19 ZCL Control Field
                // 01 ZCL SQN
                // 41 Commad Id: Get Group Id Response
                // 01 Total
                // 00 Start Index
                // 01 Count
                // 00 Group Type
                // 001B Group Id

                $addressMode            = "02";
                $targetShortAddress     = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = "01";
                $profileID              = "0104";
                $clusterID              = "FC01";
                $securityMode           = "02";
                $radius                 = "14";

                $zclControlField        = "1D";
                $Manufacturer           = AbeilleTools::reverseHex("1021");
                $transactionSequence    = "01";
                $cmdId                  = "08";
                $groupId                = AbeilleTools::reverseHex($Command['groupId']);

                $data2 = $zclControlField.$Manufacturer.$transactionSequence.$cmdId.$groupId ;

                $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2));

                $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;

                $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) , $this->debug['processCmd']);
                $this->deamonlog('debug', "  Data2: ".$zclControlField."-".$Manufacturer."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) , $this->debug['processCmd']);

                $data = $data1.$data2;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data );
                return;
            }

            if (isset($Command['getGroupMembership'])) {
                $cmd = "0062";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group count: uint8_t>
                // <group list:data>

                $addressMode = "02";
                $address = $Command['addr'];
                $sourceEndpoint = "01";
                $ep = $Command['ep'];
                $groupCount = "00";
                $groupList = "";

                /* Correcting EP size if required (ex "1" => "01") */
                if (strlen($ep) != 2) {
                    $EP = hexdec($ep);
                    $ep = sprintf("%02X", (hexdec($EP)));
                }

                $data = $addressMode.$address.$sourceEndpoint.$ep.$groupCount.$groupList;
                //  2 + 4 + 2 + 2 + 2 + 0 = 12/2 => 6
                $length = "0006";
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['viewScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']))
            {
                $cmd = "00A0";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$groupID.$sceneID;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['storeScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']))
            {
                $cmd = "00A4";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$groupID.$sceneID;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['recallScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']))
            {
                $cmd = "00A5";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addressMode                    = "02";                                    // Short Address -> 2
                $address                        = $Command['address'];                         // -> 4
                $sourceEndpoint                 = "01";                                 // -> 2
                $destinationEndpoint            = $Command['DestinationEndPoint']; // -> 2

                $groupID                        = $Command['groupID'];
                $sceneID                        = $Command['sceneID'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$groupID.$sceneID;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['sceneGroupRecall']) && isset($Command['groupID']) && isset($Command['sceneID']))
            {
                $cmd = "00A5";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addressMode                    = "01";                  // Group Address -> 1, Short Address -> 2
                $address                        = $Command['groupID'];   // -> 4
                $sourceEndpoint                 = "01";                  // -> 2
                $destinationEndpoint            = "02";                  // -> 2

                $groupID                        = $Command['groupID'];
                $sceneID                        = $Command['sceneID'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$groupID.$sceneID;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['addScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) && isset($Command['sceneName']))
            {
                $cmd = "00A1";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>
                // <transition time: uint16_t>
                // <scene name length: uint8_t>
                // <scene name max length: uint8_t>
                // <scene name data: data each element is uint8_t>

                $addressMode            = "02";
                $address                = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = $Command['DestinationEndPoint'];

                $groupID                = $Command['groupID'];
                $sceneID                = $Command['sceneID'];

                $transitionTime         = "0001";

                $sceneNameLength        = sprintf("%02s", (strlen( $Command['sceneName'] )/2));      // $Command['sceneNameLength'];
                $sceneNameMaxLength     = sprintf("%02s", (strlen( $Command['sceneName'] )/2));      // $Command['sceneNameMaxLength'];
                $sceneNameData          = $Command['sceneName'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$groupID.$sceneID.$transitionTime.$sceneNameLength.$sceneNameMaxLength.$sceneNameData ;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['getSceneMembership']) && isset($Command['address']) && isset($Command['DestinationEndPoint']))
            {
                $cmd = "00A6";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$groupID ;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['removeScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']))
            {
                $cmd = "00A2";

                //0x00A2
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>
                // <scene ID: uint8_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];
                $sceneID = $Command['sceneID'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$groupID.$sceneID ;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['removeSceneAll']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']))
            {
                $cmd = "00A3";

                //0x00A3
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <group ID: uint16_t>

                $addressMode = "02";                                    // Short Address -> 2
                $address = $Command['address'];                         // -> 4
                $sourceEndpoint = "01";                                 // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2

                $groupID = $Command['groupID'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$groupID ;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['sceneLeftIkea']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']))
            {
                $this->deamonlog('debug', "  Specific Command to simulate Ikea Telecommand < and >");

                // Msg Type = 0x0530
                $cmd = "0530";

                $addressMode = "01"; // 01 pour groupe
                $targetShortAddress = $Command['address'];
                $sourceEndpointBind = "01";
                $destinationEndpointBind = "01";
                $profileIDBind = "0104";
                $clusterIDBind = "0005";
                $securityMode = "02";
                $radius = "30";
                // $dataLength = "16";

                $FrameControlField = "05";  // 1
                $manu = "7C11";
                $SQN = "00";
                $cmdIkea = "07";
                $cmdIkeaParams = "00010D00";

                $data2 = $FrameControlField.$manu.$SQN.$cmdIkea.$cmdIkeaParams;
                $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

                $data1 = $addressMode.$targetShortAddress.$sourceEndpointBind.$destinationEndpointBind.$clusterIDBind.$profileIDBind.$securityMode.$radius.$dataLength;

                $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpointBind."-".$destinationEndpointBind."-".$clusterIDBind."-".$profileIDBind."-".$securityMode."-".$radius."-".$dataLength." len: ".(dechex(strlen($data1)/2)), $this->debug['processCmd'] );
                $this->deamonlog('debug', "  Data2: ".$dummy."-".$targetExtendedAddress."-".$targetEndpoint."-".$clusterID."-".$destinationAddressMode."-".$destinationAddress."-".$destinationEndpoint." len: ".(dechex(strlen($data2)/2)), $this->debug['processCmd'] );

                $data = $data1.$data2;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
                return;
            }

            // https://zigate.fr/documentation/commandes-zigate/ Windows covering (v3.0f only)
            if (isset($Command['WindowsCovering']) && isset($Command['address']) && isset($Command['clusterCommand']))
            {
                // 0x00FA    Windows covering (v3.0f only)
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <cluster command : uint8_t>
                // 0 = Up/Open
                // 1 = Down/Close
                // 2 = Stop
                // 4 = Go To Lift Value (extra cmd : Value in cm)
                // 5 = Go To Lift Percentage (extra cmd : percentage 0-100)
                // 7 = Go To Tilt Value (extra cmd : Value in cm)
                // 8 = Go To Tilt Percentage (extra cmd : percentage 0-100)
                // <extra command : uint8_t or uint16_t >

                $cmd = "00FA";

                $addressMode    = "02"; // 01 pour groupe, 02 pour NE
                $address        = $Command['address'];
                $srcEp          = "01";
                $detEP          = "01";
                $clusterCommand = $Command['clusterCommand'];

                $data = $addressMode.$address.$srcEp.$detEP.$clusterCommand;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['WindowsCoveringLevel']) && isset($Command['address']) && isset($Command['clusterCommand']))
            {
                // echo "Windows Covering test for Store Ikea: lift to %\n";

                $cmd = '0530';

                $addressMode            = "02";                // 01 pour groupe, 02 pour NE
                $targetShortAddress     = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = "01";
                $profile                = "0104";
                $cluster                = "0102";
                $securityMode           = "02";
                $radius                 = "30";
                // $dataLength = "16";

                $FrameControlField      = "11";
                $SQN                    = "00";
                $cmdLift                = "05"; // 00: Up, 01: Down, 02: Stop, 04: Go to lift value (not supported), 05: Got to lift pourcentage.
                $liftValue              = $Command['liftValue'];

                $data2 = $FrameControlField.$SQN.$cmdLift.$liftValue.$liftValue;
                $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

                $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$cluster.$profile.$securityMode.$radius.$dataLength;

                $data = $data1.$data2;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
                return;
            }

            // https://zigate.fr/documentation/commandes-zigate/ Windows covering (v3.0f only)
            if (isset($Command['WindowsCoveringGroup']) && isset($Command['address']) && isset($Command['clusterCommand']))
            {
                // 0x00FA    Windows covering (v3.0f only)
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <cluster command : uint8_t>
                // 0 = Up/Open
                // 1 = Down/Close
                // 2 = Stop
                // 4 = Go To Lift Value (extra cmd : Value in cm)
                // 5 = Go To Lift Percentage (extra cmd : percentage 0-100)
                // 7 = Go To Tilt Value (extra cmd : Value in cm)
                // 8 = Go To Tilt Percentage (extra cmd : percentage 0-100)
                // <extra command : uint8_t or uint16_t >

                $cmd = "00FA";

                $addressMode    = "04"; // 01 pour groupe, 02 pour NE, 03 pour , 04 pour broadcast
                $address        = $Command['address'];
                $srcEp          = "01";
                $detEP          = "01";
                $clusterCommand = $Command['clusterCommand'];

                $data = $addressMode.$address.$srcEp.$detEP.$clusterCommand;

                $length = sprintf("%04s", dechex(strlen($data )/2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            /* Expected format:
            net/0000 ActiveEndpointRequest address=<addr>*/
            if (isset($Command['ActiveEndPoint']))
            {
                $cmd = "0045";

                // <target short address: uint16_t>

                $address = $Command['address']; // -> 4

                //  4 = 4/2 => 2
                $length = "0002";

                $data = $address;

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['SimpleDescriptorRequest']))
            {
                $cmd = "0043";

                // <target short address: uint16_t>
                // <endpoint: uint8_t>

                $address = sprintf("%04X", hexdec($Command['address']));
                $endpoint = sprintf("%02X", hexdec($Command['endPoint']));

                $data = $address.$endpoint;
                $length = sprintf("%04s", dechex(strlen($data) / 2));
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            //----------------------------------------------------------------------------
            if (isset($Command['Network_Address_request']))
            {
                $cmd = "0040";

                // <target short address: uint16_t> -> 4
                // <extended address:uint64_t>      -> 16
                // <request type: uint8_t>          -> 2
                // <start index: uint8_t>           -> 2
                // Request Type:
                // 0 = Single Request 1 = Extended Request
                // -> 24 / 2 = 12 => 0x0C

                $address = $Command['address'];
                $IeeeAddress = $Command['IEEEAddress'];
                $requestType = "01";
                $startIndex = "00";

                $data = $address.$IeeeAddress.$requestType.$startIndex ;
                $length = "000C"; // A verifier

                $this->deamonlog('debug', '    Network_Address_request: '.$data.' - '.$length, $this->debug['processCmd']  );

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['IEEE_Address_request']))
            {
                // IEEE Address request
                $cmd = "0041";

                // <target short address: uint16_t> -> 4
                // <short address: uint16_t>        -> 4
                // <request type: uint8_t>          -> 2    Request Type: 0 = Single 1 = Extended
                // <start index: uint8_t>           -> 2

                $address        = $Command['address'];
                $shortAddress   = $Command['shortAddress'];
                $requestType    = "01";
                $startIndex     = "00";

                $data = $address.$shortAddress.$requestType.$startIndex ;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->deamonlog('debug', '    IEEE_Address_request: '.$data.' - '.$length, $this->debug['processCmd']  );

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['Management_LQI_request'])) // Mgmt_Lqi_req. OBSOLETE: Use 'getNeighborTable' instead
            {
                $cmd = "004E";

                // <target short address: uint16_t>
                // <Start Index: uint8_t>

                $address = $Command['address'];     // -> 4
                $startIndex = $Command['StartIndex']; // -> 2

                //  4 + 2 = 6/2 => 3
                $length = "0003";

                $data = $address.$startIndex ;

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['getNeighborTable'])) // Mgmt_Lqi_req
            {
                /* Expecting 2 parameters: 'addr' & 'startIndex' */

                $cmd = "004E";

                // <target short address: uint16_t>
                // <Start Index: uint8_t>

                $address = $Command['addr'];
                $startIndex = $Command['startIndex'];

                //  4 + 2 = 6/2 => 3
                $length = "0003";

                $data = $address.$startIndex ;

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['identifySend']) && isset($Command['address']) && isset($Command['duration']) && isset($Command['DestinationEndPoint']))
            {
                $cmd = "0070";
                // Msg Type = 0x0070
                // Identify Send

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <time: uint16_t> Time: Seconds

                //                 Start  Type         Length           Short       Addr
                // 17:29:31.398 -> 01     02 10 70     02 10 02 17      10 02    12 6E    1B 02 11 02 11 02 10 10 03
                // 01: Start
                // 02 10 70: 00 70 - Msg Type Identify Send
                // 02 10 02 17 => Length -> 7
                // 10 02 => Mode 2 -> Short
                //

                // 17:29:31.461 <- 01 80 00 00 05 FE 00 0B 00 70 00 03
                // 17:29:31.523 <- 01 81 01 00 07 F5 0B 01 00 03 00 00 7B 03

                $addressMode = "02"; // Short Address -> 2
                $address = $Command['address']; // -> 4
                $sourceEndpoint = "01"; // -> 2
                $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2
                $time = $Command['duration']; // -> 4
                //  2 + 4 + 2 + 2 + 4 = 14/2 => 7
                $length = "0007";
                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$time ;

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            // Don't know how to make it works
            if (isset($Command['touchLinkFactoryResetTarget']))
            {
                if ($Command['touchLinkFactoryResetTarget']=="DO")
                {
                    $this->addCmdToQueue($priority,$priority,$dest,"00D2","0000");
                }
                return;
            }

            // setLevel on one object
            if (isset($Command['setLevel']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['destinationEndpoint']) && isset($Command['Level']) && isset($Command['duration'])) {
                $cmd = "0081";

                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <onoff : uint8_t>
                // <Level: uint8_t >
                // <Transition Time: uint16_t>

                $addressMode            = $Command['addressMode'];
                $address                = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = $Command['destinationEndpoint'];
                $onoff = "01";
                $level = sprintf("%02s",dechex($Command['Level']));
                $duration = sprintf("%04s",dechex($Command['duration']));

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$onoff.$level.$duration ;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);

                if ($addressMode=="02") {
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+2), "ep=".$destinationEndpoint."&clustId=0006&attrId=0000" );
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+3), "ep=".$destinationEndpoint."&clustId=0008&attrId=0000" );

                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+2+$Command['duration']), "ep=".$destinationEndpoint."&clustId=0006&attrId=0000" );
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+3+$Command['duration']), "ep=".$destinationEndpoint."&clustId=0008&attrId=0000" );
                }
                return;
            }

            if (isset($Command['moveToLiftAndTiltBSO']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['destinationEndpoint']) && isset($Command['inclinaison']) && isset($Command['duration']))
            {
                $this->deamonlog('debug', "    command moveToLiftAndTiltBSO", $this->debug['processCmd']);

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1
                //                                                                                12 -> 0x0C
                // <data: auint8_t>
                // 19 ZCL Control Field
                // 01 ZCL SQN
                // 41 Commad Id
                // etc

                $addressMode            = $Command['addressMode'];
                $targetShortAddress     = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = "01";
                $profileID              = "0104";
                $clusterID              = "0008";
                $securityMode           = "02";
                $radius                 = "1E";

                $zclControlField        = "05";
                $ManfufacturerCode      = "1011"; // inverted
                $transactionSequence    = "01";
                $cmdId                  = "10";  // Cmd Proprio Profalux
                $option                 = "03";  // Je ne touche que le Tilt
                // $Lift                   = "00";  // Not used / between 1 and 254 (see CurrentLevel attribute)
                $Lift                   = sprintf( "%02s",dechex($Command['lift']));
                // $Tilt                   = "2D";  // 2D move to 45deg / between 0 and 90 (See CurrentOrentation attribute)
                $Tilt                   = sprintf( "%02s",dechex($Command['inclinaison']));  // 2D move to 45deg / between 0 and 90 (See CurrentOrentation attribute)
                // $transitionTime         = "FFFF"; // FFFF ask the Tilt to move as fast as possible
                $transitionTime         = sprintf( "%04s",dechex($Command['duration']));

                $data2 = $zclControlField.$ManfufacturerCode.$transactionSequence.$cmdId.$option.$Lift.$Tilt.$transitionTime ;

                $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2));

                $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;

                $this->deamonlog('debug', "    Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) , $this->debug['processCmd']);
                $this->deamonlog('debug', "    Data2: ".$zclControlField."-".$ManfufacturerCode."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) , $this->debug['processCmd']);

                $data = $data1.$data2;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
                return;
            }

            // setLevelStop
            if (isset($Command['setLevelStop']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['sourceEndpoint']) && isset($Command['destinationEndpoint']))
            {
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>

                $cmd = "0084";
                $addressMode            = $Command['addressMode'];
                $address                = $Command['address'];
                $sourceEndpoint         = $Command['sourceEndpoint'];
                $destinationEndpoint    = $Command['destinationEndpoint'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint ;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            // WriteAttributeRequest ------------------------------------------------------------------------------------
            if ((isset($Command['WriteAttributeRequest'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']))
            {
                $this->setParam3( $dest, $Command );
                return;
            }

            // WriteAttributeRequest ------------------------------------------------------------------------------------
            if ((isset($Command['WriteAttributeRequestGeneric'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['attributeType']) && isset($Command['value']))
            {
                $this->setParamGeneric( $dest, $Command );
                return;
            }

            // WriteAttributeRequestVibration ------------------------------------------------------------------------------------
            if ((isset($Command['WriteAttributeRequestVibration'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']))
            {
                // Tcharp38: WHere is this code ??? $this->setParamXiaomi($dest, $Command);
                // $this->deamonlog('debug', "ERROR: WriteAttributeRequestVibration() CAN'T be executed. Missing setParamXiaomi()", $this->debug['processCmd']);
                $this->ReportParamXiaomi($dest, $Command);
                return;
            }

            // WriteAttributeRequestVibration ------------------------------------------------------------------------------------
            if ((isset($Command['WriteAttributeRequestActivateDimmer'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']))
            {
                $this->setParam4( $dest, $Command );
                return;
            }

            // ReadAttributeRequest ------------------------------------------------------------------------------------
            // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
            if (isset($Command['ReadAttributeRequest'])) {
                if (isset($Command['address']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['EP']) && isset($Command['Proprio']))
                    $this->readAttribute($priority, $dest, $Command['address'], $Command['EP'], $Command['clusterId'], $Command['attributeId'], $Command['Proprio'] );
                else if ((isset($Command['ReadAttributeRequest'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['EP']))
                    $this->readAttribute($priority, $dest, $Command['address'], $Command['EP'], $Command['clusterId'], $Command['attributeId']);
                return;
            }

            if (isset($Command['readAttributeRequest'])) {
                $this->readAttribute($priority, $dest, $Command['addr'], $Command['ep'], $Command['clustId'], $Command['attrId']);
                return;
            }

            // ReadAttributeRequestMulti ------------------------------------------------------------------------------------
            if ((isset($Command['ReadAttributeRequestMulti'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']))
            {
                $this->getParamMulti( $Command );
                return;
            }

            // ReadAttributeRequest ------------------------------------------------------------------------------------
            // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
            if ((isset($Command['ReadAttributeRequestHue'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']))
            {
                // echo "ReadAttributeRequest pour address: ".$Command['address']."\n";
                // if ($Command['ReadAttributeRequest']==1 )
                //{
                // $this->getParamHue( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "0B" );
                $this->readAttribute($priority, $dest, $Command['address'], "0B", $Command['clusterId'], $Command['attributeId']);
                //}
                return;
            }

            // ReadAttributeRequest ------------------------------------------------------------------------------------
            // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
            if ((isset($Command['ReadAttributeRequestOSRAM'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']))
            {
                // echo "ReadAttributeRequest pour address: ".$Command['address']."\n";
                // if ($Command['ReadAttributeRequest']==1 )
                //{
                // getParamOSRAM( $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "01" );
                $this->readAttribute($priority, $dest, $Command['address'], "03", $Command['clusterId'], $Command['attributeId']);
                //}
                return;
            }

            if (isset($Command['writeAttributeRequestIAS_WD'])) {
                // Parameters: EP=#EP&mode=Flash&duration=#slider#

                    $this->deamonlog('debug', "    command writeAttributeRequestIAS_WD", $this->debug['processCmd']);
                    // Msg Type = 0x0111

                    $priority = $Command['priority'];

                    $cmd = "0111";
                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <direction: uint8_t>
                    // <manufacturer specific: uint8_t>
                    // <manufacturer id: uint16_t>
                    // <Warning Mode: uint8_t>
                    // <Warning Duration: uint16_t>
                    // <Strobe duty cycle : uint8_t>
                    // <Strobe level : uint8_t>

                    $addressMode = "02";
                    $targetShortAddress = $Command['address'];
                    $sourceEndpoint = "01";
                    $destinationEndpoint = "01";
                    $direction = "01";
                    $manufacturerSpecific = "00";
                    $manufacturerId = "0000";
                    $warningMode = "04";
                    if ($Command['mode'] == "Flash" )      $warningMode = "04";        // 14, 24, 34: semble faire le meme son meme si la doc indique: Burglar, Fire, Emergency / 04: que le flash
                    if ($Command['mode'] == "Sound" )      $warningMode = "10";
                    if ($Command['mode'] == "FlashSound" ) $warningMode = "14";
                    $warningDuration = "000A"; // en seconde
                    if ($Command['duration'] > 0 )         $warningDuration = sprintf("%04s", dechex($Command['duration']));
                    // $strobeDutyCycle = "01";
                    // $strobeLevel = "F0";

                    $data = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$direction.$manufacturerSpecific.$manufacturerId.$warningMode.$warningDuration; //.$strobeDutyCycle.$strobeLevel;

                    $length = sprintf("%04s", dechex(strlen($data) / 2));

                    $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
                    return;
                }

            if (isset($Command['addGroup']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupAddress']))
            {
                $this->deamonlog('debug', "    Add a group to a device", $this->debug['processCmd']);
                //echo "Add a group to an IKEA bulb\n";

                // 15:24:36.029 -> 01 02 10 60 02 10 02 19 6D 02 12 83 DF 02 11 02 11 C2 98 02 10 02 10 03
                // 15:24:36.087 <- 01 80 00 00 04 54 00 B0 00 60 03
                // 15:24:36.164 <- 01 80 60 00 07 08 B0 01 00 04 00 C2 98 03
                // Add Group
                // Message Description
                // Msg Type = 0x0060 Command ID = 0x00
                $cmd    = "0060";
                $length  = "0007";
                // <address mode: uint8_t>
                //<target short address: uint16_t>
                //<source endpoint: uint8_t>
                //<destination endpoint: uint8_t>
                //<group address: uint16_t>
                $addressMode            = "02";
                $address                = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = $Command['DestinationEndPoint'];
                $groupAddress           = $Command['groupAddress'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$groupAddress ;
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            // Add Group APS
            // Title => 000B57fffe3025ad (IEEE de l ampoule) <= to be reviewed
            // message => reportToAddress=00158D0001B22E24&ClusterId=0006 <= to be reviewed

            if (isset($Command['addGroupAPS'])  )
            {
                $this->deamonlog('debug', "    command add group with APS", $this->debug['processCmd']);
                // Msg Type = 0x0530
                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (05 -> 0x05)
                // <data: auint8_t>
                // APS Part <= data
                // dummy 00 to align mesages                      -> 1
                // <cmdAddGroup>                                  -> 1
                // <group>                                        -> 2
                // <length>                                       -> 1

                // => 16 -> 0x10

                $addressMode = "02";
                $targetShortAddress = $Command['address'];
                $sourceEndpointBind = "01";
                $destinationEndpointBind = "01";
                $profileIDBind = "0104";
                $clusterIDBind = "0004";
                $securityMode = "02";
                $radius = "30";
                $dataLength = "06";

                $dummy = "01";  // I don't know why I need this but if I don't put it then I'm missing some data
                $dummy1 = "00";  // Dummy

                $cmdAddGroup = "00";
                $groupId = "AAAA";
                $length = "00";

                $length = "0011";

                // $data =  $targetExtendedAddress.$targetEndpoint.$clusterID.$destinationAddressMode.$destinationAddress.$destinationEndpoint;
                // $data1 = $addressMode.$targetShortAddress.$sourceEndpointBind.$destinationEndpointBind.$profileIDBind.$clusterIDBind.$securityMode.$radius.$dataLength;
                $data1 = $addressMode.$targetShortAddress.$sourceEndpointBind.$destinationEndpointBind.$clusterIDBind.$profileIDBind.$securityMode.$radius.$dataLength;
                $data2 = $dummy.$dummy1.$cmdAddGroup.$groupId.$length;

                $this->deamonlog('debug', "    Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpointBind."-".$destinationEndpointBind."-".$clusterIDBind."-".$profileIDBind."-".$securityMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2) , $this->debug['processCmd']);
                $this->deamonlog('debug', "    Data2: ".$dummy.$dummy1.$cmdAddGroup.$groupId.$length." len: ".(strlen($data2)/2) , $this->debug['processCmd']);

                $data = $data1.$data2;
                // $this->deamonlog('debug', "Data: ".$data." len: ".(strlen($data)/2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);
                return;
            }

            if (isset($Command['removeGroup']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupAddress']))
            {
                $this->deamonlog('debug', "    Remove a group to a device", $this->debug['processCmd']);
                //echo "Remove a group to an IKEA bulb\n";

                // 15:24:36.029 -> 01 02 10 60 02 10 02 19 6D 02 12 83 DF 02 11 02 11 C2 98 02 10 02 10 03
                // 15:24:36.087 <- 01 80 00 00 04 54 00 B0 00 60 03
                // 15:24:36.164 <- 01 80 60 00 07 08 B0 01 00 04 00 C2 98 03
                // Add Group
                // Message Description
                // Msg Type = 0x0060 Command ID = 0x00
                $cmd = "0063";
                $length = "0007";
                // <address mode: uint8_t>
                //<target short address: uint16_t>
                //<source endpoint: uint8_t>
                //<destination endpoint: uint8_t>
                //<group address: uint16_t>
                $addressMode = "02";
                $address = $Command['address'];
                $sourceEndpoint = "01";
                $destinationEndpoint = $Command['DestinationEndPoint'] ;
                $groupAddress = $Command['groupAddress'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$groupAddress ;
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            // Replace Equipement
            if (isset($Command['replaceEquipement']) && isset($Command['old']) && isset($Command['new']))
            {
                $this->deamonlog('debug', "    Replace an Equipment", $this->debug['processCmd']);

                $old = $Command['old'];
                $new = $Command['new'];

                $this->deamonlog('debug',"    Update eqLogic table for new object", $this->debug['processCmd']);
                $sql =          "UPDATE `eqLogic` SET ";
                $sql = $sql.  "name = 'Abeille-".$new."-New' , logicalId = '".$new."', configuration = replace(configuration, '".$old."', '".$new."' ) ";
                $sql = $sql.  "WHERE  eqType_name = 'Abeille' AND logicalId = '".$old."' AND configuration LIKE '%".$old."%'";
                $this->deamonlog('debug',"sql: ".$sql, $this->debug['processCmd']);
                DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

                $this->deamonlog('debug',"    Update cmd table for new object", $this->debug['processCmd']);
                $sql =          "UPDATE `cmd` SET ";
                $sql = $sql.  "configuration = replace(configuration, '".$old."', '".$new."' ) ";
                $sql = $sql.  "WHERE  eqType = 'Abeille' AND configuration LIKE '%".$old."%' ";
                $this->deamonlog('debug',"    sql: ".$sql, $this->debug['processCmd']);
                DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
                return;
            }

            //
            if (isset($Command['UpGroup']) && isset($Command['address']) && isset($Command['step']))
            {
                $this->deamonlog('debug','    UpGroup for: '.$Command['address'], $this->debug['processCmd']);
                // <address mode: uint8_t>          -> 2
                // <target short address: uint16_t> -> 4
                // <source endpoint: uint8_t>       -> 2
                // <destination endpoint: uint8_t>  -> 2
                // <onoff: uint8_t>                 -> 2
                // <step mode: uint8_t >            -> 2
                // <step size: uint8_t>             -> 2
                // <Transition Time: uint16_t>      -> 4
                // -> 20/2 =10 => 0A

                $cmd = "0082";
                $length = "000A";
                if (isset ( $Command['addressMode'] )) { $addressMode = $Command['addressMode']; } else { $addressMode = "02"; }

                $address = $Command['address'];
                $sourceEndpoint = "01";
                if (isset ( $Command['destinationEndpoint'] )) { $destinationEndpoint = $Command['destinationEndpoint'];} else { $destinationEndpoint = "01"; };
                $onoff = "00";
                $stepMode = "00"; // 00 : Up, 01 : Down
                $stepSize = $Command['step'];
                $TransitionTime = "0005"; // 1/10s of a s

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
                return;
            }

            if (isset($Command['DownGroup']) && isset($Command['address']) && isset($Command['step']))
            {
                $this->deamonlog('debug','    DownGroup for: '.$Command['address'], $this->debug['processCmd']);
                // <address mode: uint8_t>          -> 2
                // <target short address: uint16_t> -> 4
                // <source endpoint: uint8_t>       -> 2
                // <destination endpoint: uint8_t>  -> 2
                // <onoff: uint8_t>                 -> 2
                // <step mode: uint8_t >            -> 2
                // <step size: uint8_t>             -> 2
                // <Transition Time: uint16_t>      -> 4
                // -> 20/2 =10 => 0A

                $cmd = "0082";
                $length = "000A";
                if (isset ( $Command['addressMode'] )) { $addressMode = $Command['addressMode']; } else { $addressMode = "02"; }

                $address = $Command['address'];
                $sourceEndpoint = "01";
                if (isset ( $Command['destinationEndpoint'] )) { $destinationEndpoint = $Command['destinationEndpoint'];} else { $destinationEndpoint = "01"; };
                $onoff = "00";
                $stepMode = "01"; // 00 : Up, 01 : Down
                $stepSize = $Command['step'];
                $TransitionTime = "0005"; // 1/10s of a s

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
                return;
            }

            // ON / OFF with no effects
            if (isset($Command['onoff']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']))
            {
                $this->deamonlog('debug','    OnOff for: '.$Command['address'].' action (0:Off, 1:On, 2:Toggle): '.$Command['action'], $this->debug['processCmd']);
                // <address mode: uint8_t>
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <command ID: uint8_t>
                    // Command Id
                    // 0 - Off
                    // 1 - On
                    // 2 - Toggle

                $cmd                    = "0092";

                $addressMode            = $Command['addressMode']; // 01: Group, 02: device
                $address                = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = $Command['destinationEndpoint'];
                $action                 = $Command['action'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$action;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);

                if ($addressMode == "02" ) {
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+2), "ep=".$destinationEndpoint."&clustId=0006&attrId=0000" );
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+3), "ep=".$destinationEndpoint."&clustId=0008&attrId=0000" );
                }
                return;
            }

            // ON / OFF with no effects RAW with no APS ACK
            // Not used as some eq have a strange behavior if the APS ACK is not set (e.g. Xiaomi Plug / should probably test again / bug from the eq ?)
            if (isset($Command['onoffraw']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']))
            {
                $this->deamonlog('debug', "    command setParam4", $this->debug['processCmd']);

                $dest       = $Command['dest'];
                $priority   = $Command['priority'];

                $cmd = "0530";

                // <address mode: uint8_t>              -> 1
                // <target short address: uint16_t>     -> 2
                // <source endpoint: uint8_t>           -> 1
                // <destination endpoint: uint8_t>      -> 1

                // <profile ID: uint16_t>               -> 2
                // <cluster ID: uint16_t>               -> 2

                // <security mode: uint8_t>             -> 1
                // <radius: uint8_t>                    -> 1
                // <data length: uint8_t>               -> 1  (22 -> 0x16)

                // <data: auint8_t> APS Part <= data

                $addressMode        = $Command['addressMode'];
                $targetShortAddress = $Command['address'];
                $sourceEndpoint     = "01";
                if ($Command['destinationEndpoint']>1 ) { $destinationEndpoint = $Command['destinationEndpoint']; } else { $destinationEndpoint = "01"; } // $destinationEndPoint; // "01";

                $profileID          = "0104";
                $clusterID          = "0006"; // $Command['clusterId'];

                $securityMode       = "02"; // ???
                $radius             = "30";
                // $dataLength <- calculated later

                $frameControl               = "11"; // Ici dans cette commande c est ZCL qu'on control
                $transqactionSequenceNumber = "1A"; // to be reviewed
                $commandWriteAttribute      = $Command['action'];

                $data2 = $frameControl.$transqactionSequenceNumber.$commandWriteAttribute;
                $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));
                $data1 = $addressMode.$targetShortAddress.$sourceEndpoint.$destinationEndpoint.$clusterID.$profileID.$securityMode.$radius.$dataLength;
                $data = $data1.$data2;

                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $targetShortAddress);

                if ($addressMode == "02" ) {
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$targetShortAddress."/ReadAttributeRequestMulti&time=".(time()+2), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
                }
                return;
            }

            // On / Off Timed Send
            if (isset($Command['OnOffTimed']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']) && isset($Command['onTime']) && isset($Command['offWaitTime']))
            {
                $this->deamonlog('debug','    OnOff for: '.$Command['address'].' action (0:Off, 1:On, 2:Toggle): '.$Command['action'].' - '.$Command['onTime'].' - '.$Command['ffWaitTime'], $this->debug['processCmd']);
                // <address mode: uint8_t>    Status
                // <target short address: uint16_t>
                // <source endpoint: uint8_t>
                // <destination endpoint: uint8_t>
                // <onoff: uint8_t>
                // <on time: uint16_t>
                // <off time: uint16_t>
                    // On / Off:
                    // 0 = Off
                    // 1 = On
                    // Time: Seconds

                $cmd = "0093";
                // $length = "0006";
                $addressMode            = $Command['addressMode'];
                $address                = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = $Command['destinationEndpoint'];
                $action                 = $Command['action'];
                $onTime                 = $Command['onTime'];
                $offWaitTime            = $Command['offWaitTime'];

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$action.$onTime.$offWaitTime;
                $length = sprintf("%04s", dechex(strlen($data) / 2));
                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);

                if ($addressMode == "02" ) {
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+2), "ep=".$destinationEndpoint."&clustId=0006&attrId=0000" );
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+3), "ep=".$destinationEndpoint."&clustId=0008&attrId=0000" );

                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+$Command['onTime']), "ep=".$destinationEndpoint."&clustId=0006&attrId=0000" );
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+$Command['onTime']), "ep=".$destinationEndpoint."&clustId=0008&attrId=0000" );
                }
                return;
            }

            // Move to Colour
            if (isset($Command['setColour']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['X']) && isset($Command['Y'])  && isset($Command['destinationEndPoint']))
            {
                // <address mode: uint8_t>              2
                // <target short address: uint16_t>     4
                // <source endpoint: uint8_t>           2
                // <destination endpoint: uint8_t>      2
                // <colour X: uint16_t>                 4
                // <colour Y: uint16_t>                 4
                // <transition time: uint16_t >         4

                $cmd = "00B7";
                // 8+16+8+8+16+16+16 = 88 /8 = 11 => 0x0B
                $length = "000B";

                $addressMode            = $Command['addressMode'];
                $address                = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = $Command['destinationEndPoint'];
                $colourX                = $Command['X'];
                $colourY                = $Command['Y'];
                if (isset($Command['duration']) && $Command['duration']>0)
                    $duration = sprintf("%04s",dechex($Command['duration']));
                else
                    $duration               = "0001";

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$colourX.$colourY.$duration ;

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            // Take RGB (0-255) convert to X, Y and send the color
            if (isset($Command['setColourRGB']))
            {
                // The reverse transformation
                // https://en.wikipedia.org/wiki/SRGB

                $R=$Command['R'];
                $G=$Command['G'];
                $B=$Command['B'];

                $a = 0.055;

                // are in the range 0 to 1. (A range of 0 to 255 can simply be divided by 255.0).
                $Rsrgb = $R / 255;
                $Gsrgb = $G / 255;
                $Bsrgb = $B / 255;

                if ($Rsrgb <= 0.04045 ) { $Rlin = $Rsrgb/12.92; } else { $Rlin = pow( ($Rsrgb+$a)/(1+$a), 2.4); }
                if ($Gsrgb <= 0.04045 ) { $Glin = $Gsrgb/12.92; } else { $Glin = pow( ($Gsrgb+$a)/(1+$a), 2.4); }
                if ($Bsrgb <= 0.04045 ) { $Blin = $Bsrgb/12.92; } else { $Blin = pow( ($Bsrgb+$a)/(1+$a), 2.4); }

                $X = 0.4124 * $Rlin + 0.3576 * $Glin + 0.1805 *$Blin;
                $Y = 0.2126 * $Rlin + 0.7152 * $Glin + 0.0722 *$Blin;
                $Z = 0.0193 * $Rlin + 0.1192 * $Glin + 0.9505 *$Blin;

                if (($X + $Y + $Z)!=0 ) {
                    $x = $X / ( $X + $Y + $Z );
                    $y = $Y / ( $X + $Y + $Z );
                }
                else {
                    echo "Can t do the convertion.";
                }

                $x = $x*255*255;
                $y = $y*255*255;

                // Meme commande que la commande du dessus
                $cmd = "00B7";
                // 8+16+8+8+16+16+16 = 88 /8 = 11 => 0x0B
                $length = "000B";

                $addressMode = "02";
                $address = $Command['address'];
                $sourceEndpoint = "01";
                $destinationEndpoint = $Command['destinationEndPoint'];
                $colourX = str_pad( dechex($x), 4, "0", STR_PAD_LEFT);
                $colourY = str_pad( dechex($y), 4, "0", STR_PAD_LEFT);
                $duration = "0001";

                $this->deamonlog( 'debug', "    colourX: ".$colourX." colourY: ".$colourY, $this->debug['processCmd'] );

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$colourX.$colourY.$duration ;

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            // Move to Colour Temperature
            if (isset($Command['setTemperature']) && isset($Command['address']) && isset($Command['temperature']) && isset($Command['destinationEndPoint']))
            {
                // <address mode: uint8_t>              2
                // <target short address: uint16_t>     4
                // <source endpoint: uint8_t>           2
                // <destination endpoint: uint8_t>      2
                // <colour temperature: uint16_t>       4
                // <transition time: uint16_t>          4

                $cmd = "00C0";

                $addressMode            = $Command['addressMode'];
                $address                = $Command['address'];
                $sourceEndpoint         = "01";
                $destinationEndpoint    = $Command['destinationEndPoint'];
                $temperature            = $Command['temperature'];
                $duration               = "0001";

                $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$temperature.$duration ;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);

                if ($addressMode == "02" ) {
                    $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/readAttribute&time=".(time()+2), "ep=".$destinationEndpoint."&clustId=0300&attrId=0007" );
                }
                return;
            }

            if (isset($Command['getManufacturerName']) && isset($Command['address']))
            {
                if ($Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
                $this->readAttribute($priority, $dest, $Command['address'], $Command['destinationEndPoint'], "0000", "0004");
                return;
            }

            if (isset($Command['getName']) && isset($Command['address']))
            {
                if ($Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
                $this->readAttribute($priority, $dest, $Command['address'], $Command['destinationEndPoint'], "0000", "0005");
                return;
            }

            if (isset($Command['getLocation']) && isset($Command['address']))
            {
                if ($Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
                $this->readAttribute($priority, $dest, $Command['address'], $Command['destinationEndPoint'], "0000", "0010");
                return;
            }

            if (isset($Command['setLocation']) && isset($Command['address']))
            {
                if ($Command['location'] == "" ) { $Command['location'] = "Not Def"; }
                if ($Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }

                $this->setParam2( $dest, $Command['address'], "0000", "0010",$Command['destinationEndPoint'],$Command['location'], "42" );
                return;
            }

            if (isset($Command['MgtLeave']) && isset($Command['address']) && isset($Command['IEEE']))
            {
                // Zigbee specification
                // 2.4.3.3.5   Mgmt_Leave_req
                // (ClusterID=0x0034)
                //2.4.3.3.5.1     When Generated The Mgmt_Leave_req is generated from a Local Device requesting that a Remote Device leave the network
                // or to request that another device leave the network. The Mgmt_Leave_req is generated by a management application which directs
                // the request to a Remote Device where the NLME-LEAVE.request is to be executed using the parameter supplied by Mgmt_Leave_req.

                $cmd = "0047";

                // <target short address: uint16_t>
                // <extended address: uint64_t>
                // <Rejoin: uint8_t>
                // <Remove Children: uint8_t>
                //  Rejoin,
                //      0 = Do not rejoin
                //      1 = Rejoin
                //  Remove Children
                //      0 = Leave, removing children
                //      1 = Leave, do not remove children

                $address        = $Command['address'];
                $IEEE           = $Command['IEEE'];
                if (isset($Command['Rejoin'])) $Rejoin = $Command['Rejoin']; else $Rejoin = "00";
                if (isset($Command['RemoveChildren']))  $RemoveChildren = $Command['RemoveChildren']; else $RemoveChildren = "01";

                $data = $address.$IEEE.$Rejoin.$RemoveChildren;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data, $address);
                return;
            }

            if (isset($Command['LeaveRequest']) && isset($Command['IEEE']))
            {
                $cmd = "004C";

                // <extended address: uint64_t>
                // <Rejoin: uint8_t>
                // <Remove Children: uint8_t>
                //  Rejoin,
                //      0 = Do not rejoin
                //      1 = Rejoin
                //  Remove Children
                //      0 = Leave, removing children
                //      1 = Leave, do not remove children

                $IEEE = $Command['IEEE'];
                if (isset($Command['Rejoin']))
                    $Rejoin = $Command['Rejoin'];
                else
                    $Rejoin = "00";
                if (isset($Command['RemoveChildren']))
                    $RemoveChildren = $Command['RemoveChildren'];
                else
                    $RemoveChildren = "01";

                $data = $IEEE.$Rejoin.$RemoveChildren;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            // if (isset($Command['Remove']) && isset($Command['address']) && isset($Command['IEEE']))
            // https://github.com/KiwiHC16/Abeille/issues/332
            if (isset($Command['Remove']) && isset($Command['ParentAddressIEEE']) && isset($Command['ChildAddressIEEE']))
            {
                // <target short address: uint64_t>
                // <extended address: uint64_t>
                $cmd = "0026";

                // Doc is probably not up to date, need to provide IEEE twice
                // Tested and works in case of a NE in direct to coordinator
                // To be tested if message is routed.
                // <target short address: uint16_t>
                // <extended address: uint64_t>

                // Zigbee specification
                // Chapter 4 Security Services Specification
                // 4.4 APS Layer Security
                // 4.4.5 Remove Device Services
                // The APSME provides services that allow a device (for example, a Trust Center) to inform another device (for example, a router) that one of its children should be removed from the network.
                // The ZDO of a device (for example, a Trust Center) shall issue this primitive when it wants to request that a parent device (for example, a router) remove one of its children from the network. For example, a Trust Center can use this primitive to remove a child device that fails to authenticate properly.
                // APSME-REMOVE-DEVICE.request { ParentAddress IEEE, ChildAddress IEEE}

                // $address        = $Command['address'];
                $address        = $Command['ParentAddressIEEE'];
                $IEEE           = $Command['ChildAddressIEEE'];

                $data = $address.$IEEE ;
                $length = sprintf("%04s", dechex(strlen($data) / 2));

                $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                return;
            }

            if (isset($Command['commandLegrand']))
            {
                $this->commandLegrand($dest,$Command);
                return;
            }

            /* Tcharp38: New way of checking commands. */
            if (isset($Command['name'])) {
                $cmdName = $Command['name'];
                $this->deamonlog('debug', '    '.$cmdName.' cmd', $this->debug['processCmd']);

                /* Note: commands are described in the following order:
                   - zigate specific commands
                   - Zigbee cluster library global commands
                   - Zigbee cluster library cluster specific commands */

                /*
                 * Zigate specific commands
                 */

                // Zigate specific command
                if ($cmdName == 'zgSetMode') {
                    $mode = $Command['mode'];
                    if ($mode == "raw") {
                        $modeVal = "01";
                    } else if ($mode == "hybrid") {
                        $modeVal = "02";
                    } else // Normal
                        $modeVal = "00";
                    $this->deamonlog('debug',"    Setting mode ".$mode."/".$modeVal);
                    $this->addCmdToQueue($priority, $dest, "0002", "0001", $modeVal);
                    return;
                }

                // Zigate specific command
                else if ($cmdName == 'SetPermit') {
                    if (isset($Command['Inclusion'])) {
                        $cmd = "0049";
                        $length = "0004";
                        $data = "FFFCFE00";
                        // <target short address: uint16_t>
                        // <interval: uint8_t>
                        // <TCsignificance: uint8_t>

                        // Target address: May be address of gateway node or broadcast (0xfffc)
                        // Interval:
                        // 0 = Disable Joining 1 – 254 = Time in seconds to allow joins 255 = Allow all joins
                        // TCsignificance:
                        // 0 = No change in authentication 1 = Authentication policy as spec

                        // 09:08:29.156 -> 01 02 10 49 02 10 02 14 50 FF FC 1E 02 10 03
                        // 01 : Start
                        // 02 10 49: 00 49: Permit Joining request Msg Type = 0x0049
                        // 02 10 02 14: Length:
                        // 50: Chrksum
                        // FF FC:<target short address: uint16_t>
                        // 1E: <interval: uint8_t>
                        // 02 10: <TCsignificance: uint8_t> 00

                        // 09:08:29.193 <- 01 80 00 00 04 F4 00 39 00 49 03
                        $this->addCmdToQueue($priority, $dest, $cmd, $length, $data); //1E = 30 secondes

                        // $CommandAdditionelle['permitJoin'] = "permitJoin";
                        // $CommandAdditionelle['permitJoin'] = "Status";
                        // processCmd( $dest, $CommandAdditionelle,$_requestedlevel );
                        $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "Cmd".$dest."/0000/permitJoin", "Status" );
                    } elseif (isset($Command["InclusionStop"])) {
                        $cmd = "0049";
                        $length = "0004";
                        $data = "FFFC0000";
                        // <target short address: uint16_t>
                        // <interval: uint8_t>
                        // <TCsignificance: uint8_t>

                        // Target address: May be address of gateway node or broadcast (0xfffc)
                        // Interval:
                        // 0 = Disable Joining 1 – 254 = Time in seconds to allow joins 255 = Allow all joins
                        // TCsignificance:
                        // 0 = No change in authentication 1 = Authentication policy as spec

                        // 09:08:29.156 -> 01 02 10 49 02 10 02 14 50 FF FC 1E 02 10 03
                        // 01 : Start
                        // 02 10 49: 00 49: Permit Joining request Msg Type = 0x0049
                        // 02 10 02 14: Length:
                        // 50: Chrksum
                        // FF FC:<target short address: uint16_t>
                        // 1E: <interval: uint8_t>
                        // 02 10: <TCsignificance: uint8_t> 00

                        // 09:08:29.193 <- 01 80 00 00 04 F4 00 39 00 49 03
                        $this->addCmdToQueue($priority, $dest, $cmd, $length, $data); //1E = 30 secondes

                        // $CommandAdditionelle['permitJoin'] = "permitJoin";
                        // $CommandAdditionelle['permitJoin'] = "Status";
                        // processCmd( $dest, $CommandAdditionelle,$_requestedlevel );
                        $this->publishMosquitto(queueKeyCmdToCmd, priorityInterrogation, "Cmd".$dest."/0000/permitJoin", "Status");
                    }
                    return;
                }

                // Zigate specific command
                else if ($cmdName == 'permitJoin') {
                    //  && $Command['permitJoin']=="Status") {
                    // $this->deamonlog("debug", "    permitJoin-Status");
                    // “Permit join” status on the target
                    // Msg Type =  0x0014

                    $cmd = "0014";
                    $length = "0000";
                    $data = "";
                    $this->addCmdToQueue($priority, $dest, $cmd, $length, $data); //1E = 30 secondes
                    return;
                }

                // Zigate specific command
                else if ($cmdName == 'getVersion') {
                    $this->addCmdToQueue($priority, $dest, "0010", "0000", "");
                    return;
                }

                // Zigate specific command
                // Set Time server (v3.0f)
                else if ($cmdName == 'setTimeServer') {
                    if (!isset($Command['time'])) {
                        $zgRef = mktime(0, 0, 0, 1, 1, 2000); // 2000-01-01 00:00:00
                        $Command['time'] = time() - $zgRef;
                    }
                    $this->deamonlog('debug', "    setTimeServer, time=".$Command['time'], $this->debug['processCmd']);

                    /* Cmd 0016 reminder
                    payload = <timestamp UTC: uint32_t> from 2000-01-01 00:00:00
                    WARNING: PHP time() is based on 1st of jan 1970 and NOT 2000 !! */
                    $cmd = "0016";
                    $data = sprintf("%08s", dechex($Command['time']));
                    $length = sprintf("%04s", dechex(strlen($data) / 2));
                    $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                    return;
                }

                // Zigate specific command
                else if ($cmdName == 'getTimeServer') {
                    $cmd = "0017";
                    $data = "";
                    $length = sprintf("%04s", dechex(strlen($data) / 2));
                    $this->addCmdToQueue($priority, $dest, $cmd, $length, $data);
                    return;
                }

                // Zigate specific command
                else if (($cmdName == 'zgStartNetwork') || ($cmdName == 'startNetwork')) {
                    $this->addCmdToQueue($priority, $dest, "0024", "0000", "");
                    return;
                }

                /*
                 * ZCL general commands
                 */

                // ZCL global: readAttribute command
                else if ($cmdName == 'readAttribute') {
                    /* Checking that mandatory infos are there */
                    $required = ['addr', 'ep', 'clustId', 'attrId'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;

                    $this->readAttribute($priority, $dest, $Command['addr'], $Command['ep'], $Command['clustId'], $Command['attrId']);
                    return;
                }

                // Tcharp38: Generic 'write attribute request' function
                // ZCL global: writeAttribute command
                else if ($cmdName == 'writeAttribute') {
                    /* Checking that mandatory infos are there */
                    $required = ['ep', 'clustId', 'attrId', 'attrVal'];
                    if (!$this->checkRequiredParams($required, $Command))
                        return;
                    if (!isset($Command['attrType'])) {
                        /* Attempting to find attribute type according to its id */
                        $attr = zbGetZCLAttribute($Command['clustId'], $Command['attrId']);
                        if (($attr === false) || !isset($attr['dataType'])) {
                            $this->deamonlog('debug', "    command writeAttribute ERROR: Missing 'attrType'");
                            return;
                        }
                        $Command['attrType'] = sprintf("%02X", $attr['dataType']);
                        $this->deamonlog('debug', "    Using attrType ".$Command['attrType'], $this->debug['processCmd']);
                    }

                    /* Cmd 0110 reminder:
                        <address mode: uint8_t>	Data Indication
                        <target short address: uint16_t>
                        <source endpoint: uint8_t>
                        <destination endpoint: uint8_t>
                        <Cluster id: uint16_t>
                        <direction: uint8_t>
                        <manufacturer specific: uint8_t>
                        <manufacturer id: uint16_t>
                        <number of attributes: uint8_t>
                        <attributes list: data list of uint16_t each>
                            Attribute ID : uint16_t
                            Attribute Type : uint8_t
                            Attribute Data : byte[32]
                    */

                    $priority = $Command['priority'];

                    $cmd            = "0110";

                    $addrMode       = "02";
                    $addr           = $Command['addr'];
                    $srcEp          = "01";
                    $destEp         = $Command['ep'];
                    $clustId        = $Command['clustId'];
                    $dir            = "01";
                    $manufSpecific  = "00";
                    $manufId        = "0000";
                    $nbOfAttributes = "01";
                    $attrList       = $Command['attrId'].$Command['attrType'].$Command['attrVal'];

                    $data = $addrMode.$addr.$srcEp.$destEp.$clustId.$dir.$manufSpecific.$manufId.$nbOfAttributes.$attrList;
                    $len = sprintf("%04s", dechex(strlen($data) / 2));

                    $this->addCmdToQueue($priority, $dest, $cmd, $len, $data, $addr);
                    return;
                }

                // ZCL global: discoverAttributes command
                else if ($cmdName == 'discoverAttributes') {
                    $this->deamonlog('debug','    addr='.$Command['addr']." - ".$Command['startAttrId']." - ".$Command['maxAttrId'], $this->debug['processCmd']);
                    $cmd = "0140";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <Cluster id: uint16_t>
                    // <Attribute id : uint16_t>
                    // <direction: uint8_t>
                    //      Direction:
                    //      0 – from server to client
                    //      1 – from client to server
                    // <manufacturer specific: uint8_t>
                    //      Manufacturer specific :
                    //      1 – Yes
                    //      0 – No
                    // <manufacturer id: uint16_t>
                    // <Max number of identifiers: uint8_t>

                    $addressMode    = "02";
                    $address        = $Command['addr'];
                    $srcEp          = "01";
                    $dstEP          = sprintf("%02X", hexdec($Command['ep']));
                    $clusterId      = sprintf("%04X", hexdec($Command['clustId']));
                    $attributeId    = $Command['startAttrId'];
                    if (!isset($Command['dir']))
                        $Command['dir'] = '00'; // Get attributes from 'server' cluster by default
                    $direction      = $Command['dir']; //	'00' – server cluster atttrib, '01' – client cluster attrib
                    $manuSpec       = "00"; //  1 – Yes	 0 – No
                    $manuId         = "0000";
                    $maxAttributeId = $Command['maxAttrId'];

                    $data = $addressMode.$address.$srcEp.$dstEP.$clusterId.$attributeId.$direction.$manuSpec.$manuId.$maxAttributeId ;
                    $len = sprintf("%04s", dechex(strlen($data) / 2));

                    $this->addCmdToQueue($priority, $dest, $cmd, $len, $data);
                    return;
                }

                // ZCL global: Discover commands received
                else if ($cmdName == 'discoverCommandsReceived') {
                    /* Mandatory infos: addr, ep, clustId */
                    $required = ['addr', 'ep', 'clustId'];
                    $missingParam = false;
                    foreach ($required as $idx => $param) {
                        if (isset($Command[$param]))
                            continue;
                        $this->deamonlog('debug', "    ERROR: Missing '".$param."'");
                        $missingParam = true;
                    }
                    if ($missingParam)
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    // <data: auint8_t>
                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode       = "02";
                    $addr           = $Command['addr'];
                    $srcEp          = "01";
                    $destEp         = $Command['ep'];
                    $profId         = "0104";
                    $clustId        = $Command['clustId'];
                    $securityMode   = "02";
                    $radius         = "1E";

                    /* ZCL header */
                    $fcf            = "10"; // Frame Control Field
                    $sqn            = "01";
                    $cmdId          = "11"; // Discover Commands Received

                    $startId        = isset($Command['startId']) ? $Command['startId'] : "00";
                    $max            = isset($Command['max']) ? $Command['max'] : "FF";

                    $data2 = $fcf.$sqn.$cmdId.$startId.$max;
                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));

                    $data1 = $addrMode.$addr.$srcEp.$destEp.$clustId.$profId.$securityMode.$radius.$dataLen2;
                    $data = $data1.$data2;
                    $len = sprintf("%04s", dechex(strlen($data) / 2));

                    $this->addCmdToQueue($priority, $dest, $cmd, $len, $data, $addr);
                    return;
                }

                // ZCL global: Discover Attributes Extended
                else if ($cmdName == 'discoverAttributesExt') {
                    /* Mandatory infos: addr, ep, clustId */
                    $required = ['addr', 'ep', 'clustId'];
                    $missingParam = false;
                    foreach ($required as $idx => $param) {
                        if (isset($Command[$param]))
                            continue;
                        $this->deamonlog('debug', "    ERROR: Missing '".$param."'");
                        $missingParam = true;
                    }
                    if ($missingParam)
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    // <data: auint8_t>
                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode       = "02";
                    $addr           = $Command['addr'];
                    $srcEp          = "01";
                    $destEp         = $Command['ep'];
                    $profId         = "0104";
                    $clustId        = $Command['clustId'];
                    $securityMode   = "02";
                    $radius         = "1E";

                    /* ZCL header */
                    $fcf            = "10"; // Frame Control Field
                    $sqn            = "01";
                    $cmdId          = "15"; // Discover Attributes Extended

                    $startId        = isset($Command['startId']) ? $Command['startId'] : "0000";
                    $max            = isset($Command['max']) ? $Command['max'] : "FF";

                    $data2 = $fcf.$sqn.$cmdId.$startId.$max;
                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));

                    $data1 = $addrMode.$addr.$srcEp.$destEp.$clustId.$profId.$securityMode.$radius.$dataLen2;
                    $data = $data1.$data2;
                    $len = sprintf("%04s", dechex(strlen($data) / 2));

                    $this->addCmdToQueue($priority, $dest, $cmd, $len, $data, $addr);
                    return;
                }

                /*
                 * ZCL cluster specific commands
                 */

                // ZCL cluster 0000 specific: (received) commands
                else if ($cmdName == 'cmd-0000') {
                    /* Mandatory infos: addr, ep, clustId */
                    $required = ['addr', 'ep'];
                    $missingParam = false;
                    foreach ($required as $idx => $param) {
                        if (isset($Command[$param]))
                            continue;
                        $this->deamonlog('debug', "    ERROR: Missing '".$param."'");
                        $missingParam = true;
                    }
                    if ($missingParam)
                        return;

                    $cmd = "0530";

                    // <address mode: uint8_t>
                    // <target short address: uint16_t>
                    // <source endpoint: uint8_t>
                    // <destination endpoint: uint8_t>
                    // <profile ID: uint16_t>
                    // <cluster ID: uint16_t>
                    // <security mode: uint8_t>
                    // <radius: uint8_t>
                    // <data length: uint8_t>

                    //  ZCL Control Field
                    //  ZCL SQN
                    //  Command Id
                    //  ....

                    $addrMode       = "02";
                    $addr           = $Command['addr'];
                    $srcEp          = "01";
                    $destEp         = $Command['ep'];
                    $profId         = "0104";
                    $clustId        = '0000';
                    $securityMode   = "02";
                    $radius         = "1E";

                    /* ZCL header */
                    $fcf            = "11"; // Frame Control Field
                    $sqn            = "23";
                    $cmdId          = "00"; // Reset to Factory Defaults

                    $data2 = $fcf.$sqn.$cmdId;
                    $dataLen2 = sprintf("%02s", dechex(strlen($data2) / 2));

                    $data1 = $addrMode.$addr.$srcEp.$destEp.$clustId.$profId.$securityMode.$radius.$dataLen2;
                    $data = $data1.$data2;
                    $len = sprintf("%04x", strlen($data) / 2);

                    $this->addCmdToQueue($priority, $dest, $cmd, $len, $data, $addr);
                    return;
                }

                else {
                    $this->deamonlog('debug', "    ERROR: Unexpected command '".$cmdName."'");
                    return;
                }
            }

            $this->deamonlog('debug', "    ERROR: Unexpected command '".json_encode($Command)."'");
        }
    }
?>
