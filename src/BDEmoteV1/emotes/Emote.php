<?php

declare(strict_types=1);

namespace BDEmoteV1\emotes;

abstract class Emote{
	abstract public function getKey() : string;
	abstract public function getTitle() : string;
	abstract public function getAnimation() : string;
	abstract public function getIconId() : string;

	public function getIcon() : string{
		// Icons ship inside the pack under textures/emotes/, not textures/ui/
		return "textures/emotes/" . $this->getIconId();
	}
}
