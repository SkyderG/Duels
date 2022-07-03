<?php


namespace duels\arena;

use duels\task\ArenaUpdate;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use duels\Loader;

class ArenaManager
{

    public Loader $plugin;

    /** @var Arena[] */
    private array $arenas = [];
    private array $players = [];
    private array $admin = [];

    private Config $data;

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
        $this->data = new Config($plugin->getDataFolder() . "/arenas.yml", Config::YAML);
        $this->loadArenas();
    }

    /**
     * @return Loader
     */
    private function getPlugin(): Loader
    {
        return $this->plugin;
    }

    private function loadArenas(): void
    {
        $loaded = 0;
        if (empty($this->getArenaConfig()->getAll())) {
            $this->getPlugin()->getLogger()->info("No arena to load.");
            return;
        }

        $data = $this->getArenaConfig()->getAll();

        foreach ($data as $key => $value) {
            $this->getPlugin()->getServer()->getWorldManager()->loadWorld($key, true);
            $this->arenas[$key] = new Arena($this->getPlugin(), $key, $value);

            $loaded++;
        }

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new ArenaUpdate($this->getPlugin()), 20);
        $this->getPlugin()->getServer()->getPluginManager()->registerEvents(new ArenaListener($this->getPlugin()), $this->getPlugin());

        $this->getPlugin()->getLogger()->info("Loaded " . $loaded . " arena(s).");
    }

    public function getArenaByName(string $name): ?Arena
    {
        return $this->arenas[$name] ?? null;
    }

    public function getPlayerArena(Player $player): ?Arena
    {
        return isset($this->players[$player->getName()]) ? $this->arenas[$this->players[$player->getName()]] : null;
    }

    public function getRandomArena(): Arena
    {
        return $this->arenas[array_rand($this->arenas)];
    }

    public function isFreeArena(Arena $arena): bool
    {
        if ($arena->getStatus() == Arena::WAITING) return true;
        if ($arena->getStatus() == Arena::STARTING && $arena->getPlayerCount() == 1 or $arena->getPlayerCount() == 0) return true;

        return false;
    }

    public function exists(Player $player): bool
    {
        return $this->players[$player->getName()];
    }

    public function onJoin(Player $player, string $arenaName = null): void
    {
        $languageManager = $this->getPlugin()->getLanguageManager();

        if (empty($this->arenas)) {
            $player->sendMessage($languageManager->translateMessage("arena.none"));
            return;
        }

        if(isset($this->players[$player->getName()])) {
            $player->sendMessage($languageManager->translateMessage("arena.in-game"));
            return;
        }

        $arena = $arenaName == null ? $this->getRandomArena() : $this->getArenaByName($arenaName);

        if (in_array($arena->getStatus(), [Arena::RUNNING, Arena::END, Arena::RESET])) {
            $player->sendMessage($this->getPlugin()->getLanguageManager()->translateMessage("arena.in-progress"));
            return;
        }

        if ($this->isFreeArena($arena)) {
            $arena->onJoin($player);
            $this->players[$player->getName()] = $arena->getName();
        }
    }

    public function inArena(Player $player): bool
    {
        if (!is_null($this->getPlayerArena($player)))
            return true;
        return false;
    }

    public function createArena(Player $player): void
    {
        $config = $this->getArenaConfig();

        $name = $this->admin[$player->getName()]["arena"];
        $data = [
            "name" => $this->admin[$player->getName()]["arena"],
            "spawns" => array_values($this->admin[$player->getName()]["spawns"]),
            "level" => $this->admin[$player->getName()]["arena"],
            "status" => 0
        ];

        $config->set($name, $data);
        $config->save();

        $this->getPlugin()->getLogger()->info("§eNew arena created: ".$name);
        $player->sendMessage("§aArena " . $name . " started.");
        $this->arenas[$name] = new Arena($this->getPlugin(), $name, $data);

        unset($this->admin[$player->getName()]);
    }

    public function isCreating(Player $player): bool
    {
        return isset($this->admin[$player->getName()]);
    }

    public function createEmptyData(Player $player, string $arenaName): void
    {
        $this->admin[$player->getName()] = [
            "arena" => $arenaName,
            "spawns" => []
        ];
    }

    public function updateCreatingSpawn(Player $player, int $spawn): void
    {
        if ($this->isCreating($player)) {
            $this->admin[$player->getName()]["spawns"][$spawn] = $player->getPosition()->getX() . ":" . $player->getPosition()->getY() . ":" . $player->getPosition()->getZ() . ":" . $player->getWorld()->getDisplayName();
            $player->sendMessage("§eSpawn $spawn defined.");
        }
    }

    /**
     * @return array
     */
    public function getCreatingData(): array
    {
        return $this->admin;
    }

    /**
     * @return Config
     */
    public function getArenaConfig(): Config
    {
        return $this->data;
    }

    public function updateArenas(): void
    {
        if(empty($this->arenas)) return;

        foreach($this->arenas as $arena) {
            $arena->onRun();
        }
    }

    public function getStatusByID(int $status) : string
    {
        return match ($status) {
            0 => "Waiting",
            1 => "Starting",
            2 => "Running",
            3 => "End",
            4 => "Reset",
        };
    }
}
