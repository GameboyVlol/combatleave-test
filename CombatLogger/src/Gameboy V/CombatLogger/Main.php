<?php

namespace Gameboy V\CombatLogger;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\scheduler\PluginTask;

class Main extends PluginBase implements Listener{

    private $players = array();
    public $tasks = array();
    public $interval = 10;

    public function onEnable(){
        $this->saveDefaultConfig();
        $this->interval = $this->getConfig()->get("interval");
        $this->getServer()->getLogger()->info("CombatLogger enabled");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable(){
        $this->getServer()->getLogger()->info("CombatLogger disabled");
    }
  
    public function EntityDamageEvent(EntityDamageEvent $event){
        if($event instanceof EntityDamageByEntityEvent){
            if($event->getDamager() instanceof Player){
                foreach(array($event->getDamager(),$event->getEntity()) as $players){
                    $this->setTime($players);
                }
            }
        }
    }

    private function setTime(Player $player){
        $msg = "[CombatLogger] Logging out now will cause you to die.\nPlease wait ".$this->interval." seconds.";
        if(isset($this->players[$player->getName()])){
            if((time() - $this->players[$player->getName()]) > $this->interval){
                $player->sendMessage($msg);
            }
            if(isset($this->tasks[$player->getName()])){
                $this->getServer()->getScheduler()->cancelTask($this->tasks[$player->getName()]);
            }
            $this->tasks[$player->getName()] = $this->getServer()->getScheduler()->scheduleRepeatingTask(new TimeMsg($this, $player), 20)->getTaskId();
        }else{
            $player->sendMessage($msg);
            $this->tasks[$player->getName()] = $this->getServer()->getScheduler()->scheduleRepeatingTask(new TimeMsg($this, $player), 20)->getTaskId();
        }
        $this->players[$player->getName()] = time();
    }

    
    public function PlayerDeathEvent(PlayerDeathEvent $event){
        if(isset($this->players[$event->getEntity()->getName()])){
            unset($this->players[$event->getEntity()->getName()]);
            if(isset($this->tasks[$event->getEntity()->getName()])) $this->getServer()->getScheduler()->cancelTask($this->tasks[$event->getEntity()->getName()]);unset($this->tasks[$event->getEntity()->getName()]);
        }
    }
  
    public function PlayerQuitEvent(PlayerQuitEvent $event){
        if(isset($this->players[$event->getPlayer()->getName()])){
            $player = $event->getPlayer();
            if((time() - $this->players[$player->getName()]) < $this->interval){
                $player->kill();
            }
            unset($this->players[$player->getName()]);
            if(isset($this->tasks[$player->getName()])) $this->getServer()->getScheduler()->cancelTask($this->tasks[$player->getName()]);unset($this->tasks[$player->getName()]);
        }
    }
}
