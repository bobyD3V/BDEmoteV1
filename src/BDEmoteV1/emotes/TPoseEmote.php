<?php

declare(strict_types=1);

namespace BDEmoteV1\emotes;

final class TPoseEmote extends Emote{
	public function getKey() : string{ return 't_pose'; }
	public function getTitle() : string{ return 'T-Pose'; }
	public function getAnimation() : string{ return 'animation.humanoid.t-pose'; }
	public function getIconId() : string{ return 'bdemotev1_icon_tpose'; }
}
