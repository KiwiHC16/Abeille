<!-- This file displays advanced equipment/zigbee infos.
     Included by 'AbeilleEq-Main.php' -->

<hr>

<?php
    if ($dbgDeveloperMode) echo __FILE__;
?>

<div class="form-group">
    <label class="col-sm-3 control-label">{{Icone}}</label>
    <div class="col-sm-3">
        <select id="sel_icon" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="ab::icon">
            <!-- <option value="Abeille">{{Abeille}}</option> -->
            <option value="Ruche">{{Ruche}}</option>
            <?php
                $items = AbeilleTools::getImagesList();
                foreach ($items as $icon) {
                    echo "<option value=\"".$icon."\">{{".$icon."}}</option>";
                }

                // require_once __DIR__.'/../../core/class/AbeilleTools.class.php';
                // $items = AbeilleTools::getDeviceNameFromJson();

                // $selectBox = array();
                // foreach ($items as $item) {
                //     $device = AbeilleTools::getDeviceModel($item, 'Abeille', 2);
                //     if (!isset($device['configuration'])) {
                //         log::add('Abeille', 'debug', 'WARNING: No configuration section in '.$item);
                //         continue; // No 'configuration' in this JSON, so no icon defined
                //     }
                //     if (!isset($device['configuration']['icon'])) {
                //         log::add('Abeille', 'debug', 'WARNING: No icon field in '.$item);
                //         continue; // No icon defined
                //     }
                //     if (!isset($device['type'])) {
                //         log::add('Abeille', 'debug', 'WARNING: No type field in '.$item);
                //         continue; // No type defined
                //     }
                //     $icon = $device['configuration']['icon'];
                //     $name = $device['type'];
                //     $selectBox[ucwords($name)] = $icon;
                // }
                // ksort($selectBox);
                // foreach ($selectBox as $key => $value) {
                //     echo "<option value=\"".$value."\">{{".$key."}}</option>";
                // }
            ?>
        </select>
    </div>
</div>

<style>
    .widthSet {
        max-width: 160px;
        width:auto;
    }
</style>

<div class="form-group">
    <div class="col-sm-3">
    </div>
    <div class="col-sm-3" style="text-align: center">
        <!-- <img name="icon_visu" src="" width="160" height="200"/> -->
        <img name="icon_visu" class="widthSet">
    </div>
</div>