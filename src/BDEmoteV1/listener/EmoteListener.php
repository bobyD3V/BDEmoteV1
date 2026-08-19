<?php

declare(strict_types=1);

namespace BDEmoteV1\listener;

use BDEmoteV1\emotes\EmoteManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;

final class EmoteListener implements Listener{

	public function __construct(private EmoteManager $manager){
	}

	/**
	 * Clear stale server-side tracking on join - deliberately NOT a full
	 * stop(). A freshly (re)connected client session has zero leftover
	 * animation-controller state (that lives on the old session, which is
	 * already gone), so there's nothing to visually reset. Sending reset
	 * packets here was actively harmful: it caused a leftover "active"
	 * tracking entry to auto-play fresh on the new session instead of being
	 * cancelled, and the accompanying skin refresh fired before the real
	 * skin was assigned, resetting players to the default Steve skin.
	 */
	public function onJoin(PlayerJoinEvent $event) : void{
		$this->manager->clearTracking($event->getPlayer());
	}

	/**
	 * Primary jump-to-stop path. PlayerJumpEvent (below) is unreliable on
	 * Bedrock - movement is largely client-authoritative, so the server-side
	 * jump heuristics it depends on don't always fire. Reading the jump flag
	 * straight off the input packet the client sends every tick is what
	 * actually works consistently.
	 *
	 * Wrapped defensively: if this Altay build's protocol classes don't match
	 * what we expect here, we just silently skip rather than risk crashing
	 * the server on every movement packet.
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		try{
			$packet = $event->getPacket();
			if(!($packet instanceof PlayerAuthInputPacket)){
				return;
			}

			$player = $event->getOrigin()->getPlayer();
			if($player === null || !$this->manager->isActive($player)){
				return;
			}

			if(method_exists($packet, 'hasFlag')
				&& class_exists(PlayerAuthInputFlags::class)
				&& $packet->hasFlag(PlayerAuthInputFlags::JUMPING)
			){
				$this->manager->stop($player, true);
			}
		}catch(\Throwable $e){
			// API mismatch on this server build - ignore, PlayerJumpEvent below
			// remains as a fallback.
		}
	}

	/**
	 * Secondary/fallback path - cancels the emote if this event does fire.
	 * Harmless if it never does; onDataPacketReceive() above is the one doing
	 * the real work.
	 */
	public function onJump(PlayerJumpEvent $event) : void{
		$player = $event->getPlayer();
		if($this->manager->isActive($player)){
			$this->manager->stop($player, true);
		}
	}
}
