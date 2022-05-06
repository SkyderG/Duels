<?php


namespace duels\arena;


use duels\entity\DuelEntity;
use duels\Loader;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;

class ArenaListener implements Listener
{

    public Loader $plugin;
    private ArenaManager $arenaManager;

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
        $this->arenaManager = $plugin->getArenaManager();
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }

    /**
     * @return Loader
     */
    public function getPlugin(): Loader
    {
        return $this->plugin;
    }

    /**
     * @return ArenaManager
     */
    public function getArenaManager(): ArenaManager
    {
        return $this->arenaManager;
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();

        if ($this->getArenaManager()->inArena($player)) {
            $this->getArenaManager()->getPlayerArena($player)->onQuit($player);
        }
    }

    public function onDeath(PlayerDeathEvent $event)
    {
        $player = $event->getPlayer();

        if ($this->getArenaManager()->inArena($player)) {
            $this->getArenaManager()->getPlayerArena($player)->onDeath($player);

            $cause = $player->getLastDamageCause();
            if ($cause instanceof EntityDamageByEntityEvent and $cause->getDamager() instanceof Player) {
                $this->getPlugin()->getPlayerManager()->addKill($cause->getDamager());
            }
        }
    }

    public function onDamage(EntityDamageEvent $event)
    {
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            $entity = $event->getEntity();

            if ($damager instanceof Player and $entity instanceof Player) {
                if ($this->getArenaManager()->inArena($damager) and $this->getArenaManager()->inArena($entity)) {
                    $entity->setScoreTag("§eHP: " . round($entity->getHealth()) . "/" . $entity->getMaxHealth());
                }
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($item->getCustomName() == "§r§cQuit") {
            $event->cancel();
            if ($this->getArenaManager()->inArena($player)) {
                $this->getArenaManager()->getPlayerArena($player)->onQuit($player);
            }
        }
    }

    public function onEntityInteract(PlayerEntityInteractEvent $event)
    {
        $player = $event->getPlayer();
        $entity = $event->getEntity();

        if ($entity instanceof DuelEntity) {
            $event->cancel();
            $this->getArenaManager()->onJoin($player);
        }
    }
}
