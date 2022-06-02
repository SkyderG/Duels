<?php


namespace duels\lang;

use pocketmine\utils\Config;
use duels\Loader;

class LanguageManager
{

    public Loader $plugin;

    public string $language;

    public Config $lconfig;

    public function __construct(Loader $plugin, string $language)
    {
        $this->plugin = $plugin;
        $this->languageManager = $this->getPlugin()->getLanguageManager();
    }

    /**
     * @return Loader
     */
    private function getPlugin(): Loader
    {
        return $this->plugin;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
        $this->lconfig = new Config($this->getPlugin()->getDataFolder() . "/lang/" . $language . ".yml", Config::YAML);
        $this->getPlugin()->getLogger()->notice("§eLanguage changed to: " . $language);
    }

    public function getLConfig(): Config
    {
        return $this->lconfig;
    }

    public function translateMessage(string $key, array $data = [])
    {
        $message = $this->getLConfig()->getNested($key);

        if (count($data) > 0) {
            for ($i = 0; $i < count($data); $i++) {
                $message = str_replace("{" . $i . "}", $data[$i], $message);
            }

            return str_replace("{n}", "\n", $message);
        }

        return str_replace("{n}", "\n", $message);
    }

    public function translateMessageOld(string $translate, array $data = [])
    {
        if (count($data) > 0) {
            for ($i = 0; $i < count($data); $i++) {
                $translate = str_replace("{" . $i . "}", $data[$i], $translate);
            }

            return str_replace("{n}", "\n", $translate);
        }

        return str_replace("{n}", "\n", $translate);
    }
}
