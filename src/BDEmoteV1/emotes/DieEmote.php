<?php

declare(strict_types=1);

namespace BDEmoteV1\emotes;

final class DieEmote extends Emote{
	public function getKey() : string{ return 'die'; }
	public function getTitle() : string{ return 'Die'; }
	public function getAnimation() : string{ return 'animation.riee.die'; }
	public function getIconId() : string{ return 'bdemotev1_icon_die'; }
}
