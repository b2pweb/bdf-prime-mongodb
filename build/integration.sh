#!/bin/bash

source $(dirname $(readlink -f $0))/ci-functions.sh

srcdir=${1:-/home/bdf-prime-mongodb}

export APPLICATION_ENV=integration
export SERVER_DB_PROFILE=integration
export APP_PATH=${srcdir}
export VERSION=$(cat ${srcdir}/release.txt)
export WORKSPACE=/var/lib/jenkins/workspace/bdf-prime-mongodb

mongodb=0

echo
echo "********************************************************************************"
echo "****************************         Setup          ****************************"
echo "********************************************************************************"
echo

run-configure ${srcdir} 'update'

echo
echo "********************************************************************************"
echo "**************************   Continous integration    **************************"
echo "********************************************************************************"
echo

targetdir="src"
run-phpunit ${srcdir} api 1 --exclude-group=dev
retval=$?
[ $retval -ne 0 ] && touch ${srcdir}/build/reports/__failure
[ -f ${srcdir}/build/reports/__failure ] || run-phpcpd ${srcdir} ${targetdir}
[ -f ${srcdir}/build/reports/__failure ] || run-pdepend ${srcdir} ${targetdir}
[ -f ${srcdir}/build/reports/__failure ] || run-phpcs ${srcdir} ${targetdir}
#[ -f ${srcdir}/build/reports/__failure ] || run-phpdoc ${srcdir} --directory ${targetdir}
change-source-path ${srcdir}

echo
echo "********************************************************************************"
echo "****************************        Shutdown        ****************************"
echo "********************************************************************************"
echo
kill $mongodb 1>/dev/null 2>&1
sleep 15
kill -9 $mongodb 1>/dev/null 2>&1

exit 0
