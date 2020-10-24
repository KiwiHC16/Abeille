WIFI
====

Adafruit
--------

Comme je voulais avoir l'option Zigate Wifi dans Abeille et un petit soucis avec le module proposé par Akila, j'ai fait quelques investigations.

Pour ceux qui connaissent Adafruit, il y a un module que j'avais en stock: https://www.adafruit.com/product/3046

.. image:: images/Capture_d_ecran_2018_06_20_a_23_54_30.png

Ce montage possede un ESP8266, un étage de "puissance" avec batterie, un CP2104 USB-Serial, ... et est programmable facilement avec l'IDE Arduino.

J'ai aussi ma Zigate version bidouille:

.. image:: images/IMG_6207.jpg

Restait à les connecter.

Voici un petit schéma du cablage:

.. image:: images/Capture_d_ecran_2018_06_21_a_00_02_11.png

Restait que le SW à faire et à téléchargé dans l'ESP8266. Le soft: https://github.com/KiwiHC16/Abeille/blob/master/WIfi_Module/WIfi_Module.ino

Pour télécharger, compiler avec l'IDE Arduino et télécharger avec le cable USB. Il est necessaire ne déconnecter le TX/RX de la Zigate.

Maintenant j'ai une Zigate autonome sur batterie en Wifi !!!

.. image:: images/IMG_6208.jpg

Batterie est égale à:

* Je peux mettre la Zigate ou je veux
* si le cable USB est branché sur un charger, je suis autonome en cas de coupure de courant

Vous trouverez le source et le bin à la page: https://github.com/KiwiHC16/Abeille/tree/master/WIfi_Module
