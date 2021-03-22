# IPSymconWiffi

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.3+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Übernahme der Daten von verschiedenen "do it yourself" _Sensormodulen_ von ([stall.biz](https://www.stall.biz/project/der-wiffi-wz-2-0-der-wohnzimmersensor)).

Unterstützt werden zur Zeit die folgenden Module (in Klammern die Firmware, mit der getestet wurde) 
- [WIFFI-WZ 2.0](https://www.stall.biz/project/der-wiffi-wz-2-0-der-wohnzimmersensor) (FW 106)
- [WIFFI-3](https://www.stall.biz/project/der-wiffi-3-0-die-raumsonde-nicht-nur-fuer-das-wohnzimmer) (FW 20?)
- [AirSniffer](https://www.stall.biz/project/der-airsniffer-schlechte-luft-kann-man-messen) (FW 11)
- [AirSniffer-mini](https://www.stall.biz/project/raumluftqualitaet-messen-mit-dem-airsniffer-mini-2) (FW 11)

## 2. Voraussetzungen

 - IP-Symcon ab Version 5.3
 - eines der genannten Sensor Module, weitere können bei Bedarf und Unterstützung implementiert werden

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ öffnen.

Anschließend oben rechts auf das Symbol für den Modulstore klicken

![Store](docs/de/img/store_icon.png?raw=true "open store")

Im Suchfeld nun _Wiffi-WZ_ eingeben, das Modul auswählen und auf _Installieren_ drücken.

#### Alternatives Installieren über Modules Instanz

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ aufrufen.

Anschließend den Objektbaum _öffnen_.

![Objektbaum](docs/de/img/objektbaum.png?raw=true "Objektbaum")

Die Instanz _Modules_ unterhalb von Kerninstanzen im Objektbaum von IP-Symcon mit einem Doppelklick öffnen und das  _Plus_ Zeichen drücken.

![Modules](docs/de/img/Modules.png?raw=true "Modules")

![Plus](docs/de/img/plus.png?raw=true "Plus")

![ModulURL](docs/de/img/add_module.png?raw=true "Add Module")

Im Feld die folgende URL eintragen und mit _OK_ bestätigen:

```
https://github.com/demel42/IPSymconWiffi.git
```

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_.

### b. Einrichtung des Geräte-Moduls

In IP-Symcon nun unterhalb des Wurzelverzeichnisses die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _stall.biz_ und als Gerät _Wiffi_ auswählen.
Es wird automatisch eine I/O-Instanz vom Type Server-Socket angelegt und das Konfigurationsformular dieser Instanz geöffnet.

Hier die Portnummer eintragen, an die der Wiffi Daten schicken soll und die Instanz aktiv schalten.

In dem Konfigurationsformular der Wiffi-Instanz kann man konfigurieren, welche Variablen übernommen werden sollen.

### c. Anpassung des Wiffi

Der Wiffi muss in zwei Punkten angepaast werden

- Einrichten der IP von IP-Symcon
```
http://<ip des Wiffi>/?ccu:<ip von IPS>:
```
- aktivieren der automatischen Übertragung
```
http://<ip des Wiffi>/?param:12:<port von IPS>:
```

damit schickt Wiffi minütlich die Daten sowie bei bestimmten Zuständen (Regen erkannt) eine ausserplanmässige Nachricht.

## 4. Funktionsreferenz

## 5. Konfiguration

#### Properties

| Eigenschaft                           | Typ      | Standardwert | Beschreibung |
| :------------------------------------ | :------  | :----------- | :----------- |
| Modultyp                              | integer  |              | 1=Wiffi-WZ, 2=Wiffi 3, 3=AirSniffer |
|                                       |          |              | |
| Höhe des Modules über NN              | integer  | 0            | |
|                                       |          |              | |
| Hitzeindex                            | boolean  | false        | Hitzeindex berechnen |
| absoluter Luftdruck                   | boolean  | false        | lokalen Luftdruck berechnen  |
|                                       |          |              | |

#### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Boolean<br>
Wiffi.MotionnDetector, Wiffi.NoiseDetector

* Integer<br>
Wiffi.Azimut, Wiffi.CO2, Wiffi.Elevation, Wiffi.IAQ, Wiffi.min, Wiffi.Percent, Wiffi.sec, Wiffi.Wifi, Wiffi.CO2_Equ

* Float<br>
Wiffi.absHumidity, Wiffi.Dewpoint, Wiffi.Heatindex, Wiffi.Humidity, Wiffi.Lux, Wiffi.Pressure, Wiffi.Temperatur,
Wiffi.VOC, Wiffi.Particles, Wiffi.RR0

* String<br>

## 6. Anhang

GUIDs
- Modul: `{7F4FEEDE-F138-0376-0B9E-727FD47200DF}`
- Instanzen:
  - Wiffi: `{92D39B81-9163-BBCC-734D-52EBBE78178B}`

## 7. Versions-Historie
- 1.5 @ 22.03.2021 21:29
  - Unterstützung neiue FIrmware des "AirSniffer-mini"

- 1.4 @ 16.10.2020 17:27
  - Unterstützung der Sensormodule "AirSniffer" und "AirSniffer-mini" durch [bumaas](https://www.symcon.de/forum/members/3610-bumaas)

- 1.3 @ 12.09.2020 11:40
  - LICENSE.md hinzugefügt
  - lokale Funktionen aus common.php in locale.php verlagert
  - Traits des Moduls haben nun Postfix "Lib"
  - define's durch statische Klassen-Variablen ersetzt

- 1.2 @ 23.04.2020 14:08
  - Fix: Profil Weatherman.pressure ersetzt durch Wiffi.pressure
  - Fix: fehlende Funtion calcHeatIndex() ergänzt

- 1.1 @ 08.01.2020 17:23
  - Fix in ReceiveData()
  - ungenutztes Property 'fields' entfernt
  - bei Wechsel des Modul-Typs bleiben ausgewählte Variablen erhalten

- 1.0 @ 19.12.2019 13:55
  - Initiale Version
