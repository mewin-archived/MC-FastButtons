<?php
/*
 * Copyright (C) 2015 mewin <mewin@mewin.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace mewin;

use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerActions;
use FML\ManiaLink;
use FML\Controls\Frame;
use FML\Controls\Quads\Quad_UIConstructionBullet_Buttons;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\Controls\Quads\Quad_Icons64x64_2;
use FML\Controls\Quad;
use FML\Controls\Label;

class FastButtons implements Plugin, ManialinkPageAnswerListener, CallbackListener
{
    const ID                    = 65;
    const INFECTION_ID          = 64;
    const VERSION               = "0.1";
    const ACTION_KICK_PLAYER    = "FastButtons.Kick";
    const ACTION_BAN_PLAYER     = "FastButtons.Ban";
    const ACTION_MUTE_PLAYER    = "FastButtons.Mute";
    const ACTION_WARN_PLAYER    = "FastButtons.Warn";
    const ACTION_SPEC_PLAYER    = "FastButtons.Spectate";
    const ACTION_INFECT_PLAYER  = "FastButtons.Infect";
    const MLID_FB               = "FastButtons.Widget";
    const SETTING_FB_POSX       = "Buttons Position (X)";
    const SETTING_FB_POSY       = "Buttons Position (Y)";
    const SETTING_FB_WIDTH      = "Buttons Width";
    const SETTING_FB_HEIGHT     = "Buttons Height";
    const SETTING_FB_GAP        = "Buttons Gap";
    const SETTING_FB_WARN       = "Warn Button";
    const SETTING_FB_MUTE       = "Mute Button";
    const SETTING_FB_KICK       = "Kick Button";
    const SETTING_FB_SPEC       = "Spectate Button";
    const SETTING_FB_BAN        = "Ban Button";
    const SETTING_FB_INFECT     = "Infect Button";
    const SETTING_PERMISSION_INFECT = "Infect";
    
    private $maniaControl;
    
    public static function getDescription()
    {
        return "Adds buttons that let you execute actions for the player you are currently spectating.";
    }

    public static function getAuthor()
    {
        return "mewin";
    }

    public static function getId()
    {
        return self::ID;
    }

    public static function getName()
    {
        return "FastButtons";
    }

    public static function getVersion()
    {
        return self::VERSION;
    }
    
    public function load(ManiaControl $maniaControl)
    {
        $this->maniaControl = $maniaControl;
        $this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_MUTE_PLAYER, $this, "action_mutePlayer");
        $this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SPEC_PLAYER, $this, "action_specPlayer");
        $this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_WARN_PLAYER, $this, "action_warnPlayer");
        $this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_KICK_PLAYER, $this, "action_kickPlayer");
        $this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_BAN_PLAYER, $this, "action_banPlayer");
        $this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_INFECT_PLAYER, $this, "action_infectPlayer");
        
        // Register for callbacks
        $this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
                
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_POSX, 110.);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_POSY, -70.);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_WIDTH, 6.);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_HEIGHT, 6.);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_GAP, 1);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_WARN, true);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_MUTE, true);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_KICK, true);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_SPEC, true);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_BAN, true);
        $this->maniaControl->settingManager->initSetting($this, self::SETTING_FB_INFECT, true);

        $this->displayWidget();
		
		return true;
    }

    public function unload()
    {
		$this->maniaControl->manialinkManager->hideManialink(self::MLID_FB);
		$this->maniaControl->manialinkManager->unregisterManialinkPageAnswerListener($this);
		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
    }
    
    private function displayWidget(Player $player = null)
    {
        if ($player == null)
        {
            $players = $this->maniaControl->playerManager->getPlayers();
            foreach ($players as $k => $player)
            {
                $this->displayWidget($player);
            }
			return;
        }
        
        $posX       = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_POSX);
        $posY       = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_POSY);
        $width      = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_WIDTH);
        $height     = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_HEIGHT);
        $gap        = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_GAP);
        $showWarn   = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_WARN);
        $showMute   = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_MUTE);
        $showKick   = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_KICK);
        $showSpec   = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_SPEC);
        $showBan    = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_BAN);
        $showInfect = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FB_INFECT);
        
        $showWarn &= $this->maniaControl->authenticationManager->checkPermission($player, PlayerActions::SETTING_PERMISSION_WARN_PLAYER);
        $showMute &= $this->maniaControl->authenticationManager->checkPermission($player, PlayerActions::SETTING_PERMISSION_MUTE_PLAYER);
        $showKick &= $this->maniaControl->authenticationManager->checkPermission($player, PlayerActions::SETTING_PERMISSION_KICK_PLAYER);
        $showSpec &= $this->maniaControl->authenticationManager->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_SPEC);
        $showBan  &= $this->maniaControl->authenticationManager->checkPermission($player, PlayerActions::SETTING_PERMISSION_BAN_PLAYER);
        $showInfect &= $this->maniaControl->pluginManager->isPluginIdInstalled(self::INFECTION_ID) && $this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_INFECT);
        
        $quadStyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
        $quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
        
        if (!$showWarn && !$showMute && !$showKick && !$showSpec && !$showBan && !$showInfect)
        {
            return;
        }
        
        $ml = new ManiaLink(self::MLID_FB);
        $frame = new Frame();
        $ml->add($frame);
        $frame->setPosition($posX, $posY);
        $bgQuad = new Quad();
        $frame->add($bgQuad);
		$bgQuad->setHAlign($bgQuad::LEFT);
		$bgQuad->setVAlign($bgQuad::CENTER);
        $bgQuad->setStyles($quadStyle, $quadSubstyle);
        
        $x = $gap;
        $y = 0;
		
		$label = new Label();
		$label->setAlign($label::CENTER, $label::BOTTOM);
		$label->setTextSize(2);
		$label->setTextColor("0f0");
		$frame->add($label);
        
        if ($showMute)
        {
            $quad = new Quad_UIConstructionBullet_Buttons();
            $quad->setSubStyle(Quad_UIConstructionBullet_Buttons::SUBSTYLE_SoundMode);
            $quad->setSize($width, $height);
            $quad->setPosition($x, $y);
            $quad->setAction(self::ACTION_MUTE_PLAYER);
			$quad->setHAlign($quad::LEFT);
			$quad->setVAlign($quad::CENTER);
			$quad->addTooltipLabelFeature($label, "(un-)mute player");
			$frame->add($quad);
            $x += $width + $gap;
        }
        
        if ($showSpec)
        {
            $quad = new Quad_UIConstruction_Buttons();
            $quad->setSubStyle(Quad_UIConstruction_Buttons::SUBSTYLE_Camera);
            $quad->setSize($width, $height);
            $quad->setPosition($x, $y);
            $quad->setAction(self::ACTION_SPEC_PLAYER);
			$quad->setHAlign($quad::LEFT);
			$quad->addTooltipLabelFeature($label, "force player to spectate");
			$frame->add($quad);
            $x += $width + $gap;
        }
        
        if ($showWarn)
        {
            $quad = new Quad_UIConstruction_Buttons();
            $quad->setSubStyle(Quad_UIConstruction_Buttons::SUBSTYLE_Validate_Step3);
            $quad->setSize($width, $height);
            $quad->setPosition($x, $y);
            $quad->setAction(self::ACTION_WARN_PLAYER);
			$quad->setHAlign($quad::LEFT);
			$quad->addTooltipLabelFeature($label, "warn player");
			$frame->add($quad);
            $x += $width + $gap;
        }
        
        if ($showKick)
        {
            $quad = new Quad_UIConstruction_Buttons();
            $quad->setSubStyle(Quad_UIConstruction_Buttons::SUBSTYLE_Validate_Step2);
            $quad->setSize($width, $height);
            $quad->setPosition($x, $y);
            $quad->setAction(self::ACTION_KICK_PLAYER);
			$quad->setHAlign($quad::LEFT);
			$quad->addTooltipLabelFeature($label, "kick player");
			$frame->add($quad);
            $x += $width + $gap;
        }
        
        if ($showBan)
        {
            $quad = new Quad_UIConstruction_Buttons();
            $quad->setSubStyle(Quad_UIConstruction_Buttons::SUBSTYLE_Validate_Step1);
            $quad->setSize($width, $height);
            $quad->setPosition($x, $y);
            $quad->setAction(self::ACTION_BAN_PLAYER);
			$quad->setHAlign($quad::LEFT);
			$quad->addTooltipLabelFeature($label, "ban player");
			$frame->add($quad);
            $x += $width + $gap;
        }
        
        if ($showInfect)
        {
            $quad = new Quad_Icons64x64_2();
            $quad->setSubStyle(Quad_Icons64x64_2::SUBSTYLE_UnknownElimination);
            $quad->setSize($width, $height);
            $quad->setPosition($x, $y);
            $quad->setAction(self::ACTION_INFECT_PLAYER);
			$quad->setHAlign($quad::LEFT);
			$quad->addTooltipLabelFeature($label, "force player to infecteds");
			$frame->add($quad);
            $x += $width + $gap;
        }
        
        $bgQuad->setSize($x + $gap, $height + 2 * $gap);
		$label->setPosition(($x - $gap) / 2, -10);
		
		// Send manialink
		$this->maniaControl->manialinkManager->sendManialink($ml, $player->login);
    }
    
    public function action_mutePlayer(array $callback, Player $player)
    {
        if (!$player->isSpectator)
        {
            $this->maniaControl->chat->sendError("You must be spectating.", $player->login);
            return;
        }
        
        $target = $this->getPlayerById($player->currentTargetId);
        if ($target == null)
        {
            $this->maniaControl->chat->sendError("You must be spectating a player.", $player->login);
            return;
        }
        
        if ($this->maniaControl->playerManager->playerActions->isPlayerMuted($target->login))
        {
            $this->maniaControl->playerManager->playerActions->unMutePlayer($player->login, $target->login);
        }
        else
        {
            $this->maniaControl->playerManager->playerActions->mutePlayer($player->login, $target->login);
        }
    }
    
    public function action_specPlayer(array $callback, Player $player)
    {
        if (!$player->isSpectator)
        {
            $this->maniaControl->chat->sendError("You must be spectating.", $player->login);
            return;
        }
        
        $target = $this->getPlayerById($player->currentTargetId);
        if ($target == null)
        {
            $this->maniaControl->chat->sendError("You must be spectating a player.", $player->login);
            return;
        }
        
        $this->maniaControl->playerManager->playerActions->forcePlayerToSpectator($player->login, $target->login);
    }
    
    public function action_warnPlayer(array $callback, Player $player)
    {
        if (!$player->isSpectator)
        {
            $this->maniaControl->chat->sendError("You must be spectating.", $player->login);
            return;
        }
        
        $target = $this->getPlayerById($player->currentTargetId);
        if ($target == null)
        {
            $this->maniaControl->chat->sendError("You must be spectating a player.", $player->login);
            return;
        }
        
        $this->maniaControl->playerManager->playerActions->warnPlayer($player->login, $target->login);
    }
    
    public function action_kickPlayer(array $callback, Player $player)
    {
        if (!$player->isSpectator)
        {
            $this->maniaControl->chat->sendError("You must be spectating.", $player->login);
            return;
        }
        
        $target = $this->getPlayerById($player->currentTargetId);
        if ($target == null)
        {
            $this->maniaControl->chat->sendError("You must be spectating a player.", $player->login);
            return;
        }
        
        $this->maniaControl->playerManager->playerActions->kickPlayer($player->login, $target->login);
    }
    
    public function action_banPlayer(array $callback, Player $player)
    {
        if (!$player->isSpectator)
        {
            $this->maniaControl->chat->sendError("You must be spectating.", $player->login);
            return;
        }
        
        $target = $this->getPlayerById($player->currentTargetId);
        if ($target == null)
        {
            $this->maniaControl->chat->sendError("You must be spectating a player.", $player->login);
            return;
        }
        
        $this->maniaControl->playerManager->playerActions->banPlayer($player->login, $target->login);
    }
    
    public function action_infectPlayer(array $callback, Player $player)
    {
        if (!$player->isSpectator)
        {
            $this->maniaControl->chat->sendError("You must be spectating.", $player->login);
            return;
        }
        
        $target = $this->getPlayerById($player->currentTargetId);
        if ($target == null)
        {
            $this->maniaControl->chat->sendError("You must be spectating a player.", $player->login);
            return;
        }
        
        $plugin = $this->maniaControl->pluginManager->getPlugin("mewin\\Infection");
        if ($plugin !== false)
        {
            $plugin->command_Infect(array(1 => array(2 => "//infect " . $target->login)), $player);
        }
    }
	
	private function getPlayerById($id)
	{
		foreach ($this->maniaControl->playerManager->getPlayers() as $player)
		{
			if ($player->pid === $id)
			{
				return $player;
			}
		}
		
		return null;
	}

    public static function prepare(ManiaControl $maniaControl)
    {
        
    }
    
    public function handlePlayerConnect(Player $player)
    {
        $this->displayWidget($player);
    }
}
