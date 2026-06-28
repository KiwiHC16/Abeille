<?php
    require_once __DIR__.'/../../../../core/php/core.inc.php';

    // Build map of IEEE → Jeedom ID for active equipment
    $activeIeees = [];
    foreach (eqLogic::byType('Abeille') as $eq) {
        $ieee = strtoupper($eq->getConfiguration('IEEE', ''));
        if ($ieee !== '') $activeIeees[$ieee] = $eq->getId();
    }

    // Fetch all IEEE history entries from config table
    $allEntries = config::searchKey('zigbee:abeille:', 'Abeille');

    $rows = [];
    foreach ($allEntries as $entry) {
        $ieee = strtoupper(substr($entry['key'], strlen('zigbee:abeille:')));
        $history = is_array($entry['value']) ? $entry['value'] : [];
        if (empty($history['entries'])) continue;

        $last = end($history['entries']);
        $rows[] = [
            'ieee'     => $ieee,
            'history'  => $history['entries'],
            'last'     => $last,
            'deleted'  => isset($last['deleted']) && $last['deleted'],
            'isActive' => isset($activeIeees[$ieee]),
        ];
    }

    // Sort by object then name (case-insensitive)
    usort($rows, function($a, $b) {
        $cmp = strcasecmp($a['last']['object'], $b['last']['object']);
        return $cmp !== 0 ? $cmp : strcasecmp($a['last']['name'], $b['last']['name']);
    });

    // Colors matching the update page (Bootstrap label-success / label-info)
    define('COLOR_GREEN',      '#5cb85c');
    define('COLOR_GREEN_BG',   'rgba(92, 184, 92, 0.13)');
    define('COLOR_GREEN_TEXT', '#3c763d');
    define('COLOR_BLUE',       '#5bc0de');
    define('COLOR_BLUE_BG',    'rgba(91, 192, 222, 0.13)');
    define('COLOR_BLUE_TEXT',  '#31708f');
?>
<div style="padding:10px;">
    <div style="margin-bottom:10px; display:flex; gap:8px; align-items:center;">
        <input type="text" id="ieeeHistoryFilter" class="form-control"
               placeholder="{{Filtrer par IEEE, nom ou objet...}}"
               style="max-width:400px;">
        <span class="text-muted" id="ieeeHistoryCount">
            <?= count($rows) ?> {{adresse(s) IEEE}}
        </span>
    </div>

    <table class="table table-condensed table-bordered tablesorter" id="table_ieeeHistory" style="font-size:0.9em;">
        <thead>
            <tr style="background:#f5f5f5;">
                <th style="width:165px;">{{IEEE}}</th>
                <th>{{Objet}}</th>
                <th>{{Nom}}</th>
                <th style="width:130px;">{{Logical ID}}</th>
                <th style="width:140px;">{{Dernière modif.}}</th>
                <th style="width:90px; text-align:center;">{{Historique}}</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($rows as $row):
    $last      = $row['last'];
    $ieee      = $row['ieee'];
    $uid       = 'ih_' . md5($ieee);
    $deleted   = $row['deleted'];
    $isActive  = $row['isActive'];
    $eqId      = $isActive ? $activeIeees[$ieee] : null;
    $nbEntries = count($row['history']);

    // IEEE code cell: green = currently active in Jeedom, blue = deleted/inactive
    $codeColor  = $isActive ? COLOR_GREEN      : COLOR_BLUE;
    $codeBg     = $isActive ? COLOR_GREEN_BG   : COLOR_BLUE_BG;
    $codeText   = $isActive ? COLOR_GREEN_TEXT : COLOR_BLUE_TEXT;
    $codeStyle  = "font-size:0.85em; background:{$codeBg}; color:{$codeText}; border:1px solid {$codeColor}; border-radius:3px; padding:1px 4px;";
