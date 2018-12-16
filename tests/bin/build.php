<?php

 /*
 *              _     _                 
 *        /\   | |   | |                
 *       /  \  | | __| | ___  _   _ ___ 
 *     / /\ \ | |/ _` |/ _ \| | | / __|
 *    / ____ \| | (_| | (_) | |_| \__ \
 *   /_/    \_\_|\__,_|\___/ \__,_|___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Implasher
 * @link https://github.com/Implasher/Aldous
 *
 */

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

if (count(glob("plugins/Aldous/*.phar")) === 0){
  echo "Aldous | Server Software PHAR file has been successfully created!\n";
  echo "The current build has been success!";
  exit(0);
}
