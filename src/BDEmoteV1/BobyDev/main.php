<?php

declare(strict_types=1);

namespace BDEmoteV1\BobyDev;

use BDEmoteV1\command\EmoteCommand;
use BDEmoteV1\emotes\EmoteManager;
use BDEmoteV1\listener\EmoteListener;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;

final class main extends PluginBase{

	private EmoteManager $manager;

	protected function onEnable() : void{
		$this->saveDefaultConfig();
		$this->saveResource("BDEmoteV1_Emotes.mcpack", true);
		$this->installPack();

		$this->manager = new EmoteManager($this);

		// NOTE: commands are NOT declared in plugin.yml (on purpose). If they were,
		// PocketMine would auto-register "bemotes"/"stopemote" as generic PluginCommands
		// the moment the plugin loads - BEFORE this code runs. Then this registration
		// below would find the name already taken and silently fall back to the
		// "bdemotev1:bemotes" prefixed alias, which is exactly the bug being fixed here.
		$map = $this->getServer()->getCommandMap();
		foreach([
			"bemotes" => "Open the BDEmoteV1 emote menu",
			"stopemote" => "Stop your current emote",
		] as $name => $description){
			$map->register("bdemotev1", new EmoteCommand($name, $this->manager, $description));
		}

		$this->getServer()->getPluginManager()->registerEvents(new EmoteListener($this->manager), $this);

		$this->getLogger()->info("BDEmoteV1 1.0.0 enabled.");
	}

	protected function onDisable() : void{
		if(isset($this->manager)){
			$this->manager->stopAll();
		}
	}

	private function installPack() : void{
		$path = $this->getDataFolder() . "BDEmoteV1_Emotes.mcpack";
		if(!is_file($path)){
			$this->getLogger()->error("Embedded emote resource pack missing.");
			return;
		}

		try{
			$pack = new ZippedResourcePack($path);
			$manager = $this->getServer()->getResourcePackManager();
			$stack = $manager->getResourceStack();
			foreach($stack as $existing){
				if($existing instanceof ZippedResourcePack && $existing->getPackId() === $pack->getPackId()){
					return;
				}
			}
			$stack[] = $pack;
			$manager->setResourceStack($stack);
			$manager->setResourcePacksRequired(true);
		}catch(\Throwable $e){
			$this->getLogger()->error("Resource pack install failed: " . $e->getMessage());
		}
	}
}
