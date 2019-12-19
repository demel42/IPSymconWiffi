<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

if (!defined('WIFFI_MODULE_NONE')) {
    define('WIFFI_MODULE_NONE', 0);
    define('WIFFI_MODULE_WZ', 1);
    define('WIFFI_MODULE_3', 2);
}

class Wiffi extends IPSModule
{
    use WiffiCommon;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('module_type', WIFFI_MODULE_NONE);
        $this->RegisterPropertyString('use_fields', '[]');
        $this->RegisterPropertyString('fields', '[]');

        $this->RegisterPropertyInteger('altitude', false);
        $this->RegisterPropertyBoolean('with_absolute_pressure', false);
        $this->RegisterPropertyBoolean('with_heatindex', false);

        $associations = [];
        $associations[] = ['Wert' => false, 'Name' => $this->Translate('Off'), 'Farbe' => -1];
        $associations[] = ['Wert' => true,  'Name' => $this->Translate('On'), 'Farbe' => 0xEE0000];
        $this->CreateVarProfile('Wiffi.NoiseDetector', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => false, 'Name' => $this->Translate('Off'), 'Farbe' => -1];
        $associations[] = ['Wert' => true,  'Name' => $this->Translate('On'), 'Farbe' => 0xEE0000];
        $this->CreateVarProfile('Wiffi.MotionDetector', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations);

        $this->CreateVarProfile('Wiffi.Wifi', VARIABLETYPE_INTEGER, ' dBm', 0, 0, 0, 0, 'Intensity');
        $this->CreateVarProfile('Wiffi.sec', VARIABLETYPE_INTEGER, ' s', 0, 0, 0, 0, 'Clock');
        $this->CreateVarProfile('Wiffi.min', VARIABLETYPE_INTEGER, ' m', 0, 0, 0, 0, 'Clock');
        $this->CreateVarProfile('Wiffi.Azimut', VARIABLETYPE_INTEGER, ' °', 0, 0, 0, 0, '');
        $this->CreateVarProfile('Wiffi.Elevation', VARIABLETYPE_INTEGER, ' °', 0, 0, 0, 0, '');
        $this->CreateVarProfile('Wiffi.Percent', VARIABLETYPE_INTEGER, ' %', 0, 0, 0, 0, '');

