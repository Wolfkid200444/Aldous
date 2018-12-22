#!/bin/bash

PHP_BINARY="php"
PM_WORKERS="auto"

while getopts "p:t:" OPTION 2> /dev/null; do
    case ${OPTION} in
		p)
			PHP_BINARY="$OPTARG"
			;;
		t)
			PM_WORKERS="$OPTARG"
            ;;
	esac
done

bash tests/lint.sh -p "$PHP_BINARY"

if [ $? -ne 0 ]; then
	echo The lint scans has failed!
	exit 1
fi

rm server.log 2> /dev/null
mkdir -p ./plugins

echo -e "\nversion\nmakeserver\nstop\n" | "$PHP_BINARY" src/pocketmine/PocketMine.php --no-installer --disable-ansi --disable-readline --debug.level=2  --settings.async-workers="$PM_WORKERS" --settings.enable-dev-builds=1
if ls plugins/Aldous/Aldous*.phar >/dev/null 2>&1; then
    echo Successfully created Aldous | Server Software PHAR file!
else
    echo Creating Aldous | Server Software PHAR file is unsuccessful!
    exit 1
fi
