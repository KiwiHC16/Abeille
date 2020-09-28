#!/bin/bash

# Hooks installation and update script

echo "Hooks: installation & mise-à-jour"

# Updating hooks if required
HOOKS=`ls .hooks/*`
for F in ${HOOKS}
do
    FILE_NAME=$(basename $F)
    if [ "${FILE_NAME}" == "install_hooks.sh" ]; then
        continue
    fi

    # Updating only new/changes hooks
    cmp .hooks/${FILE_NAME} .git/hooks/${FILE_NAME} >/dev/null 2>&1
    if [ $? != 0 ]; then
        echo "- mise-à-jour de ${FILE_NAME}"
        cp .hooks/${FILE_NAME} .git/hooks
    fi
done
echo "= OK"

exit 0
