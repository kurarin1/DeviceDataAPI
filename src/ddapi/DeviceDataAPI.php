<?php

namespace ddapi;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class DeviceDataAPI extends PluginBase implements Listener
{

    const ERROR = -1;

    const OS_ANDROID = 1;
    const OS_IOS = 2;
    const OS_MAC = 3;
    const OS_FIREOS = 4;
    const OS_GEARVR = 5;
    const OS_HOLOLENS = 6;
    const OS_WINDOWS = 7;
    const OS_WIN32 = 8;
    const OS_DEDICATED = 9;
    const OS_ORBIS = 10;
    const OS_NX = 11;

    const INPUTMODE_KEYBOARD = 1;
    const INPUTMODE_TAP = 2;
    const INPUTMODE_CONTROLLER = 3;

    private static $instance;

    private $preCache = [];
    private $cache = [];

    public function onEnable()
    {
        self::$instance = $this;

        Server::getInstance()->getPluginManager()->registerEvents($this, $this);
    }

    public function onPacketReceive(DataPacketReceiveEvent $event){
        $pk = $event->getPacket();
        if($pk instanceof LoginPacket){
            $key = $pk->username;
            $this->preCache[$key] = $pk->clientData;
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function (int $currentTick) use ($key): void {
                    unset($this->preCache[$key]);
                }
            ), 20 * 30);
        }
    }

    public function onJoin(PlayerJoinEvent $event){
        $key = $event->getPlayer()->getName();
        if(isset($this->preCache[$key])){
            $this->cache[$key] = $this->preCache[$key];
            unset($this->preCache[$key]);
        }
        else{
            Server::getInstance()->getLogger()->warning("[" . $this->getName() . "]" . $key . "のクライアントデータを取得できませんでした");
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        unset($this->cache[$event->getPlayer()->getName()]);
    }

    /*API*/

    public static function getInstance() : self {
        return self::$instance;
    }

    public function getDeviceOS(Player $player) : int
    {
        try {
            return $this->cache[$player->getName()]["DeviceOS"];
        } catch (\Exception $exception) {
            return self::ERROR;
        }
    }

    public function getDeviceModel(Player $player) : string
    {
        try {
            return $this->cache[$player->getName()]["DeviceModel"];
        } catch (\Exception $exception) {
            return (string) self::ERROR;
        }
    }

    public function getCurrentInputMode(Player $player) : int
    {
        try {
            return $this->cache[$player->getName()]["CurrentInputMode"];
        } catch (\Exception $exception) {
            return self::ERROR;
        }
    }

}