<?php


namespace duels\manager;


use duels\Loader;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class PlayerManager
{

    private Loader $plugin;
    private array $players = [];

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @return Loader
     */
    private function getPlugin(): Loader
    {
        return $this->plugin;
    }

    public function addPlayer(Player $player): void
    {
        if (!$this->exists($player)) {
            $this->players[$player->getXuid()] = $this->getPlayerConfig($player)->getAll();
            $this->getPlugin()->getScoreboardManager()->create($player);
        }
    }

    public function removePlayer(Player $player): void
    {
        if ($this->exists($player)) {
            $this->getPlayerConfig($player)->setAll($this->players[$player->getXuid()]);
            $this->getPlugin()->getScoreboardManager()->remove($player);
            unset($this->players[$player->getXuid()]);
        }
    }

    public function exists(Player $player): bool
    {
        return isset($this->players[$player->getXuid()]);
    }

    public function getPlayerConfig(Player $player): Config
    {
        return new Config(
            $this->getPlugin()->getDataFolder() . "/players/" . $player->getName() . ".yml",
            Config::YAML,
            [
                "kills" => 0,
                "deaths" => 0
            ]
        );
    }

    public function addDeath(Player $player): void
    {
        if($this->exists($player)) {
            $this->players[$player->getXuid()]["deaths"] += 1;
        }
    }

    public function addKill(Player $player): void
    {
        if($this->exists($player)) {
            $this->players[$player->getXuid()]["kills"] += 1;
        }
    }

    public function getKills(Player $player): int
    {
        return $this->players[$player->getXuid()]["kills"];
    }

    public function getDeaths(Player $player): int
    {
        return $this->players[$player->getXuid()]["deaths"];
    }
}
