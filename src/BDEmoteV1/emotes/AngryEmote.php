<?php

declare(strict_types=1);

namespace BDEmoteV1\emotes;

final class AngryEmote extends Emote{
	public function getKey() : string{ return 'angry'; }
	public function getTitle() : string{ return 'Angry'; }
	public function getAnimation() : string{ return 'animation.riee.angry'; }
	public function getIconId() : string{ return 'bdemotev1_icon_angry'; }
}
