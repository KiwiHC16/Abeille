<?php

    /* This file is part of Jeedom.
     *
     * Jeedom is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * Jeedom is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
     */

    include_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/includes/config.php';
    include_once dirname(__FILE__).'/../../resources/AbeilleDeamon/lib/Tools.php';


    /*
     [2020-03-17 16:13:31][DEBUG] : execute ->action<- function with options ->{"title":"aaaa","message":"","utid":"1584457453959"}<-
     [2020-03-17 16:13:31][DEBUG] : topic: CmdAbeille1/Ruche/IEEE_Address_request
     [2020-03-17 16:13:31][DEBUG] : request: address=#title#&#message#
     [2020-03-17 16:13:31][DEBUG] : topic: CmdAbeille1/Ruche/IEEE_Address_request request: address=aaaa&
     [2020-03-17 16:13:31][DEBUG] : (All) Msg sent: {"message":{"topic":"CmdAbeille1\/Ruche\/IEEE_Address_request","payload":"address=aaaa&"}}
     
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] Message pulled from queue queueKeyAbeilleToCmd: CmdAbeille1/Ruche/IEEE_Address_request -> address=2655&shortAddress=2655
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] ----------
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] procmsg fct - message: {"topic":"CmdAbeille1\/Ruche\/IEEE_Address_request","payload":"address=2655&shortAddress=2655","priority":1}
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] procmsg fct - Msg Received: Topic: {CmdAbeille1/Ruche/IEEE_Address_request} => address=2655&shortAddress=2655
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] procmsg fct - Type: CmdAbeille1 Address: Ruche avec Action: IEEE_Address_request
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] procmsg fct - Pour La Ruche
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] processCmd fct - begin processCmd function
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] processCmd fct - begin processCmd function, Command: {"IEEE_Address_request":"IEEE_Address_request","priority":1,"dest":"Abeille1","address":"2655","shortAddress":"2655"}
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] IEEE_Address_request: 265526550100 - 0006
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] sendCmd fct - dest: "Abeille1" cmd: "0041"
     [2020-03-17 16:21:32][AbeilleCmd][DEBUG.KIWI] sendCmd fct - Je mets la commande dans la queue: 1 - Nb Cmd:1 -> [{"received":1584458492.0989,"time":0,"retry":3,"priority":1,"dest":"Abeille1","cmd":"0041","len":"0006","datas":"265526550100"}]
     */
    
    $timeOut = 60; // min
    $timeEnd = time() + $timeOut*60;

    $dest = $argv[1];
    $addressShort = $argv[2];
    
    while ( time() < $timeEnd ) {
        Abeille::publishMosquitto( queueKeyAbeilleToCmd, priorityInterrogation, "Cmd".$dest."/Ruche/IEEE_Address_request", "address=".$addressShort."&shortAddress=".$addressShort );
        echo ".";
        sleep( 5 );
    }

?>
