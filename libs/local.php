<?php

declare(strict_types=1);

trait WiffiLocalLib
{
    public static $IS_MODULETYPEMISMATCH = IS_EBASE + 10;

    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        $formStatus[] = ['code' => self::$IS_MODULETYPEMISMATCH, 'icon' => 'error', 'caption' => 'Instance is inactive (wrong wiffi-module)'];

        return $formStatus;
    }

    public static $WIFFI_MODULE_NONE = 0;
    public static $WIFFI_MODULE_WZ = 1;
    public static $WIFFI_MODULE_3 = 2;
    public static $AIRSNIFFER = 3;
    public static $AIRSNIFFER_MINI = 4;

    private function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }

        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('Off'), 'Farbe' => -1],
            ['Wert' => true,  'Name' => $this->Translate('On'), 'Farbe' => 0xEE0000],
        ];
        $this->CreateVarProfile('Wiffi.MotionDetector', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);
        $associations = [
            ['Wert' => false, 'Name' => $this->Translate('Off'), 'Farbe' => -1],
            ['Wert' => true,  'Name' => $this->Translate('On'), 'Farbe' => 0xEE0000],
        ];
        $this->CreateVarProfile('Wiffi.NoiseDetector', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $this->CreateVarProfile('Wiffi.Azimut', VARIABLETYPE_INTEGER, ' °', 0, 0, 0, 0, '', [], $reInstall);
        $associations = [
            ['Wert' =>    0, 'Name' => '%d', 'Farbe' => 0x008000],
            ['Wert' => 1000, 'Name' => '%d', 'Farbe' => 0xFFFF00],
            ['Wert' => 1250, 'Name' => '%d', 'Farbe' => 0xFF8000],
            ['Wert' => 1300, 'Name' => '%d', 'Farbe' => 0xFF0000],
        ];
        $this->CreateVarProfile('Wiffi.CO2', VARIABLETYPE_INTEGER, ' ppm', 250, 2000, 0, 1, 'Gauge', $associations, $reInstall);
        $this->CreateVarProfile('Wiffi.CO2_Equ', VARIABLETYPE_INTEGER, ' ppm', 0, 5000, 0, 0, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('Wiffi.Elevation', VARIABLETYPE_INTEGER, ' °', 0, 0, 0, 0, '', [], $reInstall);
        $associations = [
            ['Wert' =>   0, 'Name' => '%d', 'Farbe' => 0x00E400],
            ['Wert' => 50, 'Name' => '%d', 'Farbe' => 0xFFFF00],
            ['Wert' => 100, 'Name' => '%d', 'Farbe' => 0xFF7E00],
            ['Wert' => 150, 'Name' => '%d', 'Farbe' => 0xFF0000],
            ['Wert' => 200, 'Name' => '%d', 'Farbe' => 0x99004C],
            ['Wert' => 300, 'Name' => '%d', 'Farbe' => 0x595959],
        ];
        $this->CreateVarProfile('Wiffi.IAQ', VARIABLETYPE_INTEGER, '', 0, 500, 0, 0, 'Fog', $associations, $reInstall);
        $associations = [
            ['Wert' => 1, 'Name' => $this->Translate('very good'), 'Farbe' => 0x00E400],
            ['Wert' => 2, 'Name' => $this->Translate('good'), 'Farbe' => 0xFFFF00],
            ['Wert' => 3, 'Name' => $this->Translate('satisfactory'), 'Farbe' => 0xFF7E00],
            ['Wert' => 4, 'Name' => $this->Translate('marginal'), 'Farbe' => 0xFF0000],
            ['Wert' => 5, 'Name' => $this->Translate('worse'), 'Farbe' => 0x99004C],
            ['Wert' => 6, 'Name' => $this->Translate('poor'), 'Farbe' => 0x595959],
        ];
        $this->CreateVarProfile('Wiffi.IAQ_note', VARIABLETYPE_INTEGER, '', 0, 500, 0, 0, 'Fog', $associations, $reInstall);
        $this->CreateVarProfile('Wiffi.min', VARIABLETYPE_INTEGER, ' m', 0, 0, 0, 0, 'Clock', [], $reInstall);
        $this->CreateVarProfile('Wiffi.Percent', VARIABLETYPE_INTEGER, ' %', 0, 0, 0, 0, '', [], $reInstall);
        $this->CreateVarProfile('Wiffi.sec', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Clock', [], $reInstall);
        $this->CreateVarProfile('Wiffi.Wifi', VARIABLETYPE_INTEGER, ' dBm', 0, 0, 0, 0, 'Intensity', [], $reInstall);

        $this->CreateVarProfile('Wiffi.absHumidity', VARIABLETYPE_FLOAT, ' g/m³', 10, 100, 0, 1, 'Drops', [], $reInstall);
        $this->CreateVarProfile('Wiffi.Dewpoint', VARIABLETYPE_FLOAT, ' °C', 0, 30, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('Wiffi.Heatindex', VARIABLETYPE_FLOAT, ' °C', 0, 100, 0, 0, 'Temperature', [], $reInstall);
        $this->CreateVarProfile('Wiffi.Humidity', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 0, 'Drops', [], $reInstall);
        $this->CreateVarProfile('Wiffi.Lux', VARIABLETYPE_FLOAT, ' lx', 0, 0, 0, 0, 'Sun', [], $reInstall);
        $this->CreateVarProfile('Wiffi.Particles', VARIABLETYPE_FLOAT, ' µg/m³', 0, 100, 0, 2, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('Wiffi.Pressure', VARIABLETYPE_FLOAT, ' mbar', 0, 0, 0, 0, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('Wiffi.RR0', VARIABLETYPE_FLOAT, '', 0, 1, 0, 2, 'Gauge', [], $reInstall);
        $this->CreateVarProfile('Wiffi.Temperatur', VARIABLETYPE_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature', [], $reInstall);
        $this->CreateVarProfile('Wiffi.VOC', VARIABLETYPE_FLOAT, '', 0, 0, 0, 2, 'Gauge', [], $reInstall);
    }
}
