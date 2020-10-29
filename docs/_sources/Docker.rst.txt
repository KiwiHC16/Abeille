######
Docker
######

******
VM OSX
******

Une VM sous parallel OSX: debian 9.3.0 / Jeedom 3.1.7 / Abeille 2018-06-19 01:01:07

VM
==
Configuration: choisissez un réseau ponté pour avoir un IP à vous.

Debian
======

Installation de Debian des plus classique (Un gros 1/4 d'heure depuis un ISO sur disque).

Pas d'environnement de bureau, juste un serveur ssh et les utilitaires usuels système.

Une mise a jour en fin d'installation:

.. code-block:: php

   su -
   vi /etc/apt/sources.list
   deb cdrom:[Debian GNU/Linux 9.3.0 _Stretch_ - Official amd64 DVD Binary-1 20171209-12:11]/ stretch contrib main
   apt-get update
   apt-get upgrade


Jeedom
======

La documentation Jeedom est à la page https://Jeedom.github.io/documentation/installation/fr_FR/index

Perso j'utilise le dernier chapitre (Chapitre 10 - Autres) (Un gros 1/4 d'heure)

Connectez-vous en SSH à votre système et faites :

.. code-block:: php

   su -
   wget https://raw.githubusercontent.com/Jeedom/core/stable/install/install.sh
   chmod +x install.sh
   ./install.sh
   ./install.sh -w /var/www/html  -m Jeedom
   reboot


Web Browser
===========

Ouvrir la page de votre Jeedom: http://Mon_IP_Jeedom

admin/admin

Ne plus afficher et cloture fenetre du dessus.

Creation d'un Objet Abeille pour accueillir tous les futures équipement Zigbee:

Menu->Outils->Objets->'+', Sauvegarder et retour sur la page principale(Dashboard)

Ajout Plugin Abeille
====================

Menu->Plugins->Gestion des plugins

Market

Recherche Abeille

Selectionner Abeille

Installer stable (Version 2018-06-19 01:01:07)

Voulez vous aller sur la .... -> Ok


Configuration du plugin
=======================

Activer

