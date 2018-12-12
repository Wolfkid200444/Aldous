<?php
declare(strict_types=1);

$time = time();
$port = rand(1000, 65536);

while (system("lsof -i:" . $port) != null){
	$port = rand(1000, 65536);
}

echo "Port: ".$port.PHP_EOL;
system("echo \"port=" . $port . "\" > aldous.properties");

$software = proc_open(PHP_BINARY . " src/pocketmine/PocketMine.php --no-installer --disable-readline --settings.enable-dev-builds=1", [
  0 => ["pipe", "r"],
  1 => ["pipe", "w"],
  2 => ["pipe", "w"]
], $pipes);

fwrite($pipes[0], "version\nmakeserver\nstop\n\n");
while (!feof($pipes[1]) && time() - $time < 60 * 3){
  echo fgets($pipes[1]);
}
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);

echo "\n\nReturn the value: " . proc_close($software) . "\n";

if (count(glob("plugins/Altay/*.phar")) === 0){
  echo "Glowine | Server Software PHAR file has been successfully created!\n";
  echo "The current build has been success!";
  exit(0); // Process exited.
}
