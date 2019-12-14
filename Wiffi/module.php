<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

if (!defined('WIFFI_MODULE_NONE')) {
    define('WIFFI_MODULE_NONE', 0);
    define('WIFFI_MODULE_WZ', 1);
}

class Wiffi extends IPSModule
{
    use WiffiCommon;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('module_type', WIFFI_MODULE_NONE);
        $this->RegisterPropertyString('use_fields', '[]');

        $this->RegisterPropertyInteger('altitude', false);
        $this->RegisterPropertyBoolean('with_absolute_pressure', false);
        $this->RegisterPropertyBoolean('with_heatindex', false);

        $this->CreateVarProfile('Wiffi.Wifi', VARIABLETYPE_INTEGER, ' dBm', 0, 0, 0, 0, 'Intensity');

        $this->CreateVarProfile('Wiffi.sec', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Clock');
        $this->CreateVarProfile('Wiffi.min', VARIABLETYPE_INTEGER, ' m', 0, 0, 0, 0, 'Clock');
        $this->CreateVarProfile('Wiffi.hour', VARIABLETYPE_INTEGER, ' h', 0, 0, 0, 0, 'Clock');

        $this->CreateVarProfile('Wiffi.Temperatur', VARIABLETYPE_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature');
        $this->CreateVarProfile('Wiffi.Humidity', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 0, 'Drops');
        $this->CreateVarProfile('Wiffi.absHumidity', VARIABLETYPE_FLOAT, ' g/m³', 10, 100, 0, 0, 'Drops');
        $this->CreateVarProfile('Wiffi.Pressure', VARIABLETYPE_FLOAT, ' mbar', 0, 0, 0, 0, 'Gauge');
        $this->CreateVarProfile('Wiffi.Heatindex', VARIABLETYPE_FLOAT, ' °C', 0, 100, 0, 0, 'Temperature');
        $this->CreateVarProfile('Wiffi.Dewpoint', VARIABLETYPE_FLOAT, ' °C', 0, 30, 0, 0, 'Drops');
        $this->CreateVarProfile('Wiffi.Pressure', VARIABLETYPE_FLOAT, ' mbar', 500, 1200, 0, 0, 'Gauge');
        $this->CreateVarProfile('Wiffi.Lux', VARIABLETYPE_FLOAT, ' lx', 0, 0, 0, 0, 'Sun');
        $this->CreateVarProfile('Wiffi.Azimut', VARIABLETYPE_INTEGER, ' °', 0, 0, 0, 0, '');
        $this->CreateVarProfile('Wiffi.Elevation', VARIABLETYPE_INTEGER, ' °', 0, 0, 0, 0, '');

        $associations = [];
        $associations[] = ['Wert' =>    0, 'Name' => '%d', 'Farbe' => 0x008000];
        $associations[] = ['Wert' => 1000, 'Name' => '%d', 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 1250, 'Name' => '%d', 'Farbe' => 0xFF8000];
        $associations[] = ['Wert' => 1300, 'Name' => '%d', 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('Wiffi.CO2', VARIABLETYPE_INTEGER, ' ppm', 250, 2000, 0, 1, 'Gauge', $associations);

        $associations = [];
        $associations[] = ['Wert' => false, 'Name' => $this->Translate('Off'), 'Farbe' => -1];
        $associations[] = ['Wert' => true,  'Name' => $this->Translate('On'), 'Farbe' => 0xEE0000];
        $this->CreateVarProfile('Wiffi.NoiseDetector', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => false, 'Name' => $this->Translate('Off'), 'Farbe' => -1];
        $associations[] = ['Wert' => true,  'Name' => $this->Translate('On'), 'Farbe' => 0xEE0000];
        $this->CreateVarProfile('Wiffi.MotionDetector', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations);

        $this->RequireParent('{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $status = IS_ACTIVE;

        $vpos = 1;
        $identList = [];
        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $fieldMap = $this->getFieldMap();
        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            if ($use) {
                $identList[] = $ident;
            }
            $desc = $this->GetArrayElem($map, 'desc', '');
            $vartype = $this->GetArrayElem($map, 'type', '');
            $varprof = $this->GetArrayElem($map, 'prof', '');
            $this->SendDebug(__FUNCTION__, 'register variable: ident=' . $ident . ', vartype=' . $vartype . ', varprof=' . $varprof . ', use=' . $this->bool2str($use), 0);
            $this->MaintainVariable($ident, $this->Translate($desc), $vartype, $varprof, $vpos++, $use);
        }

        $vpos = 80;

        $module_type = $this->ReadPropertyInteger('module_type');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        if ($with_heatindex) {
            switch ($module_type) {
                case WIFFI_MODULE_WZ:
                    if (!(in_array('wz_temp', $identList) && in_array('wz_feuchte_rel', $identList))) {
                        $this->SendDebug(__FUNCTION__, '"with_heatindex" needs "wz_temp", "wz_feuchte_rel"', 0);
                        $with_heatindex = false;
                        $status = IS_INVALIDCONFIG;
                    }
                    break;
                default:
                    $this->SendDebug(__FUNCTION__, '"with_heatindex" not available for module_type ' . $module_type, 0);
                    $status = IS_INVALIDCONFIG;
                    break;
            }
        }
        $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), VARIABLETYPE_FLOAT, 'Wiffi.Heatindex', $vpos++, $with_heatindex);

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        if ($with_absolute_pressure) {
            switch ($module_type) {
                case WIFFI_MODULE_WZ:
                    if (!(in_array('wz_temp', $identList) && in_array('wz_feuchte_rel', $identList))) {
                        $altitude = $this->ReadPropertyInteger('altitude');
                        if (!(in_array('wz_baro', $identList) && in_array('wz_temp', $identList) && $altitude > 0)) {
                            $this->SendDebug(__FUNCTION__, '"with_absolute_pressure" needs "wz_baro", "wz_temp" and "altitude"', 0);
                            $with_absolute_pressure = false;
                            $status = IS_INVALIDCONFIG;
                        }
                    }
                    break;
                default:
                    $this->SendDebug(__FUNCTION__, '"with_absolute_pressure" not available for module_type ' . $module_type, 0);
                    $status = IS_INVALIDCONFIG;
                    break;
            }
        }
        $this->MaintainVariable('AbsolutePressure', $this->Translate('Absolute pressure'), VARIABLETYPE_FLOAT, 'Weatherman.Pressure', $vpos++, $with_absolute_pressure);

        $vpos = 100;

        $this->MaintainVariable('LastMessage', $this->Translate('Last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('LastUpdate', $this->Translate('Last update'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('Uptime', $this->Translate('Uptime'), VARIABLETYPE_INTEGER, 'Wiffi.sec', $vpos++, true);
        $this->MaintainVariable('WifiStrength', $this->Translate('wifi-signal'), VARIABLETYPE_INTEGER, 'Wiffi.Wifi', $vpos++, true);

        $this->SetStatus($status);
    }

    public function GetConfigurationForm()
    {
        $formElements = $this->GetFormElements();
        $formActions = $this->GetFormActions();
        $formStatus = $this->GetFormStatus();

        $form = json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
        if ($form == '') {
            $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg(), 0);
            $this->SendDebug(__FUNCTION__, '=> formElements=' . print_r($formElements, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formActions=' . print_r($formActions, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formStatus=' . print_r($formStatus, true), 0);
        }
        return $form;
    }

    protected function GetFormElements()
    {
        $formElements = [];
        $formElements[] = ['type' => 'Label', 'label' => 'Wiffi'];

        $opts_module_type = [];
        $opts_module_type[] = ['label' => $this->Translate('none'), 'value' => WIFFI_MODULE_NONE];
        $opts_module_type[] = ['label' => $this->Translate('Wiffi-WZ'), 'value' => WIFFI_MODULE_WZ];

        $formElements[] = ['type' => 'Select', 'name' => 'module_type', 'caption' => 'Module type', 'options' => $opts_module_type];

        $values = [];
        $fieldMap = $this->getFieldMap();
        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            $desc = $this->GetArrayElem($map, 'desc', '');
            $values[] = ['ident' => $ident, 'desc' => $this->Translate($desc)];
        }

        $columns = [];
        $columns[] = [
            'caption' => 'Name',
            'name'    => 'ident',
            'width'   => '200px',
            'save'    => true
        ];
        $columns[] = [
            'caption' => 'Description',
            'name'    => 'desc',
            'width'   => 'auto'
        ];
        $columns[] = [
            'caption' => 'use',
            'name'    => 'use',
            'width'   => '100px',
            'edit'    => [
                'type' => 'CheckBox'
            ]
        ];

        $items = [];

        $items[] = [
            'type'     => 'List',
            'name'     => 'use_fields',
            'caption'  => 'available variables',
            'rowCount' => count($values),
            'add'      => false,
            'delete'   => false,
            'columns'  => $columns,
            'values'   => $values
        ];

        $formElements[] = ['type' => 'ExpansionPanel', 'items' => $items, 'caption' => 'Variables'];

        $items = [];

        $items[] = [
            'type'    => 'NumberSpinner',
            'name'    => 'altitude',
            'caption' => 'Module altitude'
        ];

        $items[] = [
            'type'    => 'Label',
            'caption' => 'additional Calculations'
        ];

        $module_type = $this->ReadPropertyInteger('module_type');
        switch ($module_type) {
            case WIFFI_MODULE_WZ:
                $items[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'with_heatindex',
                    'caption' => ' ... Heatindex (needs "wz_temp", "wz_feuchte_rel")'
                ];

                $items[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'with_absolute_pressure',
                    'caption' => ' ... absolute pressure (needs "wz_baro", "wz_temp" and the altitude)'
                ];
                break;
            default:
                break;
        }

        $formElements[] = ['type' => 'ExpansionPanel', 'items' => $items, 'caption' => 'Options'];

        return $formElements;
    }

    protected function GetFormActions()
    {
        $formActions = [];
        if (IPS_GetKernelVersion() < 5.2) {
            $formActions[] = [
                'type'    => 'Button',
                'caption' => 'Module description',
                'onClick' => 'echo "https://github.com/demel42/IPSymconWiffi/blob/master/README.md";'
            ];
        }

        return $formActions;
    }

    public function ReceiveData($msg)
    {
        $jmsg = json_decode($msg, true);
        $data = utf8_decode($jmsg['Buffer']);

        $rdata = $this->GetMultiBuffer('Data');
        if (substr($data, -1) == chr(4)) {
            $ndata = $rdata . substr($data, 0, -1);
            $jdata = json_decode($ndata, true);
            if ($jdata == '') {
                $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg() . ', data=' . $ndata, 0);
            } else {
                $this->ProcessData($jdata);
            }
            $ndata = '';
        } else {
            $ndata = $rdata . $data;
        }
        $this->SetMultiBuffer('Data', $ndata);
    }

