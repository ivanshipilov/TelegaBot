<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class TrutorgDB extends Model
{
    static $tablePrefix='oc';

    public function getParentCategoryTable()
    {
        $categoriesIdsTable = DB::table(self::$tablePrefix.'_t_category')->get()->all();
        $categoriesNames = DB::table(self::$tablePrefix.'_t_category_description')->get()->all();
        $parentCategories = array();
        foreach ($categoriesIdsTable as $category)
        {
            $categoryArray = json_decode(json_encode($category), true);
            if (is_null($categoryArray["fk_i_parent_id"]))
            {
                foreach ($categoriesNames as $categoriesName)
                {
                    $categoriesNameArray = json_decode(json_encode($categoriesName), true);
                    if ($categoriesNameArray["fk_i_category_id"] == $categoryArray["pk_i_id"])
                    {
                        $parentCategories[$categoryArray["pk_i_id"]]=$categoriesNameArray["s_name"];
                    }
                }
            }
        };

        return $parentCategories;
    }

    public function getChildCategoryTable($parentCategory)
    {
        $categoriesIdsTable = DB::table(self::$tablePrefix.'_t_category')->get()->all();
        $categoriesNames = DB::table(self::$tablePrefix.'_t_category_description')->get()->all();
        $childCategories = array();
        foreach ($categoriesIdsTable as $category)
        {
            $categoryArray = json_decode(json_encode($category), true);
            if ($categoryArray["fk_i_parent_id"]==$parentCategory)
            {
                foreach ($categoriesNames as $categoriesName)
                {
                    $categoriesNameArray = json_decode(json_encode($categoriesName), true);
                    if ($categoriesNameArray["fk_i_category_id"] == $categoryArray["pk_i_id"])
                    {
                        $childCategories[$categoryArray["pk_i_id"]]=$categoriesNameArray["s_name"];
                    }
                }
            }
        };

        return $childCategories;
    }

    public function getUserOffers($user_id)
    {

        $activeAdd = DB::table(self::$tablePrefix.'_t_item')
            ->where ('b_active', '=', '1')
            ->pluck('pk_i_id');
        $adds = DB::table(self::$tablePrefix.'_t_item_description')
            ->where('telega_user_id', $user_id)
            ->pluck('s_title', 'fk_i_item_id');
        foreach ( $adds as $id => $title )
        {
            if (!$activeAdd->contains($id)) {$adds->forget($id);}
        }

        return $adds;
    }

    public function getCategoryNameById($id)
    {
        $categories = DB::table(self::$tablePrefix.'_t_category_description')->get()->all();
        foreach ($categories as $category)
        {
            $categoryArray = json_decode(json_encode($category), true);
            if ($categoryArray["fk_i_category_id"]==$id)
            {
                $catName =  $categoryArray["s_name"];
            }
        };

        return $catName;
    }

    public function getCategoryStats($categoryId)
    {
        $categoryStatTable = DB::table(self::$tablePrefix.'_t_category_stats')->get()->all();
        foreach ($categoryStatTable as $category)
        {
            $categoryArray = json_decode(json_encode($category), true);
            if ($categoryArray["fk_i_category_id"]==$categoryId)
            {
                $catNumItems =  $categoryArray["i_num_items"];
            }
        };

        return $catNumItems;
    }

    public function getRegionStats($regionId)
    {
        $regionStatTable = DB::table(self::$tablePrefix.'_t_region_stats')->get()->all();
        foreach ($regionStatTable as $region)
        {
            $regionArray = json_decode(json_encode($region), true);
            if ($regionArray["fk_i_region_id"]==$regionId)
            {
                $NumItems =  $regionArray["i_num_items"];
            }
        };

        return $NumItems;
    }


    public function getAllCategoryTable()
    {
        $categoriesIdsTable = DB::table(self::$tablePrefix.'_t_category')->get()->all();
        $categoriesNames = DB::table(self::$tablePrefix.'_t_category_description')->get()->all();
        $allCategories = array();
        foreach ($categoriesIdsTable as $category)
        {
            $categoryArray = json_decode(json_encode($category), true);
                foreach ($categoriesNames as $categoriesName)
                {
                    $categoriesNameArray = json_decode(json_encode($categoriesName), true);
                    if ($categoriesNameArray["fk_i_category_id"] == $categoryArray["pk_i_id"])
                    {
                        $allCategories[$categoryArray["pk_i_id"]]=$categoriesNameArray["s_name"];
                    }
                }
        };

        return $allCategories;
    }

    public function getNewItemId()
    {
        $maxItemIndexInOC_item = DB::table(self::$tablePrefix.'_t_item')->pluck('pk_i_id')->max();
        $maxItemIndexItemHistory = DB::table('item_history')->pluck('last_item_id')->max();
        if ($maxItemIndexInOC_item > $maxItemIndexItemHistory)
            {
                $maxItemId = $maxItemIndexInOC_item;
                DB::table('item_history')->update(['last_item_id' => $maxItemId + 1]);
            }
        else {$maxItemId =  $maxItemIndexItemHistory;}

        return $newItemId = $maxItemId + 1;
    }

    public function getNewItemResourceId()
    {
        $maxIndex = DB::table(self::$tablePrefix.'_t_item_resource')->pluck('pk_i_id')->max();
        return $maxIndex + 1;
    }

    public function getUserInformation($user_id, $fullArray = false)
    {
        if (DB::table('messenger_users')->where('user_id', '=', $user_id)->exists())
        {
            $userInfo = array
            (
                'user_id' => DB::table('messenger_users')->where('user_id', $user_id)->value('user_id'),
                'first_name' => DB::table('messenger_users')->where('user_id', $user_id)->value('first_name'),
                'last_name' => DB::table('messenger_users')->where('user_id', $user_id)->value('last_name'),
                'phone_number' => DB::table('messenger_users')->where('user_id', $user_id)->value('phone_number'),
                'latitude' => DB::table('messenger_users')->where('user_id', $user_id)->value('latitude'),
                'longitude' => DB::table('messenger_users')->where('user_id', $user_id)->value('longitude'),
                'user_country' => DB::table('messenger_users')->where('user_id', $user_id)->value('user_country'),
                'user_city' => DB::table('messenger_users')->where('user_id', $user_id)->value('user_city'),
                'user_district' => DB::table('messenger_users')->where('user_id', $user_id)->value('user_district'),
                'user_street' => DB::table('messenger_users')->where('user_id', $user_id)->value('user_street'),
                'user_house' => DB::table('messenger_users')->where('user_id', $user_id)->value('user_house'),
                'user_index' => DB::table('messenger_users')->where('user_id', $user_id)->value('user_index'),
            );

            if ($fullArray)
            {
                return $userInfo;
            } else
            {
                return array_filter($userInfo);
            }
        }
        else
        {
            return 0;
        }
    }

    public function getUserAbsentInformation($user_id)
    {
        $userInformation = $this->getUserInformation($user_id, true);
        $infoAboutFields = $this->getInfoAboutUserFields();
        $emptyFields = [];
        foreach ($userInformation as $key=> $value)
        {
            if(empty($value))
                $emptyFields[$key] =value($value);
        }
        $nodata = array_intersect_key($infoAboutFields,$emptyFields);
        if (array_key_exists('user_city', $nodata) && array_key_exists('user_street', $nodata))
        {
            foreach ($this->getInfoAboutUserFields(true) as $key=> $value)
            {
                unset($nodata[$key]);
            }
            $nodata['address']= value('адрес');
        }
        return $nodata;
    }

    public function getInfoAboutUserFields($onlyAddress=false)
        {
            if ($onlyAddress)
            {
                return array
                (
                    'latitude' => 'широта',
                    'longitude' => 'долгота',
                    'user_country' => 'страна',
                    'user_city' => 'город',
                    //'user_district' => 'район',
                    'user_street' => 'улица',
                    'user_house' => 'дом',
                    //'user_index' => 'имя',
                );
            }
            else
                {
                    return array
                    (
                        'first_name' => 'имя',
                        //'last_name' => 'фамилия',
                        'phone_number' => 'номер телефона',
                        'latitude' => 'широта',
                        'longitude' => 'долгота',
                        'user_country' => 'страна',
                        'user_city' => 'город',
                        //'user_district' => 'район',
                        'user_street' => 'улица',
                        'user_house' => 'дом',
                        //'user_index' => 'имя',
                    );
                }
        }


    Public function PutToTheTable($data)
    {
        DB::table(self::$tablePrefix.'_t_item')->insert(
            [
                'pk_i_id' => $data['newItem_Id'],
                'fk_i_category_id' => $data['childCatId'],
                'i_price' => $data['price']*1000000,
                'fk_c_currency_code' => 'RUB',
                'dt_pub_date' => date('Y-m-d h:m:s'),
                's_ip' => '127.0.0.1',
                's_contact_name' => $data['first_name'],
                'b_show_email' => 0,
                's_contact_email' => 'telegaBot',
                'b_active'=>1,
                'dt_expiration'=>date("Y-m-d h:m:s", strtotime("+1 month")),
            ]
        );

        DB::table(self::$tablePrefix.'_t_item_location')->insert(
            [
                'fk_i_item_id' => $data['newItem_Id'],
                'fk_c_country_code' => 'RU',
                's_country' => 'Россия',
                's_address' => 'Россия, '.$data['user_city'].', '.$data['user_street'].', '.$data['user_house'],
                'fk_i_region_id' => '781870',  //тут надо будет получать и oc таблицы айдишники регионов и пихать сюда
                's_region' => $data['user_city'],
                'fk_i_city_id' => '408071',   //тут надо будет получать и oc таблицы айдишники регионов и пихать сюда
                's_city' => $data['user_city'],
                'd_coord_lat' => $data['latitude'],
                'd_coord_long' => $data['longitude']
                //'s_city_area' => '', //надо писать функцию для получения района
            ]
        );

        DB::table(self::$tablePrefix.'_t_item_description')->insert(
            [
                'fk_i_item_id' => $data['newItem_Id'],
                'fk_c_locale_code' => 'ru_RU',
                's_title' => $data['offerName'],
                's_description' => $data['description'],
                'telega_user_id' => $data['user_id'],
            ]
        );


        DB::table(self::$tablePrefix.'_t_item_stats')->insert(
            [
                'fk_i_item_id' => $data['newItem_Id'],
                'i_num_views' => 0,
                'i_num_spam' => 0,
                'i_num_repeated' => 0,
                'i_num_bad_classified' => 0,
                'i_num_offensive' => 0,
                'i_num_expired' => 0,
                'i_num_premium_views' => 0,
                'dt_date' => date('Y-m-d'),
            ]
        );

        DB::table(self::$tablePrefix.'_t_category_stats')
            ->where('fk_i_category_id', $data['parentCatId'])
            ->orWhere('fk_i_category_id', $data['childCatId'])
            ->increment('i_num_items');

    }

    public function deactivateAdd($addId)
    {
        DB::table(self::$tablePrefix.'_t_item')
            ->where('pk_i_id', $addId)
            ->update(['b_active' => 0]);
    }

    public function putUserInformation($data, $update = false)
    {
        if ($update) {$command = 'update';} else {$command = 'insert';}
        if ($data['user_id']==0){$data['last_name'] = 'anonim';}

        DB::table('messenger_users')->$command
            ([
                'user_id' => $data['user_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone_number' => $data['phone_number'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'id_chat' => 1,
                'user_country' => 'Россия',//$data['user_country'],
                'user_city' => $data['user_city'],
                'user_district' => '',//$data['user_district'],
                'user_street' => $data['user_street'],
                'user_house' => $data['user_house'],
            ]);
    }


    Public function PutPhotoToTheTable($imageId,$newItemId,$imageExtension,$imageFullExtension,$path4imageToDB)
    {
        DB::table(self::$tablePrefix.'_t_item_resource')->insert(
            [
                'pk_i_id' => $imageId,
                'fk_i_item_id' => $newItemId,
                's_extension' => $imageExtension,
                's_content_type' => $imageFullExtension,
                's_path' => $path4imageToDB,
            ]
        );
    }
}