Dependances -> Relancer (ou vous attendez et elles devraient s'installer automatiquement).

Deux messages doivent s'afficher pour confirmer le lancement et le lien vers la doc.

Une fois les dépendances installées, la date de derniere installation doit apparaitre.

Configuration:

* Choisissez le port serie (on suppose que vous avez une Zigate ttl sur un port USB déjà branchée, sinon branchez la et rafraichissez la page)
* Choisissez l'Objet Parent: Abeille
* Sauvegarder

Le demon doit démarrer et passer au vert.

Dans mon cas mosquitto fait encore des siennes et il n'a pas démarré. Un reboot du systeme résoud le problème.

Rafraichir la page et vérifier que le demon est passé au vert: Statut Ok et Configuration: Ok.

Et maintenant tout est pret. Retour sur Dashboard. Vous devriez y touver l'équipement Ruche.


Demarrage du reseau zigbee
==========================

Si vous selectionnez "Version" alors les champs 'Last', 'Last Stamps', 'SW', 'SDK' doivent se mettre à jour. Cela confirme que cela fonctionne.

Vous pouvez démarrer le réseau "Start Network".

Et faire un "get Network Status", d'autres champs vont se mettre à jour.

Voilà l'installation d'Abeille dans Jeedom est finie. Vous pouvez intégrer vo équipements.

En tout 1h pour faire une installation from scratch (et écrire cette doc).



Installation dans un conteneur depuis Ubuntu
============================================

Introduction
------------

Debian supporte nativement Jeedom et le support est assuré par l'équipe de développement. Toute autre demande à propos d'une distribution est ignorée.   https://Jeedom.github.io/documentation/installation/fr_FR/index

Docker permet d'installer un système invité minimal dans une partie virtualisée du système hôte, tout ajout/suppression/modification du conteneur laisse tel quel le système hôte. L'interet de docker est que n'est installé que le minimum nécéssaire au fonctionnement dans l'image. ( une image éxecutée est un conteneur.) Le but ici est de faire tourner un conteneur Jeedom sur un système Ubuntu, cependant ce n'est pas limité à ce système.

Prérequis
---------

Avoir docker disponible dans les dépôts de la distribution.


Installation de docker
----------------------

.. code-block:: php

   apt-get install docker docker.io

Fonctionnement
--------------

Un des créateurs de Jeedom maintient image Jeedom. Cette image appelé Jeedom-server utilise une image Jeedom-mysql pour stocker les données dans une base de données mysql. Il faudra donc a chaque fois lancer le conteneur Jeedom-mysql puis le Jeedom-server. Les réglages restent d'une fois sur l'autre.

Récupération des images et Création des conteneurs
--------------------------------------------------

Ces deux lignes vont récupérer les images, créer les conteneurs et les configurer. Le port USB est a adapter selon le besoin ( `ls /dev/ttyUSB*` pour avoir la liste )

.. code-block:: php

   docker run --name Jeedom-mysql -e MYSQL_ROOT_PASSWORD=MJeedom96 -d mysql:latest
   docker run --name Jeedom-server -e ROOT_PASSWORD=MJeedom96 --link Jeedom-mysql:mysql -p 9180:80 -p 9443:443 -p 9022:22 --device=/dev/ttyUSB0 Jeedom/Jeedom

A ce stade, l'installation de Jeedom commence dans le conteneur Jeedom-server. il faut compter 5 a 10 minutes selon la connexion et la puissance du système hôte.

Jeedom sera disponible après quelques instants à l'adresse http://0.0.0.0:9180

.. attention::

   Le ssh est accessible via le port 9022. (root/MJeedom96)

Configuration de Jeedom
-----------------------

Dans les champs indiqués entrer la valeur surlignée.

.. code-block:: php

   [width="40%",frame="topbot",options="header,footer"]
   |==================================
   |Database hostname| Jeedom-mysql
   |Database port    | 3306
   |Database username| root
   |Database password| MJeedom96
   |Database name    | Jeedom
   |Erase database   | checked
   |==================================

Une fois, le texte `[END INSTALL SUCCESS]` affiché en bas. Aller à l 'adresse http://0.0.0.0:9180 la page de login de Jeedom devrait apparaître. Les login et mot de passe sont admin admin.

Start/stop des conteneurs
-------------------------

les conteneurs peuvent être arrêtés et relancés à la demande en gardant l'ordre mysql Jeedom au lancement, Jeedom mysql à  l'arrêt.

Arrêt `docker stop Jeedom-server && docker stop Jeedom-mysql`

Démarrage `docker start Jeedom-mysql && docker start Jeedom-server`

Repartir de zéro
----------------

il est possible de supprimer les conteneurs et de repartir d'un Jeedom tout neuf.

.. code-block:: php

   docker rm Jeedom-server && docker rm Jeedom-mysql

puis aller vers link:[Récupération des images et Création des conteneurs]


Portainer
---------

Pour ceux que ne sont pas à l'aise avec la ligne de commande, portainer propose une interface graphique pour gérer les conteneurs et les images.
C'est un conteneur à démarrage automatique qui pourra relancer les conteneurs crées.

.. code-block:: php

   docker run -d -p 9000:9000 --name portainer --restart always -v /var/run/docker.sock:/var/run/docker.sock portainer/portainer

le site sera disponible à l'adresse http://0.0.0.0:9000




******
Docker
******


Installation d'Abeille dans docker
(Il y a certainement plus simple mais je ne suis pas expert en Docker et cette méthode semble bien fonctionner).

Preparation du docker
=====================

Preparation sous Raspbian
-------------------------

* installer 2018-06-27-raspbian-stretch-lite.zip sur une SD
* demarrer le RPI3
* se logger pi/raspberry (atttention au clavier US par defaut)
* lancer raspi-config (faire la conf que vous souhaitez): sshd, all memory space, clavier, locales,...
* Vérifier la conf réseau
* Vous connecter en ssh pour la suite:


.. code-block:: php

   ssh pi@IP

* La suite se fait entant que root: sudo su -


.. code-block:: php

   sudo su -

* une classique mise a jour du systeme:

.. code-block:: php

   apt-get update, apt-get upgrade

* Restart du RPI

.. code-block:: php

   reboot
   ssh pi@IP
   sudo su -

* Installation de docker:

.. code-block:: php

   apt-get install docker
   apt-get install docker.io

* Vérifier que cela fonctionne, un docker ps -a pour voir les images:

.. code-block:: php

   docker ps -a


On voit ici qu’il n’y a pas d’image, il faut en créer une. Flasher la SD. Demarrer le PI et une commande:

.. code-block:: php

   docker ps


Permet de voir que docker fonctionne.

Preparation sous hypriot
=========================

La version officielle raspbian est un peu vieille et nous n'avons pas toutes les nouveautés. Hypriot a une version bien plus recente et nous facilite la vie (pas de config manuelle tout est prêt). Elle permet aussi de faire tourner le plugin Homebridge (macvlan).
http://blog.hypriot.com

Telecharger leur image à l adresse: http://blog.hypriot.com/downloads/

On voit ici qu'il n'y a pas d'image, il faut en créer une.

Créons un system pour le docker.
--------------------------------

http://www.guoyiang.com/2016/11/04/Build-My-Own-Raspbian-Docker-Image/

Ici je ne cherche pas à faire une image la plus petite possible mais la plus proche possible d'une install classique sur un HW RPI3. De ce fait l'image fait presque 1G.

.. code-block:: php

   mkdir DockerAbeille
   cd DockerAbeille

Recuperer le fichier 2018-06-27-raspbian-stretch-lite.zip par scp par exemple. Puis:

.. code-block:: php

   unzip 2018-06-27-raspbian-stretch-lite.zip
   losetup -Pr /dev/loop0 2018-06-27-raspbian-stretch-lite.img
   mkdir rpi
   mount -o ro /dev/loop0p2 ./rpi
   tar -C ./rpi -czpf 2018-06-27-raspbian-stretch-lite.tar.gz --numeric-owner .
   umount ./rpi
   losetup -d /dev/loop0
   rmdir rpi
   rm 2018-06-27-raspbian-stretch-lite.img
   rm 2018-06-27-raspbian-stretch-lite.zip
   echo 'FROM scratch' > Dockerfile
   echo 'ADD ./2018-06-27-raspbian-stretch-lite.tar.gz /' >> Dockerfile
   echo 'CMD ["/bin/bash"]' >> Dockerfile


Maintenant on lance la creation du docker:

.. code-block:: php

   docker build -t JeedomAbeille .

[TIP]: Bien mettre le . a la fin de la ligne.

Le résultat doit ressembler à:

.. code-block:: php

   root@docker:~/DockerAbeille= docker build -t JeedomAbeille .
   Sending build context to Docker daemon 348.4 MB
   Step 0 : FROM scratch
   --->
   Step 1 : ADD ./2018-06-27-raspbian-stretch-lite.tar.gz /
   ---> f7009768b966
   Removing intermediate container ef5668638536
   Step 2 : CMD /bin/bash
   ---> Running in d95d0e65bbb4
   ---> 286ea5048dfd
   Removing intermediate container d95d0e65bbb4
   Successfully built 286ea5048dfd


Et si vous demandez les images:

.. code-block:: php

   root@docker:~/DockerAbeille= docker images
   REPOSITORY          TAG                 IMAGE ID            CREATED             VIRTUAL SIZE
   JeedomAbeille       latest              286ea5048dfd        12 minutes ago      900.9 MB


Démarrons le container:

.. code-block:: php

   docker run -it JeedomAbeille


Le shell vous donne la main dans le docker:

.. code-block:: php

   root@52b658b7d8f8:/=


Vous pouvez arreter le docker depuis un shell sur le host:

.. code-block:: php

   root@docker:~/DockerAbeille= docker ps
   CONTAINER ID        IMAGE               COMMAND             CREATED             STATUS              PORTS               NAMES
   52b658b7d8f8        JeedomAbeille       "/bin/bash"         3 minutes ago       Up 3 minutes                            sad_stallman
   root@docker:~/DockerAbeille= docker stop 52b658b7d8f8
   52b658b7d8f8


Vous pouvez demarrer de docker depuis un shell sur le host:


.. code-block:: php

   root@docker:~/DockerAbeille= docker ps -a
   CONTAINER ID        IMAGE               COMMAND             CREATED             STATUS                       PORTS               NAMES
   52b658b7d8f8        JeedomAbeille       "/bin/bash"         7 minutes ago       Exited (127) 3 minutes ago                       sad_stallman
   root@docker:~/DockerAbeille= docker start 52b658b7d8f8
   52b658b7d8f8



Vous pouvez vous connecter au docker:

.. code-block:: php

   root@docker:~/DockerAbeille= docker attach 52b658b7d8f8
   root@52b658b7d8f8:/=

[TIP]: Faites plusieurs "enter" pour avoir le prompt.


Maintenant que le docker fonctionne on va faire l installation de Jeedom et Abeille.


[TIP]: To stop a container, use CTRL-c. This key sequence sends SIGKILL to the container. If --sig-proxy is true (the default),CTRL-c sends a SIGINT to the container. You can detach from a container and leave it running using the [underline]#CTRL-p suivi de CTRL-q# key sequence.


Service dans le docker
======================

Les services ne demarrent pas tout seuls dans le docker, il aurait probablement du le faire dans Dockfile.

Donc j'ajoute quelques lignes à /etc/rc.local pour Raspbian:


.. code-block:: php

   docker start JeedomAbeille
   docker exec -u root JeedomAbeille /etc/init.d/ssh start
   docker exec -u root JeedomAbeille /etc/init.d/mysql start
   docker exec -u root JeedomAbeille /etc/init.d/apache2 start
   docker exec -u root JeedomAbeille /etc/init.d/cron start


que je mets sur le host dans /root sous le nom startJeedomAbeileDocker.sh.
et un bon vieux:  chmod u+x startJeedomAbeileDocker.sh

et pour hypriot qui n'a pas de rc.local, je fait un script:


.. code-block:: php

   sudo su -
   cd /etc/init.d
   vi startDockers


Je mets dedans


.. code-block:: php

    =! /bin/sh
    = /etc/init.d/startDockers

    === BEGIN INIT INFO
    == Provides:          startDockers
    == Required-Start:    $remote_fs $syslog
    == Required-Stop:     $remote_fs $syslog
    == Default-Start:     2 3 4 5
    == Default-Stop:      0 1 6
    == Short-Description: Simple script to start a program at boot
    == Description:       A simple script from www.stuffaboutcode.com which will start / stop a program a boot / shutdown.
    === END INIT INFO

    == If you want a command to always run, put it here

    == Carry out specific functions when asked to by the system
    case "$1" in
    start)
    echo "Starting startDockers"
    == run application you want to start
    docker start Jeedomgite
    docker exec -u root Jeedomgite /etc/init.d/ssh start
    docker exec -u root Jeedomgite /etc/init.d/mysql start
    docker exec -u root Jeedomgite /etc/init.d/apache2 start
    docker exec -u root Jeedomgite /etc/init.d/cron start
    ;;
    stop)
    echo "Stopping startDockers"
    = kill application you want to stop
    docker stop Jeedomgite
    ;;
    *)
    echo "Usage: /etc/init.d/startDockers {start|stop}"
    exit 1
    ;;
    esac

    exit 0