    private function ProcessData($jdata)
    {
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($jdata, true), 0);

        $modultyp = $this->GetArrayElem($jdata, 'modultyp', '');

        $module_type = $this->ReadPropertyInteger('module_type');
        switch ($module_type) {
            case WIFFI_MODULE_WZ:
                if ($modultyp != 'wiffi-wz') {
                    $this->SendDebug(__FUNCTION__, 'wrong module-type "' . $modultyp . '"', 0);
                    $this->SetStatus(IS_MODULETYPEMISMATCH);
                    return;
                }
                break;
            default:
                return;
        }

        $systeminfo = $this->GetArrayElem($jdata, 'Systeminfo', '');
        $this->SendDebug(__FUNCTION__, 'Systeminfo=' . print_r($systeminfo, true), 0);

        switch ($module_type) {
            case WIFFI_MODULE_WZ:
                $tstamp = 0;
                $this->SetValue('LastMessage', $tstamp);

                $uptime = $this->GetArrayElem($jdata, 'Systeminfo.millis_seit_reset', 0);
                $this->SetValue('Uptime', $uptime / 1000);

                $rssi = $this->GetArrayElem($jdata, 'Systeminfo.WLAN_Signal_dBm', '');
                $this->SetValue('WifiStrength', $rssi);

                $this->SendDebug(__FUNCTION__, 'modultyp=' . $modultyp . ', tstamp=' . date('d.m.Y H:i:s', $tstamp) . ', rssi=' . $rssi . ', uptime=' . $uptime . 's', 0);
                break;
        }

