<?php

declare(strict_types=1);

namespace BDEmoteV1\emotes;

final class SleepEmote extends Emote{
	public function getKey() : string{ return 'sleep'; }
	public function getTitle() : string{ return 'Sleep'; }
	public function getAnimation() : string{ return 'animation.riee.sleep'; }
	public function getIconId() : string{ return 'bdemotev1_icon_sleep'; }
}
