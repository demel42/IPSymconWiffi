<?php

declare(strict_types=1);

trait WiffiLocalLib
{
    public static $IS_INVALIDCONFIG = IS_EBASE + 1;
    public static $IS_MODULETYPEMISMATCH = IS_EBASE + 2;

    public static $WIFFI_MODULE_NONE = 0;
    public static $WIFFI_MODULE_WZ = 1;
    public static $WIFFI_MODULE_3 = 2;
    public static $AIRSNIFFER = 3;
    public static $AIRSNIFFER_MINI = 4;

    private function GetFormStatus()
    {
        $formStatus = [];
        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => self::$IS_INVALIDCONFIG, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid configuration)'];
        $formStatus[] = ['code' => self::$IS_MODULETYPEMISMATCH, 'icon' => 'error', 'caption' => 'Instance is inactive (wrong wiffi-module)'];

        return $formStatus;
    }
}
