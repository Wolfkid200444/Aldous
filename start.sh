#!/bin/bash
DIR="$(cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd)"
cd "$DIR"

while getopts "p:f:l" OPTION 2> /dev/null; do
	case ${OPTION} in
		p)
			PHP_BINARY="$OPTARG"
			;;
		f)
			POCKETMINE_FILE="$OPTARG"
			;;
		l)
			DO_LOOP="yes"
			;;
		\?)
			break
			;;
	esac
done

if [ "$PHP_BINARY" == "" ]; then
	if [ -f ./bin/php7/bin/php ]; then
		export PHPRC=""
		PHP_BINARY="./bin/php7/bin/php"
	elif [[ ! -z $(type php) ]]; then
		PHP_BINARY=$(type -p php)
	else
		echo "Couldn't find a working PHP 7.2 binary, please use the installer."
		exit 1
	fi
fi

if [ "$ALDOUS_FILE" == "" ]; then
	if [ -f ./Aldous.phar ]; then
        ALDOUS_FILE="./Aldous.phar"
	elif [ -f ./src/pocketmine/PocketMine.php ]; then
		ALDOUS_FILE="./src/pocketmine/PocketMine.php"
	else
		echo "Aldous.phar not found."
		echo "Downloads can be found at https://github.com/Implasher/Aldous/releases"
		exit 1
	fi
fi

LOOPS=0

set +e

if [ "$DO_LOOP" == "yes" ]; then
	while true; do
		if [ ${LOOPS} -gt 0 ]; then
			echo "Restarted for $LOOPS times."
		fi
		"$PHP_BINARY" "$ALDOUS_FILE" $@
		echo "To escape the loop, press CTRL+C now. Otherwise, please wait 5 seconds for the server to restart."
		sleep 5
		((LOOPS++))
	done
else
	exec "$PHP_BINARY" "$ALDOUS_FILE" $@
fi