?>
            <tr class="ieee-row"
                data-ieee="<?= htmlspecialchars(strtolower($ieee)) ?>"
                data-name="<?= htmlspecialchars(strtolower($last['name'])) ?>"
                data-object="<?= htmlspecialchars(strtolower($last['object'])) ?>">
                <td><code style="<?= $codeStyle ?>"><?= htmlspecialchars($ieee) ?></code></td>
                <td><?= htmlspecialchars($last['object']) ?></td>
                <td>
                    <?php if ($eqId): ?>
                    <a href="index.php?v=d&m=Abeille&p=Abeille&id=<?= $eqId ?>"><?= htmlspecialchars($last['name']) ?></a>
                    <?php else: ?>
                    <?= htmlspecialchars($last['name']) ?>
                    <?php endif; ?>
                    <?php if ($deleted): ?>
                    <span class="label label-danger" style="margin-left:4px;">{{supprimé}}</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($last['logical_id']) ?></td>
                <td style="white-space:nowrap;"><?= htmlspecialchars(substr($last['date'], 0, 16)) ?></td>
                <td style="text-align:center;">
                    <?php if ($nbEntries > 1): ?>
                    <button class="btn btn-xs btn-default btn-toggle-history"
                            data-target="<?= $uid ?>">
                        <i class="fa fa-history"></i> <?= $nbEntries ?>
                    </button>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
<?php if ($nbEntries > 1): ?>
            <tr class="history-detail tablesorter-ignoreRow" id="<?= $uid ?>" style="display:none;">
                <td colspan="6" style="background:#fafafa; padding:6px 20px;">
                    <table class="table table-condensed" style="margin:0; font-size:0.85em;">
                        <thead>
                            <tr>
                                <th>{{Date}}</th>
                                <th>{{Objet}}</th>
                                <th>{{Nom}}</th>
                                <th>{{Logical ID}}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach (array_reverse($row['history']) as $idx => $e):
    // First entry after reverse = most recent = current → green; others → blue
    $trBg = ($idx === 0) ? COLOR_GREEN_BG : COLOR_BLUE_BG;
?>
                            <tr style="background:<?= $trBg ?>;">
                                <td style="white-space:nowrap;"><?= htmlspecialchars(substr($e['date'], 0, 16)) ?></td>
                                <td><?= htmlspecialchars($e['object']) ?></td>
                                <td><?= htmlspecialchars($e['name']) ?></td>
                                <td><?= htmlspecialchars($e['logical_id']) ?></td>
                                <td>
<?php if (isset($e['deleted']) && $e['deleted']): ?>
                                    <span class="label label-danger">{{supprimé}}</span>
<?php elseif (isset($e['backfill']) && $e['backfill']): ?>
                                    <span class="label label-info">{{initial}}</span>
<?php endif; ?>
                                </td>
                            </tr>
<?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
<?php endif; ?>
<?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function() {
    // Init tablesorter — sort by Objet (col 1) then Nom (col 2) by default, last column not sortable
    $("#table_ieeeHistory").tablesorter({
        sortList: [[1, 0], [2, 0]],
        headers: { 5: { sorter: false } }
    });

    function triggerTablesorterUpdate() {
        $("#table_ieeeHistory").trigger('update');
    }

    // Toggle history detail rows
    // Move the detail row right after its parent before showing (tablesorter reorders the DOM)
    $(document).on('click', '.btn-toggle-history', function() {
        var $btn    = $(this);
        var $row    = $btn.closest('tr');
        var $detail = $('#' + $btn.data('target'));

        if ($detail.is(':visible')) {
            $detail.hide();
        } else {
            $row.after($detail); // reattach after current sorted position
            $detail.show();
        }
        $btn.find('i').toggleClass('fa-history fa-chevron-up');
    });

    // Live filter on IEEE / name / object
    $('#ieeeHistoryFilter').on('input', function() {
        var filter = $(this).val().toLowerCase().trim();
        var visible = 0;
        $('#table_ieeeHistory tbody tr.ieee-row').each(function() {
            var ieee   = $(this).data('ieee')   || '';
            var name   = $(this).data('name')   || '';
            var object = $(this).data('object') || '';
            var show = !filter
                || ieee.indexOf(filter) >= 0
                || name.indexOf(filter) >= 0
                || object.indexOf(filter) >= 0;
            $(this).toggle(show);
            if (!show) {
                var targetId = $(this).find('.btn-toggle-history').data('target');
                if (targetId) $('#' + targetId).hide();
            }
            if (show) visible++;
        });
        $('#ieeeHistoryCount').text(visible + ' {{adresse(s) IEEE}}');
        triggerTablesorterUpdate();
    });

    setTimeout(function() { $('#ieeeHistoryFilter').focus(); }, 200);
})();
</script>
