# IPSymconWiffi

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.3+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Module-Version](https://img.shields.io/badge/Modul_Version-1.1-blue.svg)
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

Übernahme der Daten von dem "do it yourself" _Wifi-Sensor_ von ([stall.biz](https://www.stall.biz/project/der-wiffi-wz-2-0-der-wohnzimmersensor)).

Getestet mit Wiffi-WZ mit der Version **106** und Wiffi 3.0 mit der Version **20**?

## 2. Voraussetzungen

 - IP-Symcon ab Version 5.3
 - ein _Wiffi-Wohnzimmersensor_ oder _Wiffi 3.0_, weitere Wiffi-Module können bei Bedarf und Unterstützung implementiert werden

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ öffnen.

Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

![Store](docs/de/img/store_icon.png?raw=true "open store")

Im Suchfeld nun _Wiffi-WZ_ eingeben, das Modul auswählen und auf _Installieren_ drücken.

#### Alternatives Installieren über Modules Instanz (IP-Symcon < 5.1)

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
| Modultyp                              | integer  |              | 1=Wiffi-WZ, 2=Wiffi 3 |
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
Wiffi.Azimut, Wiffi.CO2, Wiffi.Elevation, Wiffi.IAQ, Wiffi.min, Wiffi.Percent, Wiffi.sec, Wiffi.Wifi,

* Float<br>
Wiffi.absHumidity, Wiffi.Dewpoint, Wiffi.Heatindex, Wiffi.Humidity, Wiffi.Lux, Wiffi.Pressure, Wiffi.Temperatur,
Wiffi.VOC,

* String<br>

## 6. Anhang

GUIDs
- Modul: `{7F4FEEDE-F138-0376-0B9E-727FD47200DF}`
- Instanzen:
  - Wiffi: `{92D39B81-9163-BBCC-734D-52EBBE78178B}`

## 7. Versions-Historie

- 1.1 @ 02.01.2020 14:15
  - Fix in ReceiveData()
  - ungenutztes Protery 'fields' entfernt

- 1.0 @ 19.12.2019 13:55
  - Initiale Version
