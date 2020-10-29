#######
Polling
#######

****
Ping
****

Ping toutes les 15 minutes

Par défaut le cron, toutes les 15 minutes, fait un "ping" des équipements qui n'ont pas de batterie définie. On suppose qu'ils sont sur secteur et que donc ils écoutent et qu'ils répondent à la requête.

****
État
****

État toutes les minutes

Récupère les infos que ne remonte pas par défaut toutes les minutes si défini dans l'équipement.

*****
Santé
*****

Santé des équipements

Il y a probablement deux informations qu'il est intéressant de monitorer pour vérifier que tout fonctionne:

* le niveau des batteries
* et le fait que des messages sont échangés.

Je vous propose 2 méthodes.

*********
Heartbeat
*********

Dixit: https://www.jeedom.com/forum/viewtopic.php?p=716483#p718402

Toutes les 5 min, le core va faire les actions suivantes pour tous les plugins:

* Si aucune config (hearbeat vide), ca sera par défaut 0
* Si vide donc ou 0 ou une valeur non numérique, aucun check
* Si aucun équipement actif, aucun check
* ensuite il regarde si un équipement a eu un "changement" (en vérifiant que la "lastcommunication" est supérieur à l'heure actuelle moins le temps définit par le hearbeat)
* s'il ne trouve pas d'équipement répondant à cette condition, il poste un message (dans le centre de notification)
* s'il la case "relancer le démon" est coché, le démon est relancé

Dans notre cas, les devices ont des timeout de l ordre de l heure, donc descendre en dessous n'est pas une bonne idée à moins de redescendre le timeout du la ruche.
Je vais le mettre à 2h (120min) avec restart sur mon système de prod.
