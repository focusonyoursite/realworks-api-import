<?php

    namespace BvdB\Realworks;

    // This class is used for generating the JSON files and importing them afterwards
    class Meta
    {   
        /**
         * Default mapping format for details
         *
         * @return void
         */
        public function mapping()
        {
            // Details
            $defaults = array(
                'overdracht' => array(
                    'aanvaarding' => null,
                    'datum_laatste_wijziging' => null,
                ),
                'bouw' => array(
                    'bouwjaar' => null,
                    'dak' => null,
                    'isolatievormen' => null,
                    'energielabel' => null,
                ),
                'oppervlakte_en_inhoud' => array(
                    'woonoppervlakte' => null,
                    'inhoud' => null,
                    'buitenruimtes' => null,
                    'tuinoppervlakte' => null,
                ),
                'locatie' => array(
                    'plaats' => null,
                    'postcode' => null,
                    'ligging' => null,
                ),
                'uitrusting' => array(
                    'soorten_warm_water' => null,
                    'parkeer_faciliteiten' => null,
                ),
            );

            return $defaults;
        }

        /**
         * Simple factory method for calling
         * correct meta-function
         *
         * @param string $type
         * @param array $data
         * @return array meta values
         */
        public function formatMeta( string $type, array $data )
        {
            $call = 'format' . ucfirst($type) . 'Meta';
            return $this->$call( $data );
        }
        
        /**
         * Format Wonen Metadata
         *
         * @param array $data
         * @return array formatted meta data
         */
        public function formatWonenMeta( array $data )
        {

            // Get default mapping
            $map = $this->mapping();

            // Setup raw media
            $map['media_raw'] = $data['media'];

            // Meta for: Overdracht
            $map['overdracht']['aanvaarding'] = $data['financieel']['overdracht']['aanvaarding'];
            $map['overdracht']['datum_laatste_wijziging'] = $data['diversen']['diversen']['wijzigingsdatum'];

            // Meta for: Bouw
            $map['bouw']['bouwjaar'] = $data['algemeen']['bouwjaar'];
            $map['bouw']['dak'] = $data['algemeen']['dakmaterialen'];
            $map['bouw']['isolatievormen'] = $data['algemeen']['isolatievormen'];
            $map['bouw']['energielabel'] = $data['algemeen']['energieklasse'];
            $map['bouw']['vervaldatum_energielabel'] = $data['algemeen']['energiedatum'];

            // Meta for: Oppervlakte en inhoud
            $map['oppervlakte_en_ligging']['oppervlakte'] = $data['algemeen']['woonoppervlakte'];
            $map['oppervlakte_en_ligging']['inhoud'] = $data['algemeen']['inhoud'];
            $map['oppervlakte_en_ligging']['gebouwgebonden_buitenruimte'] = $data['detail']['buitenruimte']['oppervlakteGebouwgebondenBuitenruimte'];
            $map['oppervlakte_en_ligging']['externe_buitenruimte'] = $data['detail']['buitenruimte']['oppervlakteExterneBergruimte'];
            $map['oppervlakte_en_ligging']['tuinoppervlakte'] = $data['detail']['buitenruimte']['hoofdtuinoppervlakte'];
            
            // Indeling
            $map['indeling']['aantal_kamers'] = $data['algemeen']['aantalKamers'];
            $map['indeling']['aantal_slaapkamers'] = 0;
            $map['indeling']['aantal_badkamers'] = 0;

            if( !empty($data['detail']['etages']) )
            {
                foreach( $data['detail']['etages'] as $etage ) 
                {
                    $map['indeling']['aantal_slaapkamers'] += $etage['aantalSlaapkamers'];
                    $map['indeling']['aantal_badkamers'] += count($etage['badkamers']);
                }
            }

            // Meta for: Locatie
            $map['locatie']['plaats'] = $data['adres']['plaats'];
            $map['locatie']['postcode'] = $data['adres']['postcode'];
            $map['locatie']['ligging'] = $data['algemeen']['liggingen'];

            // Meta for: Tuin
            $map['tuin']['type'] = $data['detail']['buitenruimte']['tuintypes'];
            $map['tuin']['ligging'] = $data['detail']['buitenruimte']['hoofdtuinlocatie'];
            $map['tuin']['staat'] = $data['detail']['buitenruimte']['tuinkwaliteit'];
            $map['tuin']['achterom'] = $data['detail']['buitenruimte']['hoofdtuinAchterom'];

            // Meta for: Uitrusting
            $map['uitrusting']['soorten_warm_water'] = array_merge( $data['algemeen']['verwarmingsoorten'], $data['algemeen']['warmwatersoorten'] );
            $map['uitrusting']['parkeer_faciliteiten'] = $data['detail']['buitenruimte']['parkeerfaciliteiten'];

            return $map;
        }

        /**
         * Format Business Metadata
         *
         * @param array $data
         * @return array formatted meta data
         */
        public function formatBusinessMeta( array $data )
        {

            // Get default mapping
            $map = $this->mapping();

            // Setup raw media
            $map['media_raw'] = $data['media'];

            // Meta for: Overdracht
            $map['overdracht']['aanvaarding'] = $data['financieel']['overdracht']['koopEnOfHuur']['aanvaarding'];
            $map['overdracht']['datum_laatste_wijziging'] = $data['tijdstipLaatsteWijziging'];

            // Meta for: Bouw
            $map['bouw']['bouwjaar'] = $data['gebouwdetails']['bouwjaar']['bouwjaar1'];
            $map['bouw']['bouwperiode'] = $data['gebouwdetails']['bouwjaar']['bouwperiode'];
            $map['bouw']['bouwvorm'] = $data['diversen']['diversen']['bouwvorm'];
            $map['bouw']['energielabel'] = $data['gebouwdetails']['energielabel']['energieindex'];
            $map['bouw']['vervaldatum_energielabel'] = $data['gebouwdetails']['energielabel']['energieEinddatum'];

            // Meta for: Prijs
            $map['prijs']['servicekosten']['prijs'] = $data['financieel']['overdracht']['koopEnOfHuur']['servicekosten'];
            $map['prijs']['servicekosten']['conditie'] = $data['financieel']['overdracht']['koopEnOfHuur']['servicekostenconditie'];

            // Meta for: Oppervlakte en inhoud
            if( !empty( $data['object']['functies'] ) )
            {
                // Holder for details
                $map['object_details'] = array();

                foreach( $data['object']['functies'] as $key => $object )
                {
                    // Set type
                    $map['object_details'][$key] = $object['type'];

                    if( $object['type'] === 'KANTOORRUIMTE' ) 
                    {
                        $map['object_details'][$key] = array(
                            'oppervlakte' => $object['kantoorruimte']['oppervlakte'],
                            'units_vanaf' => $object['kantoorruimte']['unitsVanaf'],
                            'aantal_verdiepingen' => $object['kantoorruimte']['aantalVerdiepingen'],
                            'voorzieningen' => $object['kantoorruimte']['voorzieningen'],
                            'luchtbehandeling' => $object['kantoorruimte']['luchtbehandeling'],
                        );
                    }
    
                    if( $object['type'] === 'WINKELRUIMTE' ) 
                    {
                        $map['object_details'][$key] = array(
                            'oppervlakte' => $object['winkelruimte']['oppervlakte'],
                            'verkoop_vloeroppervlakte' => $object['winkelruimte']['verkoopVloeroppervlakte'],
                            'welstandsklasse' => $object['winkelruimte']['welstandsklasse'],
                        );
                    }
                    
                    if( $object['type'] === 'BEDRIJFSRUIMTE' ) 
                    {
                        $map['object_details'][$key] = array(
                            'oppervlakte_bedrijfshal' => $object['bedrijfsruimte']['bedrijfshal']['oppervlakte'],
                            'oppervlakte_kantoorruimte' => $object['bedrijfsruimte']['bedrijfsruimteKantoorruimte']['kantoorruimteOppervlakte'],
                            'oppervlakte_terrein' => $object['bedrijfsruimte']['terrein']['terreinOppervlakte'],
                        );
                    }

                    if( $object['type'] === 'HORECA' ) 
                    {
                        $map['object_details'][$key] = array(
                            'oppervlakte' => $object['horeca']['oppervlakte'],
                            'verkoop_vloeroppervlakte' => $object['horeca']['verkoopVloeroppervlakte']
                        );
                    }

                    if( $object['type'] === 'BELEGGING' ) 
                    {
                        $map['object_details'][$key] = array(
                            'oppervlakte' => $object['belegging']['oppervlakte'],
                            'beleggingstype' => $object['belegging']['beleggingstype']
                        );
                    }

                    if( $object['type'] === 'BOUWGROND' ) 
                    {
                        $map['object_details'][$key] = array(
                            'bebouwingsmogelijkheid' => $object['bouwgrond']['bebouwingsmogelijkheid']
                        );
                    }

                    if( $object['type'] === 'BOUWGROND' ) 
                    {
                        $map['object_details'][$key] = array(
                            'bebouwingsmogelijkheid' => $object['bouwgrond']['bebouwingsmogelijkheid']
                        );
                    }

                    if( $object['type'] === 'MAATSCHAPPELIJK_VASTGOED' ) 
                    {
                        $map['object_details'][$key] = array(
                            'oppervlakte' => $object['maatschappelijkvastgoed']['instellingen'][0]['oppervlakte'],
                            'type' => $object['maatschappelijkvastgoed']['instellingen'][0]['type']
                        );
                    }

                    if( $object['type'] === 'OVERIG' ) 
                    {
                        $map['object_details'][$key] = array(
                            'oppervlakte' => $object['overige']['oppervlakte'],
                            'type' => $object['overige']['categorie'],
                        );
                    }

                }


            }

            // Meta for: Locatie
            $map['locatie']['plaats'] = $data['plaats'];
            $map['locatie']['postcode'] = $data['postcode'];
            $map['locatie']['ligging'] = $data['gebouwdetails']['lokatie']['ligging'];
            $map['locatie']['ligging'] = $data['gebouwdetails']['lokatie']['ligging'];

            return $map;
        
        }

        /**
         * Format Nieuwbouw Metadata
         *
         * @param array $data
         * @return array formatted meta data
         */
        public function formatNieuwbouwMeta( array $data )
        {
            return array();
        }


    }