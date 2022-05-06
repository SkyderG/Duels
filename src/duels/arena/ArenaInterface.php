<?php


namespace duels\arena;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use duels\Loader;
use pocketmine\world\World;

interface ArenaInterface
{

    public function __construct(Loader $plugin, $name, $data);

    public function getPlugin() : Loader;
    public function getServer() : Server;

    public function getName() : string;
    public function getData() : array;

    public function getStatus() : int;
    public function getPlayerCount() : int;

    public function getLevel() : World;
    public function getSpawn(int $id) : Vector3;

    public function broadcastMessage(Player $player, string $message, int $method = 0);

    public function onJoin(Player $player);
    public function onQuit(Player $player);
    public function onWin(Player $player);

    public function onRun();
}