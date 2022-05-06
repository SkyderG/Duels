<?php

namespace duels;

use duels\arena\ArenaManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener
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

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $this->getPlugin()->getPlayerManager()->addPlayer($player);
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        $this->getPlugin()->getPlayerManager()->removePlayer($player);
    }
}