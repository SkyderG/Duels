<?php

namespace duels\manager;

use duels\Loader;
use duels\task\ScoreboardUpdate;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;

class ScoreboardManager
{

    public array $scoreboard = [];

    public Loader $plugin;

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
        $this->languageManager = $this->getPlugin()->getLanguageManager();
        $plugin->getScheduler()->scheduleRepeatingTask(new ScoreboardUpdate($plugin), 120);
    }

    /**
     * @return Loader
     */
    private function getPlugin(): Loader
    {
        return $this->plugin;
    }

    public function exists(Player $player): bool
    {
        return isset($this->scoreboard[$player->getName()]);
    }

    public function getObjectiveName(Player $player): ?string
    {
        return $this->exists($player) ? $this->scoreboard[$player->getName()] : null;
    }

    public function create(Player $player, string $objectiveName = "Main", string $displayName = "§l§eDUELS")
    {
        if ($this->exists($player)) {
            $this->remove($player);
        }

        $pk = SetDisplayObjectivePacket::create(
            "sidebar",
            $objectiveName,
            $displayName,
            "dummy",
            0
        );

        $player->getNetworkSession()->sendDataPacket($pk);

        $this->scoreboard[$player->getName()] = $objectiveName;
    }

    public function setLine(Player $player, int $score, string $message)
    {
        if (!$this->exists($player)) return;
        if ($score < 1 or $score > 15) return;

        $objectiveName = $this->getObjectiveName($player);

        $entry = new ScorePacketEntry();
        $entry->objectiveName = $objectiveName;
        $entry->type = $entry::TYPE_FAKE_PLAYER;
        $entry->customName = $message;
        $entry->score = $score;
        $entry->scoreboardId = $score;

        $pk = SetScorePacket::create(SetScorePacket::TYPE_CHANGE, [$entry]);

        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function remove(Player $player)
    {
        if ($this->exists($player)) {
            $objectiveName = $this->getObjectiveName($player);

            $pk = RemoveObjectivePacket::create($objectiveName);

            $player->getNetworkSession()->sendDataPacket($pk);
            unset($this->scoreboard[$player->getName()]);
        }
    }

    public function updateScoreboard(Player $player)
    {
        $manager = $this->plugin->getArenaManager();
        $langManager = $this->getPlugin()->getLanguageManager();
        $playerManager = $this->getPlugin()->getPlayerManager();

        $langConfig = $langManager->getLConfig();

        $arena = $manager->getPlayerArena($player);
        $lines = [];

        $this->create($player);

        if (!is_null($arena)) {
            $statusID = strtolower($manager->getStatusByID($arena->getStatus()));

            if (in_array($statusID, ["end", "reset"])) return true;
            $scorelines = $langConfig->getNested("scoreboard." . $statusID);

            $translated = [];
            switch ($arena->getStatus()) {
                case $arena::WAITING:
                    $translated = [$arena->getPlayerCount()];
                    break;
                case $arena::STARTING:
                    $translated = [
                        $arena->getPlayerCount(),
                        gmdate("i:s", $arena->starting)
                    ];
                    break;
                case $arena::RUNNING:
                    $translated = [
                        gmdate("i:s", $arena->getTime()),

                    ];
                    break;
            }

            foreach ($scorelines as $id => $line) {
                $config = $langConfig->get("scoreboard")[$statusID][$id];
                $lines[] = $this->getPlugin()->getLanguageManager()->translateMessageOld($config, $translated);
            }

            for ($i = 0; $i < count($lines); $i++) {
                $this->setLine($player, $i, $lines[$i]);
            }

            return true;
        } else {
            $scorelines = $langConfig->getNested("scoreboard.lobby");

            $translated = [
                $playerManager->getKills($player),
                $playerManager->getDeaths($player)
            ];

            foreach ($scorelines as $id => $line) {
                $config = $langConfig->get("scoreboard")["lobby"][$id];
                $lines[] = $this->getPlugin()->getLanguageManager()->translateMessageOld($config, $translated);
            }

            for ($i = 0; $i < count($lines); $i++) {
                $this->setLine($player, $i, $lines[$i]);
            }
        }
        return true;
    }

    public function updateAllScoreboards()
    {
        if (empty($this->getPlugin()->getServer()->getOnlinePlayers())) return;

        foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $players) {
            $this->updateScoreboard($players);
        }
    }
}
