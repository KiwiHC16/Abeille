
<legend><i class="fa fa-cogs"></i> {{Remplacement d equipements}}</legend>
<label>Remplacement d equipement</label> <a class="btn btn-primary btn-xs" target="_blank" href="http://kiwihc16.free.fr/"><i class="fas fa-book"></i>Documentation</a></br>

<form action="/plugins/Abeille/desktop/php/AbeilleFormAction.php" method="post">
    Ghost / Equipement Cassé:
    <select name="ghost" >
    <?php
        foreach (Abeille::byType('Abeille',1) as $eq) {
            echo '<option value="' . $eq->getId() . '">' . $eq->getObject()->getName() . ' -> ' . $eq->getName() . '</option>';
        }
    ?>
    </select>
    Remplacé par:
    <select name="real" >
    <?php
        foreach (Abeille::byType('Abeille',1) as $eq) {
            echo '<option value="' . $eq->getId() . '">' . $eq->getObject()->getName() . ' -> ' . $eq->getName() . '</option>';
        }
    ?>
    </select>
    <input type="submit" name="submitButton" value="Remplace">
</form>

</br>
</br>
