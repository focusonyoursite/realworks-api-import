# Realworks API-koppeling

De Realworks API koppeling vormt de verbinding tussen Realworks en WordPress. 

## Installation

Needs update

## Usage

Needs update

## Running the import

De import functionaliteit werkt op basis van WP CLI. Het volgende commando kan in de WP-folder handmatig worden gerunned op de server om de eerste batch import te draaien van alle records in Realworks:

```bash
wp bvdb-import start --import-type=initial | tee logs/import-$(date '+%Y%m%dT%H:%M').log
```

Hierna kan er handmatig het volgende commando worden uitgevoerd om de synchronisatie eenmalig uit te voeren:

```bash
wp bvdb-import start | tee logs/import-$(date '+%Y%m%dT%H:%M').log
```

Om de database up to date te laten lopen met het aanbod in Realworks dient de volgende cronjob te worden ingesteld:

```bash
*/15 * * * * /usr/local/bin/wp --path=/home/kolpahoek/domains/<<DOMEIN>>/public_html/wp/ bvdb-import start > logs/import-$(date '+%Y%m%dT%H:%M').log
```

### Flags

Er is één flag die kan worden toegevoegd om het type import te bepalen. Deze flag voeg je toe achter het initiële commando (is te zien in eerste voorbeeld):

```bash
--import-type=initial
```

Deze set de globale variabele `import-type` op initial, wat inhoudt dat de gehele database wordt gesynchroniseerd met Realworks. De standaard waarde van deze variabele is `latest`  wat inhoudt dat enkel de records gesynchroniseerd worden die zijn gewijzigd nadat de laatste synchronisatie was uitgevoerd. 



### Logs

Er worden logs gegenereerd zodra deze in het commando worden meegegeven. De basiscommando's zoals hierboven en de cronjob plaatsen de logs van elke import in de map logs in de plug-in folder. Aangezien de import elk kwartier wordt gedraaid zullen de meeste logs vrij klein zijn. De logs worden automatisch opgeschoond waarbij er maximaal 100 bewaard worden. 
