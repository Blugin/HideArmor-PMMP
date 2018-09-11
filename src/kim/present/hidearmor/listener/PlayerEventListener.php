<?php

/*
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\hidearmor\listener;

use kim\present\hidearmor\HideArmor;
use pocketmine\event\Listener;
use pocketmine\event\player\{
	PlayerChangeSkinEvent, PlayerJoinEvent
};

class PlayerEventListener implements Listener{
	/** @var HideArmor */
	private $plugin;

	/**
	 * PlayerEventListener constructor.
	 *
	 * @param HideArmor $plugin
	 */
	public function __construct(HideArmor $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @priority LOWEST
	 *
	 * @param PlayerJoinEvent $event
	 */
	public function onPlayerJoinEvent(PlayerJoinEvent $event) : void{
		$this->plugin->updateArmorHide($event->getPlayer());
	}

	/**
	 * @priority LOWEST
	 *
	 * @param PlayerChangeSkinEvent $event
	 */
	public function onPlayerChangeSkinEvent(PlayerChangeSkinEvent $event) : void{
		$event->setNewSkin($this->plugin->applyToSkin($event->getPlayer()->getName(), $event->getNewSkin()));
	}
}