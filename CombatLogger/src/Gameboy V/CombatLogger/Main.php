<?php

namespace Gameboy V\CombatLogger;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
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
  
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event){
        $damager = $event->getDamager();
        $entity = $event->getEntity();
        
        if($damager instanceof Player && $entity instanceof Player){
           $this->setTime($damager);
           $this->setTime($entity);
        }
    }

    private function setTime(Player $player){
        $msg = "[CombatLogger] Logging out now will cause you to die.\nPlease wait ".$this->interval." seconds.";
        
        if(isset($this->players[$player->getUniqueId()->toString()])){
            $expiry = $this->players[$player->getUniqueId()->toString()];
            
            if((time() - $expiry) > $this->interval){
                $player->sendMessage($msg);
            }
            
            if(isset($this->tasks[$player->getUniqueId()->toString()])){
                $this->getServer()->getScheduler()->cancelTask($this->tasks[$player->getUniqueId()->toString()]);
            }
            
            $this->tasks[$player->getUniqueId()->toString()] = $this->getServer()->getScheduler()->scheduleRepeatingTask(new TimeMsg($this, $player), 20)->getTaskId();
        }else{
            $player->sendMessage($msg);
            $this->tasks[$player->getUniqueId()->toString()] = $this->getServer()->getScheduler()->scheduleRepeatingTask(new TimeMsg($this, $player), 20)->getTaskId();
        }
        $this->players[$player->getUniqueId()->toString()] = time();
    }

    
    public function PlayerDeathEvent(PlayerDeathEvent $event){
        if(isset($this->players[$event->getEntity()->getUniqueId()->toString()])){
            unset($this->players[$event->getEntity()->getUniqueId()->toString()]);
            if(isset($this->tasks[$event->getEntity()->getUniqueId()->toString()])) $this->getServer()->getScheduler()->cancelTask($this->tasks[$event->getEntity()->getUniqueId()->toString()]);unset($this->tasks[$event->getEntity()->getUniqueId()->toString()]);
        }
    }
  
    public function PlayerQuitEvent(PlayerQuitEvent $event){
        if(isset($this->players[$event->getPlayer()->getUniqueId()->toString()])){
            $player = $event->getPlayer();
            if((time() - $this->players[$player->getUniqueId()->toString()]) < $this->interval){
                $player->kill();
            }
            unset($this->players[$player->getUniqueId()->toString()]);
            if(isset($this->tasks[$player->getUniqueId()->toString()])) $this->getServer()->getScheduler()->cancelTask($this->tasks[$player->getUniqueId()->toString()]);unset($this->tasks[$player->getUniqueId()->toString()]);
        }
    }
}
