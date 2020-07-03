<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class googleApi extends Model
{
    public $apiKey;

    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->apiKey = getenv('GOOGLE_API_KEY');
    }

    public function getAddress($data)
    {
        $latitude = $data['latitude'];
        $longitude = $data['longitude'];
        $addressJson = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$latitude.','.$longitude.'&language=ru&key='.$this->apiKey);
        $address = json_decode($addressJson, true);
        return array
        (
            'user_country' => $address['results'][0]['address_components'][6]['long_name'],
            'user_city' => $address['results'][0]['address_components'][5]['long_name'],
            'user_district' => $address['results'][0]['address_components'][4]['long_name'],
            'user_street' => $address['results'][0]['address_components'][1]['short_name'],
            'user_house' => $address['results'][0]['address_components'][0]['long_name'],
            'user_index' => $address['results'][0]['address_components'][7]['long_name'],
        );
    }
    /*public function tst()
    {
        $address = $this->testResult;

        return array
        (
            'user_country' => $address['results'][0]['address_components'][6]['long_name'],
            'user_city' => $address['results'][0]['address_components'][5]['long_name'],
            'user_district' => $address['results'][0]['address_components'][4]['long_name'],
            'user_street' => $address['results'][0]['address_components'][5]['long_name'],
            'user_house' => $address['results'][0]['address_components'][0]['long_name'],
            'user_index' => $address['results'][0]['address_components'][7]['long_name'],
        );
    }

    public $testResult = array (
        'plus_code' =>
            array (
                'compound_code' => 'J9RR+RC Западный административный округ, Москва, Россия',
                'global_code' => '9G7VJ9RR+RC',
            ),
        'results' =>
            array (
                0 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => '12 корпус 2',
                                        'short_name' => '12 корпус 2',
                                        'types' =>
                                            array (
                                                0 => 'street_number',
                                            ),
                                    ),
                                1 =>
                                    array (
                                        'long_name' => 'Производственная улица',
                                        'short_name' => 'Производственная ул.',
                                        'types' =>
                                            array (
                                                0 => 'route',
                                            ),
                                    ),
                                2 =>
                                    array (
                                        'long_name' => 'Западный административный округ',
                                        'short_name' => 'Западный административный округ',
                                        'types' =>
                                            array (
                                                0 => 'political',
                                                1 => 'sublocality',
                                                2 => 'sublocality_level_1',
                                            ),
                                    ),
                                3 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'locality',
                                                1 => 'political',
                                            ),
                                    ),
                                4 =>
                                    array (
                                        'long_name' => 'Солнцево',
                                        'short_name' => 'Солнцево',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_3',
                                                1 => 'political',
                                            ),
                                    ),
                                5 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_2',
                                                1 => 'political',
                                            ),
                                    ),
                                6 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                                7 =>
                                    array (
                                        'long_name' => '119619',
                                        'short_name' => '119619',
                                        'types' =>
                                            array (
                                                0 => 'postal_code',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'Производственная ул., 12 корпус 2, Москва, Россия, 119619',
                        'geometry' =>
                            array (
                                'location' =>
                                    array (
                                        'lat' => 55.6423043,
                                        'lng' => 37.3902244,
                                    ),
                                'location_type' => 'ROOFTOP',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.6436532802915,
                                                'lng' => 37.3915733802915,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.6409553197085,
                                                'lng' => 37.3888754197085,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJ6ZEr6RZTtUYRaIn3sDrvRFQ',
                        'plus_code' =>
                            array (
                                'compound_code' => 'J9RR+W3 Западный административный округ, Москва, Россия',
                                'global_code' => '9G7VJ9RR+W3',
                            ),
                        'types' =>
                            array (
                                0 => 'establishment',
                                1 => 'food',
                                2 => 'grocery_or_supermarket',
                                3 => 'point_of_interest',
                                4 => 'store',
                                5 => 'supermarket',
                            ),
                    ),
                1 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => '12 корпус 2',
                                        'short_name' => '12 корпус 2',
                                        'types' =>
                                            array (
                                                0 => 'street_number',
                                            ),
                                    ),
                                1 =>
                                    array (
                                        'long_name' => 'Производственная улица',
                                        'short_name' => 'Производственная ул.',
                                        'types' =>
                                            array (
                                                0 => 'route',
                                            ),
                                    ),
                                2 =>
                                    array (
                                        'long_name' => 'Западный административный округ',
                                        'short_name' => 'Западный административный округ',
                                        'types' =>
                                            array (
                                                0 => 'political',
                                                1 => 'sublocality',
                                                2 => 'sublocality_level_1',
                                            ),
                                    ),
                                3 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'locality',
                                                1 => 'political',
                                            ),
                                    ),
                                4 =>
                                    array (
                                        'long_name' => 'Солнцево',
                                        'short_name' => 'Солнцево',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_3',
                                                1 => 'political',
                                            ),
                                    ),
                                5 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_2',
                                                1 => 'political',
                                            ),
                                    ),
                                6 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                                7 =>
                                    array (
                                        'long_name' => '119619',
                                        'short_name' => '119619',
                                        'types' =>
                                            array (
                                                0 => 'postal_code',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'Производственная ул., 12 корпус 2, Москва, Россия, 119619',
                        'geometry' =>
                            array (
                                'location' =>
                                    array (
                                        'lat' => 55.6426158,
                                        'lng' => 37.3909065,
                                    ),
                                'location_type' => 'ROOFTOP',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.6439647802915,
                                                'lng' => 37.3922554802915,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.64126681970851,
                                                'lng' => 37.3895575197085,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJP-M8kYJTtUYRN1e96Js37PM',
                        'plus_code' =>
                            array (
                                'compound_code' => 'J9VR+29 Западный административный округ, Москва, Россия',
                                'global_code' => '9G7VJ9VR+29',
                            ),
                        'types' =>
                            array (
                                0 => 'street_address',
                            ),
                    ),
                2 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => 'Unnamed Road',
                                        'short_name' => 'Unnamed Road',
                                        'types' =>
                                            array (
                                                0 => 'route',
                                            ),
                                    ),
                                1 =>
                                    array (
                                        'long_name' => 'Западный административный округ',
                                        'short_name' => 'Западный административный округ',
                                        'types' =>
                                            array (
                                                0 => 'political',
                                                1 => 'sublocality',
                                                2 => 'sublocality_level_1',
                                            ),
                                    ),
                                2 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'locality',
                                                1 => 'political',
                                            ),
                                    ),
                                3 =>
                                    array (
                                        'long_name' => 'Солнцево',
                                        'short_name' => 'Солнцево',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_3',
                                                1 => 'political',
                                            ),
                                    ),
                                4 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_2',
                                                1 => 'political',
                                            ),
                                    ),
                                5 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                                6 =>
                                    array (
                                        'long_name' => '119619',
                                        'short_name' => '119619',
                                        'types' =>
                                            array (
                                                0 => 'postal_code',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'Unnamed Road, Москва, Россия, 119619',
                        'geometry' =>
                            array (
                                'bounds' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.6432738,
                                                'lng' => 37.3908318,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.6416668,
                                                'lng' => 37.3892648,
                                            ),
                                    ),
                                'location' =>
                                    array (
                                        'lat' => 55.64228,
                                        'lng' => 37.3896314,
                                    ),
                                'location_type' => 'GEOMETRIC_CENTER',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.6438192802915,
                                                'lng' => 37.3913972802915,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.6411213197085,
                                                'lng' => 37.3886993197085,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJhV5EmBVStUYRgtSrhpejSvs',
                        'types' =>
                            array (
                                0 => 'route',
                            ),
                    ),
                3 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => '119619',
                                        'short_name' => '119619',
                                        'types' =>
                                            array (
                                                0 => 'postal_code',
                                            ),
                                    ),
                                1 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'Россия, 119619',
                        'geometry' =>
                            array (
                                'bounds' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.68443990000001,
                                                'lng' => 37.396831,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.6405881,
                                                'lng' => 37.3388411,
                                            ),
                                    ),
                                'location' =>
                                    array (
                                        'lat' => 55.66667589999999,
                                        'lng' => 37.3737481,
                                    ),
                                'location_type' => 'APPROXIMATE',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.68443990000001,
                                                'lng' => 37.396831,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.6405881,
                                                'lng' => 37.3388411,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJi8jn_ohRtUYRSR3Q-Uy9pr4',
                        'types' =>
                            array (
                                0 => 'postal_code',
                            ),
                    ),
                4 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => 'Солнцево',
                                        'short_name' => 'Солнцево',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_3',
                                                1 => 'political',
                                            ),
                                    ),
                                1 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'locality',
                                                1 => 'political',
                                            ),
                                    ),
                                2 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_2',
                                                1 => 'political',
                                            ),
                                    ),
                                3 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'Солнцево, Москва, Россия',
                        'geometry' =>
                            array (
                                'bounds' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.680991,
                                                'lng' => 37.4355281,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.627807,
                                                'lng' => 37.3661631,
                                            ),
                                    ),
                                'location' =>
                                    array (
                                        'lat' => 55.652417,
                                        'lng' => 37.3877577,
                                    ),
                                'location_type' => 'APPROXIMATE',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.680991,
                                                'lng' => 37.4355281,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.627807,
                                                'lng' => 37.3661631,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJybzt3xFStUYR-Cal3ifBcFY',
                        'types' =>
                            array (
                                0 => 'administrative_area_level_3',
                                1 => 'political',
                            ),
                    ),
                5 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => 'район Солнцево',
                                        'short_name' => 'р-н Солнцево',
                                        'types' =>
                                            array (
                                                0 => 'political',
                                                1 => 'sublocality',
                                                2 => 'sublocality_level_2',
                                            ),
                                    ),
                                1 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'locality',
                                                1 => 'political',
                                            ),
                                    ),
                                2 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_2',
                                                1 => 'political',
                                            ),
                                    ),
                                3 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'р-н Солнцево, Москва, Россия',
                        'geometry' =>
                            array (
                                'bounds' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.6810679,
                                                'lng' => 37.432899,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.627838,
                                                'lng' => 37.3662559,
                                            ),
                                    ),
                                'location' =>
                                    array (
                                        'lat' => 55.6524572,
                                        'lng' => 37.3877161,
                                    ),
                                'location_type' => 'APPROXIMATE',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.6810679,
                                                'lng' => 37.432899,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.627838,
                                                'lng' => 37.3662559,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJn-5D7Q5StUYRdB-6KjxSH9k',
                        'types' =>
                            array (
                                0 => 'political',
                                1 => 'sublocality',
                                2 => 'sublocality_level_2',
                            ),
                    ),
                6 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => 'Западный административный округ',
                                        'short_name' => 'Западный административный округ',
                                        'types' =>
                                            array (
                                                0 => 'political',
                                                1 => 'sublocality',
                                                2 => 'sublocality_level_1',
                                            ),
                                    ),
                                1 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'locality',
                                                1 => 'political',
                                            ),
                                    ),
                                2 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_2',
                                                1 => 'political',
                                            ),
                                    ),
                                3 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'Западный административный округ, Москва, Россия',
                        'geometry' =>
                            array (
                                'bounds' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.806527,
                                                'lng' => 37.574348,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.5812001,
                                                'lng' => 36.888006,
                                            ),
                                    ),
                                'location' =>
                                    array (
                                        'lat' => 55.70620049999999,
                                        'lng' => 37.5138505,
                                    ),
                                'location_type' => 'APPROXIMATE',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 55.806527,
                                                'lng' => 37.574348,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.5812001,
                                                'lng' => 36.888006,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJS5RUaD9OtUYRjmtxxTJcamE',
                        'types' =>
                            array (
                                0 => 'political',
                                1 => 'sublocality',
                                2 => 'sublocality_level_1',
                            ),
                    ),
                7 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'locality',
                                                1 => 'political',
                                            ),
                                    ),
                                1 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_2',
                                                1 => 'political',
                                            ),
                                    ),
                                2 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'Москва, Россия',
                        'geometry' =>
                            array (
                                'bounds' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 56.0214609,
                                                'lng' => 37.9678221,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.142591,
                                                'lng' => 36.8032249,
                                            ),
                                    ),
                                'location' =>
                                    array (
                                        'lat' => 55.755826,
                                        'lng' => 37.6172999,
                                    ),
                                'location_type' => 'APPROXIMATE',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 56.0214609,
                                                'lng' => 37.9678221,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.142591,
                                                'lng' => 36.8032249,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJybDUc_xKtUYRTM9XV8zWRD0',
                        'types' =>
                            array (
                                0 => 'locality',
                                1 => 'political',
                            ),
                    ),
                8 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_2',
                                                1 => 'political',
                                            ),
                                    ),
                                1 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_1',
                                                1 => 'political',
                                            ),
                                    ),
                                2 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'Москва, Россия',
                        'geometry' =>
                            array (
                                'bounds' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 56.02156799999999,
                                                'lng' => 37.9678191,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.1428781,
                                                'lng' => 36.8037541,
                                            ),
                                    ),
                                'location' =>
                                    array (
                                        'lat' => 55.5464948,
                                        'lng' => 37.2926266,
                                    ),
                                'location_type' => 'APPROXIMATE',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 56.02156799999999,
                                                'lng' => 37.9678191,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.1428781,
                                                'lng' => 36.8037541,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJP5cfydBLtUYRyvGyUIpYc50',
                        'types' =>
                            array (
                                0 => 'administrative_area_level_2',
                                1 => 'political',
                            ),
                    ),
                9 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => 'Москва',
                                        'short_name' => 'Москва',
                                        'types' =>
                                            array (
                                                0 => 'administrative_area_level_1',
                                                1 => 'political',
                                            ),
                                    ),
                                1 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'Москва, Россия',
                        'geometry' =>
                            array (
                                'bounds' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 56.02156799999999,
                                                'lng' => 37.9678191,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.1428781,
                                                'lng' => 36.8037541,
                                            ),
                                    ),
                                'location' =>
                                    array (
                                        'lat' => 55.5464948,
                                        'lng' => 37.2926266,
                                    ),
                                'location_type' => 'APPROXIMATE',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 56.02156799999999,
                                                'lng' => 37.9678191,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 55.1428781,
                                                'lng' => 36.8037541,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJr-dYmSzISkERysTPiPSjVws',
                        'types' =>
                            array (
                                0 => 'administrative_area_level_1',
                                1 => 'political',
                            ),
                    ),
                10 =>
                    array (
                        'address_components' =>
                            array (
                                0 =>
                                    array (
                                        'long_name' => 'Россия',
                                        'short_name' => 'RU',
                                        'types' =>
                                            array (
                                                0 => 'country',
                                                1 => 'political',
                                            ),
                                    ),
                            ),
                        'formatted_address' => 'Россия',
                        'geometry' =>
                            array (
                                'bounds' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 82.1673907,
                                                'lng' => -168.97788,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 41.185353,
                                                'lng' => 19.6160999,
                                            ),
                                    ),
                                'location' =>
                                    array (
                                        'lat' => 61.52401,
                                        'lng' => 105.318756,
                                    ),
                                'location_type' => 'APPROXIMATE',
                                'viewport' =>
                                    array (
                                        'northeast' =>
                                            array (
                                                'lat' => 82.1673907,
                                                'lng' => -168.97788,
                                            ),
                                        'southwest' =>
                                            array (
                                                'lat' => 41.185353,
                                                'lng' => 19.6160999,
                                            ),
                                    ),
                            ),
                        'place_id' => 'ChIJ-yRniZpWPEURE_YRZvj9CRQ',
                        'types' =>
                            array (
                                0 => 'country',
                                1 => 'political',
                            ),
                    ),
            ),
        'status' => 'OK',
    );*/
}
