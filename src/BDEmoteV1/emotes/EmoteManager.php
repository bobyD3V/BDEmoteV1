<?php

declare(strict_types=1);

namespace BDEmoteV1\emotes;

use BDEmoteV1\BobyDev\main;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\player\Player;

final class EmoteManager{

	// Both play() and stop() MUST use this exact same controller name.
	// AnimateEntityPacket only overrides an animation if the new packet
	// targets the same controller that started it - a different/empty
	// controller does NOT cancel the old one, it just gets ignored while
	// the original (which was sent with a ~11 day blend-out) keeps playing.
	private const CONTROLLER = "controller.animation.bdemotev1.articulated";

	/** @var array<string, Emote> */
	private array $emotes = [];

	/** @var array<string, string> lowercase player name => active emote key */
	private array $active = [];

	public function __construct(private main $plugin){
		foreach([
			new SitEmote(),
			new TPoseEmote(),
			new SleepEmote(),
			new DieEmote(),
			new BalancingEmote(),
			new AngryEmote(),
		] as $emote){
			$this->emotes[$emote->getKey()] = $emote;
		}
	}

	/**
	 * @return Emote[]
	 */
	public function getEmotes() : array{
		return $this->emotes;
	}

	public function has(string $key) : bool{
		return isset($this->emotes[strtolower($key)]);
	}

	public function play(Player $player, string $key) : void{
		$key = strtolower($key);
		$emote = $this->emotes[$key] ?? null;
		if($emote === null){
			$player->sendMessage($this->prefix() . "§cUnknown emote.");
			return;
		}

		$packet = AnimateEntityPacket::create(
			$emote->getAnimation(),
			"",
			"",
			0,
			self::CONTROLLER,
			1000000.0,
			[$player->getId()]
		);
		$player->getNetworkSession()->sendDataPacket($packet);
		foreach($player->getViewers() as $viewer){
			if($viewer->isConnected()){
				$viewer->getNetworkSession()->sendDataPacket($packet);
			}
		}

		$this->active[strtolower($player->getName())] = $key;
		$player->sendMessage($this->prefix() . "§aPlaying: §f" . $emote->getTitle());
	}

	public function stop(Player $player, bool $message = true) : void{
		$name = strtolower($player->getName());
		$activeKey = $this->active[$name] ?? null;
		unset($this->active[$name]);

		if($activeKey !== null && isset($this->emotes[$activeKey])){
			// Resend the EXACT animation that's playing, not a different one.
			// AnimateEntityPacket only treats a new packet as "cancel" for an
			// in-progress custom-controller animation when the animation name
			// matches what's already active on that controller - a different
			// animation name (even on the same controller) is treated as
			// starting a brand-new, competing animation rather than replacing
			// the old one. That's why swapping to a generic "base_pose"
			// packet never actually cleared a stuck pose.
			$this->sendReset($player, $this->emotes[$activeKey]->getAnimation());
		}else{
			// We don't know (or no longer know) which exact animation is
			// stuck on the client - e.g. tracking was lost across a
			// restart/reload. Defensively cancel every known emote
			// animation; harmless no-ops for any that aren't actually
			// playing.
			foreach($this->emotes as $emote){
				$this->sendReset($player, $emote->getAnimation());
			}
		}

		// Belt-and-braces fix for the "reverts after a second" bug: a plain
		// cancel packet can blend the pose's weight to zero without actually
		// removing the queued entry on that controller, so the client snaps
		// right back to it on the next animation recompute (e.g. exactly
		// when landing from a jump). Forcing a full skin refresh rebuilds
		// the player's render entity - skeleton and animation controllers
		// included - from scratch, which reliably wipes any leftover state
		// the animate-packet approach alone couldn't fully clear.
		$this->refreshSkin($player);

		if($message){
			$player->sendMessage($this->prefix() . "§eEmote stopped.");
		}
	}

	private function refreshSkin(Player $player) : void{
		try{
			if(method_exists($player, 'sendSkin')){
				// Refresh for everyone currently viewing this player.
				$player->sendSkin();
				// Also refresh the player's own client view of themselves,
				// since their own AnimateEntityPacket was sent directly to
				// their own session too (third-person/self-view can get
				// stuck the same way viewers do).
				$player->sendSkin([$player]);
			}
		}catch(\Throwable $e){
			// If this build's sendSkin() signature/behaviour differs,
			// silently skip - the animate-packet reset above still applies.
		}
	}

	private function sendReset(Player $player, string $animation) : void{
		// Step 1: resend the SAME animation name on the SAME controller with
		// an immediate (0s) blend-out. This is what gets recognized as
		// "replace the in-progress animation" rather than layering a new one
		// on top of it.
		$this->sendAnimatePacket($player, $animation, 0.0);

		// Step 2: follow up with an EMPTY animation name on the same
		// controller. Step 1 alone only blends the pose's weight down to
		// zero for a moment - the controller's queued entry isn't actually
		// removed, so on the next animation recompute (e.g. exactly when
		// landing from a jump) the client snaps right back to it. An empty
		// animation name is the real "clear this controller" instruction
		// that removes the entry for good.
		$this->sendAnimatePacket($player, "", 0.0);
	}

	private function sendAnimatePacket(Player $player, string $animation, float $blendOutTime) : void{
		$packet = AnimateEntityPacket::create(
			$animation,
			"",
			"",
			0,
			self::CONTROLLER,
			$blendOutTime,
			[$player->getId()]
		);
		$player->getNetworkSession()->sendDataPacket($packet);
		foreach($player->getViewers() as $viewer){
			if($viewer->isConnected()){
				$viewer->getNetworkSession()->sendDataPacket($packet);
			}
		}
	}

	public function stopAll() : void{
		// Must actually reset each player's client-side pose, not just clear
		// our tracking array - otherwise (as happened before this fix) a
		// server restart/reload leaves players visually stuck in their emote
		// with the server no longer aware anything is playing.
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			if(isset($this->active[strtolower($player->getName())])){
				$this->stop($player, false);
			}
		}
		$this->active = [];
	}

	public function isActive(Player $player) : bool{
		return isset($this->active[strtolower($player->getName())]);
	}

	public function clearTracking(Player $player) : void{
		// Clears server-side "active emote" bookkeeping WITHOUT sending any
		// packets to the client. A freshly (re)connected session never has
		// any leftover custom-controller animation state to begin with -
		// there's nothing for the client to cancel. Resending an animate
		// packet here doesn't "cancel" a prior emote, it just STARTS that
		// animation fresh on the new session (since nothing was already
		// playing for it to override) - which is exactly what caused emotes
		// to auto-play (and, via the accompanying skin refresh, reset skins
		// to default) on every join.
		unset($this->active[strtolower($player->getName())]);
	}

	private function prefix() : string{
		return "§8[§bBDEmoteV1§8] §r";
	}
}
