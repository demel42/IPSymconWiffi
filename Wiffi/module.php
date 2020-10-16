<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php'; // globale Funktionen
require_once __DIR__ . '/../libs/local.php';  // lokale Funktionen

class Wiffi extends IPSModule
{
    use WiffiCommonLib;
    use WiffiLocalLib;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('module_type', self::$WIFFI_MODULE_NONE);
        $this->RegisterPropertyString('use_fields', '[]');

        $this->RegisterPropertyInteger('altitude', 0);
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
        $this->CreateVarProfile('Wiffi.absHumidity', VARIABLETYPE_FLOAT, ' g/m³', 10, 100, 0, 1, 'Drops');
        $this->CreateVarProfile('Wiffi.Pressure', VARIABLETYPE_FLOAT, ' mbar', 0, 0, 0, 0, 'Gauge');
        $this->CreateVarProfile('Wiffi.Heatindex', VARIABLETYPE_FLOAT, ' °C', 0, 100, 0, 0, 'Temperature');
        $this->CreateVarProfile('Wiffi.Dewpoint', VARIABLETYPE_FLOAT, ' °C', 0, 30, 0, 0, 'Drops');
        $this->CreateVarProfile('Wiffi.Lux', VARIABLETYPE_FLOAT, ' lx', 0, 0, 0, 0, 'Sun');
        $this->CreateVarProfile('Wiffi.VOC', VARIABLETYPE_FLOAT, '', 0, 0, 0, 2, 'Gauge');
        $this->CreateVarProfile('Wiffi.Particles', VARIABLETYPE_FLOAT, ' µg/m³', 0, 100, 0, 2, 'Gauge');
        $this->CreateVarProfile('Wiffi.CO2_Equ', VARIABLETYPE_INTEGER, ' ppm', 0, 5000, 0, 0, 'Gauge');
        $this->CreateVarProfile('Wiffi.RR0', VARIABLETYPE_FLOAT, '', 0, 1, 0, 2, 'Gauge');

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
                case self::$WIFFI_MODULE_WZ:
                case self::$WIFFI_MODULE_3:
                case self::$AIRSNIFFER:
                    if (!(in_array('temp', $identList) && in_array('feuchte', $identList))) {
                        $this->SendDebug(__FUNCTION__, '"with_heatindex" needs "temp", "feuchte"', 0);
                        $with_heatindex = false;
                        $status = self::$IS_INVALIDCONFIG;
                    }
                    break;
                default:
                    $this->SendDebug(__FUNCTION__, '"with_heatindex" not available for module_type ' . $module_type, 0);
                    $status = self::$IS_INVALIDCONFIG;
                    break;
            }
        }
        $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), VARIABLETYPE_FLOAT, 'Wiffi.Heatindex', $vpos++, $with_heatindex);

        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        if ($with_absolute_pressure) {
            switch ($module_type) {
                case self::$WIFFI_MODULE_WZ:
                case self::$WIFFI_MODULE_3:
                case self::$AIRSNIFFER:
                case self::$AIRSNIFFER_MINI:
                    if (!(in_array('temp', $identList) && in_array('feuchte_rel', $identList))) {
                        $altitude = $this->ReadPropertyInteger('altitude');
                        if (!(in_array('baro', $identList) && in_array('temp', $identList) && $altitude > 0)) {
                            $this->SendDebug(__FUNCTION__, '"with_absolute_pressure" needs "baro", "temp" and "altitude"', 0);
                            $with_absolute_pressure = false;
                            $status = self::$IS_INVALIDCONFIG;
                        }
                    }
                    break;
                default:
                    $this->SendDebug(__FUNCTION__, '"with_absolute_pressure" not available for module_type ' . $module_type, 0);
                    $status = self::$IS_INVALIDCONFIG;
                    break;
            }
        }
        $this->MaintainVariable('AbsolutePressure', $this->Translate('Absolute pressure'), VARIABLETYPE_FLOAT, 'Wiffi.Pressure', $vpos++, $with_absolute_pressure);

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

    public function UpdateFields(int $module_type, object $use_fields)
    {
        $values = [];

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

    protected function GetFormElements()
    {
        $formElements = [];
        $formElements[] = ['type' => 'Label', 'caption' => 'Wiffi'];

        $opts_module_type = [];
        $opts_module_type[] = ['caption' => $this->Translate('none'), 'value' => self::$WIFFI_MODULE_NONE];
        $opts_module_type[] = ['caption' => $this->Translate('Wiffi-WZ'), 'value' => self::$WIFFI_MODULE_WZ];
        $opts_module_type[] = ['caption' => $this->Translate('Wiffi 3'), 'value' => self::$WIFFI_MODULE_3];
        $opts_module_type[] = ['caption' => $this->Translate('AirSniffer'), 'value' => self::$AIRSNIFFER];
        $opts_module_type[] = ['caption' => $this->Translate('AirSniffer-mini'), 'value' => self::$AIRSNIFFER_MINI];

        $formElements[] = [
            'type'     => 'Select',
            'name'     => 'module_type',
            'caption'  => 'Module type',
            'options'  => $opts_module_type,
            'onChange' => 'Wiffi_UpdateFields($id, $module_type, $use_fields);'
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

        //expanded = true ist ein Workaround. Siehe https://www.symcon.de/forum/threads/42842-Fehler-bei-onChange-in-Verbindung-mit-ExpansionPanel?p=437371#post437371
        $formElements[] = ['type' => 'ExpansionPanel', 'items' => $items, 'caption' => 'Variables', 'expanded' => true];

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
            case self::$WIFFI_MODULE_WZ:
            case self::$WIFFI_MODULE_3:
            case self::$AIRSNIFFER:
            case self::$AIRSNIFFER_MINI:
                $items[] = [
                    'type'    => 'CheckBox',
                    'name'    => 'with_heatindex',
                    'caption' => ' ... Heatindex (needs "temp", "feuchte")'
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
                $this->SendDebug(__FUNCTION__, $jmsg['ClientIP'] . ':' . $jmsg['ClientPort'] . ' => disconnected', 0);
                $rdata = $this->GetMultiBuffer('Data');
                if ($rdata != '') {
                    $rdata = str_replace(',]', ']', $rdata); //Workaround für einen Syntaxfehler beim AirSniffer, Firmware 10
                    $jdata = json_decode($rdata, true);
                    if ($jdata == '') {
                        $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg() . ', data=' . $rdata, 0);
                    } else {
                        $this->ProcessData($jdata);
                    }
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
            case self::$WIFFI_MODULE_WZ:
                if ($modultyp != 'wiffi-wz') {
                    $this->SendDebug(__FUNCTION__, 'wrong module-type "' . $modultyp . '"', 0);
                    $this->SetStatus(self::$IS_MODULETYPEMISMATCH);
                    return;
                }
                break;
            case self::$WIFFI_MODULE_3:
                if ($modultyp != 'wiffi-3.0') {
                    $this->SendDebug(__FUNCTION__, 'wrong module-type "' . $modultyp . '"', 0);
                    $this->SetStatus(self::$IS_MODULETYPEMISMATCH);
                    return;
                }
                break;
            case self::$AIRSNIFFER:
            case self::$AIRSNIFFER_MINI:
                if ($modultyp != 'airsniffer') {
                    $this->SendDebug(__FUNCTION__, 'wrong module-type "' . $modultyp . '"', 0);
                    $this->SetStatus(self::$IS_MODULETYPEMISMATCH);
                    return;
                }
                break;
            default:
                return;
        }

        // get system info

        $systeminfo = $this->GetArrayElem($jdata, 'Systeminfo', '');
        $this->SendDebug(__FUNCTION__, 'Systeminfo=' . print_r($systeminfo, true), 0);

        switch ($module_type) {
            case self::$WIFFI_MODULE_WZ:
                $s = $this->GetArrayElem($jdata, 'Systeminfo.wiffizeit', '');
                if (preg_match('#^[ ]*([0-9]+)[ ]+([0-9]+):([0-9]+)$#', $s, $r)) {
                    $tstamp = strtotime($r[2] . ':' . $r[3] . ':00');
                    $tstamp_ign = false;
                } else {
                    $tstamp = 0;
                    $tstamp_ign = true;
                }

                $uptime = $this->GetArrayElem($jdata, 'Systeminfo.millis_seit_reset', 0);
                $this->SetValue('Uptime', (int) $uptime / 1000);

                break;
            case self::$WIFFI_MODULE_3:
            case self::$AIRSNIFFER:
            case self::$AIRSNIFFER_MINI:
                $s = $this->GetArrayElem($jdata, 'Systeminfo.zeitpunkt', '');
                if (preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)[ ]*/([0-9]+)h([0-9]+)$#', $s, $r)) {
                    $tstamp = strtotime($r[1] . '-' . $r[2] . '-' . $r[3] . ' ' . $r[4] . ':' . $r[5] . ':00');
                    $tstamp_ign = false;
                } else {
                    $tstamp = 0;
                    $tstamp_ign = true;
                }

                $uptime = $this->GetArrayElem($jdata, 'Systeminfo.sec_seit_reset', 0);
                $this->SetValue('Uptime', $uptime);

                break;
        }

        if ($tstamp_ign === false){
            $this->SetValue('LastMessage', $tstamp);
        }

        $rssi = $this->GetArrayElem($jdata, 'Systeminfo.WLAN_Signal_dBm', 0);
        if ($rssi !== 0){
            $this->SetValue('WifiStrength', $rssi);
            $rssi_ign = false;
        } else {
            $rssi_ign = true;
        }

        $this->SendDebug(
            __FUNCTION__,
            sprintf(
                'modultyp=%s, tstamp=%s%s, rssi=%s%s, uptime=%ss',
                $modultyp,
                date('d.m.Y H:i:s', $tstamp),
                ($tstamp_ign ? ' (ignore)' : ''),
                $rssi,
                ($rssi_ign ? ' (ignore)' : ''),
                $uptime
            ),
            0
        );

        // get sensor info

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
            if (!isset($var['value'])) {
                continue;
            }
            $value = $var['value'];

            $found = false;

            $vartype = VARIABLETYPE_STRING;
            $ignore = false;
            foreach ($fieldMap as $map) {
                if ($ident == $this->GetArrayElem($map, 'ident', '')) {
                    $found = true;
                    $vartype = $this->GetArrayElem($map, 'type', '');
                    $ignore = $this->GetArrayElem($map, 'ignore', false);
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

                    $ign = false;
                    if ($value == 'bitte_warten') {
                        $ign = true;
                    }
                    switch ($vartype) {
                        case VARIABLETYPE_INTEGER:
                            if ($ignore !== false && (int) $value == (int) $ignore) {
                                $ign = true;
                            }
                            break;
                        case VARIABLETYPE_FLOAT:
                            if ($ignore !== false && (float) $value == (float) $ignore) {
                                $ign = true;
                            }
                            break;
                        default:
                            break;
                    }

                    $this->SendDebug(__FUNCTION__, sprintf('use ident "%s", value=%s%s', $ident, $value, ($ign ? ' (ignore)' : '')), 0);

                    if ($ign) {
                        break;
                    }

                    if (in_array($ident, ['luftdrucktrend', 'iaq'])) {
                        $value = str_replace('_', ' ', $value);
                    }

                    switch ($vartype) {
                        case VARIABLETYPE_INTEGER:
                            $this->SetValue($ident, (int) $value);
                            break;
                        case VARIABLETYPE_FLOAT:
                            $this->SetValue($ident, (float) $value);
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
            case self::$WIFFI_MODULE_WZ:
            case self::$WIFFI_MODULE_3:
            case self::$AIRSNIFFER:
                $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
                if ($with_heatindex) {
                    $temperatur = $this->GetValue('temp');
                    $feuchte_rel = $this->GetValue('feuchte');
                    $v = $this->calcHeatindex((float) $temperatur, (float) $feuchte_rel);
                    $this->SetValue('Heatindex', $v);
                }

                $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
                if ($with_absolute_pressure) {
                    $baro = $this->GetValue('baro');
                    $temp = $this->GetValue('temp');
                    $altitude = $this->ReadPropertyInteger('altitude');
                    $v = $this->calcAbsolutePressure((float) $baro, (float) $temp, $altitude);
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

        //die Liste entspricht den zur Verfügung gestellten Daten von http://<IP>/?json:
        // das Ergebnis lässt sich gut mit https://jsonlint.com/ formatieren

        $map_airsniffer = [
            [
                'ident'  => 'ip',
                'desc'   => 'IP-address',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'temp',
                'desc'   => 'Temperature',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Temperatur',
            ],
            [
                'ident'  => 'feuchte',
                'desc'   => 'Humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Humidity',
            ],
            [
                'ident'  => 'taupunkt',
                'desc'   => 'Dewpoint',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Dewpoint',
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
                'ident'   => 'luftdrucktrend',
                'desc'    => 'Trend of air pressure',
                'type'    => VARIABLETYPE_STRING,
            ],
            [
                'ident'   => 'pm10',
                'desc'    => 'Particles 10',
                'type'    => VARIABLETYPE_FLOAT,
                'prof'    => 'Wiffi.Particles',
                'ignore'  => 0.0,
            ],
            [
                'ident'   => 'pm2_5',
                'desc'    => 'Particles 2.5',
                'type'    => VARIABLETYPE_FLOAT,
                'prof'    => 'Wiffi.Particles',
                'ignore'  => 0.0,
            ],
            [
                'ident'   => 'pm1_0',
                'desc'    => 'Particles 1.0',
                'type'    => VARIABLETYPE_FLOAT,
                'prof'    => 'Wiffi.Particles',
                'ignore'  => 0,
            ],
            [
                'ident'   => 'iaq10',
                'desc'    => 'IAQ Particles 10',
                'type'    => VARIABLETYPE_INTEGER,
                'prof'    => 'Wiffi.IAQ',
                'ignore'  => 0,
            ],
            [
                'ident'   => 'iaq2_5',
                'desc'    => 'IAQ Particles 2.5',
                'type'    => VARIABLETYPE_INTEGER,
                'prof'    => 'Wiffi.IAQ',
                'ignore'  => 0,
            ],
            [
                'ident'   => 'iaq1_0',
                'desc'    => 'IAQ Particles 1.0',
                'type'    => VARIABLETYPE_INTEGER,
                'prof'    => 'Wiffi.IAQ',
                'ignore'  => 0,
            ],
            [
                'ident'   => 'iaq_co2',
                'desc'    => 'Airquality (CO2-IAQ)',
                'type'    => VARIABLETYPE_INTEGER,
                'prof'    => 'Wiffi.IAQ',
                'ignore'  => 0,
            ],
            [
                'ident'   => 'co2_equ',
                'desc'    => 'Airquality (CO2-Equ.)',
                'type'    => VARIABLETYPE_INTEGER,
                'prof'    => 'Wiffi.CO2_Equ',
                'ignore'  => 0,
            ],
            [
                'ident'   => 'IAQ_max_note',
                'desc'    => 'Airquality Max.Note',
                'type'    => VARIABLETYPE_STRING,
            ],
            [
                'ident'   => 'rr0_value',
                'desc'    => 'Airquality (R/R0)',
                'type'    => VARIABLETYPE_FLOAT,
                'prof'    => 'Wiffi.RR0',
                'ignore'  => 0.0,
            ],
        ];

        $map_airsniffer_mini = [
            [
                'ident'  => 'ip',
                'desc'   => 'IP-address',
                'type'   => VARIABLETYPE_STRING,
            ],
            [
                'ident'  => 'temp',
                'desc'   => 'Temperature',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Temperatur',
            ],
            [
                'ident'  => 'feuchte',
                'desc'   => 'Humidity',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Humidity',
            ],
            [
                'ident'  => 'taupunkt',
                'desc'   => 'Dewpoint',
                'type'   => VARIABLETYPE_FLOAT,
                'prof'   => 'Wiffi.Dewpoint',
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
                'ident'   => 'luftdrucktrend',
                'desc'    => 'Trend of air pressure',
                'type'    => VARIABLETYPE_STRING,
            ],
            [
                'ident'   => 'iaq_co2',
                'desc'    => 'Airquality (CO2-IAQ)',
                'type'    => VARIABLETYPE_INTEGER,
                'prof'    => 'Wiffi.IAQ',
                'ignore'  => 0,
            ],
            [
                'ident'   => 'co2_equ',
                'desc'    => 'Airquality (CO2-Equ.)',
                'type'    => VARIABLETYPE_INTEGER,
                'prof'    => 'Wiffi.CO2_Equ',
                'ignore'  => 0,
            ],
            [
                'ident'   => 'IAQ_max_note',
                'desc'    => 'Airquality Max.Note',
                'type'    => VARIABLETYPE_STRING,
            ],
            [
                'ident'   => 'rr0_value',
                'desc'    => 'Airquality (R/R0)',
                'type'    => VARIABLETYPE_FLOAT,
                'prof'    => 'Wiffi.RR0',
                'ignore'  => 0.0,
            ],
        ];

        switch ($module_type) {
            case self::$WIFFI_MODULE_WZ:
                $map = $map_wz;
                break;
            case self::$WIFFI_MODULE_3:
                $map = $map_3;
                break;
            case self::$AIRSNIFFER:
                $map = $map_airsniffer;
                break;
            case self::$AIRSNIFFER_MINI:
                $map = $map_airsniffer_mini;
                break;
            default:
                $map = [];
        }
        return $map;
    }

    // Luftdruck (Meereshöhe) in absoluten (lokaler) Luftdruck umrechnen
    //   Quelle: https://rechneronline.de/barometer/hoehe.php
    private function calcAbsolutePressure(float $pressure, float $temp, int $altitude): float
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
    // Temperatur als Heatindex umrechnen
    //   Quelle: https://de.wikipedia.org/wiki/Hitzeindex
    private function calcHeatindex(float $temp, float $hum)
    {
        if ($temp < 27 || $hum < 40) {
            return $temp;
        }
        $c1 = -8.784695;
        $c2 = 1.61139411;
        $c3 = 2.338549;
        $c4 = -0.14611605;
        $c5 = -1.2308094 * pow(10, -2);
        $c6 = -1.6424828 * pow(10, -2);
        $c7 = 2.211732 * pow(10, -3);
        $c8 = 7.2546 * pow(10, -4);
        $c9 = -3.582 * pow(10, -6);

        $hi = $c1
            + $c2 * $temp
            + $c3 * $hum
            + $c4 * $temp * $hum
            + $c5 * pow($temp, 2)
            + $c6 * pow($hum, 2)
            + $c7 * pow($temp, 2) * $hum
            + $c8 * $temp * pow($hum, 2)
            + $c9 * pow($temp, 2) * pow($hum, 2);
        $hi = round($hi); // ohne NK
        return $hi;
    }
}
