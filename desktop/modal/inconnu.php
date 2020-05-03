<!--Liste les informations definis comme inconnues por le demon Abeille.-->
<!--exemple objets inconnus, commandes inconnus,......-->
<!--Pour essayer de compléter les modèles, EventConfig-->

<!DOCTYPE html>
<html>
<body>

<h1>{{Inconnu ...}}</h1>




<p>{{Ci-dessous la liste des informations inconnues remontant du reseau zigbee et de ce fait non gérées par Abeille.}}</p>
<p>{{Collecter ces informations permettrait d'améliorer le code d'Abeille.}}</p>
<p>-------------------------------------</p>
<?php
$cmd = "grep -e 'Objet existe mais pas la commande' /var/www/html/log/Abeille | grep -v 'ff01' ";

exec($cmd, $output);

if ( count($output) == 0 ) {
  echo "{{Liste vide}}";
  }
else {
  foreach ( $output as $line ) {
    echo $line."<br>\n";
  }
}
?>
<p>-------------------------------------</p>
</body>
</html>
