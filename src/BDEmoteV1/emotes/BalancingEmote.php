<?php

declare(strict_types=1);

namespace BDEmoteV1\emotes;

final class BalancingEmote extends Emote{
	public function getKey() : string{ return 'balancing'; }
	public function getTitle() : string{ return 'Balancing'; }
	public function getAnimation() : string{ return 'animation.riee.balancing'; }
	public function getIconId() : string{ return 'bdemotev1_icon_balancing'; }
}
