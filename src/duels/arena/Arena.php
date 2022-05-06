<?php

namespace duels\arena;

use duels\manager\ScoreboardManager;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use duels\lang\LanguageManager;
use duels\Loader;
use pocketmine\world\Position;
use pocketmine\world\World;

class Arena implements ArenaInterface
{
    const WAITING = 0,
        STARTING = 1,
        RUNNING = 2,
        END = 3,
        RESET = 4;

    const MAX_PLAYERS = 2;

    public Loader $plugin;

    public array $data;
    public string $name;

    public int $starting = 5;
    public int $time = 120;

    private ScoreboardManager $scoreboardManager;
    private LanguageManager $languageManager;

    public function __construct(Loader $plugin, $name, $data)
    {
        $this->plugin = $plugin;
        $this->name = $name;
        $this->data = $data;

        $this->scoreboardManager = $this->plugin->getScoreboardManager();
        $this->languageManager = $this->plugin->getLanguageManager();
    }

    /**
     * @return LanguageManager
     */
    public function getLanguageManager(): LanguageManager
    {
        return $this->languageManager;
    }

    /**
     * @return Loader
     */
    public function getPlugin(): Loader
    {
        return $this->plugin;
    }

    /**
     * @return ScoreboardManager
     */
    public function getScoreboardManager(): ScoreboardManager
    {
        return $this->scoreboardManager;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->data["status"];
    }

    public function setStatus(int $status)
    {
        $this->data["status"] = $status;
    }

    /**
     * @return int
     */
    public function getPlayerCount(): int
    {
        return count($this->data["players"]);
    }

    /**
     * @return World
     */
    public function getLevel(): World
    {
        return $this->getServer()->getWorldManager()->getWorldByName($this->data["level"]);
    }

    /**
     * @param int $id
     * @return Vector3
     */
    public function getSpawn(int $id): Vector3
    {
        $data = explode(":", $this->data["spawns"][$id]);
        return new Vector3((float)$data[0], (float)$data[1], (float)$data[2]);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->data["name"];
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->getPlugin()->getServer();
    }

    public function getPlayers()
    {
        return $this->data["players"];
    }

    public function removePlayer(Player $player)
    {
        if ($this->inArena($player)) {
            unset($this->data["players"][$player->getName()]);
        }
    }

    public function inArena(Player $player): bool
    {
        return isset($this->data["players"][$player->getName()]);
    }

    /**
     * @param Player $player
     * @return void
     */
    public function onJoin(Player $player): void
    {
        $this->data["players"][$player->getName()] = true;

        $this->getScoreboardManager()->create($player);

        $player->sendMessage($this->getLanguageManager()->translateMessage("arena.join", [$this->getName()]));

        if($this->getPlayerCount() === 0) {
            $player->teleport(new Position($this->getSpawn(0)->getX(), $this->getSpawn(0)->getY(), $this->getSpawn(0)->getZ(), $this->getLevel()));
        } else {
            $player->teleport(new Position($this->getSpawn(1)->getX(), $this->getSpawn(1)->getY(), $this->getSpawn(1)->getZ(), $this->getLevel()));
        }

        foreach ($this->getPlayers() as $players => $value) {
            $p = $player->getServer()->getPlayerExact($players);
            $p->sendMessage($this->getLanguageManager()->translateMessage("arena.joined", [$player->getName(), $this->getPlayerCount(), self::MAX_PLAYERS]));
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function onQuit(Player $player): void
    {
        $this->removePlayer($player);
        $this->getScoreboardManager()->remove($player);

        $players = $this->getPlayers();
        foreach ($players as $ps => $value) {
            $p = $this->getServer()->getPlayerExact($ps);
            $p->sendMessage($this->getLanguageManager()->translateMessage("arena.quit", [$p->getName()]));
        }

        if ($this->getStatus() == self::RUNNING) {
            foreach ($players as $ps => $value) {
                if ($ps ==! $player->getName()) {
                    $this->onWin($this->getServer()->getPlayerExact($ps));
                }
            }
        }

        $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
    }

    public function broadcastMessage(Player $player, string $message, int $method = 0)
    {
        /* 0 = Message - 1 = Tip */
        switch ($method) {
            case 0:
                $player->sendMessage($message);
                break;
            case 1:
                $player->sendTip($message);
                break;
        }
    }

    /**
     * @return void
     */
    public function onRun(): void
    {
        if ($this->getPlayerCount() === 0) return;

        switch ($this->getStatus()) {
            case self::WAITING:
                if ($this->getPlayerCount() == self::MAX_PLAYERS) {
                    $this->setStatus(self::STARTING);
                }
                break;

            case self::STARTING:
                $this->starting--;

                if ($this->getPlayerCount() == 1) {
                    $this->setStatus(self::WAITING);
                }

                if ($this->starting <= 0) {
                    $this->setStatus(self::RUNNING);
                }

                break;

            case self::RUNNING:
                if ($this->getTime() <= 0) {
                    $this->setStatus(self::END);
                }

                if ($this->time == 300) {
                    foreach ($this->getPlayers() as $player => $value) {
                        $player = $this->getServer()->getPlayerExact($player);
                        $player->sendMessage($this->getLanguageManager()->translateMessage("arena.started"));
                        // TODO: kit.
                    }
                }

                $this->time--;
                break;

            case self::END:
                foreach ($this->getPlayers() as $player => $value) {
                    $player = $this->getServer()->getPlayerExact($player);
                    if (is_null($player)) continue;
                    $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
                }
                break;

            case self::RESET:
                break;

            default:
                $this->setStatus(self::WAITING);
                break;
        }
    }

    public function reset(): void
    {
        $this->data["players"] = [];
        $this->starting = 5;
        $this->time = 120;
        $this->setStatus(self::WAITING);
    }

    /**
     * @param Player $player
     * @return void
     */
    public function onWin(Player $player): void
    {
        $this->removePlayer($player);
        $this->getScoreboardManager()->remove($player);

        $player->sendMessage($this->getLanguageManager()->translateMessage("arena.win"));
        $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());

        $this->reset();
    }

    public function onDeath(Player $player)
    {
        $this->removePlayer($player);
        $this->getScoreboardManager()->remove($player);

        $players = $this->getPlayers();
        $player->sendMessage($this->getLanguageManager()->translateMessage("arena.lose"));

        if ($this->getStatus() == self::RUNNING) {
            foreach ($players as $ps => $value) {
                if ($ps ==! $player->getName()) {
                    $this->onWin($this->getServer()->getPlayerExact($ps));
                }
            }
        }

        $this->getPlugin()->getPlayerManager()->addDeath($player);
        $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
    }
}
