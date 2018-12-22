#!/bin/bash

PHP_BINARY="php"

while getopts "p:" OPTION 2> /dev/null; do
  case ${OPTION} in
  p)
  PHP_BINARY="$OPTARG"
  ;;
  esac
done

echo Starting to running Aldous's PHP lint scans...

OUTPUT=`find ./src/pocketmine -name "*.php" -print0 | xargs -0 -n1 -P4 "$PHP_BINARY" -l`

if [ $? -ne 0 ]; then
  echo $OUTPUT | grep -v "No syntax errors detected!"
  exit 1
fi

echo The lint scans has been completed.
echo All files have no syntax error!
