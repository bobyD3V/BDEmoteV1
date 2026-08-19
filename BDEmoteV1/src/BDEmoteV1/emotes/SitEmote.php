<?php

declare(strict_types=1);

namespace BDEmoteV1\emotes;

final class SitEmote extends Emote{
	public function getKey() : string{ return 'sit'; }
	public function getTitle() : string{ return 'Sit'; }
	public function getAnimation() : string{ return 'animation.riee.sit'; }
	public function getIconId() : string{ return 'bdemotev1_icon_sit'; }
}