        $fieldMap = $this->getFieldMap();
        $this->SendDebug(__FUNCTION__, 'fieldMap="' . print_r($fieldMap, true) . '"', 0);
        $identV = [];
        foreach ($fieldMap as $map) {
            $identV[] = $this->GetArrayElem($map, 'ident', '');
        }
        $identS = implode(',', $identV);
        $this->SendDebug(__FUNCTION__, 'known idents=' . $identS, 0);

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $use_fieldsV = [];
        foreach ($use_fields as $field) {
            if ((bool) $this->GetArrayElem($field, 'use', false)) {
                $use_fieldsV[] = $this->GetArrayElem($field, 'ident', '');
            }
        }
        $use_fieldsS = implode(',', $use_fieldsV);
        $this->SendDebug(__FUNCTION__, 'use fields=' . $use_fieldsS, 0);

        $vars = $this->GetArrayElem($jdata, 'vars', '');
        foreach ($vars as $var) {
            $this->SendDebug(__FUNCTION__, 'var=' . print_r($var, true), 0);
            $ident = $this->GetArrayElem($var, 'homematic_name', '');
            $value = $this->GetArrayElem($var, 'value', '');

            $found = false;

            $vartype = VARIABLETYPE_STRING;
            $varprof = '';
            foreach ($fieldMap as $map) {
                if ($ident == $this->GetArrayElem($map, 'ident', '')) {
                    $found = true;
                    $vartype = $this->GetArrayElem($map, 'type', '');
                    $varprof = $this->GetArrayElem($map, 'prof', '');
                    break;
                }
            }

            if (!$found) {
                $this->SendDebug(__FUNCTION__, 'unknown ident "' . $ident . '", value=' . $value, 0);
                $this->LogMessage(__FUNCTION__ . ': unknown ident ' . $ident . ', value=' . $value, KL_NOTIFY);
                continue;
            }

            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    if (!$use) {
                        $this->SendDebug(__FUNCTION__, 'ignore ident "' . $ident . '", value=' . $value, 0);
                        continue;
                    }

                    $this->SendDebug(__FUNCTION__, 'found ident "' . $ident . '", value=' . $value, 0);

                    if ($ident == 'wz_luftdrucktrend') {
                        $value = str_replace('_', ' ', $value);
                    }

                    switch ($vartype) {
                        case VARIABLETYPE_INTEGER:
                            $this->SetValue($ident, intval($value));
                            break;
                        default:
                            $this->SetValue($ident, $value);
                            break;
                    }
                    break;
                }
            }
        }

        switch ($module_type) {
            case WIFFI_MODULE_WZ:
                $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
                if ($with_heatindex) {
                    $wz_temperatur = $this->GetValue('wz_temperatur');
                    $wz_feuchte_rel = $this->GetValue('wz_feuchte_rel');
                    $v = $this->calcHeatindex($wz_temperatur, $wz_feuchte_rel);
                    $this->SetValue('Heatindex', $v);
                }

                $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
                if ($with_absolute_pressure) {
                    $wz_baro = $this->GetValue('wz_baro');
                    $wz_temp = $this->GetValue('wz_temp');
                    $altitude = $this->ReadPropertyInteger('altitude');
                    $v = $this->calcAbsolutePressure($wz_baro, $wz_temp, $altitude);
                    $this->SetValue('AbsolutePressure', $v);
                }
                break;
            default:
                break;
        }

        $this->SetValue('LastUpdate', time());
    }

    private function getFieldMap()
    {
        $map_wz = [
            [
                'ident'  => 'wz_ip',
                'desc'   => 'IP-address',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'wz_co2',
                'desc'   => 'CO2',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.CO2',
            ],
            [
                'ident'  => 'wz_temp',
                'desc'   => 'Temperature',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Temperatur',
            ],
            [
                'ident'  => 'wz_taupunkt',
                'desc'   => 'Dewpoint',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Dewpoint',
            ],
            [
                'ident'  => 'wz_feuchte',
                'desc'   => 'Humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Humidity',
            ],
            [
                'ident'  => 'wz_feuchte_abs',
                'desc'   => 'Absolute humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.absHumidity',
            ],
            [
                'ident'  => 'wz_baro',
                'desc'   => 'Air pressure',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Pressure',
            ],
            [
                'ident'  => 'wz_luftdrucktrend',
                'desc'   => 'Trend of air pressure',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'wz_lux',
                'desc'   => 'Brightness',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Lux',
            ],
            [
                'ident'  => 'wz_motion_left',
                'desc'   => 'Left motion detected',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Wiffi.MotionDetector',
            ],
            [
                'ident'  => 'wz_motion_right',
                'desc'   => 'Right motion detected',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Wiffi.MotionDetector',
            ],
            [
                'ident'  => 'wz_motion',
                'desc'   => 'Motion detected',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Wiffi.MotionDetector',
            ],
            [
                'ident'  => 'wz_noise',
                'desc'   => 'Noise detected',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Wiffi.NoiseDetector',
            ],
            [
                'ident'  => 'wz_elevation',
                'desc'   => 'Sun elevation',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.Elevation',
            ],
            [
                'ident'  => 'wz_azimut',
                'desc'   => 'Sun azimut',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.Azimut',
            ],
            /*
'ident'		=> 'wz_buzzer", "desc": "Buzzer", "type": "boolean"
'ident'		=> 'wz_relais", "desc": "Relais", "type": "boolean",
             */
        ];

        $module_type = $this->ReadPropertyInteger('module_type');
        switch ($module_type) {
            case WIFFI_MODULE_WZ:
                $map = $map_wz;
                break;
            default:
                $map = [];
        }
        return $map;
    }

    // Luftdruck (Meereshöhe) in absoluten (lokaler) Luftdruck umrechnen
    //   Quelle: https://rechneronline.de/barometer/hoehe.php
    private function calcAbsolutePressure(float $pressure, float $temp, int $altitude)
    {
        // Temperaturgradient (geschätzt)
        $TG = 0.0065;

        // Höhe = Differenz Meereshöhe zu Standort
        $ad = $altitude * -1;

        // Temperatur auf Meereshöhe herunter rechnen
        //     Schätzung: Temperatur auf Meereshöhe = Temperatur + Temperaturgradient * Höhe
        $T = $temp + $TG * $ad;
        // Temperatur in Kelvin
        $TK = $T + 273.15;

        // Luftdruck auf Meereshöhe = Barometeranzeige / (1-Temperaturgradient*Höhe/Temperatur auf Meereshöhe in Kelvin)^(0,03416/Temperaturgradient)
        $AP = $pressure / pow((1 - $TG * $ad / $TK), (0.03416 / $TG));

        return $AP;
    }
}