        $associations = [];
        $associations[] = ['Wert' =>    0, 'Name' => '%d', 'Farbe' => 0x008000];
        $associations[] = ['Wert' => 1000, 'Name' => '%d', 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 1250, 'Name' => '%d', 'Farbe' => 0xFF8000];
        $associations[] = ['Wert' => 1300, 'Name' => '%d', 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('Wiffi.CO2', VARIABLETYPE_INTEGER, ' ppm', 250, 2000, 0, 1, 'Gauge', $associations);

        $associations = [];
        $associations[] = ['Wert' =>   0, 'Name' => '%d', 'Farbe' => 0x00E400];
        $associations[] = ['Wert' =>  50, 'Name' => '%d', 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 100, 'Name' => '%d', 'Farbe' => 0xFF7E00];
        $associations[] = ['Wert' => 150, 'Name' => '%d', 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 200, 'Name' => '%d', 'Farbe' => 0x99004C];
        $associations[] = ['Wert' => 300, 'Name' => '%d', 'Farbe' => 0x595959];
        $this->CreateVarProfile('Wiffi.IAQ', VARIABLETYPE_INTEGER, '', 0, 500, 0, 0, 'Fog', $associations);

        $this->CreateVarProfile('Wiffi.Temperatur', VARIABLETYPE_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature');
        $this->CreateVarProfile('Wiffi.Humidity', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 0, 'Drops');
        $this->CreateVarProfile('Wiffi.absHumidity', VARIABLETYPE_FLOAT, ' g/m³', 10, 100, 0, 0, 'Drops');
        $this->CreateVarProfile('Wiffi.Pressure', VARIABLETYPE_FLOAT, ' mbar', 0, 0, 0, 0, 'Gauge');
        $this->CreateVarProfile('Wiffi.Heatindex', VARIABLETYPE_FLOAT, ' °C', 0, 100, 0, 0, 'Temperature');
        $this->CreateVarProfile('Wiffi.Dewpoint', VARIABLETYPE_FLOAT, ' °C', 0, 30, 0, 0, 'Drops');
        $this->CreateVarProfile('Wiffi.Lux', VARIABLETYPE_FLOAT, ' lx', 0, 0, 0, 0, 'Sun');
        $this->CreateVarProfile('Wiffi.VOC', VARIABLETYPE_FLOAT, '', 0, 0, 0, 2, 'Gauge');

        $this->RequireParent('{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $status = IS_ACTIVE;

        $module_type = $this->ReadPropertyInteger('module_type');

        $vpos = 1;
        $varList = [];

        $identList = [];
        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $fieldMap = $this->getFieldMap($module_type);
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
            $varList[] = $ident;
        }

        $vpos = 80;

        $module_type = $this->ReadPropertyInteger('module_type');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        if ($with_heatindex) {
            switch ($module_type) {
                case WIFFI_MODULE_WZ:
                case WIFFI_MODULE_3:
                    if (!(in_array('temp', $identList) && in_array('feuchte_rel', $identList))) {
                        $this->SendDebug(__FUNCTION__, '"with_heatindex" needs "temp", "feuchte_rel"', 0);
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
                case WIFFI_MODULE_3:
                    if (!(in_array('temp', $identList) && in_array('feuchte_rel', $identList))) {
                        $altitude = $this->ReadPropertyInteger('altitude');
                        if (!(in_array('baro', $identList) && in_array('temp', $identList) && $altitude > 0)) {
                            $this->SendDebug(__FUNCTION__, '"with_absolute_pressure" needs "baro", "temp" and "altitude"', 0);
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

        $objList = [];
        $this->findVariables($this->InstanceID, $objList);
        foreach ($objList as $obj) {
            $ident = $obj['ObjectIdent'];
            if (!in_array($ident, $varList)) {
                $this->SendDebug(__FUNCTION__, 'unregister variable: ident=' . $ident, 0);
                $this->UnregisterVariable($ident);
            }
        }

        $this->SetStatus($status);
    }

    private function findVariables($objID, &$objList)
    {
        $chldIDs = IPS_GetChildrenIDs($objID);
        foreach ($chldIDs as $chldID) {
            $obj = IPS_GetObject($chldID);
            switch ($obj['ObjectType']) {
                case OBJECTTYPE_VARIABLE:
                    if (preg_match('#^[a-z_]+$#', $obj['ObjectIdent'], $r)) {
                        $objList[] = $obj;
                    }
                    break;
                case OBJECTTYPE_CATEGORY:
                    $this->findVariables($chldID, $objList);
                    break;
                default:
                    break;
            }
        }
    }

    public function UpdateFields(int $module_type)
    {
        $values = [];

        $use_fields = json_decode($this->ReadPropertyString('use_fields'), true);
        $fieldMap = $this->getFieldMap($module_type);

        foreach ($fieldMap as $map) {
            $ident = $this->GetArrayElem($map, 'ident', '');
            $desc = $this->GetArrayElem($map, 'desc', '');
            $use = false;
            foreach ($use_fields as $field) {
                if ($ident == $this->GetArrayElem($field, 'ident', '')) {
                    $use = (bool) $this->GetArrayElem($field, 'use', false);
                    break;
                }
            }
            $values[] = ['ident' => $ident, 'desc' => $this->Translate($desc), 'use' => $use];
        }

        $this->UpdateFormField('use_fields', 'values', json_encode($values));
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
        $formElements[] = ['type' => 'Label', 'caption' => 'Wiffi'];

        $opts_module_type = [];
        $opts_module_type[] = ['caption' => $this->Translate('none'), 'value' => WIFFI_MODULE_NONE];
        $opts_module_type[] = ['caption' => $this->Translate('Wiffi-WZ'), 'value' => WIFFI_MODULE_WZ];
        $opts_module_type[] = ['caption' => $this->Translate('Wiffi 3'), 'value' => WIFFI_MODULE_3];

        $formElements[] = [
            'type'     => 'Select',
            'name'     => 'module_type',
            'caption'  => 'Module type',
            'options'  => $opts_module_type,
            'onChange' => 'Wiffi_UpdateFields($id, $module_type);'
        ];

        $module_type = $this->ReadPropertyInteger('module_type');

        $values = [];
        $fieldMap = $this->getFieldMap($module_type);
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
            case WIFFI_MODULE_3:
                $items[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'with_heatindex',
                    'caption' => ' ... Heatindex (needs "temp", "feuchte_rel")'
                ];

                $items[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'with_absolute_pressure',
                    'caption' => ' ... absolute pressure (needs "baro", "temp" and the altitude)'
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

        switch ((int) $jmsg['Type']) {
            case 0: /* Data */
                $this->SendDebug(__FUNCTION__, $jmsg['ClientIP'] . ':' . $jmsg['ClientPort'] . ' => received: ' . $data, 0);
                $rdata = $this->GetMultiBuffer('Data');
                if (substr($data, -1) == chr(4)) {
                    $ndata = $rdata . substr($data, 0, -1);
                } else {
                    $ndata = $rdata . $data;
                }
                break;
            case 1: /* Connected */
                $this->SendDebug(__FUNCTION__, $jmsg['ClientIP'] . ':' . $jmsg['ClientPort'] . ' => connected', 0);
                $ndata = '';
                break;
            case 2: /* Disconnected */
                $this->SendDebug(__FUNCTION__, $jmsg['ClientIP'] . ':' . $jmsg['ClientPort'] . ' => disonnected', 0);
                $rdata = $this->GetMultiBuffer('Data');
                $jdata = json_decode($rdata, true);
                if ($jdata == '') {
                    $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg() . ', data=' . $ndata, 0);
                } else {
                    $this->ProcessData($jdata);
                }
                $ndata = '';
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'unknown Type, jmsg=' . print_r($jmsg, true), 0);
                break;
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
            case WIFFI_MODULE_3:
                if ($modultyp != 'wiffi-3.0') {
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
                $s = $this->GetArrayElem($jdata, 'Systeminfo.wiffizeit', '');
                if (preg_match('#^[ ]*([0-9]+)[ ]+([0-9]+):([0-9]+)$#', $s, $r)) {
                    $tstamp = strtotime($r[2] . ':' . $r[3] . ':00');
                } else {
                    $tstamp = 0;
                }
                $this->SetValue('LastMessage', $tstamp);

                $uptime = $this->GetArrayElem($jdata, 'Systeminfo.millis_seit_reset', 0);
                $this->SetValue('Uptime', $uptime / 1000);

                $rssi = $this->GetArrayElem($jdata, 'Systeminfo.WLAN_Signal_dBm', '');
                $this->SetValue('WifiStrength', $rssi);

                $this->SendDebug(__FUNCTION__, 'modultyp=' . $modultyp . ', tstamp=' . date('d.m.Y H:i:s', $tstamp) . ', rssi=' . $rssi . ', uptime=' . $uptime . 's', 0);
                break;
            case WIFFI_MODULE_3:
                $s = $this->GetArrayElem($jdata, 'Systeminfo.zeitpunkt', '');
                if (preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)[ ]*/([0-9]+)h([0-9]+)$#', $s, $r)) {
                    $tstamp = strtotime($r[1] . '-' . $r[2] . '-' . $r[3] . ' ' . $r[4] . ':' . $r[5] . ':00');
                } else {
                    $tstamp = 0;
                }
                $this->SetValue('LastMessage', $tstamp);

                $uptime = $this->GetArrayElem($jdata, 'Systeminfo.sec_seit_reset', 0);
                $this->SetValue('Uptime', $uptime);

                $rssi = $this->GetArrayElem($jdata, 'Systeminfo.WLAN_Signal_dBm', '');
                $this->SetValue('WifiStrength', $rssi);

                $this->SendDebug(__FUNCTION__, 'modultyp=' . $modultyp . ', tstamp=' . date('d.m.Y H:i:s', $tstamp) . ', rssi=' . $rssi . ', uptime=' . $uptime . 's', 0);
                break;
        }

        $fieldMap = $this->getFieldMap($module_type);
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
            $ident = $this->GetArrayElem($var, 'homematic_name', '');
            if (preg_match('#^[^_]+_(.+)$#', $ident, $r)) {
                $ident = $r[1];
            }
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

                    $this->SendDebug(__FUNCTION__, 'use ident "' . $ident . '", value=' . $value, 0);

                    if (in_array($ident, ['luftdrucktrend', 'iaq'])) {
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
            case WIFFI_MODULE_3:
                $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
                if ($with_heatindex) {
                    $temperatur = $this->GetValue('temperatur');
                    $feuchte_rel = $this->GetValue('feuchte_rel');
                    $v = $this->calcHeatindex($temperatur, $feuchte_rel);
                    $this->SetValue('Heatindex', $v);
                }

                $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
                if ($with_absolute_pressure) {
                    $baro = $this->GetValue('baro');
                    $temp = $this->GetValue('temp');
                    $altitude = $this->ReadPropertyInteger('altitude');
                    $v = $this->calcAbsolutePressure($baro, $temp, $altitude);
                    $this->SetValue('AbsolutePressure', $v);
                }
                break;
            default:
                break;
        }

        $this->SetValue('LastUpdate', time());
    }

    private function getFieldMap(int $module_type)
    {
        $map_wz = [
            [
                'ident'  => 'ip',
                'desc'   => 'IP-address',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'co2',
                'desc'   => 'CO2',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.CO2',
            ],
            [
                'ident'  => 'temp',
                'desc'   => 'Temperature',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Temperatur',
            ],
            [
                'ident'  => 'taupunkt',
                'desc'   => 'Dewpoint',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Dewpoint',
            ],
            [
                'ident'  => 'feuchte',
                'desc'   => 'Humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Humidity',
            ],
            [
                'ident'  => 'feuchte_abs',
                'desc'   => 'Absolute humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.absHumidity',
            ],
            [
                'ident'  => 'baro',
                'desc'   => 'Air pressure',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Pressure',
            ],
            [
                'ident'  => 'luftdrucktrend',
                'desc'   => 'Trend of air pressure',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'lux',
                'desc'   => 'Brightness',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Lux',
            ],
            [
                'ident'  => 'motion_left',
                'desc'   => 'Left motion detected',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Wiffi.MotionDetector',
            ],
            [
                'ident'  => 'motion_right',
                'desc'   => 'Right motion detected',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Wiffi.MotionDetector',
            ],
            [
                'ident'  => 'motion',
                'desc'   => 'Motion detected',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Wiffi.MotionDetector',
            ],
            [
                'ident'  => 'noise',
                'desc'   => 'Noise detected',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Wiffi.NoiseDetector',
            ],
            [
                'ident'  => 'elevation',
                'desc'   => 'Sun elevation',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.Elevation',
            ],
            [
                'ident'  => 'azimut',
                'desc'   => 'Sun azimut',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.Azimut',
            ],
            [
                'ident'  => 'buzzer',
                'desc'   => 'Buzzer',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => '~Alert',
            ],
            [
                'ident'  => 'relais',
                'desc'   => 'Relais',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => '~Alert',
            ],
        ];
        $map_3 = [
            [
                'ident'  => 'ip',
                'desc'   => 'IP-address',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'co2',
                'desc'   => 'CO2',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.CO2',
            ],
            [
                'ident'  => 'voc',
                'desc'   => 'VOC',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.VOC',
            ],
            [
                'ident'  => 'temp',
                'desc'   => 'Temperature',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Temperatur',
            ],
            [
                'ident'  => 'taupunkt',
                'desc'   => 'Dewpoint',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Dewpoint',
            ],
            [
                'ident'  => 'feuchte',
                'desc'   => 'Humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Humidity',
            ],
            [
                'ident'  => 'feuchte_abs',
                'desc'   => 'Absolute humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.absHumidity',
            ],
            [
                'ident'  => 'baro',
                'desc'   => 'Air pressure',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Pressure',
            ],
            [
                'ident'  => 'luftdrucktrend',
                'desc'   => 'Trend of air pressure',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'lux',
                'desc'   => 'Brightness',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Lux',
            ],
            [
                'ident'  => 'motion',
                'desc'   => 'Motion detected',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Wiffi.MotionDetector',
            ],
            [
                'ident'  => 'noise',
                'desc'   => 'Noise detected',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => 'Wiffi.NoiseDetector',
            ],
            [
                'ident'  => 'elevation',
                'desc'   => 'Sun elevation',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.Elevation',
            ],
            [
                'ident'  => 'azimut',
                'desc'   => 'Sun azimut',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.Azimut',
            ],
            [
                'ident'  => 'minuten_vor_sa',
                'desc'   => 'Minutes from sunrise',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.min',
            ],
            [
                'ident'  => 'minuten_vor_su',
                'desc'   => 'Minutes from sunset',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.min',
            ],
            [
                'ident'  => 'schalter',
                'desc'   => 'Switch',
                'type'   => VARIABLETYPE_BOOLEAN,
                'prof'   => '~Switch',
            ],

            [
                'ident'  => 'iaq',
                'desc'   => 'Airquality',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'iaq_value',
                'desc'   => 'Airquality (IAQ-Value)',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.IAQ',
            ],
            [
                'ident'  => 'iaq_rr0_value',
                'desc'   => 'Airquality (R/R0)',
                'type'   => VARIABLETYPE_INTEGER,
            ],
            [
                'ident'  => 'noise_value',
                'desc'   => 'Noise-Value',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.Percent',
            ],
            [
                'ident'  => 'noise_peak',
                'desc'   => 'Noise-Peak (60s)',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.Percent',
            ],
            [
                'ident'  => 'noise_avg',
                'desc'   => 'Noise-Average (60s)',
                'type'   => VARIABLETYPE_INTEGER,
                'prof'   => 'Wiffi.Percent',
            ],
        ];

        switch ($module_type) {
            case WIFFI_MODULE_WZ:
                $map = $map_wz;
                break;
            case WIFFI_MODULE_3:
                $map = $map_3;
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
