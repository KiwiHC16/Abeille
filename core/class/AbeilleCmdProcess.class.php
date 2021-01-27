<?php

include_once __DIR__.'/AbeilleDebug.class.php';

class AbeilleCmdProcess extends AbeilleDebug {

    // Ne semble pas fonctionner et me fait planté la ZiGate, idem ques etParam()
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

        $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $clusterId . $direction . $manufacturerSpecific . $proprio . $numberOfAttributes . $attributeId . $attributeType . $value;

        $lenth = sprintf("%04s",dechex(strlen( $data )/2));

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);

        if ( isset($Command['repeat']) ) {
            if ( $Command['repeat']>1 ) {
                for ($x = 2; $x <= $Command['repeat']; $x++) {
                    sleep(5);
                    $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
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

        $priority = $Command['priority'];

        $cmd = "0110";
        $lenth = "000E";

        $addressMode = "02";
        // $address = $Command['address'];
        $sourceEndpoint = "01";
        // $destinationEndpoint = "01";
        //$ClusterId = "0006";
        $Direction = "01";
        $manufacturerSpecific = "00";
        $manufacturerId = "0000";
        $numberOfAttributes = "01";
        // $attributesList = "0000";
        $attributesList = $attributeId;
        $attributesList = "Salon1         ";

        $data = $addressMode . $address . $sourceEndpoint . $destinationEndPoint . $clusterId . $Direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $attributesList;
        // $this->deamonlog('debug','data: '.$data);
        // $this->deamonlog('debug','len data: '.strlen($data));
        //echo "Read Attribute command data: ".$data."\n";

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
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
        $this->deamonlog('debug', "  command setParam2");

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

        $data2 = $frameControl . $transqactionSequenceNumber . $commandWriteAttribute . $attributeId . $dataType . $lengthAttribut . $attributValue;

        $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));
        // $this->deamonlog('debug', "data2: ".$data2 );
        // $this->deamonlog('debug', "length data2: ".$dataLength );

        $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

        $data = $data1 . $data2;

        $lenth = sprintf("%04s",dechex(strlen( $data )/2));
        // $this->deamonlog('debug', "data: ".$data );
        // $this->deamonlog('debug', "lenth data: ".$lenth );

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
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
        $this->deamonlog('debug', "command setParam3");

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

        if ( isset($Command['destinationEndpoint']) ) {
            if ( $Command['destinationEndpoint']>1 ) {
                $destinationEndpoint = $Command['destinationEndpoint'];
            }
            else {
                $destinationEndpoint = "01";
            }
        }
        else {
            $destinationEndpoint = "01";
        }

        $profileID      = "0104";
        $clusterID      = $Command['clusterId'];

        $securityMode   = "02"; // ???
        $radius         = "30";
        // $dataLength <- define later

        $frameControlAPS = "40";   // APS Control Field
        // If Ack Request 0x40 If no Ack then 0x00
        // Avec 0x40 j'ai un default response

        $frameControlZCL = "14";   // ZCL Control Field
        // Disable Default Response + Manufacturer Specific

        $frameControl = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

        $Proprio = $Command['Proprio'][2].$Command['Proprio'][3].$Command['Proprio'][0].$Command['Proprio'][1];

        $transqactionSequenceNumber = "1A"; // to be reviewed
        $commandWriteAttribute      = "02";

        $attributeId = $Command['attributeId'][2].$Command['attributeId'][3].$Command['attributeId'][0].$Command['attributeId'][1];

        $dataType = $Command['attributeType'];

        $attributValue = $Command['value'];

        $data2 = $frameControl . $Proprio . $transqactionSequenceNumber . $commandWriteAttribute . $attributeId . $dataType . $attributValue;

        $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

        $this->deamonlog('debug', "data2: ".$data2 . " length data2: ".$dataLength );

        $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

        $data = $data1 . $data2;

        $lenth = sprintf("%04s",dechex(strlen( $data )/2));

        // $this->deamonlog('debug', "data: ".$data );
        // $this->deamonlog('debug', "lenth data: ".$lenth );

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
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

        $this->deamonlog('debug', "command setParam4");

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
        if ( $Command['destinationEndpoint']>1 ) { $destinationEndpoint = $Command['destinationEndpoint']; } else { $destinationEndpoint = "01"; } // $destinationEndPoint; // "01";

        $profileID          = "0104";
        $clusterID          = $Command['clusterId'];

        $securityMode       = "02"; // ???
        $radius             = "30";
        // $dataLength <- calculated later

        $frameControlAPS    = "40";   // APS Control Field - If Ack Request 0x40 If no Ack then 0x00 - Avec 0x40 j'ai un default response

        $frameControlZCL    = "10";   // ZCL Control Field - Disable Default Response + Not Manufacturer Specific

        $frameControl       = $frameControlZCL; // Ici dans cette commande c est ZCL qu'on control

        $transqactionSequenceNumber = "1A"; // to be reviewed
        $commandWriteAttribute      = "02";

        $attributeId        = $Command['attributeId'][2].$Command['attributeId'][3].$Command['attributeId'][0].$Command['attributeId'][1];

        $dataType           = $Command['attributeType'];
        $attributValue      = $Command['value'];

        $data2 = $frameControl . $transqactionSequenceNumber . $commandWriteAttribute . $attributeId . $dataType . $attributValue;

        $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

        // $this->deamonlog('debug', "data2: ".$data2 . " length data2: ".$dataLength );

        $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

        $data = $data1 . $data2;

        $lenth = sprintf("%04s",dechex(strlen( $data )/2));

        // $this->deamonlog('debug', "data: ".$data );
        // $this->deamonlog('debug', "lenth data: ".$lenth );

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
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

        $this->deamonlog('debug', "command setParam4");

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
        if ( $Command['destinationEndpoint']>1 ) { $destinationEndpoint = $Command['destinationEndpoint']; } else { $destinationEndpoint = "01"; } // $destinationEndPoint; // "01";

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

        $data2 = $frameControl . $Proprio . $transqactionSequenceNumber . $commandWriteAttribute . $attributeId . $dataType . $attributValue;

        $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

        // $this->deamonlog('debug', "data2: ".$data2 . " length data2: ".$dataLength );

        $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

        $data = $data1 . $data2;

        $lenth = sprintf("%04s",dechex(strlen( $data )/2));

        // $this->deamonlog('debug', "data: ".$data );
        // $this->deamonlog('debug', "lenth data: ".$lenth );

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
    }

    // Needed for fc41 of Legrand Contacteur
    function commandLegrand($dest,$Command) {

        // $this->deamonlog('debug',"commandLegrand()");

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
        if ( $Command['destinationEndpoint']>1 ) { $destinationEndpoint = $Command['destinationEndpoint']; } else { $destinationEndpoint = "01"; } // $destinationEndPoint; // "01";

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

        $data2 = $frameControlAPS . $manufacturerCode . $transqactionSequenceNumber . $command . $data;

        $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

        // $this->deamonlog('debug',"data2: ".$data2 . " length data2: ".$dataLength );

        $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

        $data = $data1 . $data2;

        $lenth = sprintf("%04s",dechex(strlen( $data )/2));

        // $this->deamonlog('debug',"data: ".$data );
        // $this->deamonlog('debug',"lenth data: ".$lenth );

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
    }

    function getParam($priority,$dest,$address,$clusterId,$attributeId,$destinationEndPoint,$Proprio) {
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
            Manufacturer specific :
            0 – No
            1 – Yes
        */

        $cmd = "0100";

        $addressMode = "02";
        // $address = $Command['address']; in the fct call
        $sourceEndpoint = "01";

        $ClusterId = $clusterId;
        $Direction = "00";
        if ( (strlen($Proprio)<1) || ($Proprio="0000") ) {
            $manufacturerSpecific = "00";
            $manufacturerId = "0000";
        }
        else {
            $manufacturerSpecific = "01";
            $manufacturerId = $Proprio;
        }
        $numberOfAttributes = "01";
        $attributesList = $attributeId;

        $data = $addressMode . $address . $sourceEndpoint . $destinationEndPoint . $ClusterId . $Direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $attributesList;

        $lenth = sprintf("%04s",dechex(strlen( $data )/2));

        // $this->deamonlog('debug','data: '.$data.' length: '.$lenth);

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
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
        $this->deamonlog('debug', " command getParamMulti");

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
        $this->deamonlog('debug', "     attribut list: ".json_encode($attributList));
        foreach ($attributList as $attribut) {
            $attributs .=  reverse_hex(str_pad( $attribut, 4, "0", STR_PAD_LEFT));
            $this->deamonlog('debug', "     attributs: ".$attributs);
        }

        $sep = "";
        $data2 = $zclControlField . $sep . $transactionSequence . $sep.  $cmdId . $sep . $attributs;
        $sep = "-";
        $data2Txt = $zclControlField . $sep . $transactionSequence . $sep.  $cmdId . $sep . $attributs;

        $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2) );

        $sep = "";
        $data1 = $addressMode . $sep . $targetShortAddress . $sep . $sourceEndpoint . $sep . $destinationEndpoint . $sep . $clusterID . $sep . $profileID . $sep . $securityMode . $sep . $radius . $sep . $dataLength;
        $sep = "-";
        $data1Txt = $addressMode . $sep . $targetShortAddress . $sep . $sourceEndpoint . $sep . $destinationEndpoint . $sep . $clusterID . $sep . $profileID . $sep . $securityMode . $sep . $radius . $sep . $dataLength;

        $this->deamonlog('debug', "  Data1: ".$data1Txt );
        $this->deamonlog('debug', "  Data2: ".$data2Txt );

        $data = $data1 . $data2;
        $lenth = sprintf("%04s",dechex(strlen( $data )/2));
        // $this->deamonlog('debug', "  Data: ".$data." len: ".$lenth );

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
    }

    // getParamHue: based on getParam for testing purposes. If works then perhaps merge with get param and manage the diff by parameters like destination endpoint
    function getParamHue($priority,$dest,$address,$clusterId,$attributeId) {
        $this->deamonlog('debug','getParamHue');

        $priority = $Command['priority'];

        $cmd = "0100";
        $lenth = "000E";
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

        $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $ClusterId . $Direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $attributesList;
        // $this->deamonlog('debug','len data: '.strlen($data));
        //echo "Read Attribute command data: ".$data."\n";

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
    }

    // getParamOSRAM: based on getParam for testing purposes. If works then perhaps merge with get param and manage the diff by parameters like destination endpoint
    function getParamOSRAM($priority,$dest,$address,$clusterId,$attributeId) {
        $this->deamonlog('debug','getParamOSRAM');

        $priority = $Command['priority'];

        $cmd = "0100";
        $lenth = "000E";
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

        $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $ClusterId . $Direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $attributesList;
        // $this->deamonlog('debug','len data: '.strlen($data));
        //echo "Read Attribute command data: ".$data."\n";

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
    }

    function processCmd($Command) {

        $this->deamonlog("debug", "    L1 - processCmd(".json_encode($Command).")", $this->debug['processCmd']);

        if (!isset($Command)) {
            $this->deamonlog('debug', "  Command not set return", $this->debug['processCmd']);
            return;
        }

        if (isset($Command['priority'])) {
            if ( isset($Command['address']) ) {
                if ($NE = Abeille::byLogicalId($Command['dest'].'/'.$Command['address'], 'Abeille')) {
                    if ($NE->getIsEnable()) {
                        if (( time() - strtotime($NE->getStatus('lastCommunication')) ) > (60*$NE->getTimeout() ) ) {
                            $this->deamonlog('debug', "  NE en Time Out alors je mets la priorite au minimum.");
                            $priority = priorityLostNE;
                        }
                        else {
                            $priority = $Command['priority'];
                        }
                    }
                    else {
                        $this->deamonlog('debug', "  NE desactive, je ne fais rien.");
                        return;
                    }
                }
                else {
                    $this->deamonlog('debug', "  NE introuvable, probablement une annonce, j envoie la commande.");
                    $priority = $Command['priority'];
                }
            }
            else {
                $priority = $Command['priority'];
            }
        }
        else {
            $this->deamonlog('debug', "  priority not defined !!!");
            $priority = priorityInterrogation;
        }

        $dest = $Command['dest'];



        //---- PDM ------------------------------------------------------------------

        if (isset($Command['PDM']) && $Command['PDM'] == "PDM") {

            if (isset($Command['req']) && $Command['req'] == "E_SL_MSG_PDM_HOST_AVAILABLE_RESPONSE") {
                $cmd = "8300";

                $PDM_E_STATUS_OK = "00";
                $data = $PDM_E_STATUS_OK;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }

            if (isset($Command['req']) && $Command['req'] == "E_SL_MSG_PDM_EXISTENCE_RESPONSE") {
                $cmd = "8208";

                $recordId = $Command['recordId'];

                $recordExist = "00";

                if ($recordExist == "00" ) {
                    $size = '0000';
                    $persistedData = "";
                }

                $data = $recordId . $recordExist . $size;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }
        }

        //----------------------------------------------------------------------

        // En gros 0 normal, 1 RAW mode, 2 Mode hybride
        if (isset($Command['setModeHybride'])) {
            if ($Command['setModeHybride'] == "normal") {
                $this->deamonlog('debug',"  Set Mode Hybride", $this->debug['processCmd']);
                $this->sendCmd($priority, $dest,"0002","0001","00");
            } elseif ($Command['setModeHybride'] == "RAW") {
                $this->deamonlog('debug',"  Set Mode Hybride", $this->debug['processCmd']);
                $this->sendCmd($priority, $dest,"0002","0001","01");
            } elseif ($Command['setModeHybride'] == "hybride") {
                $this->deamonlog('debug',"  Set Mode Hybride", $this->debug['processCmd']);
                $this->sendCmd($priority, $dest,"0002","0001","02");
            }
        }

        if (isset($Command['getVersion']) && $Command['getVersion'] == "Version") {
            $this->deamonlog('debug', "  Get Version", $this->debug['processCmd']);
            $this->sendCmd($priority, $dest,"0010","0000","");
        }

        if (isset($Command['reset']) && $Command['reset'] == "reset") {
            //    16:56:56.300 -> 01 02 10 11 02 10 02 10 11 03
            // 01 start
            // 02 10 11: 00 11: Reset
            // 02 10 02 10 : 00 00: Length
            // 11: crc
            // 03: Stop
            $this->sendCmd($priority,$dest,"0011","0000","");
        }

        if (isset($Command['ErasePersistentData']) && $Command['ErasePersistentData'] == "ErasePersistentData") {
            $this->sendCmd($priority,$dest,"0012","0000","");
        }

        // Resets (“Factory New”) the Control Bridge but persists the frame counters.
        if (isset($Command['FactoryNewReset']) && $Command['FactoryNewReset'] == "FactoryNewReset") {
            $this->sendCmd($priority,$dest,"0013","0000","");
        }

        // abeilleList abeilleListAll
        if (isset($Command['abeilleList']) && $Command['abeilleList'] == "abeilleListAll") {
            $this->deamonlog('debug', "  Get Abeilles List");
            //echo "Get Abeilles List\n";
            $this->sendCmd($priority,$dest,"0015","0000","");
        }
        //----------------------------------------------------------------------
        // Set Time server (v3.0f)
        if (isset($Command['setTimeServer'])) {
            if (!isset($Command['time']) ) {
                $Command['time'] = time();
            }
            $this->deamonlog('debug', "  setTimeServer");
            $cmd = "0016";
            $data = sprintf("%08s",dechex($Command['time']));

            $lenth = sprintf("%04s", dechex(strlen( $data )/2));
            $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
        }

        if (isset($Command['getTimeServer'])) {
            $this->deamonlog('debug', "  getTimeServer");
            $cmd = "0017";
            $data = "";

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
        }
        //----------------------------------------------------------------------
        if (isset($Command['setOnZigateLed'])) {
            $this->deamonlog('debug', "  setOnZigateLed");
            $cmd = "0018";
            $data = "01";

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
        }

        if (isset($Command['setOffZigateLed'])) {
            $this->deamonlog('debug', "  setOffZigateLed");
            $cmd = "0018";
            $data = "00";

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
        }
        //----------------------------------------------------------------------
        if (isset($Command['setCertificationCE'])) {
            $this->deamonlog('debug', "  setCertificationCE");
            $cmd = "0019";
            $data = "01";

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
        }

        if (isset($Command['setCertificationFCC'])) {
            $this->deamonlog('debug', "  setCertificationFCC");
            $cmd = "0019";
            $data = "02";

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
        }
        //----------------------------------------------------------------------
        // https://github.com/fairecasoimeme/ZiGate/issues/145
        // PHY_PIB_TX_POWER_DEF (default - 0x80)
        // PHY_PIB_TX_POWER_MIN (minimum - 0)
        // PHY_PIB_TX_POWER_MAX (maximum - 0xbf)
        if (isset($Command['TxPower'])  ) {
            $this->deamonlog('debug', "  TxPower");
            $cmd = "0806";
            $data = $Command['TxPower'];
            if ( $data < 10 ) $data = '0'.$data;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
        }
        //----------------------------------------------------------------------
            // https://github.com/fairecasoimeme/ZiGate/issues/145
            // Added cmd 0807 Get Tx Power #175
            // PHY_PIB_TX_POWER_DEF (default - 0x80)
            // PHY_PIB_TX_POWER_MIN (minimum - 0)
            // PHY_PIB_TX_POWER_MAX (maximum - 0xbf)
            if (isset($Command['GetTxPower'])) {
                $this->deamonlog('debug', "  GetTxPower");
                $cmd = "0807";
                $data = "";

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
            }
        //----------------------------------------------------------------------
        if (isset($Command['setChannelMask'])) {
            $this->deamonlog('debug', "  setChannelMask");
            $cmd = "0021";
            $data = $Command['setChannelMask'];

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
        }
        //----------------------------------------------------------------------
        if (isset($Command['setExtendedPANID'])) {
            $this->deamonlog('debug', "  setExtendedPANID");
            $cmd = "0020";
            $data = $Command['setExtendedPANID'];

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            $this->sendCmd($priority,$dest,$cmd,$lenth,$data);
        }
        //----------------------------------------------------------------------
        if (isset($Command["startNetwork"]) && $Command['startNetwork']=="StartNetwork") {
            $this->sendCmd($priority,$dest,"0024","0000","");
        }

        if (isset($Command["getNetworkStatus"]) && $Command['getNetworkStatus']=="getNetworkStatus") {
            $this->sendCmd($priority,$dest,"0009","0000","");
        }

        if (isset($Command['SetPermit'])) {
            if ($Command['SetPermit'] == "Inclusion") {
                $cmd = "0049";
                $lenth = "0004";
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
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data); //1E = 30 secondes

                // $CommandAdditionelle['permitJoin'] = "permitJoin";
                // $CommandAdditionelle['permitJoin'] = "Status";
                // processCmd( $dest, $CommandAdditionelle,$_requestedlevel );
                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "Cmd".$dest."/Ruche/permitJoin", "Status" );
            } elseif ($Command['SetPermit'] == "InclusionStop") {
                $cmd = "0049";
                $lenth = "0004";
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
                $this->sendCmd($priority,$dest,$cmd,$lenth,$data); //1E = 30 secondes

                // $CommandAdditionelle['permitJoin'] = "permitJoin";
                // $CommandAdditionelle['permitJoin'] = "Status";
                // processCmd( $dest, $CommandAdditionelle,$_requestedlevel );
                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "Cmd".$dest."/Ruche/permitJoin", "Status" );
            }
        }

        if (isset($Command['permitJoin']) && $Command['permitJoin']=="Status") {
            // “Permit join” status on the target
            // Msg Type =  0x0014

            $cmd = "0014";
            $lenth = "0000";
            $data = "";

            $this->sendCmd($priority,$dest,$cmd,$lenth,$data); //1E = 30 secondes
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

            $data = $shortAddress . $channelMask . $scanDuration . $scanCount . $networkUpdateId .$networkManagerShortAddress;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $shortAddress);
        }

        //----------------------------------------------------------------------
        // 2.4.3.3.3   Mgmt_Rtg_req
        //
        if (isset($Command['Mgmt_Rtg_req']) && isset($Command['address'])) {
            $this->deamonlog('debug', "  command Mgmt_Rtg_req");
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
            //

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

            $data2 = $SQN . $startIndex;
            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));
            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength );
            $this->deamonlog('debug', "  Data2: ".$SQN."-".$startIndex );

            $data = $data1 . $data2;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        //----------------------------------------------------------------------
        // Bind
        // Title => 000B57fffe3025ad (IEEE de l ampoule)
        // message => reportToAddress=00158D0001B22E24&ClusterId=0006
        if ( isset($Command['bind']) )
        {
            $this->deamonlog('debug', "  command bind");
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
            if ( isset($Command['targetEndpoint']) ) {
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
            // $lenth = "000F";
            //  16 + 2 + 4 + 2 + 16 + 2 = 42/2 => 21 => 15
            $lenth = "0015";

            $data =  $targetExtendedAddress . $targetEndpoint . $clusterID . $destinationAddressMode . $destinationAddress . $destinationEndpoint;

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
        }

        // Bind Short
        // Title => 000B57fffe3025ad (IEEE de l ampoule) <= to be reviewed
        // message => reportToAddress=00158D0001B22E24&ClusterId=0006 <= to be reviewed
        if ( isset($Command['bindShort']) )
        {
            $this->deamonlog('debug', "  command bind short");
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

            $addressMode = "02";
            $targetShortAddress = $Command['address'];
            $sourceEndpointBind = "00";
            $destinationEndpointBind = "00";
            $profileIDBind = "0000";
            $clusterIDBind = "0021";
            $securityMode = "02";
            $radius = "30";
            $dataLength = "16";

            $dummy = "00";  // I don't know why I need this but if I don't put it then I'm missing some data: C'est ls SQN que je met à 00 car de toute facon je ne sais pas comment le calculer.

            // $targetExtendedAddress = "1d1369feff9ffd90";
            $targetExtendedAddress = reverse_hex($Command['targetExtendedAddress']);
            // $targetEndpoint = "01";
            $targetEndpoint = $Command['targetEndpoint'];
            // $clusterID = "0600";  // 0006 but need to be inverted
            $clusterID = reverse_hex($Command['clusterID']);
            $destinationAddressMode = "03";
            // $destinationAddressMode = $Command['destinationAddressMode'];
            // $destinationAddress = "221b9a01008d1500";
            $destinationAddress = reverse_hex($Command['destinationAddress']);
            // $destinationEndpoint = "01";
            $destinationEndpoint = $Command['destinationEndpoint'];

            // $targetExtendedAddress  = "000B57fffe3025ad";
            // $targetExtendedAddress  = $Command['address'];
            //
            //// if ( isset($Command['targetEndpoint']) ) {
            ////    $targetEndpoint         = $Command['targetEndpoint'];
            ////}
            ////else {
            ////    $targetEndpoint         = "01";
            ////}

            // $clusterID              = "0006";
            // // $clusterIDBind             = $Command['ClusterId'];
            // $destinationAddressMode = "02";
            // // $destinationAddressMode = "03";

            // $destinationAddress     = "0000";
            // $destinationAddress     = "00158D0001B22E24";
            // // $destinationAddress     = $Command['reportToAddress'];

            ////$destinationEndpoint    = "01";

            $lenth = "0022";

            // $data =  $targetExtendedAddress . $targetEndpoint . $clusterID . $destinationAddressMode . $destinationAddress . $destinationEndpoint;
            // $data1 = $addressMode . $targetShortAddress . $sourceEndpointBind . $destinationEndpointBind . $profileIDBind . $clusterIDBind . $securityMode . $radius . $dataLength;
            $data1 = $addressMode . $targetShortAddress . $sourceEndpointBind . $destinationEndpointBind . $clusterIDBind . $profileIDBind . $securityMode . $radius . $dataLength;
            $data2 = $dummy . $targetExtendedAddress . $targetEndpoint . $clusterID  . $destinationAddressMode . $destinationAddress . $destinationEndpoint;

            $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpointBind."-".$destinationEndpointBind."-".$clusterIDBind."-".$profileIDBind."-".$securityMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2) );
            $this->deamonlog('debug', "  Data2: ".$dummy."-".$targetExtendedAddress."-".$targetEndpoint."-".$clusterID."-".$destinationAddressMode."-".$destinationAddress."-".$destinationEndpoint." len: ".(strlen($data2)/2) );

            $data = $data1 . $data2;
            // $this->deamonlog('debug', "Data: ".$data." len: ".(strlen($data)/2) );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        // setReport
        // Title => setReport
        // message => address=d45e&ClusterId=0006&AttributeId=0000&AttributeType=10
        if ( isset($Command['setReport']) )
        {
            $this->deamonlog('debug', "  command setReport");
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
            if ( isset( $Command['sourceEndpoint'] ) ) { $sourceEndpoint = $Command['sourceEndpoint']; } else { $sourceEndpoint = "01"; }
            if ( isset( $Command['targetEndpoint'] ) ) { $targetEndpoint = $Command['targetEndpoint']; } else { $targetEndpoint = "01"; }
            $ClusterId              = $Command['ClusterId'];
            $direction              = "00";                     // 
            $manufacturerSpecific   = "00";                     // 
            $manufacturerId         = "0000";                   // 
            $numberOfAttributes     = "01";                     // One element at a time

            if ( isset( $Command['AttributeDirection'] ) ) { $AttributeDirection = $Command['AttributeDirection']; } else { $AttributeDirection = "00"; } // See if below                    
            
            $AttributeId            = $Command['AttributeId'];    // "0000"

            if ( $AttributeDirection == "00" ) { 
                if ( isset($Command['AttributeType']) ) {$AttributeType = $Command['AttributeType']; } 
                else { 
                    $this->deamonlog('error', "set Report with an AttributeType not defines for equipment: ". $targetShortAddress . " attribut: " . $AttributeId . " can t process" );
                    return;
                }
                if ( isset($Command['MinInterval']) )   { $MinInterval  = $Command['MinInterval']; } else { $MinInterval    = "0000"; }
                if ( isset($Command['MaxInterval']) )   { $MaxInterval  = $Command['MaxInterval']; } else { $MaxInterval    = "0000"; }
                if ( isset($Command['Change']) )        { $Change       = $Command['Change']; }      else { $Change         = "00"; }
                $Timeout = "ABCD"; 
            }
            else if ( $AttributeDirection == "01" ) { 
                $AttributeType          = "12";     // Put crappy info to see if they are passed to zigbee by zigate but it's not.
                $MinInterval            = "3456";   // Need it to respect cmd 0120 format.
                $MaxInterval            = "7890";
                $Change                 = "12";
                if ( isset($Command['Timeout']) )   { $Timeout      = $Command['Timeout']; }     else { $Timeout        = "0000"; }
            }
            else {
                $this->deamonlog('error', "set Report with an AttributeDirection (".$AttributeDirection.") not valid for equipment: ". $targetShortAddress . " attribut: " . $AttributeId . " can t process");
                return;
            }
            
            $data =  $addressMode . $targetShortAddress . $sourceEndpoint . $targetEndpoint . $ClusterId . $direction . $manufacturerSpecific . $manufacturerId . $numberOfAttributes . $AttributeDirection . $AttributeType . $AttributeId . $MinInterval . $MaxInterval . $Timeout . $Change ;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->deamonlog('debug', "Data: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$targetEndpoint."-".$ClusterId."-".$direction."-".$manufacturerSpecific."-".$manufacturerId."-".$numberOfAttributes."-".$AttributeDirection."-".$AttributeType."-".$AttributeId."-".$MinInterval."-".$MaxInterval."-".$Timeout."-".$Change);

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        // setReportRaw
        // Title => setReportRaw
        // message => address=d45e&ClusterId=0006&AttributeId=0000&AttributeType=10
        // For the time being hard coded to run tests but should replace setReport due to a bug on Timeout of command 0120. See my notes.
        if ( isset($Command['setReportRaw']) ) {
            $this->deamonlog('debug', " command setReportRaw");

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

            $data2 = $zclControlField . $transactionSequence . $cmdId . $directionReport . $attribut . $attributeType . $MinInterval . $MaxInterval . $change;

            $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2) );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) );
            $this->deamonlog('debug', "  Data2: ".$zclControlField."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) );

            $data = $data1 . $data2;
            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            // $this->deamonlog('debug', "  Data: ".$data." len: ".$lenth );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        // CmdAbeille/Ruche/commissioningGroupAPS -> address=a048&groupId=AA00
        // Commission group for Ikea Telecommande On/Off still interrupteur
        if ( isset($Command['commissioningGroupAPS']) )
        {
            $this->deamonlog('debug', " commissioningGroupAPS");

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
            $groupId                = reverse_hex($Command['groupId']);
            $groupType              = "00";

            $data2 = $zclControlField . $transactionSequence . $cmdId . $total . $startIndex . $count . $groupId . $groupType;

            $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2) );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) );
            $this->deamonlog('debug', "  Data2: ".$zclControlField."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) );

            $data = $data1 . $data2;
            // $this->deamonlog('debug', "  Data: ".$data." len: ".sprintf("%04s",dechex(strlen( $data )/2)) );

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        // CmdAbeille/Ruche/commissioningGroupAPSLegrand -> address=a048&groupId=AA00
        // Commission group for Legrand Telecommande On/Off still interrupteur Issue #1290
        if ( isset($Command['commissioningGroupAPSLegrand']) )
        {
            $this->deamonlog('debug', "  commissioningGroupAPSLegrand");

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
            $Manufacturer           = reverse_hex("1021");
            $transactionSequence    = "01";
            $cmdId                  = "08";
            $groupId                = reverse_hex($Command['groupId']);

            $data2 = $zclControlField . $Manufacturer . $transactionSequence . $cmdId . $groupId ;

            $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2) );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) );
            $this->deamonlog('debug', "  Data2: ".$zclControlField."-".$Manufacturer."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) );

            $data = $data1 . $data2;
            // $this->deamonlog('debug',"Data: ".$data." len: ".sprintf("%04s",dechex(strlen( $data )/2)) );

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data );
        }

        if ( isset($Command['getGroupMembership']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) )
        {
            $cmd = "0062";

            // <address mode: uint8_t>
            // <target short address: uint16_t>
            // <source endpoint: uint8_t>
            // <destination endpoint: uint8_t>
            // <group count: uint8_t>
            // <group list:data>

            $addressMode = "02";                                    // Short Address -> 2
            $address = $Command['address'];                         // -> 4
            $sourceEndpoint = "01";                                 // -> 2
            $destinationEndpoint = $Command['DestinationEndPoint']; // -> 2
            $groupCount = "00";                                     // -> 2
            $groupList = "";                                        // ? Not mentionned in the ZWGUI -> 0
            //  2 + 4 + 2 + 2 + 2 + 0 = 12/2 => 6
            $lenth = "0006";

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupCount . $groupList ;

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['viewScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) )
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

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['storeScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) )
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

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['recallScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) )
        {
            $cmd = "00A5";

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

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['sceneGroupRecall']) && isset($Command['groupID']) && isset($Command['sceneID']) )
        {
            $cmd = "00A5";

            // <address mode: uint8_t>
            // <target short address: uint16_t>
            // <source endpoint: uint8_t>
            // <destination endpoint: uint8_t>
            // <group ID: uint16_t>
            // <scene ID: uint8_t>

            $addressMode = "01";                                    // Group Address -> 1, Short Address -> 2
            $address = $Command['groupID'];                         // -> 4
            $sourceEndpoint = "01";                                 // -> 2
            $destinationEndpoint = "02"; // -> 2

            $groupID = $Command['groupID'];
            $sceneID = $Command['sceneID'];

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['addScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) && isset($Command['sceneName']) )
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

            $sceneNameLength        = sprintf("%02s", (strlen( $Command['sceneName'] )/2) );      // $Command['sceneNameLength'];
            $sceneNameMaxLength     = sprintf("%02s", (strlen( $Command['sceneName'] )/2) );      // $Command['sceneNameMaxLength'];
            $sceneNameData          = $Command['sceneName'];

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID . $transitionTime . $sceneNameLength . $sceneNameMaxLength . $sceneNameData ;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['getSceneMembership']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) )
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

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID ;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['removeScene']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) && isset($Command['sceneID']) )
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

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID . $sceneID ;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['removeSceneAll']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) )
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

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupID ;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['sceneLeftIkea']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupID']) )
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

            $data2 = $FrameControlField . $manu . $SQN . $cmdIkea . $cmdIkeaParams;
            $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));

            $data1 = $addressMode . $targetShortAddress . $sourceEndpointBind . $destinationEndpointBind . $clusterIDBind . $profileIDBind . $securityMode . $radius . $dataLength;

            $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpointBind."-".$destinationEndpointBind."-".$clusterIDBind."-".$profileIDBind."-".$securityMode."-".$radius."-".$dataLength." len: ".(dechex(strlen($data1)/2)) );
            $this->deamonlog('debug', "  Data2: ".$dummy."-".$targetExtendedAddress."-".$targetEndpoint."-".$clusterID."-".$destinationAddressMode."-".$destinationAddress."-".$destinationEndpoint." len: ".(dechex(strlen($data2)/2)) );

            $data = $data1 . $data2;
            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            // $this->deamonlog('debug', "Data: ".$data." len: ".(dechex(strlen($data)/2)) );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        // https://zigate.fr/documentation/commandes-zigate/ Windows covering (v3.0f only)
        if ( isset($Command['WindowsCovering']) && isset($Command['address']) && isset($Command['clusterCommand']) )
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
            $srcEP          = "01";
            $detEP          = "01";
            $clusterCommand = $Command['clusterCommand'];

            $data = $addressMode . $address . $srcEP . $detEP . $clusterCommand;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            // $this->deamonlog('debug', "Data: ".$data." len: ".(dechex(strlen($data)/2)) );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        // https://zigate.fr/documentation/commandes-zigate/ Windows covering (v3.0f only)
        if ( isset($Command['WindowsCoveringGroup']) && isset($Command['address']) && isset($Command['clusterCommand']) )
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
            $srcEP          = "01";
            $detEP          = "01";
            $clusterCommand = $Command['clusterCommand'];

            $data = $addressMode . $address . $srcEP . $detEP . $clusterCommand;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            // $this->deamonlog('debug', "Data: ".$data." len: ".(dechex(strlen($data)/2)) );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['ActiveEndPoint']) )
        {
            $cmd = "0045";

            // <target short address: uint16_t>

            $address = $Command['address']; // -> 4

            //  4 = 4/2 => 2
            $lenth = "0002";

            $data = $address;

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['SimpleDescriptorRequest']) )
        {
            $cmd = "0043";

            // <target short address: uint16_t>
            // <endpoint: uint8_t>

            $address = $Command['address']; // -> 4
            $endpoint = $Command['endPoint']; // -> 2

            //  4 + 2 = 6/2 => 3
            $lenth = "0003";

            $data = $address . $endpoint ;

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
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

            $data = $address . $IeeeAddress . $requestType . $startIndex ;
            $lenth = "000C"; // A verifier

            $this->deamonlog('debug', '  Network_Address_request: '.$data . ' - ' . $lenth  );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['IEEE_Address_request']) )
        {
            // IEEE Address request
            $cmd = "0041";

            // <target short address: uint16_t> -> 4
            // <short address: uint16_t>        -> 4
            // <request type: uint8_t>          -> 2
            // <start index: uint8_t>           -> 2
            // Request Type: 0 = Single 1 = Extended

            $address = $Command['address'];
            $shortAddress = $Command['shortAddress'];
            $requestType = "01";
            $startIndex = "00";

            $data = $address . $shortAddress . $requestType . $startIndex ;
            // $lenth = strlen($data)/2;
            $lenth = "0006";

            $this->deamonlog('debug', '  IEEE_Address_request: '.$data . ' - ' . $lenth  );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['Management_LQI_request']) )
        {
            $cmd = "004E";

            // <target short address: uint16_t>
            // <Start Index: uint8_t>

            $address = $Command['address'];     // -> 4
            $startIndex = $Command['StartIndex']; // -> 2

            //  4 + 2 = 6/2 => 3
            $lenth = "0003";

            $data = $address . $startIndex ;

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        if ( isset($Command['identifySend']) && isset($Command['address']) && isset($Command['duration']) && isset($Command['DestinationEndPoint']) )
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
            $lenth = "0007";
            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $time ;

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        // Don't know how to make it works
        if ( isset($Command['touchLinkFactoryResetTarget']) )
        {
            if ($Command['touchLinkFactoryResetTarget']=="DO")
            {
                $this->sendCmd($priority,$priority,$dest,"00D2","0000");
            }
        }

        // setLevel on one object
        if (isset($Command['setLevel']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['destinationEndpoint']) && isset($Command['Level']) && isset($Command['duration'])) {
            $cmd = "0081";
            // 11:53:06.479 -> 01 02 10 81 02 10 02 19 C6 02 12 83 DF 02 11 02 11 02 11 AA 02 10 BB 03
            //                 01 02 10 81 02 10 02 19 d7 02 12 83 df 02 11 02 11 02 11 02 11 02 11 03
            //
            // 02 83 DF 01 01 01 ff 00 BB
            //
            // 01: Start
            // 02 10 81: Msg Type: 00 81 -> Move To Level
            // 02 10 02 19: Lenght
            // C6: CRC
            // 02 12: <address mode: uint8_t> : 02
            // 83 DF: <target short address: uint16_t> 83 DF (Lampe Z Ikea)
            // 02 11: <source endpoint: uint8_t>: 01
            // 02 11: <destination endpoint: uint8_t>: 01
            // 02 11: <onoff : uint8_t>: 01
            // AA: <Level: uint8_t > AA Value I put to identify easely: Level to reach
            // 02 10 BB: <Transition Time: uint16_t>: 00BB Value I put to identify easely: Transition duration
            $addressMode = $Command['addressMode'];
            $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = $Command['destinationEndpoint'];
            $onoff = "01";
            if ($Command['Level'] < 16) {
                $level = "0".dechex($Command['Level']);
                // $this->deamonlog('debug',"setLevel: ".$Command['Level']."-".$level);
            } else {
                $level = dechex($Command['Level']);
                // $this->deamonlog('debug', "setLevel: ".$Command['Level']."-".$level);
            }

            // $duration = "00" . $Command['duration'];
            if ( $Command['duration'] < 16) {
                $duration = "0".dechex($Command['duration']); // echo "duration: ".$Command['duration']."-".$duration."-\n";
            } else {
                $duration = dechex($Command['duration']); // echo "duration: ".$Command['duration']."-".$duration."-\n";
            }
            $duration = "00" . $duration;

            // 11:53:06.543 <- 01 80 00 00 04 53 00 56 00 81 03
            // 11:53:06.645 <- 01 81 01 00 06 DD 56 01 00 08 04 00 03
            // 8 16 8 8 8 8 16
            // 2  4 2 2 2 2  4 = 18/2d => 9d => 0x09
            $lenth = "0009";

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $onoff . $level . $duration ;
            // echo "data: " . $data . "\n";

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);

            $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
            $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+3), "EP=".$destinationEndpoint."&clusterId=0008&attributeId=0000" );

            $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2+$Command['duration']), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
            $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+3+$Command['duration']), "EP=".$destinationEndpoint."&clusterId=0008&attributeId=0000" );
        }

        if ( isset($Command['moveToLiftAndTiltBSO']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['destinationEndpoint']) && isset($Command['inclinaison']) && isset($Command['duration']) )
        {
            $this->deamonlog('debug', "  command moveToLiftAndTiltBSO");

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

            $data2 = $zclControlField . $ManfufacturerCode . $transactionSequence . $cmdId . $option . $Lift . $Tilt . $transitionTime ;

            $dataLength = sprintf( "%02s",dechex(strlen( $data2 )/2) );

            $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;

            $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpoint."-".$destinationEndpoint."-".$clusterID."-".$profileID."-".$securityMode."-".$radius."-".$dataLength." len: ".sprintf("%04s",dechex(strlen( $data1 )/2)) );
            $this->deamonlog('debug', "  Data2: ".$zclControlField."-".$ManfufacturerCode."-".$targetExtendedAddress." len: ".sprintf("%04s",dechex(strlen( $data2 )/2)) );

            $data = $data1 . $data2;
            // $this->deamonlog('debug', "Data: ".$data." len: ".sprintf("%04s",dechex(strlen( $data )/2)) );

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        // setLevelStop
        if ( isset($Command['setLevelStop']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['sourceEndpoint']) && isset($Command['destinationEndpoint']) )
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

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint ;

            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        // WriteAttributeRequest ------------------------------------------------------------------------------------
        if ( (isset($Command['WriteAttributeRequest'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']) )
        {
            $this->setParam3( $dest, $Command );
        }

        // WriteAttributeRequest ------------------------------------------------------------------------------------
        if ( (isset($Command['WriteAttributeRequestGeneric'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['attributeType']) && isset($Command['value']) )
        {
            $this->setParamGeneric( $dest, $Command );
        }

        // WriteAttributeRequestVibration ------------------------------------------------------------------------------------
        if ( (isset($Command['WriteAttributeRequestVibration'])) && (isset($Command['address'])) && isset($Command['Proprio']) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']) )
        {
            $this->setParamXiaomi( $dest, $Command );
        }

        // WriteAttributeRequestVibration ------------------------------------------------------------------------------------
        if ( (isset($Command['WriteAttributeRequestActivateDimmer'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['value']) )
        {
            $this->setParam4( $dest, $Command );
        }

        // ReadAttributeRequest ------------------------------------------------------------------------------------
        // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004

        if ( (isset($Command['ReadAttributeRequest'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['EP']) && isset($Command['Proprio']) )
        {
            $this->getParam( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], $Command['EP'], $Command['Proprio'] );
        }
        else
        {
            if ( (isset($Command['ReadAttributeRequest'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) && isset($Command['EP']) )
            {
                $this->getParam( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], $Command['EP'], "0000" );
            }
        }

        // ReadAttributeRequestMulti ------------------------------------------------------------------------------------
        if ( (isset($Command['ReadAttributeRequestMulti'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) )
        {
            $this->getParamMulti( $Command );
        }

        // ReadAttributeRequest ------------------------------------------------------------------------------------
        // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
        if ( (isset($Command['ReadAttributeRequestHue'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) )
        {
            // echo "ReadAttributeRequest pour address: " . $Command['address'] . "\n";
            // if ( $Command['ReadAttributeRequest']==1 )
            //{
            // $this->getParamHue( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "0B" );
            $this->getParam( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "0B", "0000" );
            //}
        }

        // ReadAttributeRequest ------------------------------------------------------------------------------------
        // http://zigate/zigate/sendCmd.php?address=83DF&ReadAttributeRequest=1&clusterId=0000&attributeId=0004
        if ( (isset($Command['ReadAttributeRequestOSRAM'])) && (isset($Command['address'])) && isset($Command['clusterId']) && isset($Command['attributeId']) )
        {
            // echo "ReadAttributeRequest pour address: " . $Command['address'] . "\n";
            // if ( $Command['ReadAttributeRequest']==1 )
            //{
            // getParamOSRAM( $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "01" );
            $this->getParam( $priority, $dest, $Command['address'], $Command['clusterId'], $Command['attributeId'], "03", "0000" );
            //}
        }

        if ( isset($Command['writeAttributeRequestIAS_WD']) ) {
            // Parameters: EP=#EP&mode=Flash&duration=#slider#

                $this->deamonlog('debug', "  command writeAttributeRequestIAS_WD");
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
                if ( $Command['mode'] == "Flash" )      $warningMode = "04";        // 14, 24, 34: semble faire le meme son meme si la doc indique: Burglar, Fire, Emergency / 04: que le flash
                if ( $Command['mode'] == "Sound" )      $warningMode = "10";
                if ( $Command['mode'] == "FlashSound" ) $warningMode = "14";
                $warningDuration = "000A"; // en seconde
                if ( $Command['duration'] > 0 )         $warningDuration = sprintf("%04s", dechex($Command['duration']) );
                // $strobeDutyCycle = "01";
                // $strobeLevel = "F0";

                $data = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $direction . $manufacturerSpecific . $manufacturerId . $warningMode . $warningDuration; // . $strobeDutyCycle . $strobeLevel;

                $lenth = sprintf("%04s",dechex(strlen( $data )/2));

                $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        if ( isset($Command['addGroup']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupAddress']) )
        {
            $this->deamonlog('debug', "  Add a group to a device");
            //echo "Add a group to an IKEA bulb\n";

            // 15:24:36.029 -> 01 02 10 60 02 10 02 19 6D 02 12 83 DF 02 11 02 11 C2 98 02 10 02 10 03
            // 15:24:36.087 <- 01 80 00 00 04 54 00 B0 00 60 03
            // 15:24:36.164 <- 01 80 60 00 07 08 B0 01 00 04 00 C2 98 03
            // Add Group
            // Message Description
            // Msg Type = 0x0060 Command ID = 0x00
            $cmd = "0060";
            $lenth = "0007";
            // <address mode: uint8_t>
            //<target short address: uint16_t>
            //<source endpoint: uint8_t>
            //<destination endpoint: uint8_t>
            //<group address: uint16_t>
            $addressMode = "02";
            $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = $Command['DestinationEndPoint'];
            $groupAddress = $Command['groupAddress'];

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupAddress ;
            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        // Add Group APS
        // Title => 000B57fffe3025ad (IEEE de l ampoule) <= to be reviewed
        // message => reportToAddress=00158D0001B22E24&ClusterId=0006 <= to be reviewed

        if ( isset($Command['addGroupAPS'])  )
        {
            $this->deamonlog('debug', "  command add group with APS");
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

            $lenth = "0011";

            // $data =  $targetExtendedAddress . $targetEndpoint . $clusterID . $destinationAddressMode . $destinationAddress . $destinationEndpoint;
            // $data1 = $addressMode . $targetShortAddress . $sourceEndpointBind . $destinationEndpointBind . $profileIDBind . $clusterIDBind . $securityMode . $radius . $dataLength;
            $data1 = $addressMode . $targetShortAddress . $sourceEndpointBind . $destinationEndpointBind . $clusterIDBind . $profileIDBind . $securityMode . $radius . $dataLength;
            $data2 = $dummy . $dummy1 . $cmdAddGroup . $groupId . $length;

            $this->deamonlog('debug', "  Data1: ".$addressMode."-".$targetShortAddress."-".$sourceEndpointBind."-".$destinationEndpointBind."-".$clusterIDBind."-".$profileIDBind."-".$securityMode."-".$radius."-".$dataLength." len: ".(strlen($data1)/2) );
            $this->deamonlog('debug', "  Data2: ".$dummy . $dummy1 . $cmdAddGroup . $groupId . $length." len: ".(strlen($data2)/2) );

            $data = $data1 . $data2;
            // $this->deamonlog('debug', "Data: ".$data." len: ".(strlen($data)/2) );

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);
        }

        if ( isset($Command['removeGroup']) && isset($Command['address']) && isset($Command['DestinationEndPoint']) && isset($Command['groupAddress']) )
        {
            $this->deamonlog('debug', "  Remove a group to a device");
            //echo "Remove a group to an IKEA bulb\n";

            // 15:24:36.029 -> 01 02 10 60 02 10 02 19 6D 02 12 83 DF 02 11 02 11 C2 98 02 10 02 10 03
            // 15:24:36.087 <- 01 80 00 00 04 54 00 B0 00 60 03
            // 15:24:36.164 <- 01 80 60 00 07 08 B0 01 00 04 00 C2 98 03
            // Add Group
            // Message Description
            // Msg Type = 0x0060 Command ID = 0x00
            $cmd = "0063";
            $lenth = "0007";
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

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $groupAddress ;
            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        // Replace Equipement
        if ( isset($Command['replaceEquipement']) && isset($Command['old']) && isset($Command['new']) )
        {
            $this->deamonlog('debug', "  Replace an Equipment");

            $old = $Command['old'];
            $new = $Command['new'];

            $this->deamonlog('debug',"  Update eqLogic table for new object");
            $sql =          "UPDATE `eqLogic` SET ";
            $sql = $sql .   "name = 'Abeille-".$new."-New' , logicalId = '".$new."', configuration = replace(configuration, '".$old."', '".$new."' ) ";
            $sql = $sql .   "WHERE  eqType_name = 'Abeille' AND logicalId = '".$old."' AND configuration LIKE '%".$old."%'";
            $this->deamonlog('debug',"sql: ".$sql);
            DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

            $this->deamonlog('debug',"  Update cmd table for new object");
            $sql =          "UPDATE `cmd` SET ";
            $sql = $sql .   "configuration = replace(configuration, '".$old."', '".$new."' ) ";
            $sql = $sql .   "WHERE  eqType = 'Abeille' AND configuration LIKE '%".$old."%' ";
            $this->deamonlog('debug',"  sql: ".$sql);
            DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
        }

        //
        if ( isset($Command['UpGroup']) && isset($Command['address']) && isset($Command['step']) )
        {
            $this->deamonlog('debug','  UpOnOffGroup for: '.$Command['address']);
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
            $lenth = "000A";
            if ( isset ( $Command['addressMode'] ) ) { $addressMode = $Command['addressMode']; } else { $addressMode = "02"; }

            $address = $Command['address'];
            $sourceEndpoint = "01";
            if ( isset ( $Command['destinationEndpoint'] ) ) { $destinationEndpoint = $Command['destinationEndpoint'];} else { $destinationEndpoint = "01"; };
            $onoff = "00";
            $stepMode = "00"; // 00 : Up, 01 : Down
            $stepSize = $Command['step'];
            $TransitionTime = "0005"; // 1/10s of a s

            $this->sendCmd($priority, $dest, $cmd, $lenth, $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
        }

        if ( isset($Command['DownGroup']) && isset($Command['address']) && isset($Command['step']) )
        {
            $this->deamonlog('debug','  UpOnOffGroup for: '.$Command['address']);
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
            $lenth = "000A";
            if ( isset ( $Command['addressMode'] ) ) { $addressMode = $Command['addressMode']; } else { $addressMode = "02"; }

            $address = $Command['address'];
            $sourceEndpoint = "01";
            if ( isset ( $Command['destinationEndpoint'] ) ) { $destinationEndpoint = $Command['destinationEndpoint'];} else { $destinationEndpoint = "01"; };
            $onoff = "00";
            $stepMode = "01"; // 00 : Up, 01 : Down
            $stepSize = $Command['step'];
            $TransitionTime = "0005"; // 1/10s of a s

            $this->sendCmd($priority, $dest, $cmd, $lenth, $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$onoff.$stepMode.$stepSize.$TransitionTime, $address);
        }

        // ON / OFF with no effects
        if ( isset($Command['onoff']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']) )
        {
            $this->deamonlog('debug','  OnOff for: '.$Command['address'].' action (0:Off, 1:On, 2:Toggle): '.$Command['action'], $this->debug['processCmd']);
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
            $lenth                  = "0006";
            $addressMode            = $Command['addressMode'];
            $address                = $Command['address'];
            $sourceEndpoint         = "01";
            $destinationEndpoint    = $Command['destinationEndpoint'];
            $action                 = $Command['action'];

            $this->sendCmd($priority, $dest, $cmd, $lenth, $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$action, $address);

            if ( $addressMode != "01" ) {
                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+3), "EP=".$destinationEndpoint."&clusterId=0008&attributeId=0000" );
                }
        }

        // ON / OFF with no effects RAW with no APS ACK
        if ( isset($Command['onoffraw']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']) )
        {

        $this->deamonlog('debug', "command setParam4");

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
        if ( $Command['destinationEndpoint']>1 ) { $destinationEndpoint = $Command['destinationEndpoint']; } else { $destinationEndpoint = "01"; } // $destinationEndPoint; // "01";

        $profileID          = "0104";
        $clusterID          = "0006"; // $Command['clusterId'];

        $securityMode       = "02"; // ???
        $radius             = "30";
        // $dataLength <- calculated later

        $frameControl               = "11"; // Ici dans cette commande c est ZCL qu'on control
        $transqactionSequenceNumber = "1A"; // to be reviewed
        $commandWriteAttribute      = $Command['action'];

        $data2 = $frameControl . $transqactionSequenceNumber . $commandWriteAttribute;
        $dataLength = sprintf("%02s",dechex(strlen( $data2 )/2));
        $data1 = $addressMode . $targetShortAddress . $sourceEndpoint . $destinationEndpoint . $clusterID . $profileID . $securityMode . $radius . $dataLength;
        $data = $data1 . $data2;

        $lenth = sprintf("%04s",dechex(strlen( $data )/2));

        // $this->deamonlog('debug', "data: ".$data );
        // $this->deamonlog('debug', "lenth data: ".$lenth );

        $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $targetShortAddress);

        if ( $addressMode != "01" ) {
            $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$targetShortAddress."/ReadAttributeRequestMulti&time=".(time()+2), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
        }
    }

        // On / Off Timed Send
        if ( isset($Command['OnOffTimed']) && isset($Command['addressMode']) && isset($Command['address']) && isset($Command['destinationEndpoint']) && isset($Command['action']) && isset($Command['onTime']) && isset($Command['offWaitTime']) )
        {
            $this->deamonlog('debug','  OnOff for: '.$Command['address'].' action (0:Off, 1:On, 2:Toggle): '.$Command['action'].' - '.$Command['onTime'].' - '.$Command['ffWaitTime'], $this->debug['processCmd']);
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
            // $lenth = "0006";
            $addressMode = $Command['addressMode'];
            $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = $Command['destinationEndpoint'];
            $action = $Command['action'];
            $onTime = $Command['onTime'];
            $offWaitTime = $Command['offWaitTime'];

            $data = $addressMode.$address.$sourceEndpoint.$destinationEndpoint.$action.$onTime.$offWaitTime;
            $lenth = sprintf("%04s",dechex(strlen( $data )/2));
            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);

            $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
            $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+3), "EP=".$destinationEndpoint."&clusterId=0008&attributeId=0000" );

            $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+$Command['onTime']), "EP=".$destinationEndpoint."&clusterId=0006&attributeId=0000" );
            $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+$Command['onTime']), "EP=".$destinationEndpoint."&clusterId=0008&attributeId=0000" );
        }

        // Move to Colour
        if ( isset($Command['setColour']) && isset($Command['address']) && isset($Command['addressMode']) && isset($Command['X']) && isset($Command['Y'])  && isset($Command['destinationEndPoint']) )
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
            $lenth = "000B";

            $addressMode            = $Command['addressMode'];
            $address                = $Command['address'];
            $sourceEndpoint         = "01";
            $destinationEndpoint    = $Command['destinationEndPoint'];
            $colourX                = $Command['X'];
            $colourY                = $Command['Y'];
            $duration               = "0001";

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $colourX . $colourY . $duration ;

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        // Take RGB (0-255) convert to X, Y and send the color
        if ( isset($Command['setColourRGB']) )
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

            if ( $Rsrgb <= 0.04045 ) { $Rlin = $Rsrgb/12.92; } else { $Rlin = pow( ($Rsrgb+$a)/(1+$a), 2.4); }
            if ( $Gsrgb <= 0.04045 ) { $Glin = $Gsrgb/12.92; } else { $Glin = pow( ($Gsrgb+$a)/(1+$a), 2.4); }
            if ( $Bsrgb <= 0.04045 ) { $Blin = $Bsrgb/12.92; } else { $Blin = pow( ($Bsrgb+$a)/(1+$a), 2.4); }

            $X = 0.4124 * $Rlin + 0.3576 * $Glin + 0.1805 *$Blin;
            $Y = 0.2126 * $Rlin + 0.7152 * $Glin + 0.0722 *$Blin;
            $Z = 0.0193 * $Rlin + 0.1192 * $Glin + 0.9505 *$Blin;

            if ( ($X + $Y + $Z)!=0 ) {
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
            $lenth = "000B";

            $addressMode = "02";
            $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = $Command['destinationEndPoint'];
            $colourX = str_pad( dechex($x), 4, "0", STR_PAD_LEFT);
            $colourY = str_pad( dechex($y), 4, "0", STR_PAD_LEFT);
            $duration = "0001";

            $this->deamonlog( 'debug', "  colourX: ".$colourX." colourY: ".$colourY );

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $colourX . $colourY . $duration ;

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        // Move to Colour Temperature
        if ( isset($Command['setTemperature']) && isset($Command['address']) && isset($Command['temperature']) && isset($Command['destinationEndPoint']) )
        {
            // <address mode: uint8_t>              2
            // <target short address: uint16_t>     4
            // <source endpoint: uint8_t>           2
            // <destination endpoint: uint8_t>      2
            // <colour temperature: uint16_t>       4
            // <transition time: uint16_t>          4

            $cmd = "00C0";
            // 2+4+2+2+4+4 = 18 /2 = 9 => 0x09
            $lenth = "0009";

            $addressMode = $Command['addressMode'];
            $address = $Command['address'];
            $sourceEndpoint = "01";
            $destinationEndpoint = $Command['destinationEndPoint'];
            $temperature = $Command['temperature'];
            $duration = "0001";

            $data = $addressMode . $address . $sourceEndpoint . $destinationEndpoint . $temperature . $duration ;

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);

            if ( $addressMode != "01" ) {
                $this->publishMosquitto( queueKeyCmdToCmd, priorityInterrogation, "TempoCmd".$dest."/".$address."/ReadAttributeRequest&time=".(time()+2), "EP=".$destinationEndpoint."&clusterId=0300&attributeId=0007" );
            }
        }

        if ( isset($Command['getManufacturerName']) && isset($Command['address']) )
        {
            // $this->deamonlog('debug','  Get Manufacturer from: '.$Command['address']);
            //echo "Get Manufacturer from: ".$Command['address']."\n";
            if ( $Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
            $this->getParam( $priority, $dest, $Command['address'], "0000", "0004", $Command['destinationEndPoint'], "0000" );
        }
        
        if ( isset($Command['getName']) && isset($Command['address']) )
        {
            // $this->deamonlog('debug','  Get Name from: '.$Command['address']);
            //echo "Get Name from: ".$Command['address']."\n";
            if ( $Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
            $this->getParam( $priority, $dest, $Command['address'], "0000", "0005", $Command['destinationEndPoint'], "0000" );
        }

        if ( isset($Command['getLocation']) && isset($Command['address']) )
        {
            //echo "Get Name from: ".$Command['address']."\n";
            if ( $Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }
            // $this->deamonlog('debug','  Get Location from: '.$Command['address'].'->'.$Command['destinationEndPoint'].'<-');
            $this->getParam( $priority, $dest, $Command['address'], "0000", "0010", $Command['destinationEndPoint'], "0000" );
        }

        if ( isset($Command['setLocation']) && isset($Command['address']) )
        {
            // $this->deamonlog('debug','  Set Location of: '.$Command['address']);
            if ( $Command['location'] == "" ) { $Command['location'] = "Not Def"; }
            if ( $Command['destinationEndPoint'] == "" ) { $Command['destinationEndPoint'] = "01"; }

            $this->setParam2( $dest, $Command['address'], "0000", "0010",$Command['destinationEndPoint'],$Command['location'], "42" );
        }

        if ( isset($Command['MgtLeave']) && isset($Command['address']) && isset($Command['IEEE']) )
        {
            // $this->deamonlog('debug',' Leave for: '.$Command['address']." - ".$Command['IEEE']);
            $cmd = "0047";
            //$lenth = "";

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
            $Rejoin         = "00";
            $RemoveChildren = "01";

            $data = $address . $IEEE . $Rejoin . $RemoveChildren;
            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data, $address);
        }

        // if ( isset($Command['Remove']) && isset($Command['address']) && isset($Command['IEEE']) )
        // https://github.com/KiwiHC16/Abeille/issues/332
        if ( isset($Command['Remove']) && isset($Command['IEEE']) )
        {
            // $this->deamonlog('debug','Remove for: '.$Command['address']." - ".$Command['IEEE']);
            $this->deamonlog('debug', '  Remove for: '.$Command['IEEE']);
            $cmd = "0026";

            // Doc is probably not up to date, need to provide IEEE twice
            // Tested and works in case of a NE in direct to coordinator
            // To be tested if message is routed.
            // <target short address: uint16_t>
            // <extended address: uint64_t>

            // $address        = $Command['address'];
            $address        = $Command['IEEE'];
            $IEEE           = $Command['IEEE'];

            $data = $address . $IEEE ;
            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data);
        }

        if ( isset($Command['commandLegrand']) )
        {
            $this->commandLegrand($dest,$Command);
        }

        // DiscoverAttributesCommand
        if ( isset($Command['DiscoverAttributesCommand']) && isset($Command['address']) && isset($Command['startAttributeId']) && isset($Command['maxAttributeId']) )
        {
            $this->deamonlog('debug','DiscoverAttributesCommand for: '.$Command['address']." - ".$Command['startAttributeId']." - ".$Command['maxAttributeId']);
            $cmd = "0140";

            // <address mode: uint8_t>
            // <target short address: uint16_t>
            // <source endpoint: uint8_t>	
            // <destination endpoint: uint8_t>	
            // <Cluster id: uint16_t>	
            // <Attribute id : uint16_t>	
            // <direction: uint8_t>	
            // <manufacturer specific: uint8_t>	
            // <manufacturer id: uint16_t>	
            // <Max number of identifiers: uint8_t>	
            // Direction:	
            // 0 – from server to client	
            // 1 – from client to server	
            // Manufacturer specific :	
            // 1 – Yes	
            // 0 – No

            $addressMode    = "02";
            $address        = $Command['address'];
            $srcEP          = "01";
            $dstEP          = $Command['EP'];
            $clusterId      = $Command['clusterId'];
            $attributeId    = $Command['startAttributeId'];
            $direction      = "00"; //	0 – from server to client	1 – from client to server
            $manuSpec       = "00"; //  1 – Yes	 0 – No
            $manuId         = "0000";
            $maxAttributeId = $Command['maxAttributeId'];

            $data = $addressMode . $address . $srcEP . $dstEP . $clusterId . $attributeId . $direction . $manuSpec . $manuId . $maxAttributeId ;
            $lenth = sprintf("%04s",dechex(strlen( $data )/2));

            $this->sendCmd($priority, $dest, $cmd, $lenth, $data);
        }

        if ( isset($Command['commandLegrand']) )
        {
            $this->commandLegrand($dest,$Command);
        }

    }
}


?>
