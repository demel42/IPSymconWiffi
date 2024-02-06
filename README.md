# IPSymconWiffi

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
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

 - IP-Symcon ab Version 6.0
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
Wiffi.MotionnDetector,
Wiffi.NoiseDetector

* Integer<br>
Wiffi.Azimut,
Wiffi.CO2,
Wiffi.CO2_Equ,
Wiffi.Elevation,
Wiffi.IAQ,
Wiffi.IAQ_note,
Wiffi.min,
Wiffi.Percent,
Wiffi.sec,
Wiffi.Wifi

* Float<br>
Wiffi.absHumidity,
Wiffi.Dewpoint,
Wiffi.Heatindex,
Wiffi.Humidity,
Wiffi.Lux,
Wiffi.Particles,
Wiffi.Pressure,
Wiffi.RR0,
Wiffi.Temperatur,
Wiffi.VOC

* String<br>

## 6. Anhang

GUIDs
- Modul: `{7F4FEEDE-F138-0376-0B9E-727FD47200DF}`
- Instanzen:
  - Wiffi: `{92D39B81-9163-BBCC-734D-52EBBE78178B}`

## 7. Versions-Historie

- 1.11 @ 06.02.2024 09:46
  - Verbesserung: Angleichung interner Bibliotheken anlässlich IPS 7
  - update submodule CommonStubs

- 1.10 @ 03.11.2023 11:06
  - Neu: Ermittlung von Speicherbedarf und Laufzeit (aktuell und für 31 Tage) und Anzeige im Panel "Information"
  - update submodule CommonStubs

- 1.9 @ 04.07.2023 14:44
  - Vorbereitung auf IPS 7 / PHP 8.2
  - update submodule CommonStubs
    - Absicherung bei Zugriff auf Objekte und Inhalte

- 1.8.1 @ 07.10.2022 13:59
  - update submodule CommonStubs
    Fix: Update-Prüfung wieder funktionsfähig

- 1.8 @ 05.07.2022 17:00
  - Verbesserung: IPS-Status wird nur noch gesetzt, wenn er sich ändert

- 1.7.1 @ 22.06.2022 10:33
  - Fix: Angabe der Kompatibilität auf 6.2 korrigiert

- 1.7 @ 29.05.2022 14:47
  - update submodule CommonStubs
    Fix: Ausgabe des nächsten Timer-Zeitpunkts
  - einige Funktionen (GetFormElements, GetFormActions) waren fehlerhafterweise "protected" und nicht "private"
  - interne Funktionen sind nun entweder private oder nur noch via IPS_RequestAction() erreichbar

- 1.6.2 @ 17.05.2022 15:38
  - update submodule CommonStubs
    Fix: Absicherung gegen fehlende Objekte

- 1.6.1 @ 10.05.2022 15:06
  - update submodule CommonStubs

- 1.6 @ 06.05.2022 10:12
  - IPS-Version ist nun minimal 6.0
  - Anzeige der Modul/Bibliotheks-Informationen, Referenzen und Timer
  - Implememtierung einer Update-Logik
  - Überlagerung von Translate und Aufteilung von locale.json in 3 translation.json (Modul, libs und CommonStubs)
  - diverse interne Änderungen

- 1.5 @ 22.03.2021 21:29
  - Unterstützung neue Firmware des "AirSniffer-mini"

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
