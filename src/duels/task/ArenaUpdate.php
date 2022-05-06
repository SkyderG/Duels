<?php


namespace duels\task;


use duels\Loader;
use pocketmine\scheduler\Task;

class ArenaUpdate extends Task
{

    public Loader $plugin;

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @return Loader
     */
    public function getPlugin(): Loader
    {
        return $this->plugin;
    }

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        $this->getPlugin()->getArenaManager()->updateArenas();
    }
}