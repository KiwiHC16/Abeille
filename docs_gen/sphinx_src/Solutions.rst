################################
Solutions Multiples et Distantes
################################

Depuis decembre 2019, Abeille supporte le MultiZigate.
L'idée est que vous pouvez connecter plusieurs ZiGate sur une même Abeille sur un unique Jeedom.
Bien sûr vous pouvez toujours utiliser le plugin JeeLink qui est très performant. Mais si comme moi vous avez un gros système avec de multiple zigate, la solution JeeLink devient lourde à gérer quand vous avez de nobreux changements.

Et comme dit Soyann::

  Les raisons peuvent être multiples: deux zones à couvrir sans routeurs entre les deux,
  trop d’équipements pour une seule passerelle, différents équipements qui dialoguent mal
  ensemble (ex: prises Osram avec capteurs Xiaomi)… ou alors (comme dans mon cas),
  faire de la sécurité: j’ai chez moi une deuxième Zigate à l’opposé de la maison pour que
  mes capteurs de portes se connectent directement dessus: si je passe par des routeurs et
  qu’un ‘visiteur’ coupe le jus de la maison, autant ma box et mon Jeedom sont sur
  du courant ondulé, autant mes ampoules sont directement sur du 220V, donc mes abeilles
  se connectant dessus ne peuvent plus remonter d’informations)…
  C’est donc une excellent nouvelle, car ça veut dire qu’à terme, je vais pouvoir me
  passer de la VM sur ma Delta juste pour ça (avec Jeedom Link), par contre, ça veut
  dire que je vais devoir investir dans un module Wifi

Perso mon systeme tourne sur un serveur dans une VM, les connections physiques (port USB n'existent pas). Il existe la solution Wifi qui est très flexible mais peut dans certaines conditions ne pas être très stable. Mais j'ai des RPI cablés en ethernet dans la maison alors pourquoi ne pas les utiliser !!!

L'idée est de connecter une ZiGate USB sur un port USB d'un RPI connecté en ethernet. En gros la conf est ::

  ZiGate USB <-> Port USB <-> RPI <-> ser2net <-> ethernet <-> socat <-> Abeille <-> Jeedom

Pour ce faire dans la conf abeille il est proposé les ports monitZigate de 1 à 5.

Pour mettre en place la configuration:

Sur le RPI accueillant la Zigate:

Installation de ser2net::

  apt-get install ser2net

Dans le fichier /etc/ser2net.conf mettre à la fin::

  3336:raw:0:/dev/ttyUSB5:115200 8DATABITS NONE 1STOPBIT

Evidement choisir le bon port serie.

Après pour les plus fous vous pouvez comme moi déporter un dongle Zwave, RFXCom et SMS

Pour un dongle Zwave::

  3333:raw:0:/dev/ttyACM0:115200 8DATABITS NONE 1STOPBIT

Pour un dongle RFXCom::

  3334:raw:0:/dev/ttyUSB0:38400 8DATABITS NONE 1STOPBIT

Pour un dongle SMS::

  3335:raw:0:/dev/ttyUSB1:115200 8DATABITS NONE 1STOPBIT

Puis démarrer le service::

  /etc/init.d/ser2net start


Maintenant sur le jeedom, j'utilise monit pour maintenir la connection. Ici je ne couvre que le cas Zigate mais vous pouvez faire de meme pour le dongle Zwave, SMS et RFXCom::

  apt-get install monit

Editez le fichier de conf de monit en ouvrant le serveur monit et en ajoutant la connection Zigate::

  vi /etc/monit/monitrc

  set httpd port 2812
  allow myuser:mypassword

  check program monit_devZigate with path /root/monit_zigate_status.sh
    start program = "/root/monit_zigate_start.sh"
    stop program = "/root/monit_zigate_stop.sh"
    if status != 0 then alert


Ensuite il faut créer 4 fichiers:

fichier /root/monit_zigate_status.sh::

  #!/bin/sh
  ps -ef | grep "/usr/bin/socat pty,raw,echo=0,waitslave,link=/dev/monitZigate1" | grep -v grep
  exit $?

fichier /root/monit_zigate_start.sh::

  #!/bin/sh
  /usr/bin/nohup /root/monit_zigate_process.sh &
  exit $?

fichier /root/monit_zigate_stop.sh::

  #!/bin/sh
  kill `ps -ef | grep "/usr/bin/socat pty,raw,echo=0,waitslave,link=/dev/monitZigate1" | grep -v grep | awk '{ print $2 }'`
  exit $?

fichier /root/monit_zigate_process.sh::

  #!/bin/sh
  while true
    do
      /usr/bin/nohup /usr/bin/socat pty,raw,echo=0,waitslave,link=/dev/monitZigate1  tcp:_RPI_IP_:3336
      sleep 5
      /bin/chmod 777 /dev/monitZigate1
    done
  exit $?

Ensuite dans la configuration Abeille il faut choisir le port Monit (de 1 à 5) que vous avez defini ci dessus. par ex: Monit1 pour /dev/monitZigate1, etc...
