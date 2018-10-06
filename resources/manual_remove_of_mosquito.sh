#! /bin/bash

#functions
removeMosquitto(){

    for phpdir in "${PHPETC}/cli" "${PHPETC}/fpm" "${PHPETC}/apache2"
    do
        if [[ -d "${phpdir}" ]]; then
            sed -i '/extension=mosquitto.so/d' ${phpdir}/php.ini
        fi
    done
    service php${PHPVER}-fpm restart >/dev/null 2>&1
    service apache2 restart >/dev/null 2>&1
}

#Main
PHPVER=""
[[ -d "/etc/php5/" ]] && PHPVER=5 && PHPETC=/etc/php5
[[ ! -z ${PHPVER} ]] && removeMosquitto
PHPVER=""
[[ -d "/etc/php/7.0" ]] && PHPVER=7.0 && PHPETC=/etc/php/7.0
[[ ! -z ${PHPVER} ]] && removeMosquitto
PHPVER=""
[[ -d "/etc/php/7.2" ]] && PHPVER=7.2 && PHPETC=/etc/php/7.2
[[ ! -z ${PHPVER} ]] && removeMosquitto
