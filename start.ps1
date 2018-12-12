[CmdletBinding(PositionalBinding=$false)]
param (
	[string]$php = "",
	[switch]$Loop = $false,
	[string]$file = "",
	[string][Parameter(ValueFromRemainingArguments)]$Aldous
)

if ($php -ne ""){
	$binary = $php
} else if (Test-Path "bin\php\php.exe"){
	$env:PHPRC = ""
	$binary = "bin\php\php.exe"
} else {
	$binary = "php"
}

if ($file -eq ""){
	if (Test-Path "Aldous.phar"){
	    $file = "Aldous.phar"
	} else {
	    echo "Aldous.phar not found."
	    echo "Downloads can be found at https://github.com/Implasher/Aldous/releases"
	    pause
	    exit 1
	}
}

function StartServer{
	$command = "powershell -NoProfile " + $binary + " " + $file + " " + $Aldous
	iex $command
}

$loops = 0

StartServer

while ($Loop){
	if ($loops -ne 0){
		echo ("Restarted for " + $loops + " times.")
	}
	$loops++
	echo "To escape the loop, press CTRL+C now. Otherwise, please wait 5 seconds for the server to restart."
	echo ""
	Start-Sleep 5
	StartServer
}