Je sauvegarde.

.. code-block:: php

    chmod 755 /etc/init.d/startDockers
    /etc/init.d/startDockers start
    update-rc.d startDockers defaults

Ajouter la ligne

.. code-block:: php

    * * * * * su --shell=/bin/bash - www-data -c '/usr/bin/php /var/www/html/core/php/jeeCron.php' >> /dev/null

dans le cron root.

Thanks to https://www.stuffaboutcode.com/2012/06/raspberry-pi-run-program-at-start-up.html

Installation Jeedom
-------------------

Dans le container precedent nous n'avons pas pris en compte les besoins réseaux et port série.
Effaçons l'ancien container.

.. code-block:: php

  docker rm 52b658b7d8f8


Créons en un nouveau avec les ports mysql, apache, ssh et le port serie ttyUSB0 (la Zigate).

.. code-block:: php

  docker run --name=JeedomAbeille --device=/dev/ttyUSB0 -p 2222:22 -p 80:80 -p 3306:3306 -it JeedomAbeille
  docker run --name=Jeedomgite --device=/dev/ttyACM0 -p 51826:51826 -p 5353:5353 -p 2222:22 -p 80:80 -p 3306:3306 -it Jeedomgite


Si vous êtes sur hyprio et voulez exposer la machine completement, créé le Networks puis le Containers:

.. code-block:: php

    docker network create -d macvlan --subnet=192.168.4.0/24 --gateway=192.168.4.2 -o parent=eth0 pub_net
    docker run --name=Jeedomgite --device=/dev/ttyACM0 --network pub_net --ip=192.168.4.38 --hostname=Jeedomgite -it Jeedomgite /bin/bash


Attention de ne pas vous prendre les pieds dans le tapis entre les adresses du "HW" rpi et les addresses des containers.


Donc Jeedom sera accessible sur le port 80 à l'adresse IP du host. 2222 pour ssh et 3306 pour mysql.
J'ai mis un nom pour être plus sympas à gérer.

Vous pourrez le demarrer/arreter par:

.. code-block:: php

  docker stop JeedomAbeille
  docker start JeedomAbeille

Passons a l installation des services:

.. code-block:: php

  docker attach JeedomAbeille
  apt-get update
  apt-get upgrade
  apt-get install openssh-server
  dpkg-reconfigure openssh-server
  /etc/init.d/ssh start
  apt-get install mariadb-server
  apt-get install apache2

Maintenant le systeme doit être prêt pour l installation de Jeedom lui-meme.
(https://Jeedom.github.io/documentation/installation/fr_FR/index => Chap 10)

.. code-block:: php

  wget https://raw.githubusercontent.com/Jeedom/core/stable/install/install.sh
  chmod +x install.sh
  ./install.sh -w /var/www/html -m Jeedom

L installation va se dérouler en 11 grandes étapes.

.. code-block:: php

  étape 11 vérification de Jeedom réussie
  /!\ IMPORTANT /!\ Le mot de passe root MySQL est Jeedom
  Installation finie. Un redémarrage devrait être effectué

avec un ps -ef, vous devriez voir apache, ssh et mysql fonctionner.

Puis vous vous connecter à Jeedom avec l adresse http://IP_Host:80/
Connectez vous avec admin/admin.
Sauf que cela ne fonctionne pas !! ->Mot de passe ou nom d'utilisateur incorrect<-

Il demande un reboot donc allons y:

.. code-block:: php

  docker stop JeedomAbeille
  docker start JeedomAbeille
  docker attach JeedomAbeille
  /etc/init.d/ssh start
  /etc/init.d/mysql start
  /etc/init.d/apache2 start

On ne peut toujours pas se connecter, je ne sais pas pourquoi....

Donc on va passer par une autre solution: https://Jeedom.github.io/documentation/howto/fr_FR/reset.password

Problement de "Could not reliably determine the server's fully qualified domain name, using 172.17.0.14. Set the 'ServerName' directive globally to suppress this message":
mettre en debut de fichier /etc/apache2/apache2.conf la line :

.. code-block:: php

  Global configuration
  ServerName 2b8faafb19a4
  root@2b8faafb19a4:/etc/apache2= apachectl configtest
  Syntax OK

.. code-block:: php

  = Global configuration
  =
  ServerName 2b8faafb19a4


Puis tester:

.. code-block:: php

  root@2b8faafb19a4:/etc/apache2= apachectl configtest
  Syntax OK

.. code-block:: php

  root@2b8faafb19a4:/etc/apache2= cat /etc/hosts
  127.0.0.1    localhost
  ::1    localhost ip6-localhost ip6-loopback
  fe00::0    ip6-localnet
  ff00::0    ip6-mcastprefix
  ff02::1    ip6-allnodes
  ff02::2    ip6-allrouters
  172.17.0.14    2b8faafb19a4    JeedomAbeille
  172.17.0.14    JeedomAbeille.bridge

  .. code-block:: php

  cat /var/www/html/core/config/common.config.php
  mysql -uJeedom -p
  use Jeedom;
  REPLACE INTO user SET `login`='adminTmp',password='c7ad44cbad762a5da0a452f9e854fdc1e0e7a52a38015f23f3eab1d80b931dd472634dfac71cd34ebc35d16ab7fb8a90c81f975113d6c7538dc69dd8de9077ec',profils='admin', enable='1';
  exit

Et maintenant on peut se connecter en adminTmp/admin.

Aller dans la conf reseau et mettre l adresse du host dans les adresses http.

Maintenant on peut se connecter en admin/admin donc on peut effacer l utilisateur adminTmp.

Installation du plugin Abeille
------------------------------

* Créer un objet Abeille.
* Installer le plugin Abeille depuis le market.
* L'activer.
* Lancer l installation des dépendances.
* Definissez les bons parametres du demon.
* Lancer le demon
* L objet Ruche doit être créé.
* un petit getVersion et vous devriez avoir le champ SW et SDK qui se mettent à jour.

Enjoy !!!


.. hint::

  Vous allez certainement avoir le message:
  "Jeedom est en cours de démarrage, veuillez patienter. La page se rechargera automatiquement une fois le démarrage terminé."

  Aller dans le "Moteur de taches" et lancer "Jeedom-cron".


Backup du Docker
================

Plusieures solutions s'offrent à nous. Il est interessant de comprende ce qui se passe. Un bon article à lire: https://tuhrig.de/difference-between-save-and-export-in-docker/

Toutes les operations suivantes se font depuis le host.

Commit / Save / Load
--------------------

Permet de garder tout l'historique.

Commit
------

Pour avoir les docker en fonctionnement :

.. code-block:: php

  docker ps

Pour avoir les docker en stock:
.. code-block:: php

  docker ps -a

Créons un image du docker en prod: JeedomAbeille et appelons cette image JeedomAbeille_backup

.. code-block:: php

  docker commit -p JeedomAbeille JeedomAbeille_backup

Attention: avec le -p le container est en pause donc Jeedom ne fonctionne plus le temps de faire la capture.

Par exemple: faites cette operation avant de faire des opérations irréversibles qui risquent de planter votre Jeedom.


Pour voir les images crées et disponiqbles:

.. code-block:: php

  docker images


Save
====

.. code-block:: php

  docker save -o ~/JeedomAbeille_backup.tar JeedomAbeille_backup
  ls -l ~/JeedomAbeille_backup.tar


soyez patient le tar fait 3G.

Load
====

If we have transferred our "container1.tar" backup file to another docker host system we first need to load backed up tar file into a docker's local image repository:


.. code-block:: php

  docker load -i /root/JeedomAbeille_backup.tar
  docker images

Export / Import
===============

Garde que la derniere version.

Export
======

.. code-block:: php

  docker ps -a
  docker export <CONTAINER ID> > /home/export.tar

Import
======

.. code-block:: php

  cat /home/export.tar | sudo docker import - NameYouWant:latest

Conclusion
==========

Plus besoin d'aller chercher les cartes SD dans les differents RPI3 pour en faire de images. Tout va se faire à distance maintenant !!! YaaahhhOOOOUUU !!!!!


Vous pouvez effacer de vieilles images par:

.. code-block:: php

  docker rmi JeedomAbeille_backup

Docker GUI
==========

Sur la raspbian
---------------

Thanks to:
* http://blog.hypriot.com/post/new-docker-ui-portainer/
* https://portainer.readthedocs.io/en/latest/deployment.html

Il semble qu'on puisse utiliser une interface graphique "portainer.io" sur le rpi, saisir:

.. code-block:: php

  docker run -d -p 9000:9000 --name portainer --restart always -v /var/run/docker.sock:/var/run/docker.sock portainer/portainer:arm -H unix:///var/run/docker.sock


Puis se logger sur http://IP_Host:9000
Tout ne fonctionne pas mais c'est plus sympas que la ligne de commande.

Il semble que la version rpi par defaut est un peu ancienne et certaine feature comme volume ne sont pas dispo.

Sur la hypriot
--------------

https://hub.docker.com/r/hypriot/rpi-portainer/

.. code-block:: php

  docker run -d -p 9000:9000 -v /var/run/docker.sock:/var/run/docker.sock hypriot/rpi-portainer

Puis se logger sur http://IP_Host:9000.
Tout fonctionne bien mieux que sur la version raspbian.

Plugins
=======

Zwave
-----

Sur ma machine Jeedomprorpi, le repertoire /tmp/Jeedom/openzwave n'a pas les bons droits et le demon est toujours en erreur. Je viens de faire un chmod 777 /tmp/Jeedom/openzwave et tout est ok maintenant.

homebridge
----------

Comme il faut que le docker soit exposé au sous réseau, il faut utiliser macvlan et affecter une adresse spécifique.


Installation sur une VM Ubuntu
==============================

Installation de l'OS
--------------------

Fichier ISO: ubuntu-16.04.1-server-amd64.iso

Installation classique de l'OS (Je ne détaille pas car cela dépend de votre envirroement de virtualisation).

Preparation de l'OS
-------------------

login: (user créé pendant l install avec son password associé).

.. code-block:: php

  sudo su -
  apt-get update
  apt-get upgrade
  apt-get autoremove


Installation de la base mysql
-----------------------------

installation à la main de mysql (car l instanllation par Jeedom ne fonctionne pas)

.. code-block:: php

  apt-get install mysql-server
  apt-get install mysql-client

Installation de Jeedom
----------------------

.. code-block:: php

  wget https://raw.githubusercontent.com/Jeedom/core/stable/install/install.sh
  chmod +x install.sh

Enlever le php7.0-ssh2 du fichier install.sh

.. code-block:: php

  ./install.sh -m motDePasse

A cette étape vous devoir pourvoir ouvrir un browser et utiliser Jeedom.

Installation du Plugin Abeille
------------------------------

.. code-block:: php

  ./install.sh -m motDePasse

  cd /var/www/html/plugins/

  git clone https://github.com/KiwiHC16/Abeille.git Abeille

  chmod -R 777 /var/www/html/plugins/Abeille
  chown -R www-data:www-data /var/www/html/plugins/Abeille

Utilisation de Jeedom
----------------------

Il ne vous reste plus qu'à vous connecter à Jeedom...


Installation sur une machine Odroid XU4 avec Ubuntu
---------------------------------------------------

Installation de l'OS
--------------------

Fichier img: ubuntu-14.04lts-server-odroid-xu3-20150725.img
que l on trouve sur le server odroid: https://odroid.in/ubuntu_14.04lts/

Installation classique odroid de l'OS : https://wiki.odroid.com/odroid-xu4/odroid-xu4

Preparation de l'OS
-------------------

login: (root/odroid).

.. code-block:: php

  apt-get update
  apt-get upgrade
  apt-get autoremove

Installation de la base mysql
-----------------------------

installation à la main de mysql (car l instanllation par Jeedom ne fonctionne pas)

.. code-block:: php

  apt-get install mysql-server
  apt-get install mysql-client

Installation de Jeedom
----------------------

.. code-block:: php

  wget https://raw.githubusercontent.com/Jeedom/core/stable/install/install.sh
  chmod +x install.sh

Enlever le php7.0-ssh2 du fichier install.sh

.. code-block:: php

  ./install.sh -m motDePasse


A cette étape vous devoir pourvoir ouvrir un browser et utiliser Jeedom.

Installation du Plugin Abeille
------------------------------

.. code-block:: php

  ./install.sh -m motDePasse

  cd /var/www/html/plugins/

  git clone https://github.com/KiwiHC16/Abeille.git Abeille

  chmod -R 777 /var/www/html/plugins/Abeille
  chown -R www-data:www-data /var/www/html/plugins/Abeille


Utilisation de Jeedom
---------------------

Il ne vous reste plus qu'à vous connecter à Jeedom...



De-installation
===============

Le plugin Abeille utilise:
- le code du plugin lui-même et
- un broker MQTT mosquitto.

Par défaut, lors de l'installation de Abeille, le code du plugin est installé depuis le market et le broker est installé lors de l installation des dépendances.

Le broker MQTT peux être utilisé par d'autres logiciels comme par d'autres plugins.

C'est pourquoi lors de la desinstallation d'Abeille, mosquitto n'est pas desintallé, ni sa configuration.

Si vous souhaitez le desinstaller, vous avez le script "manual_remove_of_mosquito.sh" qui peut vous aider à enlever les déclaraitons faites dans apaches.

Pour la désinstallation de mosquitto, cela depend de votre système et il y a plein de doc sur le net (je manque de temps pour faire la doc...).
