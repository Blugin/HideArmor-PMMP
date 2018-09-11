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

namespace kim\present\hidearmor;

use kim\present\hidearmor\lang\PluginLang;
use kim\present\hidearmor\listener\PlayerEventListener;
use pocketmine\command\{
	Command, CommandSender, PluginCommand
};
use pocketmine\entity\Skin;
use pocketmine\permission\{
	Permission, PermissionManager
};
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class HideArmor extends PluginBase{
	public const DEFAULT_GEOMETRY_DATA = [
		"bones" => [
			["name" => "body", "pivot" => [0, 24, 0], "cubes" => [["origin" => [-4, 12, -2], "size" => [8, 12, 4], "uv" => [16, 16]]]],
			["name" => "head", "pivot" => [0, 24, 0], "cubes" => [["origin" => [-4, 24, -4], "size" => [8, 8, 8], "uv" => [0, 0]]]],
			["name" => "hat", "pivot" => [0, 24, 0], "cubes" => [["origin" => [-4, 24, -4], "size" => [8, 8, 8], "uv" => [32, 0], "inflate" => 0.5]]],
			["name" => "rightArm", "pivot" => [-5, 22, 0], "cubes" => [["origin" => [-8, 12, -2], "size" => [4, 12, 4], "uv" => [40, 16]]]],
			["name" => "leftArm", "pivot" => [5, 22, 0], "cubes" => [["origin" => [4, 12, -2], "size" => [4, 12, 4], "uv" => [40, 16]]], "mirror" => true],
			["name" => "rightLeg", "pivot" => [-1.9, 12, 0], "cubes" => [["origin" => [-3.9, 0, -2], "size" => [4, 12, 4], "uv" => [0, 16]]]],
			["name" => "leftLeg", "pivot" => [1.9, 12, 0], "cubes" => [["origin" => [-0.1, 0, -2], "size" => [4, 12, 4], "uv" => [0, 16]]], "mirror" => true],
		]
	];
	public const MODEL_HIDE_ARMOR = "animationDontShowArmor";

	/** @var HideArmor */
	private static $instance;

	/**
	 * @return HideArmor
	 */
	public static function getInstance() : HideArmor{
		return self::$instance;
	}

	/** @var PluginLang */
	private $language;

	/** @var PluginCommand */
	private $command;

	/**
	 * Called when the plugin is loaded, before calling onEnable()
	 */
	public function onLoad() : void{
		self::$instance = $this;
	}

	/**
	 * Called when the plugin is enabled
	 */
	public function onEnable() : void{
		//Save default resources
		$this->saveResource("lang/eng/lang.ini", false);
		$this->saveResource("lang/kor/lang.ini", false);
		$this->saveResource("lang/language.list", false);

		//Load config file
		$config = $this->getConfig();

		//Load language file
		$this->language = new PluginLang($this, $config->getNested("settings.language"));
		$this->getLogger()->info($this->language->translate("language.selected", [$this->language->getName(), $this->language->getLang()]));

		//Register main command
		$this->command = new PluginCommand($config->getNested("command.name"), $this);
		$this->command->setPermission("hidearmor.cmd");
		$this->command->setAliases($config->getNested("command.aliases"));
		$this->command->setUsage($this->language->translate("commands.hidearmor.usage"));
		$this->command->setDescription($this->language->translate("commands.hidearmor.description"));
		$this->getServer()->getCommandMap()->register($this->getName(), $this->command);

		//Load permission's default value from config
		$permissions = PermissionManager::getInstance()->getPermissions();
		$defaultValue = $config->getNested("permission.main");
		if($defaultValue !== null){
			$permissions["hidearmor.cmd"]->setDefault(Permission::getByName($config->getNested("permission.main")));
		}

		//Create enabled data folder
		if(!file_exists($dataFolder = "{$this->getDataFolder()}enabled/")){
			mkdir($dataFolder, 0777, true);
		}

		//Register event listeners
		$this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener($this), $this);
	}

	/**
	 * @param CommandSender $sender
	 * @param Command       $command
	 * @param string        $label
	 * @param string[]      $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if($sender instanceof Player){
			$this->setEnabledTo($playerName = $sender->getName(), $whether = !$this->isEnabledTo($playerName));
			$this->updateArmorHide($sender);

			$sender->sendMessage($this->language->translate("commands.hidearmor." . ($whether ? "enable" : "disable")));
		}else{
			$sender->sendMessage($this->language->translate("commands.generic.onlyPlayer"));
		}
		return true;
	}

	/**
	 * @Override for multilingual support of the config file
	 *
	 * @return bool
	 */
	public function saveDefaultConfig() : bool{
		$resource = $this->getResource("lang/{$this->getServer()->getLanguage()->getLang()}/config.yml");
		if($resource === null){
			$resource = $this->getResource("lang/eng/config.yml");
		}

		if(!file_exists($configFile = "{$this->getDataFolder()}config.yml")){
			$ret = stream_copy_to_stream($resource, $fp = fopen($configFile, "wb")) > 0;
			fclose($fp);
			fclose($resource);
			return $ret;
		}
		return false;
	}

	/**
	 * @param string $playerName
	 *
	 * @return bool
	 */
	public function isEnabledTo(string $playerName) : bool{
		return file_exists("{$this->getDataFolder()}enabled/" . strtolower($playerName));
	}

	/**
	 * @param string $playerName
	 * @param bool   $whether
	 */
	public function setEnabledTo(string $playerName, bool $whether) : void{
		$fileName = "{$this->getDataFolder()}enabled/" . strtolower($playerName);
		if($whether){
			file_put_contents($fileName, "");
		}else{
			if(file_exists($fileName)){
				unlink($fileName);
			}
		}
	}

	/**
	 * @param string $playerName
	 * @param Skin   $skin
	 *
	 * @return Skin
	 */
	public function applyToSkin(string $playerName, Skin $skin) : Skin{
		$whether = $this->isEnabledTo($playerName);
		$geometryData = json_decode($skin->getGeometryData(), true);
		$geometryName = $skin->getGeometryName();
		if(!isset($geometryData[$geometryName])){
			$geometryData[$geometryName] = self::DEFAULT_GEOMETRY_DATA;
		}
		$newGeometryName = $geometryName . ($whether ? ".hide" : ".disable");
		$geometryData[$newGeometryName] = $geometryData[$geometryName];
		$geometryData[$newGeometryName][self::MODEL_HIDE_ARMOR] = $whether;

		return new Skin($skin->getSkinId(), $skin->getSkinData(), $skin->getCapeData(), $newGeometryName, json_encode($geometryData));
	}

	/**
	 * @param Player $player
	 */
	public function updateArmorHide(Player $player) : void{
		$player->setSkin($this->applyToSkin($player->getName(), $player->getSkin()));
		$player->sendSkin([$player]);
		$player->sendSkin($this->getServer()->getOnlinePlayers());
	}
}