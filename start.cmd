@echo off
TITLE Aldous - Server software for Minecraft: Bedrock Edition.
cd /d %~dp0

if exist bin\php\php.exe (
	set PHPRC=""
	set PHP_BINARY=bin\php\php.exe
) else (
	set PHP_BINARY=php
)

if exist Aldous.phar (
	set ALDOUS_FILE=Aldous.phar
) else (
	if exist src\pocketmine\PocketMine.php (
		set ALDOUS_FILE=src\pocketmine\PocketMine.php
	) else (
		echo Aldous.phar not found.
		echo Downloads can be found at https://github.com/Implasher/Aldous/releases
		pause
		exit 1
	)
)

if exist bin\mintty.exe (
	start "" bin\mintty.exe -o Columns=88 -o Rows=32 -o AllowBlinking=0 -o FontQuality=3 -o Font="Consolas" -o FontHeight=10 -o CursorType=0 -o CursorBlinks=1 -h error -t "Aldous" -w max %PHP_BINARY% %ALDOUS_FILE% --enable-ansi %*
) else (
	REM pause on exitcode != 0 so the user can see what went wrong
	%PHP_BINARY% -c bin\php %ALDOUS_FILE% %* || pause
)
