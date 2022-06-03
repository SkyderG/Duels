<?php

namespace duels;

use duels\manager\PlayerManager;
use duels\arena\ArenaManager;
use duels\entity\DuelEntity;
use duels\lang\LanguageManager;
use duels\manager\ScoreboardManager;
use duels\task\ScoreboardUpdate;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\World;

class Loader extends PluginBase implements Listener
{
    private ArenaManager $arenaManager;
    private LanguageManager $languageManager;
    private ScoreboardManager $scoreboardManager;
    private PlayerManager $playerManager;

    private array $admin = [];

    protected function onLoad(): void
    {
        $this->saveDefaultConfig();
        @mkdir($this->getDataFolder() . "lang/");
        @mkdir($this->getDataFolder() . "players/");

        $this->getLogger()->info("Loading Duel...");

        $this->languageManager = new LanguageManager($this, $this->getConfig()->get("language"));
        $this->scoreboardManager = new ScoreboardManager($this);
    }

    protected function onEnable(): void
    {
        $this->arenaManager = new ArenaManager($this);
        $this->playerManager = new PlayerManager($this);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        new EventListener($this);

        EntityFactory::getInstance()->register(DuelEntity::class, function(World $world, CompoundTag $nbt): DuelEntity {
            return new DuelEntity(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ["duel:base_entity", "duels:base_entity"]);
    }

    /**
     * @return ScoreboardManager
     */
    public function getScoreboardManager(): ScoreboardManager
    {
        return $this->scoreboardManager;
    }

    /**
     * @return PlayerManager
     */
    public function getPlayerManager(): PlayerManager
    {
        return $this->playerManager;
    }

    /**
     * @return ArenaManager
     */
    public function getArenaManager(): ArenaManager
    {
        return $this->arenaManager;
    }

    /**
     * @return LanguageManager
     */
    public function getLanguageManager(): LanguageManager
    {
        return $this->languageManager;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if (!$sender instanceof Player) return false;

        if ($command->getName() == "duels") {
            if (!isset($args[0])) {
                if ($sender->hasPermission(duels.usage)) {
                    $sender->sendMessage("§c/duels create [arena]\n§c/duels setspawn [1:2]\n§c/duels build\n§c/duels setentity\n§c/duels join - quit");
                } else {
                    $sender->sendMessage("§c/duels [join - quit]");
                }
                return false;
            }

            switch ($args[0]) {
                case "create":
                    if (!isset($args[1])) return true;
                    if (!$this->getServer()->getWorldManager()->isWorldGenerated($args[1])) return true;

                    if (!$this->getServer()->getWorldManager()->isWorldLoaded($args[1])) {
                        $this->getServer()->getWorldManager()->loadWorld($args[1]);
                    }

                    $world = $this->getServer()->getWorldManager()->getWorldByName($args[1]);
                    $sender->teleport($world->getSafeSpawn());
                    $sender->sendMessage("§eUse: /duels setspawn [1:2]");
                    $this->getArenaManager()->createEmptyData($sender, $args[1]);
                    break;

                case "setspawn":
                    if (!isset($args[1]) or $args[1] <= 0 or $args[1] > 2) return true;

                    $this->getArenaManager()->updateCreatingSpawn($sender, $args[1]);
                    break;

                case "build":
                    if (!isset($args[1]) or !$this->getArenaManager()->isCreating($sender)) return true;

                    $this->getArenaManager()->createArena($sender);
                    $sender->sendMessage("§aArena " . $this->admin[$sender->getName()]["arena"] . " started.");
                    break;

                case "join":
                    $this->getArenaManager()->onJoin($sender);
                    break;

                case "quit":
                    if ($this->getArenaManager()->inArena($sender)) {
                        $this->getArenaManager()->getPlayerArena($sender)->onQuit($sender);
                    } else {
                        $sender->sendMessage("§cYou are not in an arena!");
                    }
                    break;

                case "setentity":
                    $entity = new DuelEntity($sender->getLocation(), $sender->getSkin());

                    $entity->setNameTag("§l§eDUELS\n§7(Click to join)");
                    $entity->setNameTagVisible();
                    $entity->spawnToAll();
                    break;

                case "debug":
                    for ($i = 1; $i < 2; $i++) {
                        $this->getServer()->dispatchCommand(
                            new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()),
                            "s s teste" . $i
                        );
                        $this->getServer()->dispatchCommand(
                            new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()),
                            "s c teste" . $i . " /duel join"
                        );
                    }
                    break;
            }
        }
        return false;
    }

    public function getConfig(): Config
    {
        return new Config($this->getDataFolder()."/config.yml", Config::YAML);
    }
}
