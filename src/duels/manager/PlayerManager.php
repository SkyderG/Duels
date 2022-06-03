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
    
    public function addKill(Player $player, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($player->getName());
		$p["kills"] = ($p["kills"] + $add);
		$tops->set($player->getName(), $p);
		$tops->save();
	}
	
	public function addKillByName(string $name, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($name);
		$p["kills"] = ($p["kills"] + $add);
		$tops->set($name, $p);
		$tops->save();
	}
	
	public function addDeath(Player $player, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($player->getName());
		$p["deaths"] = ($p["deaths"] + $add);
		$tops->set($player->getName(), $p);
		$tops->save();
	}
	
	public function addDeathByName(string $name, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($name);
		$p["deaths"] = ($p["deaths"] + $add);
		$tops->set($name, $p);
		$tops->save();
	}
	
	public function getKills(Player $player){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($player->getName())["kills"];
	}
	
	public function getKillsByName(string $name){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($name)["kills"];
	}
	
	public function getDeaths(Player $player){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($player->getName())["deaths"];
	}
	
	public function getDeathsByName(string $name){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($name)["deaths"];
	}
}
