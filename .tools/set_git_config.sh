#!/bin/bash

USERNAME="Tcharp38"
EMAIL="fabrice.charpentier@laposte.net"

echo "Configuring user"
git config --local user.name "${USERNAME}"
git config --local user.email "${EMAIL}"

echo "Configuring CRLF"
git config --local core.autocrlf input
