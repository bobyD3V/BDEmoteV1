<?php

declare(strict_types=1);

namespace BDEmoteV1\command;

use BDEmoteV1\emotes\EmoteManager;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class EmoteCommand extends Command{

	public function __construct(string $name, private EmoteManager $manager, string $description){
		parent::__construct($name, $description);
		$this->setPermission("bdemotev1.use");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$sender instanceof Player){
			$sender->sendMessage("This command can only be used in-game.");
			return true;
		}
		if(!$sender->hasPermission("bdemotev1.use")){
			return true;
		}

		if(strtolower($this->getName()) === "stopemote"){
			$this->manager->stop($sender, true);
			return true;
		}

		$sender->sendForm($this->buildMenu());
		return true;
	}

	private function buildMenu() : SimpleForm{
		$manager = $this->manager;

		$form = new SimpleForm(function(Player $player, $data) use ($manager) : void{
			if($data === null){
				return;
			}
			$keys = array_keys($manager->getEmotes());
			if(isset($keys[$data])){
				$manager->play($player, $keys[$data]);
			}
		});

		$form->setTitle("§l§bBDEmoteV1");
		$form->setContent("Choose an emote to play:");

		foreach($this->manager->getEmotes() as $emote){
			$form->addButton($emote->getTitle(), 0, $emote->getIcon());
		}

		return $form;
	}
}
